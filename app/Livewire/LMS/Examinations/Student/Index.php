<?php

namespace App\Livewire\LMS\Examinations\Student;

use App\Models\Examination;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public Student $student;

    public string $search = '';

    public string $termId = '';

    public function mount(): void
    {
        $this->student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $this->student, 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTermId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'termId']);
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $examinations = Examination::query()
            ->with(['classSubject.schoolClass', 'classSubject.subject', 'term'])
            ->where('school_id', $this->student->school_id)
            ->whereIn('status', Examination::LEARNER_VISIBLE_STATUSES)
            ->whereHas('classSubject.schoolClass.enrollments', fn (Builder $query) => $query->where('student_id', $this->student->id)->where('status', 'active'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $exams) use ($search): void {
                    $exams->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('classSubject.subject', fn (Builder $subjects) => $subjects->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('classSubject.schoolClass', fn (Builder $classes) => $classes->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->termId), fn (Builder $query) => $query->where('term_id', $this->termId))
            ->orderBy('exam_date')
            ->paginate(15);

        return view('livewire.lms.examinations.student.index', [
            'examinations' => $examinations,
            'terms' => Term::query()
                ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->student->school_id))
                ->orderByDesc('academic_year_id')
                ->orderBy('sequence')
                ->get(),
            'upcomingCount' => Examination::query()
                ->where('school_id', $this->student->school_id)
                ->whereIn('status', Examination::LEARNER_VISIBLE_STATUSES)
                ->whereDate('exam_date', '>=', today())
                ->whereHas('classSubject.schoolClass.enrollments', fn (Builder $query) => $query->where('student_id', $this->student->id)->where('status', 'active'))
                ->count(),
        ]);
    }
}
