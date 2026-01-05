<?php

namespace App\Services;

use App\Events\NotificationBroadcast;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{

    private $title;
    private $user;
    private $document;
    private $documentId;
    private $latestVersionId;

    public function __construct(string $title, User $user, $document, $latestVersionId = null)
    {
        $this->title = $title;
        $this->user = $user;
        $this->document = $document;
        $this->documentId = is_object($document) ? $document->id : $document;
        // Use provided latestVersionId or try to get from document object
        $this->latestVersionId = $latestVersionId ?? (is_object($document) && $document->latestVersion ? $document->latestVersion->id : null);
    }

    protected function getNotificationConfig(string $action): ?array
    {
        return config("notifications.types.$action");
    }


    public function notifyBasedOnAction(string $action): void
    {
        $config = $this->getNotificationConfig($action);

        if (!$config) {
            Log::warning("Unknown notification action: $action");
            return;
        }

        $title = __($config['title']);
        $body = __($config['body']);

        $notificationData = [
            'type' => $config['type'],
            'title' => $title,
            'body' => str_replace(':title', $this->title, $body),
            'action' => $action,
            'action_text' => 'View Document',
            'action_url' => route('documents.show', $this->documentId),
            'icon' => $config['icon'],
            'document_id' => $this->documentId,
            'document_latest_version_id' => $this->latestVersionId,
        ];

        $this->user->notify(new GeneralNotification(
            $notificationData['type'],
            $notificationData['title'],
            $notificationData['body'],
            $notificationData['action'],
            $notificationData['document_id'],
            $this->latestVersionId,
            $notificationData['icon']
        ));

        // Broadcast the notification event
        event(new NotificationBroadcast($this->user->id,$notificationData));
    }

    public function notifyAdmins(string $action): void
    {
        $config = $this->getNotificationConfig($action);

        if (!$config) {
            Log::warning("Unknown notification action for admins: $action");
            return;
        }

        // Get document relationships for scoping
        $documentDeptId = is_object($this->document) ? $this->document->department_id : null;
        $documentServiceId = is_object($this->document) ? $this->document->service_id : null;

        // Always notify Master, Super Administrator, and legacy admin (global scope)
        $globalAdmins = User::role(['master', 'Super Administrator', 'admin'])->get();

        // Scoped admins: Admin de pole filtered by department
        $poleAdmins = collect();
        if ($documentDeptId) {
            $poleAdmins = User::role('Admin de pole')
                ->whereHas('departments', function($q) use ($documentDeptId) {
                    $q->where('departments.id', $documentDeptId);
                })
                ->get();
        }

        // Scoped admins: Admin de departments filtered by department
        $deptAdmins = collect();
        if ($documentDeptId) {
            $deptAdmins = User::role('Admin de departments')
                ->whereHas('departments', function($q) use ($documentDeptId) {
                    $q->where('departments.id', $documentDeptId);
                })
                ->get();
        }

        // Scoped admins: Admin de cellule filtered by service
        $celluleAdmins = collect();
        if ($documentServiceId) {
            $celluleAdmins = User::role('Admin de cellule')
                ->whereHas('services', function($q) use ($documentServiceId) {
                    $q->where('services.id', $documentServiceId);
                })
                ->get();
        }

        // Merge all relevant admins
        $admins = $globalAdmins->merge($poleAdmins)->merge($deptAdmins)->merge($celluleAdmins)->unique('id');

        foreach ($admins as $admin) {
            // Translate title and body
            $title = __($config['title']);
            $body = __($config['body'], ['title' => $this->title]);
            
            // Broadcast once for admins or separately per admin as needed
            $notificationData = [
                'type' => $config['type'],
                'title' => $title,
                'body' => $body,
                'action' => $action,
                'documentId' => $this->documentId,
                'latestVersionId' => $this->latestVersionId,
                'icon' => $config['icon'],
            ];

            $admin->notify(new GeneralNotification(
                $notificationData['type'],
                $notificationData['title'],
                $notificationData['body'],
                $action,
                $this->documentId,
                $this->latestVersionId,
                $notificationData['icon']
            ));

            event(new NotificationBroadcast($admin->id,$notificationData));
        }


    }
}
