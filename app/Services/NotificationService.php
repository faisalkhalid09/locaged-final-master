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

    private $documentId;

    private $latestVersionId;

    public function __construct(string $title, User $user,$documentId,$latestVersionId)
    {
        $this->title = $title;
        $this->user = $user;
        $this->documentId = $documentId;
        $this->latestVersionId = $latestVersionId;
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

        $notificationData = [
            'type' => $config['type'],
            'title' => $config['title'],
            'body' => str_replace(':title', $this->title, $config['body']),
            'action' => $action,
            'documentId' => $this->documentId,
            'latestVersionId' => $this->latestVersionId,
            'icon' => $config['icon'],
        ];

        $this->user->notify(new GeneralNotification(
            $notificationData['type'],
            $notificationData['title'],
            $notificationData['body'],
            $notificationData['action'],
            $notificationData['documentId'],
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

        $admins = User::role([
            'master',
            'Super Administrator',
            'admin',
            'Admin de pole',
            'Admin de departments',
            'Admin de cellule'
        ])->get();
        
        \Log::info("NotificationService: Found " . $admins->count() . " admins for action '$action'. IDs: " . $admins->pluck('id')->implode(', '));

        foreach ($admins as $admin) {
            // Broadcast once for admins or separately per admin as needed
            $notificationData = [
                'type' => $config['type'],
                'title' => $config['title'],
                'body' => str_replace(':title', $this->title, $config['body']),
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
