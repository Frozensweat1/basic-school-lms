<?php

namespace App\Livewire\LMS\Examinations\Parent;

use App\Models\Examination;
use App\Models\ParentGuardian;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public ParentGuardian $parent;

    public string $studentId = '';

    public string $search = '';

    public string $termId = '';

    public function mount(): void
    {
        $this->parent = auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent') && $this->parent, 403);

        $this->studentId = (string) ($this->parent->students()
            ->where('students.status', 'active')
            ->value('students.id') ?? '');
    }

    public function updatedStudentId(): void
    {
        $this->resetPage();
        abort_unless($this->studentId === '' || $this->parent->students()->where('students.status', 'active')->whereKey((int) $this->studentId)->exists(), 403);
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
        $students = $this->parent->students()
            ->where('students.status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $isOwnedStudent = filled($this->studentId)
            && $students->contains('id', (int) $this->studentId);
        $search = trim($this->search);

        $examinations = $isOwnedStudent
            ? Examination::query()
                ->with(['classSubject.schoolClass', 'classSubject.subject', 'term'])
                ->where('school_id', $this->parent->school_id)
                ->whereIn('status', Examination::LEARNER_VISIBLE_STATUSES)
                ->whereHas('classSubject.schoolClass.enrollments', fn (Builder $query) => $query->where('student_id', (int) $this->studentId)->where('status', 'active'))
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
                ->paginate(15)
            : Examination::query()->whereRaw('1 = 0')->paginate(15);

        return view('livewire.lms.examinations.parent.index', [
            'students' => $students,
            'examinations' => $examinations,
            'terms' => Term::query()
                ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->parent->school_id))
                ->orderByDesc('academic_year_id')
                ->orderBy('sequence')
                ->get(),
        ]);
    }
}
