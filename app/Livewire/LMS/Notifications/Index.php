<?php

namespace App\Livewire\LMS\Notifications;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public function markRead(string $notificationId): void
    {
        auth()->user()->notifications()->whereKey($notificationId)->update(['read_at' => now()]);
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.lms.notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(25),
        ]);
    }
}
