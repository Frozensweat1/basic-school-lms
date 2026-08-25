<?php

namespace App\Livewire\LMS\SchedulePeriods;

use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\TimetableEntry;
use Illuminate\Database\Eloquent\Builder;
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

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $search = '';

    public string $filterUsage = '';

    public string $name = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $sequence = '0';

    public function mount(): void
    {
        $this->authorize('viewAny', SchedulePeriod::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterUsage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterUsage']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', SchedulePeriod::class);
        $this->resetForm();
        $this->sequence = (string) ((int) $this->scopedPeriods()->max('sequence') + 1);
        $this->showFormModal = true;
    }

    public function edit(SchedulePeriod $period): void
    {
        $this->assertVisible($period);
        $this->authorize('update', $period);

        $this->editingId = $period->id;
        $this->name = $period->name;
        $this->startsAt = substr($period->starts_at, 0, 5);
        $this->endsAt = substr($period->ends_at, 0, 5);
        $this->sequence = (string) $period->sequence;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $schoolId = $this->schoolId();
        $period = $this->editingId
            ? $this->scopedPeriods()->findOrFail($this->editingId)
            : null;

        $this->authorize($period ? 'update' : 'create', $period ?? SchedulePeriod::class);

        try {
            $this->name = trim($this->name);
            $data = $this->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('schedule_periods', 'name')
                        ->where(fn ($query) => $query->where('school_id', $schoolId))
                        ->ignore($period?->id),
                ],
                'startsAt' => ['required', 'date_format:H:i'],
                'endsAt' => ['required', 'date_format:H:i', 'after:startsAt'],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);

            $overlapExists = $this->scopedPeriods()
                ->where('starts_at', '<', $data['endsAt'])
                ->where('ends_at', '>', $data['startsAt'])
                ->when($period, fn (Builder $query) => $query->whereKeyNot($period->id))
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'startsAt' => 'This time overlaps another schedule period.',
                ]);
            }

            SchedulePeriod::updateOrCreate(
                ['id' => $period?->id],
                [
                    'school_id' => $schoolId,
                    'name' => $data['name'],
                    'starts_at' => $data['startsAt'],
                    'ends_at' => $data['endsAt'],
                    'sequence' => $data['sequence'],
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($period ? 'Schedule period updated' : 'Schedule period added')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the schedule period form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save schedule period')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(SchedulePeriod $period): void
    {
        $this->assertVisible($period);
        $this->authorize('delete', $period);

        $this->deletingId = $period->id;
        $this->resetErrorBag('delete');
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $period = $this->deletingId
            ? $this->scopedPeriods()->findOrFail($this->deletingId)
            : null;

        abort_unless($period, 404);
        $this->authorize('delete', $period);

        if ($period->timetableEntries()->exists()) {
            $this->addError('delete', 'This period is used by timetable entries. Move those entries to another period before deleting it.');
            LivewireAlert::title('Schedule period cannot be removed')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $period->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Schedule period deleted')
                ->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete schedule period')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        $search = trim($this->search);
        $filtered = $this->scopedPeriods()
            ->withCount('timetableEntries')
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
            ->when($this->filterUsage === 'used', fn (Builder $query) => $query->has('timetableEntries'))
            ->when($this->filterUsage === 'unused', fn (Builder $query) => $query->doesntHave('timetableEntries'));

        $periods = (clone $filtered)
            ->orderBy('sequence')
            ->orderBy('starts_at')
            ->paginate(15);

        $firstPeriod = (clone $filtered)->orderBy('starts_at')->first();
        $lastPeriod = (clone $filtered)->orderByDesc('ends_at')->first();

        return view('livewire.lms.schedule-periods.index', [
            'periods' => $periods,
            'configuredCount' => (clone $filtered)->count(),
            'usedCount' => (clone $filtered)->has('timetableEntries')->count(),
            'entryCount' => TimetableEntry::query()
                ->whereIn('schedule_period_id', (clone $filtered)->select('schedule_periods.id'))
                ->count(),
            'dayStartsAt' => $firstPeriod?->formattedStart(),
            'dayEndsAt' => $lastPeriod?->formattedEnd(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'name', 'startsAt', 'endsAt', 'sequence']);
        $this->sequence = '0';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing schedule periods.');

        return (int) $schoolId;
    }

    private function scopedPeriods(): Builder
    {
        return SchedulePeriod::query()->where('school_id', $this->schoolId());
    }

    private function assertVisible(SchedulePeriod $period): void
    {
        abort_unless($this->scopedPeriods()->whereKey($period->id)->exists(), 404);
    }
}
