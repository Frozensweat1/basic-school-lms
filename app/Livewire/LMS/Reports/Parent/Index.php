<?php

namespace App\Livewire\LMS\Reports\Parent;

use App\Models\ParentGuardian;
use App\Models\ReportCard;
use App\Models\Term;
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

    public string $filterTermId = '';

    public function mount(): void
    {
        $parent = auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent') && $parent, 403);
        $this->parent = $parent;
        $this->studentId = (string) ($this->activeStudents()->value('students.id') ?? '');
    }

    public function updatedStudentId(): void
    {
        abort_unless($this->studentId === '' || $this->activeStudents()->whereKey((int) $this->studentId)->exists(), 403);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterTermId']);
        $this->resetPage();
    }

    public function render()
    {
        $students = $this->activeStudents()->orderBy('last_name')->orderBy('first_name')->get();
        $student = $students->firstWhere('id', (int) $this->studentId);
        $filtered = ReportCard::query()
            ->with(['term.academicYear', 'academicYear', 'schoolClass'])
            ->where('status', 'published')
            ->whereHas('student', fn ($query) => $query->where('school_id', $this->parent->school_id))
            ->when($student, fn ($query) => $query->where('student_id', $student->id), fn ($query) => $query->whereRaw('1 = 0'))
            ->when(filled($this->filterTermId), fn ($query) => $query->where('term_id', (int) $this->filterTermId))
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($nested) => $nested
                    ->whereHas('term', fn ($terms) => $terms->where('name', 'like', $term))
                    ->orWhereHas('academicYear', fn ($years) => $years->where('name', 'like', $term))
                    ->orWhereHas('schoolClass', fn ($classes) => $classes->where('name', 'like', $term)));
            });

        return view('livewire.lms.reports.parent.index', [
            'students' => $students,
            'selectedStudent' => $student,
            'reports' => (clone $filtered)->latest('published_at')->paginate(12),
            'terms' => Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $this->parent->school_id))->with('academicYear')->latest('starts_at')->get(),
            'reportCount' => (clone $filtered)->count(),
            'attendanceAverage' => round((float) ((clone $filtered)->avg('attendance_percentage') ?? 0), 1),
            'latestReport' => (clone $filtered)->latest('published_at')->first(),
        ]);
    }

    private function activeStudents()
    {
        return $this->parent->students()
            ->where('students.school_id', $this->parent->school_id)
            ->where('students.status', 'active');
    }
}
