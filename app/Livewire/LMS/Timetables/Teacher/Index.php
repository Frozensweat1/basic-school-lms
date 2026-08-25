<?php

namespace App\Livewire\LMS\Timetables\Teacher;

use App\Models\SchedulePeriod;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\TimetableEntry;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public Teacher $teacher;

    public string $search = '';

    public string $filterDay = '';

    public string $filterTermId = '';

    public string $viewMode = 'calendar';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('teacher') && auth()->user()->teacher, 403);
        $this->teacher = auth()->user()->teacher;
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
        $filtered = TimetableEntry::query()
            ->where('teacher_id', $this->teacher->id)
            ->whereHas('timetable', fn ($query) => $query
                ->where('status', 'published')
                ->when($this->filterTermId !== '', fn ($term) => $term->where('term_id', (int) $this->filterTermId))
                ->whereHas('academicYear', fn ($year) => $year->where('school_id', $this->teacher->school_id)))
            ->when($this->filterDay !== '', fn ($query) => $query->where('day_of_week', (int) $this->filterDay))
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('room', 'like', $term)
                        ->orWhereHas('schoolClass', fn ($class) => $class->where('name', 'like', $term))
                        ->orWhereHas('classSubject.subject', fn ($subject) => $subject->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('schedulePeriod', fn ($period) => $period->where('name', 'like', $term));
                });
            });

        $entries = (clone $filtered)
            ->with(['schoolClass', 'classSubject.subject', 'schedulePeriod', 'timetable.term.academicYear'])
            ->orderBy('day_of_week')
            ->orderBy(SchedulePeriod::select('sequence')->whereColumn('schedule_periods.id', 'timetable_entries.schedule_period_id'))
            ->paginate(20);

        return view('livewire.lms.timetables.teacher.index', [
            'entries' => $entries,
            'gridEntries' => (clone $filtered)->with(['schoolClass', 'classSubject.subject', 'schedulePeriod', 'timetable.term.academicYear'])->get(),
            'periods' => SchedulePeriod::query()->where('school_id', $this->teacher->school_id)->orderBy('sequence')->get(),
            'terms' => Term::query()->whereHas('academicYear', fn ($query) => $query->where('school_id', $this->teacher->school_id))->with('academicYear')->latest('starts_at')->get(),
            'sessionCount' => (clone $filtered)->count(),
            'classCount' => (clone $filtered)->distinct()->count('school_class_id'),
            'subjectCount' => (clone $filtered)->distinct()->count('class_subject_id'),
        ]);
    }
}
