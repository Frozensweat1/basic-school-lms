<?php

namespace App\Livewire\LMS\Assignments\Admin;

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
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
    }

    protected function classSubjects(): Builder
    {
        return ClassSubject::with(['schoolClass', 'subject', 'teacher'])
            ->whereHas('schoolClass.academicYear', fn (Builder $query) => $query->where('school_id', $this->schoolId()))
            ->orderBy('school_class_id');
    }

    protected function teacherIdFor(array $data, ClassSubject $classSubject): int
    {
        $teacherId = filled($data['teacherId']) ? (int) $data['teacherId'] : $classSubject->teacher_id;
        abort_unless($teacherId, 422, 'Assign a teacher before creating this assignment.');

        return $teacherId;
    }

    protected function componentView(): string
    {
        return 'livewire.lms.assignments.admin.index';
    }
}
