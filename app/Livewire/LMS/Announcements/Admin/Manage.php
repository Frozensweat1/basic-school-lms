<?php

namespace App\Livewire\LMS\Announcements\Admin;

use App\Livewire\LMS\Announcements\Index as Shared;
use App\Models\Announcement;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Manage extends Shared
{
    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
    }
}
