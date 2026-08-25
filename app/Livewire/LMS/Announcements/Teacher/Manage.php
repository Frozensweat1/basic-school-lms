<?php

namespace App\Livewire\LMS\Announcements\Teacher;

use App\Livewire\LMS\Announcements\Index as Shared;
use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Manage extends Shared
{
    public function mount(): void
    {
        $this->authorize('viewAny', Announcement::class);
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    protected function announcementQuery(): Builder
    {
        $teacher = auth()->user()->teacher;
        $classIds = $teacher->classSubjects()->pluck('school_class_id');
        $subjectIds = $teacher->classSubjects()->pluck('subject_id');

        return Announcement::query()
            ->where('school_id', $teacher->school_id)
            ->where(function (Builder $query) use ($classIds, $subjectIds): void {
                $query->whereIn('audience', ['school', 'teachers'])
                    ->orWhere('created_by', auth()->id())
                    ->orWhere(fn (Builder $classes) => $classes->where('audience', 'class')->whereIn('school_class_id', $classIds))
                    ->orWhere(fn (Builder $subjects) => $subjects->where('audience', 'subject')->whereIn('subject_id', $subjectIds));
            });
    }

    protected function availableClasses(): Builder
    {
        return SchoolClass::query()
            ->whereHas('classSubjects', fn (Builder $query) => $query->where('teacher_id', auth()->user()->teacher->id))
            ->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', auth()->user()->teacher->school_id));
    }

    protected function availableSubjects(): Builder
    {
        return Subject::query()
            ->where('school_id', auth()->user()->teacher->school_id)
            ->whereHas('classSubjects', fn (Builder $query) => $query->where('teacher_id', auth()->user()->teacher->id));
    }

    protected function allowedAudiences(): array
    {
        return ['class', 'subject'];
    }

    protected function defaultAudience(): string
    {
        return 'class';
    }

    protected function schoolId(): int
    {
        return (int) auth()->user()->teacher->school_id;
    }
}
