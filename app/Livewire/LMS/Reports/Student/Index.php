<?php

namespace App\Livewire\LMS\Reports\Student;

use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Term;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public Student $student;

    public string $search = '';

    public string $filterTermId = '';

    public function mount(): void
    {
        $student = auth()->user()->student;
        abort_unless(auth()->user()->hasRole('student') && $student, 403);
        $this->student = $student;
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
        $filtered = ReportCard::query()
            ->with(['term.academicYear', 'academicYear', 'schoolClass'])
            ->where('student_id', $this->student->id)
            ->where('status', 'published')
            ->whereHas('student', fn ($query) => $query->where('school_id', $this->student->school_id))
            ->when(filled($this->filterTermId), fn ($query) => $query->where('term_id', (int) $this->filterTermId))
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($nested) => $nested
                    ->whereHas('term', fn ($terms) => $terms->where('name', 'like', $term))
                    ->orWhereHas('academicYear', fn ($years) => $years->where('name', 'like', $term))
                    ->orWhereHas('schoolClass', fn ($classes) => $classes->where('name', 'like', $term)));
            });

        return view('livewire.lms.reports.student.index', [
            'reports' => (clone $filtered)->latest('published_at')->paginate(12),
            'terms' => Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))->with('academicYear')->latest('starts_at')->get(),
            'reportCount' => (clone $filtered)->count(),
            'attendanceAverage' => round((float) ((clone $filtered)->avg('attendance_percentage') ?? 0), 1),
            'latestReport' => (clone $filtered)->latest('published_at')->first(),
        ]);
    }
}
