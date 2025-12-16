<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class NotificationBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $type;
    public $title;
    public $body;
    public $action;
    public $documentId;
    public $latestVersionId;
    public $icon;

    protected $userId;


    public function __construct(int $userId, array $data)
    {
        $this->userId = $userId;

        $this->type = $data['type'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->body = $data['body'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->documentId = $data['documentId'] ?? null;
        $this->latestVersionId = $data['latestVersionId'] ?? null;
        $this->icon = $data['icon'] ?? null;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("App.Models.User.{$this->userId}");
    }

    public function broadcastAs()
    {
        return 'notification-event';
    }
}
