<?php

namespace App\Livewire\LMS\Announcements;

use App\Models\Announcement;
use App\Models\ClassSubject;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Feed extends Index
{
    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
        abort_unless(auth()->user()->hasAnyRole(['student', 'parent']), 403);
    }

    public function render()
    {
        $user = auth()->user();
        $student = $user->student;
        $parent = $user->parentGuardian;
        $students = $student ? collect([$student]) : ($parent?->students()->where('students.status', 'active')->get() ?? collect());
        $schoolId = (int) ($student?->school_id ?? $parent?->school_id);
        $classIds = $students->flatMap(fn ($profile) => $profile->enrollments()->where('status', 'active')->pluck('school_class_id'))->unique()->values();
        $subjectIds = ClassSubject::whereIn('school_class_id', $classIds)->pluck('subject_id')->unique()->values();
        $announcements = Announcement::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($query) => $query->where('audience', 'school')->orWhere(fn ($class) => $class->where('audience', 'class')->whereIn('school_class_id', $classIds))->orWhere(fn ($subject) => $subject->where('audience', 'subject')->whereIn('subject_id', $subjectIds)))
            ->latest('published_at')
            ->paginate(15);

        return view('livewire.lms.announcements.feed', compact('announcements'));
    }
}
