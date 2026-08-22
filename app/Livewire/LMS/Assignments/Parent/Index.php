<?php

namespace App\Livewire\LMS\Assignments\Parent;

use App\Models\{Assignment, ParentGuardian};
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public ParentGuardian $parent;
    public string $studentId = '';

    public function mount(): void
    {
        $this->parent = auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent') && $this->parent, 403);
        $this->studentId = (string) ($this->parent->students()->where('students.status', 'active')->value('students.id') ?? '');
    }

    public function updatedStudentId(): void
    {
        $this->resetPage();
        abort_unless($this->studentId === '' || $this->parent->students()->whereKey((int) $this->studentId)->where('students.status', 'active')->exists(), 403);
    }

    public function render()
    {
        $students = $this->parent->students()->where('students.status', 'active')->orderBy('last_name')->get();
        $student = $students->firstWhere('id', (int) $this->studentId);
        $assignments = $student
            ? Assignment::where('status', 'published')->whereHas('classSubject.schoolClass.academicYear',
            fn ($query) => $query->where('school_id', $this->parent->school_id))->whereHas('classSubject.schoolClass.enrollments',
                fn ($query) => $query->where('student_id', $student->id)->where('status', 'active'))->with('classSubject.subject')->latest('due_at')->paginate(15)->through(function ($assignment) use ($student)
            {
                $assignment->submission = $assignment->submissions()->where('student_id', $student->id)->first();
            return $assignment;
            })
            : Assignment::query()->whereRaw('1 = 0')->paginate(15);

        return view('livewire.lms.assignments.parent.index', compact('students', 'student', 'assignments'));
    }
}
