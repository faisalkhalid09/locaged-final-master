<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDropdown extends Component
{
    public $notifications = [];
    public User $user;

    public function mount()
    {
        $this->user = auth()->user();
        $this->fetchNotifications();
    }

    #[On('echo-private:App.Models.User.{user.id},.notification-event')]
    public function notifyNewOrder($event)
    {

        $this->fetchNotifications();
        $this->dispatch('notify', [
            'title' => $event['title'] ?? 'New Notification',
            'body' => $event['body'] ?? 'You have a new notification',
            'type' => $event['type'] ?? 'info',
        ]);
    }

    public function fetchNotifications()
    {
        $user = auth()->user();
        $this->notifications = $user ? $user->unreadNotifications()->latest()->limit(10)->get() : collect();
    }

    public function markAllAsRead()
    {
        auth()->user()?->unreadNotifications->markAsRead();
        $this->fetchNotifications();
        $this->dispatch('$refresh');

    }

    public function deleteNotification($id)
    {
        auth()->user()?->notifications()->where('id', $id)->delete();
        $this->fetchNotifications();
    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
