<?php

namespace App\Livewire\LMS\Assignments\Teacher;

use App\Livewire\LMS\Assignments\Concerns\ManagesAssignments;
use App\Models\Assignment;
use App\Models\ClassSubject;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class Index extends ManagesAssignments
{
    public function mount(): void
    {
        $this->authorize('viewAny', Assignment::class);
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
    }

    protected function classSubjects(): Builder
    {
        return ClassSubject::with(['schoolClass', 'subject', 'teacher'])
            ->where('teacher_id', auth()->user()->teacher->id)
            ->whereHas('schoolClass.academicYear', fn (Builder $query) => $query->where('school_id', $this->schoolId()))
            ->orderBy('school_class_id');
    }

    protected function teacherIdFor(array $data, ClassSubject $classSubject): int
    {
        abort_unless($classSubject->teacher_id === auth()->user()->teacher->id, 403);

        return auth()->user()->teacher->id;
    }

    protected function componentView(): string
    {
        return 'livewire.lms.assignments.teacher.index';
    }
}
