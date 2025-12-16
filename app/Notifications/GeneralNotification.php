<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    public $type, $title, $body , $action, $icon , $documentId , $documentLatestVersionId;

    public function __construct($type, $title, $body,$action, $documentId , $documentLatestVersionId,$icon = null)
    {
        $this->type = $type;
        $this->title = $title;
        $this->body = $body;
        $this->action = $action;
        $this->documentId = $documentId;
        $this->documentLatestVersionId = $documentLatestVersionId;
        $this->icon = $icon;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Send email for approval reminder notifications.
        if (in_array($this->action, ['pending_approval_1w', 'pending_approval_1m'], true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage())
            ->subject($this->title)
            ->line($this->body);

        if ($this->documentLatestVersionId) {
            $url = route('document-versions.preview', [
                'id' => $this->documentLatestVersionId,
                'approval' => 1,
            ]);

            $mail->action('View document', $url);
        }

        return $mail;
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'action' => $this->action,
            'title' => $this->title,
            'body' => $this->body,
            'documentId' => $this->documentId,
            'documentLatestVersionId' => $this->documentLatestVersionId,
            'icon' => $this->icon
        ];
    }
}
