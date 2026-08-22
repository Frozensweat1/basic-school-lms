<?php

namespace App\Livewire\LMS\Results\Parent;

use App\Models\ParentGuardian;
use App\Models\SubjectResult;
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
        $this->studentId = (string) ($this->activeStudents()->value('students.id') ?? '');
    }

    public function updatedStudentId(): void
    {
        $this->resetPage();
        abort_unless($this->studentId === '' || $this->activeStudents()->whereKey((int) $this->studentId)->exists(), 403);
    }

    public function render()
    {
        $students = $this->activeStudents()->orderBy('last_name')->get();
        $studentId = (int) $this->studentId;
        $results = SubjectResult::query()
            ->with(['classSubject.subject', 'term', 'gradingScale'])
            ->where('status', 'published')
            ->whereHas('student', fn ($query) => $query->where('school_id', $this->parent->school_id))
            ->when($studentId, fn ($query) => $query->where('student_id', $studentId))
            ->latest('term_id')
            ->paginate(15);

        return view('livewire.lms.results.parent.index', compact('students', 'results'));
    }

    private function activeStudents()
    {
        return $this->parent->students()->where('students.status', 'active');
    }
}
