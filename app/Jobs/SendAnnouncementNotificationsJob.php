<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Support\LmsNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $announcementId) {}

    public function handle(): void
    {
        $announcement = Announcement::query()->find($this->announcementId);
        if (! $announcement || $announcement->notified_at || ! $announcement->published_at || $announcement->published_at->isFuture()) {
            return;
        }

        if ($announcement->expires_at?->isPast()) {
            return;
        }

        LmsNotifier::send(
            LmsNotifier::announcementAudience($announcement),
            'School announcement',
            $announcement->title,
            route('lms.announcements.feed'),
            'announcement',
        );
        $announcement->forceFill(['notified_at' => now()])->save();
    }
}
