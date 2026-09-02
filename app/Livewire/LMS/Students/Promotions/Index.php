<?php

namespace App\Livewire\LMS\Students\Promotions;

use App\Models\AcademicYear;
use App\Models\ClassEnrollment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentPromotionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'source_year', except: '')]
    public string $sourceYearId = '';

    #[Url(as: 'source_class', except: '')]
    public string $sourceClassId = '';

    #[Url(as: 'target_year', except: '')]
    public string $targetYearId = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'placement', except: '')]
    public string $placementFilter = '';

    #[Url(as: 'per_page', except: 15)]
    public int $perPage = 15;

    public string $bulkDestination = '';

    public string $effectiveDate = '';

    public string $notes = '';

    /** @var array<int, int|string> */
    public array $selectedStudentIds = [];

    /** @var array<int|string, string> */
    public array $studentDestinations = [];

    public bool $showConfirmationModal = false;

    public bool $showCancelModal = false;

    public ?int $cancellingEnrollmentId = null;

    /** @var array<string, mixed> */
    public array $confirmationSummary = [];

    public function mount(): void
    {
        $this->authorize('create', Student::class);
        $this->ensureSchoolConfigured();
        $this->initialiseContext();
    }

    public function updatedSourceYearId(): void
    {
        $this->sourceClassId = (string) ($this->sourceClassesQuery()->value('id') ?? '');
        $this->targetYearId = (string) ($this->targetYearsQuery()->value('id') ?? '');
        $this->syncTargetDefaults();
        $this->resetWorkflow();
    }

    public function updatedSourceClassId(): void
    {
        $this->resetWorkflow();
    }

    public function updatedTargetYearId(): void
    {
        $this->syncTargetDefaults();
        $this->resetWorkflow();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPlacementFilter(): void
    {
        if (! in_array($this->placementFilter, ['', 'unplanned', 'planned', 'conflict'], true)) {
            $this->placementFilter = '';
        }

        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 15, 25, 50], true)) {
            $this->perPage = 15;
        }

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'placementFilter']);
        $this->resetPage();
    }

    public function selectVisible(): void
    {
        $ids = $this->filteredEnrollmentsQuery()
            ->paginate($this->normalisedPerPage())
            ->getCollection()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->selectedStudentIds = collect($this->selectedStudentIds)
            ->merge($ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function clearSelection(): void
    {
        $this->reset(['selectedStudentIds', 'studentDestinations']);
        $this->resetValidation(['selectedStudentIds', 'studentDestinations', 'bulkDestination']);
    }

    public function applyDestinationToSelected(): void
    {
        if ($this->selectedStudentIds === []) {
            $this->addError('selectedStudentIds', 'Select at least one learner first.');

            return;
        }

        if (! $this->isValidDestination($this->bulkDestination)) {
            $this->addError('bulkDestination', 'Choose a valid destination or outcome first.');

            return;
        }

        foreach ($this->normalisedSelectedIds() as $studentId) {
            $this->studentDestinations[$studentId] = $this->bulkDestination;
        }

        $this->resetValidation(['bulkDestination', 'studentDestinations']);
    }

    public function reviewOne(int $studentId): void
    {
        $this->selectedStudentIds = [$studentId];
        $this->reviewSelected();
    }

    public function reviewSelected(): void
    {
        $this->authorize('create', Student::class);

        try {
            [$sourceYear, $sourceClass, $targetYear, $plan] = $this->promotionPlan();
            $labels = $this->destinationLabels();
            $groups = collect($plan)
                ->countBy()
                ->mapWithKeys(fn (int $count, string $decision) => [($labels[$decision] ?? 'Unknown outcome') => $count])
                ->all();

            $this->confirmationSummary = [
                'count' => count($plan),
                'source' => $sourceClass->name.' · '.$sourceYear->name,
                'target' => $targetYear->name,
                'mode' => $targetYear->is_active ? 'Apply immediately' : 'Prepare as pending',
                'groups' => $groups,
                'effective_date' => $this->effectiveDate,
            ];
            $this->showConfirmationModal = true;
        } catch (ValidationException $exception) {
            LivewireAlert::title('Review the promotion plan')
                ->text('Correct the highlighted selections before continuing.')
                ->warning()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        }
    }

    public function processPromotions(StudentPromotionService $promotionService): void
    {
        $this->authorize('create', Student::class);

        try {
            [$sourceYear, $sourceClass, $targetYear, $plan] = $this->promotionPlan();
            $counts = $promotionService->process(
                $sourceYear,
                $sourceClass,
                $targetYear,
                $plan,
                $this->effectiveDate,
                filled($this->notes) ? trim($this->notes) : null,
            );

            $this->showConfirmationModal = false;
            $this->reset(['selectedStudentIds', 'studentDestinations', 'confirmationSummary', 'notes']);
            $this->resetPage();

            $message = $counts['pending'] > 0
                ? "{$counts['pending']} placement(s) prepared for target-year activation."
                : "{$counts['processed']} learner transition(s) completed.";

            LivewireAlert::title('Student promotions saved')
                ->text($message)
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            $this->showConfirmationModal = false;
            LivewireAlert::title('Promotion was not applied')
                ->text('No learner records were changed. Review the highlighted issue and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->showConfirmationModal = false;
            LivewireAlert::title('Unable to process promotions')
                ->text('No learner records were changed. Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmCancelPendingPlacement(int $enrollmentId): void
    {
        $this->cancellingEnrollmentId = $enrollmentId;
        $this->showCancelModal = true;
    }

    public function cancelPendingPlacement(?int $enrollmentId = null): void
    {
        $this->authorize('create', Student::class);
        $enrollmentId ??= $this->cancellingEnrollmentId;

        try {
            throw_unless($enrollmentId, ValidationException::withMessages([
                'selectedStudentIds' => 'Choose a pending placement to cancel.',
            ]));

            [$sourceYear, $sourceClass, $targetYear] = $this->promotionContext();
            app(StudentPromotionService::class)->cancelPendingPlacement(
                $sourceYear,
                $sourceClass,
                $targetYear,
                $enrollmentId,
            );

            $this->closeModals();
            LivewireAlert::title('Pending placement cancelled')
                ->text('The learner remains active in the source class.')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            $this->closeModals();
            LivewireAlert::title('Unable to cancel placement')
                ->text('The placement may have already been applied or changed.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->closeModals();
            LivewireAlert::title('Unable to cancel placement')
                ->text('Please refresh the page and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function closeModals(): void
    {
        $this->reset(['showConfirmationModal', 'showCancelModal', 'cancellingEnrollmentId', 'confirmationSummary']);
    }

    public function render()
    {
        $sourceYears = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->orderByDesc('starts_at')
            ->get();
        $sourceClasses = $this->sourceClassesQuery()->with('stream')->orderBy('name')->orderBy('stream_id')->get();
        $targetYears = $this->targetYearsQuery()->get();
        $targetClasses = $this->targetClassesQuery()->with('stream')->orderBy('name')->orderBy('stream_id')->get();
        $enrollments = $this->filteredEnrollmentsQuery()->paginate($this->normalisedPerPage());
        $studentIds = $enrollments->getCollection()->pluck('student_id');

        $targetYearIsScoped = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->targetYearId)
            ->exists();

        $targetPlacements = $targetYearIsScoped && $studentIds->isNotEmpty()
            ? ClassEnrollment::query()
                ->with('schoolClass.stream')
                ->whereIn('student_id', $studentIds)
                ->whereHas('schoolClass.academicYear', fn ($years) => $years
                    ->whereKey((int) $this->targetYearId)
                    ->where('school_id', $this->schoolId()))
                ->get()
                ->groupBy('student_id')
            : collect();

        $eligibleCount = $this->baseEnrollmentsQuery()->count();
        $plannedCount = $this->placementCount([ClassEnrollment::STATUS_PENDING]);
        $conflictCount = $this->placementCount([ClassEnrollment::STATUS_ACTIVE, ClassEnrollment::STATUS_COMPLETED]);
        $targetYear = $targetYears->firstWhere('id', (int) $this->targetYearId)
            ?? AcademicYear::query()->where('school_id', $this->schoolId())->find((int) $this->targetYearId);

        return view('livewire.lms.students.promotions.index', [
            'sourceYears' => $sourceYears,
            'sourceClasses' => $sourceClasses,
            'targetYears' => $targetYears,
            'targetClasses' => $targetClasses,
            'targetYear' => $targetYear,
            'enrollments' => $enrollments,
            'targetPlacements' => $targetPlacements,
            'eligibleCount' => $eligibleCount,
            'plannedCount' => $plannedCount,
            'conflictCount' => $conflictCount,
        ]);
    }

    private function initialiseContext(): void
    {
        $source = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->when($this->sourceYearId !== '', fn ($years) => $years->whereKey((int) $this->sourceYearId))
            ->first();

        $source ??= AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->first();

        $this->sourceYearId = (string) ($source?->id ?? '');

        if (! $this->sourceClassesQuery()->whereKey((int) $this->sourceClassId)->exists()) {
            $this->sourceClassId = (string) ($this->sourceClassesQuery()->value('id') ?? '');
        }

        if (! $this->targetYearsQuery()->whereKey((int) $this->targetYearId)->exists()) {
            $this->targetYearId = (string) ($this->targetYearsQuery()->value('id') ?? '');
        }

        $this->syncTargetDefaults();
    }

    private function syncTargetDefaults(): void
    {
        $target = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->targetYearId)
            ->first();

        $this->bulkDestination = '';
        $this->effectiveDate = $target?->starts_at?->toDateString() ?? '';
    }

    private function resetWorkflow(): void
    {
        $this->reset([
            'selectedStudentIds',
            'studentDestinations',
            'showConfirmationModal',
            'showCancelModal',
            'cancellingEnrollmentId',
            'confirmationSummary',
            'notes',
        ]);
        $this->resetValidation();
        $this->resetPage();
    }

    /** @return array{0: AcademicYear, 1: SchoolClass, 2: AcademicYear} */
    private function promotionContext(): array
    {
        $this->validate([
            'sourceYearId' => ['required', 'integer'],
            'sourceClassId' => ['required', 'integer'],
            'targetYearId' => ['required', 'integer', 'different:sourceYearId'],
        ]);

        $sourceYear = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->sourceYearId)
            ->first();
        if (! $sourceYear) {
            throw ValidationException::withMessages(['sourceYearId' => 'The source academic year is unavailable.']);
        }

        $sourceClass = SchoolClass::query()
            ->where('academic_year_id', $sourceYear->id)
            ->where('status', 'active')
            ->whereKey((int) $this->sourceClassId)
            ->first();
        if (! $sourceClass) {
            throw ValidationException::withMessages(['sourceClassId' => 'The source class is unavailable.']);
        }

        $targetYear = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->targetYearId)
            ->first();
        if (! $targetYear) {
            throw ValidationException::withMessages(['targetYearId' => 'The target academic year is unavailable.']);
        }

        if ($targetYear->starts_at->lte($sourceYear->ends_at)) {
            throw ValidationException::withMessages([
                'targetYearId' => 'Choose a later academic year that starts after the source year ends.',
            ]);
        }

        if ($sourceYear->is_locked || $targetYear->is_locked) {
            throw ValidationException::withMessages([
                $sourceYear->is_locked ? 'sourceYearId' : 'targetYearId' => 'Locked academic years cannot be used for student promotions.',
            ]);
        }

        return [$sourceYear, $sourceClass, $targetYear];
    }

    /** @return array{0: AcademicYear, 1: SchoolClass, 2: AcademicYear, 3: array<int, string>} */
    private function promotionPlan(): array
    {
        $this->validate([
            'selectedStudentIds' => ['required', 'array', 'min:1', 'max:500'],
            'selectedStudentIds.*' => ['required', 'integer', 'distinct'],
            'effectiveDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        [$sourceYear, $sourceClass, $targetYear] = $this->promotionContext();
        $selectedIds = $this->normalisedSelectedIds();
        $eligibleIds = $this->baseEnrollmentsQuery()
            ->whereIn('student_id', $selectedIds)
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id);

        if ($eligibleIds->count() !== count($selectedIds)) {
            throw ValidationException::withMessages([
                'selectedStudentIds' => 'Every selected learner must still be eligible and enrolled in the source class.',
            ]);
        }

        $targetClassIds = $this->targetClassesQuery()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $plan = [];

        foreach ($selectedIds as $studentId) {
            $decision = trim((string) ($this->studentDestinations[$studentId] ?? $this->bulkDestination));

            if (! in_array($decision, [StudentPromotionService::OUTCOME_GRADUATE, StudentPromotionService::OUTCOME_TRANSFER], true)
                && ! in_array($decision, $targetClassIds, true)) {
                throw ValidationException::withMessages([
                    "studentDestinations.{$studentId}" => 'Choose a destination class or outcome for this learner.',
                ]);
            }

            if (! $targetYear->is_active
                && in_array($decision, [StudentPromotionService::OUTCOME_GRADUATE, StudentPromotionService::OUTCOME_TRANSFER], true)) {
                throw ValidationException::withMessages([
                    "studentDestinations.{$studentId}" => 'Graduation and transfer outcomes can only be applied when the target academic year is active.',
                ]);
            }

            $plan[$studentId] = $decision;
        }

        return [$sourceYear, $sourceClass, $targetYear, $plan];
    }

    private function sourceClassesQuery(): Builder
    {
        return SchoolClass::query()
            ->where('academic_year_id', (int) $this->sourceYearId)
            ->where('status', 'active')
            ->whereHas('academicYear', fn ($years) => $years->where('school_id', $this->schoolId()));
    }

    private function targetYearsQuery(): Builder
    {
        $source = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->find((int) $this->sourceYearId);

        return AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->when($source, fn ($years) => $years->whereDate('starts_at', '>', $source->ends_at))
            ->when(! $source, fn ($years) => $years->whereRaw('1 = 0'))
            ->where('is_locked', false)
            ->whereHas('classes', fn ($classes) => $classes->where('status', 'active'))
            ->orderBy('starts_at');
    }

    private function targetClassesQuery(): Builder
    {
        return SchoolClass::query()
            ->where('academic_year_id', (int) $this->targetYearId)
            ->where('status', 'active')
            ->whereHas('academicYear', fn ($years) => $years->where('school_id', $this->schoolId()));
    }

    private function baseEnrollmentsQuery(): Builder
    {
        if (! $this->sourceClassesQuery()->whereKey((int) $this->sourceClassId)->exists()) {
            return ClassEnrollment::query()->whereRaw('1 = 0');
        }

        return ClassEnrollment::query()
            ->where('school_class_id', (int) $this->sourceClassId)
            ->where('status', ClassEnrollment::STATUS_ACTIVE)
            ->whereHas('student', fn ($students) => $students
                ->where('school_id', $this->schoolId())
                ->whereIn('status', ['active', 'suspended']));
    }

    private function filteredEnrollmentsQuery(): Builder
    {
        $search = trim($this->search);
        $query = $this->baseEnrollmentsQuery()
            ->with(['student', 'schoolClass.stream'])
            ->when($search !== '', fn ($enrollments) => $enrollments->whereHas('student', function ($students) use ($search): void {
                $students->where('student_id', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            }));

        $targetYearIsScoped = $this->targetYearId !== '' && AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->targetYearId)
            ->exists();

        if ($targetYearIsScoped) {
            $placement = function ($enrollments, array $statuses): void {
                $enrollments->whereIn('status', $statuses)
                    ->whereHas('schoolClass', fn ($classes) => $classes->where('academic_year_id', (int) $this->targetYearId));
            };

            match ($this->placementFilter) {
                'planned' => $query->whereHas('student.enrollments', fn ($enrollments) => $placement($enrollments, [ClassEnrollment::STATUS_PENDING])),
                'conflict' => $query->whereHas('student.enrollments', fn ($enrollments) => $placement($enrollments, [ClassEnrollment::STATUS_ACTIVE, ClassEnrollment::STATUS_COMPLETED])),
                'unplanned' => $query->whereDoesntHave('student.enrollments', fn ($enrollments) => $enrollments
                    ->whereHas('schoolClass', fn ($classes) => $classes->where('academic_year_id', (int) $this->targetYearId))),
                default => null,
            };
        }

        return $query->orderBy('student_id');
    }

    /** @param array<int, string> $statuses */
    private function placementCount(array $statuses): int
    {
        if ($this->targetYearId === '' || ! AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->whereKey((int) $this->targetYearId)
            ->exists()) {
            return 0;
        }

        return $this->baseEnrollmentsQuery()
            ->whereHas('student.enrollments', fn ($enrollments) => $enrollments
                ->whereIn('status', $statuses)
                ->whereHas('schoolClass', fn ($classes) => $classes->where('academic_year_id', (int) $this->targetYearId)))
            ->count();
    }

    /** @return array<int, string> */
    private function destinationLabels(): array
    {
        return $this->targetClassesQuery()
            ->with('stream')
            ->get()
            ->mapWithKeys(fn (SchoolClass $class) => [
                (string) $class->id => $class->name.($class->stream ? ' · '.$class->stream->name : ''),
            ])
            ->merge([
                StudentPromotionService::OUTCOME_GRADUATE => 'Graduate learners',
                StudentPromotionService::OUTCOME_TRANSFER => 'Transfer out',
            ])
            ->all();
    }

    private function isValidDestination(string $decision): bool
    {
        return array_key_exists($decision, $this->destinationLabels());
    }

    /** @return array<int, int> */
    private function normalisedSelectedIds(): array
    {
        return collect($this->selectedStudentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalisedPerPage(): int
    {
        return in_array($this->perPage, [10, 15, 25, 50], true) ? $this->perPage : 15;
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before promoting students.');

        return $schoolId;
    }
}
