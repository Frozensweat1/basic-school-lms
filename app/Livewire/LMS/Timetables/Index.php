<?php

namespace App\Livewire\LMS\Timetables;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\Term;
use App\Models\Timetable;
use App\Services\Timetables\TimetableGenerator;
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

    public bool $showGenerateModal = false;

    public bool $replaceExistingEntries = true;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $generatingId = null;

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterTermId = '';

    public string $filterStatus = '';

    public string $academicYearId = '';

    public string $termId = '';

    public string $name = '';

    public string $status = 'draft';

    public string $sessionsPerSubject = '1';

    public function mount(): void
    {
        $this->authorize('viewAny', Timetable::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAcademicYearId(): void
    {
        $this->filterTermId = '';
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedAcademicYearId(): void
    {
        $this->termId = '';
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAcademicYearId', 'filterTermId', 'filterStatus']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Timetable::class);
        $this->resetForm();

        $year = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->first();
        $this->academicYearId = (string) ($year?->id ?? '');
        $this->termId = (string) ($year?->terms()->orderByDesc('is_active')->orderBy('sequence')->value('id') ?? '');
        $this->showFormModal = true;
    }

    public function edit(Timetable $timetable): void
    {
        $this->assertVisible($timetable);
        $this->authorize('update', $timetable);

        $this->editingId = $timetable->id;
        $this->academicYearId = (string) $timetable->academic_year_id;
        $this->termId = (string) $timetable->term_id;
        $this->name = $timetable->name;
        $this->status = $timetable->status;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $timetable = $this->editingId
            ? $this->scopedTimetables()->findOrFail($this->editingId)
            : null;

        $this->authorize($timetable ? 'update' : 'create', $timetable ?? Timetable::class);

        try {
            $this->name = trim($this->name);
            $data = $this->validate([
                'academicYearId' => ['required', 'integer', Rule::exists('academic_years', 'id')],
                'termId' => ['required', 'integer', Rule::exists('terms', 'id')],
                'name' => ['required', 'string', 'max:100'],
                'status' => ['required', Rule::in(Timetable::STATUSES)],
            ]);

            $year = AcademicYear::query()
                ->whereKey($data['academicYearId'])
                ->where('school_id', $this->schoolId())
                ->firstOrFail();
            $term = Term::query()
                ->whereKey($data['termId'])
                ->where('academic_year_id', $year->id)
                ->firstOrFail();

            if ($timetable && $timetable->entries()->exists() && $timetable->academic_year_id !== $year->id) {
                throw ValidationException::withMessages([
                    'academicYearId' => "Remove this timetable's entries before moving it to another academic year.",
                ]);
            }

            if ($data['status'] === 'published' && (! $timetable || ! $timetable->entries()->exists())) {
                throw ValidationException::withMessages([
                    'status' => 'Add or generate timetable entries before publishing this timetable.',
                ]);
            }

            $duplicateExists = $this->scopedTimetables()
                ->where('academic_year_id', $year->id)
                ->where('term_id', $term->id)
                ->where('name', $data['name'])
                ->when($timetable, fn (Builder $query) => $query->whereKeyNot($timetable->id))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'name' => 'A timetable with this name already exists for the selected term.',
                ]);
            }

            Timetable::updateOrCreate(
                ['id' => $timetable?->id],
                [
                    'academic_year_id' => $year->id,
                    'term_id' => $term->id,
                    'name' => $data['name'],
                    'status' => $data['status'],
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($timetable ? 'Timetable updated' : 'Timetable created')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the timetable form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save timetable')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmGenerate(Timetable $timetable): void
    {
        $this->assertVisible($timetable);
        $this->authorize('update', $timetable);

        $this->generatingId = $timetable->id;
        $this->sessionsPerSubject = '1';
        $this->replaceExistingEntries = true;
        $this->resetErrorBag('generation');
        $this->showGenerateModal = true;
    }

    public function generateAutomatically(TimetableGenerator $generator): void
    {
        $timetable = $this->generatingId
            ? $this->scopedTimetables()->findOrFail($this->generatingId)
            : null;

        abort_unless($timetable, 404);
        $this->authorize('update', $timetable);

        try {
            $data = $this->validate([
                'sessionsPerSubject' => ['required', 'integer', 'between:1,5'],
                'replaceExistingEntries' => ['boolean'],
            ]);

            $result = $generator->generate(
                $timetable,
                (int) $data['sessionsPerSubject'],
                (bool) $data['replaceExistingEntries'],
            );

            $this->showGenerateModal = false;
            $this->generatingId = null;
            $unscheduledCount = count($result['unscheduled']);

            if ($unscheduledCount > 0) {
                LivewireAlert::title('Timetable generated with gaps')
                    ->text("{$result['scheduled_count']} sessions added; {$unscheduledCount} could not be placed without a clash.")
                    ->warning()->asToast()->position('top-end')->show();
            } else {
                LivewireAlert::title('Timetable generated')
                    ->text("{$result['scheduled_count']} sessions were added without class or teacher clashes.")
                    ->success()->asToast()->position('top-end')->show();
            }
        } catch (ValidationException $exception) {
            LivewireAlert::title('Unable to generate timetable')
                ->text(collect($exception->errors())->flatten()->first() ?? 'Check the generation options.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to generate timetable')
                ->text('Please review the schedule setup and try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Timetable $timetable): void
    {
        $this->assertVisible($timetable);
        $this->authorize('delete', $timetable);
        $this->deletingId = $timetable->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $timetable = $this->deletingId
            ? $this->scopedTimetables()->findOrFail($this->deletingId)
            : null;

        abort_unless($timetable, 404);
        $this->authorize('delete', $timetable);

        try {
            $timetable->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Timetable deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete timetable')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->showGenerateModal = false;
        $this->generatingId = null;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function render()
    {
        $schoolId = $this->schoolId();
        $years = AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('starts_at')->get();
        $terms = Term::query()
            ->whereIn('academic_year_id', $years->pluck('id'))
            ->with('academicYear')
            ->orderBy('academic_year_id')
            ->orderBy('sequence')
            ->get();
        $search = trim($this->search);
        $filtered = $this->scopedTimetables()
            ->with(['academicYear', 'term'])
            ->withCount('entries')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $items) use ($search): void {
                    $items->where('name', 'like', "%{$search}%")
                        ->orWhereHas('academicYear', fn (Builder $years) => $years->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('term', fn (Builder $terms) => $terms->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterAcademicYearId), fn (Builder $query) => $query->where('academic_year_id', $this->filterAcademicYearId))
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', $this->filterTermId))
            ->when(filled($this->filterStatus), fn (Builder $query) => $query->where('status', $this->filterStatus));

        $timetables = (clone $filtered)
            ->latest()
            ->paginate(15);
        $generatingTimetable = $this->generatingId
            ? $this->scopedTimetables()->withCount('entries')->find($this->generatingId)
            : null;
        $generationSubjectCount = $generatingTimetable
            ? ClassSubject::query()->whereHas('schoolClass', fn (Builder $classes) => $classes->where('academic_year_id', $generatingTimetable->academic_year_id)->where('status', 'active'))->count()
            : 0;

        return view('livewire.lms.timetables.index', [
            'timetables' => $timetables,
            'years' => $years,
            'terms' => $terms,
            'publishedCount' => (clone $filtered)->where('status', 'published')->count(),
            'draftCount' => (clone $filtered)->where('status', 'draft')->count(),
            'entryCount' => (clone $filtered)->get()->sum('entries_count'),
            'generatingTimetable' => $generatingTimetable,
            'generationPeriodCount' => SchedulePeriod::query()->where('school_id', $schoolId)->count(),
            'generationSubjectCount' => $generationSubjectCount,
        ]);
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing timetables.');

        return (int) $schoolId;
    }

    private function scopedTimetables(): Builder
    {
        return Timetable::query()
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()));
    }

    private function assertVisible(Timetable $timetable): void
    {
        abort_unless($this->scopedTimetables()->whereKey($timetable->id)->exists(), 404);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'academicYearId', 'termId', 'name', 'status']);
        $this->status = 'draft';
        $this->resetValidation();
    }
}
