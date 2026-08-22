<?php

namespace App\Livewire\LMS\Quizzes\Parent;

use App\Models\ParentGuardian;
use App\Models\Quiz;
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
        $student = $students->firstWhere('id', (int) $this->studentId);
        $quizzes = $student
            ? Quiz::query()
                ->where('status', 'published')
                ->whereHas('classSubject.schoolClass.academicYear', fn ($query) => $query->where('school_id', $this->parent->school_id))
                ->whereHas('classSubject.schoolClass.enrollments', fn ($query) => $query->where('student_id', $student->id)->where('status', 'active'))
                ->with(['classSubject.subject'])
                ->paginate(15)
                ->through(function (Quiz $quiz) use ($student): Quiz {
                    $quiz->attempt = $quiz->attempts()->where('student_id', $student->id)->latest('attempt_number')->first();
                    return $quiz;
                })
            : Quiz::query()->whereRaw('1 = 0')->paginate(15);

        return view('livewire.lms.quizzes.parent.index', compact('students', 'student', 'quizzes'));
    }

    private function activeStudents()
    {
        return $this->parent->students()->where('students.status', 'active');
    }
}
