<?php

namespace App\Livewire\LMS\Examinations\Parent;

use App\Models\{Examination, ParentGuardian};
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
        abort_unless($this->studentId === '' || $this->parent->students()->where('students.status', 'active')->whereKey((int) $this->studentId)->exists(), 403);
    }

    public function render()
    {
        $students = $this->parent->students()->where('students.status', 'active')->orderBy('last_name')->get();
        $examinations = $this->studentId && $this->parent->students()->where('students.status', 'active')->whereKey((int) $this->studentId)->exists()
            ? Examination::with(['classSubject.subject', 'term'])->where('school_id', $this->parent->school_id)->whereIn('status', ['scheduled', 'completed'])->whereHas('classSubject.schoolClass.enrollments', fn ($q) => $q->where('student_id', (int) $this->studentId)->where('status', 'active'))->orderBy('exam_date')->paginate(15)
            : Examination::query()->whereRaw('1 = 0')->paginate(15);

        return view('livewire.lms.examinations.parent.index', compact('students', 'examinations'));
    }
}
