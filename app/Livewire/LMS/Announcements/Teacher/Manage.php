<?php

namespace App\Livewire\LMS\Announcements\Teacher;

use App\Livewire\LMS\Announcements\Index as Shared;
use App\Models\Announcement;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Manage extends Shared
{
    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    public function edit(Announcement $announcement): void
    {
        abort_unless($announcement->audience !== 'school', 403);
        parent::edit($announcement);
    }

    public function save(): void
    {
        abort_unless($this->audience !== 'school', 403, 'Teachers can publish class or subject announcements only.');
        $teacher = auth()->user()->teacher;
        if ($this->audience === 'class') {
            abort_unless($teacher->classSubjects()->where('school_class_id', (int) $this->classId)->exists(), 403);
        }
        if ($this->audience === 'subject') {
            abort_unless($teacher->classSubjects()->where('subject_id', (int) $this->subjectId)->exists(), 403);
        }
        parent::save();
    }
}
