<?php

namespace App\Livewire\LMS\TimetableEntries;

use App\Models\ClassSubject;
use App\Models\SchedulePeriod;
use App\Models\SchoolClass;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public Timetable $timetable;

    public string $search = '';

    public string $filterClassId = '';

    public string $filterDay = '';

    public string $filterPeriodId = '';

    public string $viewMode = 'calendar';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $classSubjectId = '';

    public string $periodId = '';

    public string $dayOfWeek = '1';

    public string $room = '';

    public function mount(Timetable $timetable): void
    {
        $this->authorize('update', $timetable);
        $this->timetable = $timetable->loadMissing(['academicYear', 'term']);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClassId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDay(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPeriodId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterClassId', 'filterDay', 'filterPeriodId']);
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

    public function create(): void
    {
        $this->authorize('update', $this->timetable);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('update', $this->timetable);

        $entry = $this->entryQuery()->findOrFail($id);
        $this->editingId = $entry->id;
        $this->classSubjectId = (string) $entry->class_subject_id;
        $this->periodId = (string) $entry->schedule_period_id;
        $this->dayOfWeek = (string) $entry->day_of_week;
        $this->room = (string) ($entry->room ?? '');
        $this->resetErrorBag();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->timetable);

        try {
            $data = $this->validate([
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'periodId' => ['required', 'integer', Rule::exists('schedule_periods', 'id')],
                'dayOfWeek' => ['required', 'integer', Rule::in(array_keys(TimetableEntry::DAYS))],
                'room' => ['nullable', 'string', 'max:100'],
            ]);

            $schoolId = (int) $this->timetable->academicYear->school_id;
            $classSubject = ClassSubject::query()
                ->with('schoolClass')
                ->whereKey((int) $data['classSubjectId'])
                ->whereHas('schoolClass', fn ($query) => $query
                    ->where('academic_year_id', $this->timetable->academic_year_id)
                    ->whereHas('academicYear', fn ($year) => $year->where('school_id', $schoolId)))
                ->firstOrFail();
            $period = SchedulePeriod::query()
                ->whereKey((int) $data['periodId'])
                ->where('school_id', $schoolId)
                ->firstOrFail();

            $slotQuery = $this->entryQuery()
                ->where('day_of_week', (int) $data['dayOfWeek'])
                ->where('schedule_period_id', $period->id)
                ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId));

            if ((clone $slotQuery)->where('school_class_id', $classSubject->school_class_id)->exists()) {
                throw ValidationException::withMessages(['periodId' => 'This class already has a lesson in the selected time slot.']);
            }

            if ($classSubject->teacher_id && (clone $slotQuery)->where('teacher_id', $classSubject->teacher_id)->exists()) {
                throw ValidationException::withMessages(['periodId' => 'The assigned teacher already teaches another class in this time slot.']);
            }

            $room = trim($data['room'] ?? '');
            if ($room !== '' && (clone $slotQuery)->whereRaw('LOWER(room) = ?', [mb_strtolower($room)])->exists()) {
                throw ValidationException::withMessages(['room' => 'This room is already assigned in the selected time slot.']);
            }

            $entry = $this->editingId
                ? $this->entryQuery()->findOrFail($this->editingId)
                : new TimetableEntry(['timetable_id' => $this->timetable->id]);

            $entry->fill([
                'school_class_id' => $classSubject->school_class_id,
                'class_subject_id' => $classSubject->id,
                'teacher_id' => $classSubject->teacher_id,
                'schedule_period_id' => $period->id,
                'day_of_week' => (int) $data['dayOfWeek'],
                'room' => $room !== '' ? $room : null,
            ])->save();

            $wasEditing = $this->editingId !== null;
            $this->showFormModal = false;
            $this->resetForm();

            LivewireAlert::title($wasEditing ? 'Timetable entry updated' : 'Timetable entry added')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the timetable entry')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save timetable entry')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('update', $this->timetable);
        $this->entryQuery()->findOrFail($id);
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorize('update', $this->timetable);

        try {
            $this->entryQuery()->findOrFail($this->deletingId)->delete();
            $this->closeDeleteModal();
            LivewireAlert::title('Timetable entry removed')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove timetable entry')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        $schoolId = (int) $this->timetable->academicYear->school_id;
        $filtered = $this->entryQuery()
            ->with(['schoolClass', 'classSubject.subject', 'teacher', 'schedulePeriod'])
            ->when(filled($this->search), function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($term): void {
                    $nested->where('room', 'like', $term)
                        ->orWhereHas('schoolClass', fn ($class) => $class->where('name', 'like', $term))
                        ->orWhereHas('classSubject.subject', fn ($subject) => $subject->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                        ->orWhereHas('schedulePeriod', fn ($period) => $period->where('name', 'like', $term));
                });
            })
            ->when($this->filterClassId !== '', fn ($query) => $query->where('school_class_id', (int) $this->filterClassId))
            ->when($this->filterDay !== '', fn ($query) => $query->where('day_of_week', (int) $this->filterDay))
            ->when($this->filterPeriodId !== '', fn ($query) => $query->where('schedule_period_id', (int) $this->filterPeriodId));

        $entries = (clone $filtered)
            ->orderBy('day_of_week')
            ->orderBy(SchedulePeriod::select('sequence')->whereColumn('schedule_periods.id', 'timetable_entries.schedule_period_id'))
            ->paginate(20);
        $periods = SchedulePeriod::query()->where('school_id', $schoolId)->orderBy('sequence')->get();

        return view('livewire.lms.timetable-entries.index', [
            'entries' => $entries,
            'gridEntries' => (clone $filtered)->get(),
            'classSubjects' => ClassSubject::query()
                ->with(['schoolClass', 'subject', 'teacher'])
                ->whereHas('schoolClass', fn ($query) => $query->where('academic_year_id', $this->timetable->academic_year_id))
                ->orderBy('school_class_id')->get(),
            'classes' => SchoolClass::query()->where('academic_year_id', $this->timetable->academic_year_id)->orderBy('name')->get(),
            'periods' => $periods,
            'matchingCount' => (clone $filtered)->count(),
            'classCount' => $this->entryQuery()->distinct()->count('school_class_id'),
            'teacherCount' => $this->entryQuery()->whereNotNull('teacher_id')->distinct()->count('teacher_id'),
        ]);
    }

    private function entryQuery()
    {
        return TimetableEntry::query()->where('timetable_id', $this->timetable->id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'classSubjectId', 'periodId', 'room']);
        $this->dayOfWeek = '1';
        $this->resetErrorBag();
    }
}
