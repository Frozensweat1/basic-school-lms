<?php

namespace App\Livewire\LMS\Timetables\Student;

use App\Models\SchedulePeriod;
use App\Models\Student;
use App\Models\Term;
use App\Models\TimetableEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public Student $student;

    public string $search = '';

    public string $filterDay = '';

    public string $filterTermId = '';

    public string $viewMode = 'calendar';

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

    public function updatedFilterDay(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterDay', 'filterTermId']);
        $this->resetPage();
    }

    public function showCalendar(): void
    {
        $this->viewMode = 'calendar';
    }

    public function showList(): void
    {
        $this->viewMode = 'list';
    }

    public function render()
    {
        $classIds = $this->student->enrollments()->where('status', 'active')->pluck('school_class_id');
        $filtered = TimetableEntry::query()
            ->whereIn('school_class_id', $classIds)
            ->whereHas('timetable', fn ($query) => $query
                ->where('status', 'published')
                ->when($this->filterTermId !== '', fn ($term) => $term->where('term_id', (int) $this->filterTermId))
                ->whereHas('academicYear', fn ($year) => $year->where('school_id', $this->student->school_id)))
            ->when($this->filterDay !== '', fn ($query) => $query->where('day_of_week', (int) $this->filterDay))
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('room', 'like', $term)
                        ->orWhereHas('classSubject.subject', fn ($subject) => $subject->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                        ->orWhereHas('schedulePeriod', fn ($period) => $period->where('name', 'like', $term));
                });
            });

        $entries = (clone $filtered)
            ->with(['schoolClass', 'classSubject.subject', 'teacher', 'schedulePeriod', 'timetable.term.academicYear'])
            ->orderBy('day_of_week')
            ->orderBy(SchedulePeriod::select('sequence')->whereColumn('schedule_periods.id', 'timetable_entries.schedule_period_id'))
            ->paginate(20);

        return view('livewire.lms.timetables.student.index', [
            'entries' => $entries,
            'gridEntries' => (clone $filtered)->with(['schoolClass', 'classSubject.subject', 'teacher', 'schedulePeriod', 'timetable.term.academicYear'])->get(),
            'periods' => SchedulePeriod::query()->where('school_id', $this->student->school_id)->orderBy('sequence')->get(),
            'terms' => Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $this->student->school_id))->with('academicYear')->latest('starts_at')->get(),
            'sessionCount' => (clone $filtered)->count(),
            'dayCount' => (clone $filtered)->distinct()->count('day_of_week'),
            'subjectCount' => (clone $filtered)->distinct()->count('class_subject_id'),
        ]);
    }
}
