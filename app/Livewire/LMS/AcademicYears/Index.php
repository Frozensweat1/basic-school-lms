<?php

namespace App\Livewire\LMS\AcademicYears;

use App\Models\AcademicYear;
use App\Models\School;
use App\Services\AcademicYearActivationService;
use App\Services\AcademicYearRolloverService;
use Illuminate\Support\Facades\DB;
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
    use WithPagination;

    public bool $showForm = false;

    public bool $showDeleteConfirmation = false;

    public bool $showRolloverModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $rolloverSourceId = null;

    public string $search = '';

    public string $name = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public bool $isActive = false;

    public string $rolloverName = '';

    public string $rolloverStartsAt = '';

    public string $rolloverEndsAt = '';

    public bool $rolloverCopyTerms = true;

    public bool $rolloverCopySubjects = true;

    public bool $rolloverCopyTeachers = false;

    public bool $rolloverPrepareStudents = false;

    public bool $rolloverActivate = false;

    public bool $rolloverActivateFirstTerm = false;

    public array $rolloverPromotions = [];

    public function mount(): void
    {
        $this->authorize('viewAny', AcademicYear::class);
    }

    public function create(): void
    {
        $this->authorize('create', AcademicYear::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(AcademicYear $academicYear): void
    {
        $this->ensureSchoolRecord($academicYear);
        $this->authorize('update', $academicYear);
        $this->editingId = $academicYear->id;
        $this->name = $academicYear->name;
        $this->startsAt = $academicYear->starts_at->toDateString();
        $this->endsAt = $academicYear->ends_at->toDateString();
        $this->isActive = $academicYear->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(AcademicYearActivationService $activationService): void
    {
        $year = $this->editingId ? AcademicYear::findOrFail($this->editingId) : null;
        if ($year) {
            $this->ensureSchoolRecord($year);
        }
        $this->authorize($year ? 'update' : 'create', $year ?? AcademicYear::class);

        $schoolId = $year?->school_id ?? School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before creating an academic year.');

        try {
            $data = $this->validate([
                'name' => ['required', 'string', 'max:100', Rule::unique('academic_years', 'name')->where('school_id', $schoolId)->ignore($year?->id)],
                'startsAt' => ['required', 'date', 'before:endsAt'],
                'endsAt' => ['required', 'date', 'after:startsAt'],
                'isActive' => ['boolean'],
            ]);

            DB::transaction(function () use ($year, $schoolId, $data, $activationService): void {
                $savedYear = AcademicYear::updateOrCreate(
                    ['id' => $year?->id],
                    [
                        'school_id' => $schoolId,
                        'name' => $data['name'],
                        'starts_at' => $data['startsAt'],
                        'ends_at' => $data['endsAt'],
                        'is_active' => false,
                    ],
                );

                if ($data['isActive']) {
                    $activationService->activate($savedYear);
                }
            });

            $this->showForm = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($year ? 'Academic year updated' : 'Academic year created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->text('Correct the highlighted fields and try again.')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save academic year')->text('Please try again.')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(AcademicYear $academicYear): void
    {
        $this->ensureSchoolRecord($academicYear);
        $this->authorize('delete', $academicYear);
        $this->deletingId = $academicYear->id;
        $this->showDeleteConfirmation = true;
    }

    public function delete(): void
    {
        $year = AcademicYear::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($year);
        $this->authorize('delete', $year);

        if ($year->is_active || $year->terms()->exists() || $year->classes()->exists()) {
            $this->addError('delete', 'Only an inactive academic year without terms or classes can be deleted.');
            LivewireAlert::title('Academic year cannot be deleted')->text('Deactivate it and remove its terms and classes first.')->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $year->delete();
            $this->showDeleteConfirmation = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Academic year deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete academic year')->text('Please try again.')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
        $this->deletingId = null;
        $this->resetErrorBag();
    }

    public function prepareRollover(AcademicYear $academicYear): void
    {
        $this->ensureSchoolRecord($academicYear);
        $this->authorize('create', AcademicYear::class);
        $this->authorize('update', $academicYear);

        $this->resetRolloverForm();
        $this->rolloverSourceId = $academicYear->id;
        $this->rolloverName = $this->suggestNextYearName($academicYear);
        $this->rolloverStartsAt = $academicYear->starts_at->copy()->addYearNoOverflow()->toDateString();
        $this->rolloverEndsAt = $academicYear->ends_at->copy()->addYearNoOverflow()->toDateString();
        $this->rolloverPromotions = $academicYear->classes()
            ->where('status', 'active')
            ->pluck('id')
            ->mapWithKeys(fn (int $classId) => [$classId => ''])
            ->all();
        $this->showForm = false;
        $this->showDeleteConfirmation = false;
        $this->showRolloverModal = true;
    }

    public function runRollover(AcademicYearRolloverService $rolloverService): void
    {
        $source = $this->rolloverSourceId
            ? AcademicYear::query()
                ->where('school_id', $this->schoolId())
                ->findOrFail($this->rolloverSourceId)
            : null;

        abort_unless($source, 404);
        $this->authorize('create', AcademicYear::class);
        $this->authorize('update', $source);

        try {
            $data = $this->validate([
                'rolloverName' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('academic_years', 'name')->where('school_id', $source->school_id),
                ],
                'rolloverStartsAt' => ['required', 'date', 'before:rolloverEndsAt'],
                'rolloverEndsAt' => ['required', 'date', 'after:rolloverStartsAt'],
                'rolloverCopyTerms' => ['boolean'],
                'rolloverCopySubjects' => ['boolean'],
                'rolloverCopyTeachers' => ['boolean'],
                'rolloverPrepareStudents' => ['boolean'],
                'rolloverActivate' => ['boolean'],
                'rolloverActivateFirstTerm' => ['boolean'],
                'rolloverPromotions' => ['array'],
                'rolloverPromotions.*' => ['nullable', 'string', 'max:30'],
            ]);

            if ($data['rolloverActivateFirstTerm'] && (! $data['rolloverActivate'] || ! $data['rolloverCopyTerms'])) {
                throw ValidationException::withMessages([
                    'rolloverActivateFirstTerm' => 'Copy terms and activate the new year before activating its first term.',
                ]);
            }

            $result = $rolloverService->rollover($source, [
                'name' => $data['rolloverName'],
                'starts_at' => $data['rolloverStartsAt'],
                'ends_at' => $data['rolloverEndsAt'],
                'copy_terms' => $data['rolloverCopyTerms'],
                'copy_subjects' => $data['rolloverCopySubjects'],
                'copy_teachers' => $data['rolloverCopyTeachers'],
                'prepare_students' => $data['rolloverPrepareStudents'],
                'activate' => $data['rolloverActivate'],
                'activate_first_term' => $data['rolloverActivateFirstTerm'],
                'promotions' => $data['rolloverPromotions'],
            ]);

            $this->showRolloverModal = false;
            $this->resetRolloverForm();
            $this->resetPage();

            LivewireAlert::title('Academic year prepared')
                ->text("{$result['terms']} terms, {$result['classes']} classes, {$result['subjects']} subject allocations, and {$result['students']} student placements were prepared.")
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the rollover plan')
                ->text('Correct the highlighted items and try again. No changes were made.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            $this->addError('rollover', 'The new academic year could not be prepared. No changes were made.');
            LivewireAlert::title('Unable to prepare academic year')
                ->text('No changes were made. Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function closeRollover(): void
    {
        $this->showRolloverModal = false;
        $this->resetRolloverForm();
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'name', 'startsAt', 'endsAt', 'isActive']);
        $this->resetValidation();
    }

    private function resetRolloverForm(): void
    {
        $this->reset([
            'rolloverSourceId',
            'rolloverName',
            'rolloverStartsAt',
            'rolloverEndsAt',
            'rolloverCopyTerms',
            'rolloverCopySubjects',
            'rolloverCopyTeachers',
            'rolloverPrepareStudents',
            'rolloverActivate',
            'rolloverActivateFirstTerm',
            'rolloverPromotions',
        ]);
        $this->resetValidation();
    }

    private function suggestNextYearName(AcademicYear $academicYear): string
    {
        $matchCount = 0;
        $suggestion = preg_replace_callback(
            '/\b\d{4}\b/',
            function (array $matches) use (&$matchCount): string {
                $matchCount++;

                return (string) ((int) $matches[0] + 1);
            },
            $academicYear->name,
        );

        return $matchCount > 0
            ? (string) $suggestion
            : $academicYear->starts_at->copy()->addYearNoOverflow()->format('Y')
                .'/'.$academicYear->ends_at->copy()->addYearNoOverflow()->format('Y');
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolRecord(AcademicYear $academicYear): void
    {
        abort_unless((int) $academicYear->school_id === $this->schoolId(), 404);
    }

    public function render()
    {
        $search = trim($this->search);
        $rolloverCandidate = AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->orderByDesc('is_active')
            ->orderByDesc('starts_at')
            ->first();
        $rolloverSource = $this->showRolloverModal && $this->rolloverSourceId
            ? AcademicYear::query()
                ->where('school_id', $this->schoolId())
                ->with([
                    'terms.assessmentComponents',
                    'classes' => fn ($query) => $query
                        ->where('status', 'active')
                        ->with('stream')
                        ->withCount([
                            'classSubjects',
                            'enrollments as active_enrollments_count' => fn ($enrollments) => $enrollments->where('status', 'active'),
                        ])
                        ->orderBy('name')
                        ->orderBy('stream_id'),
                ])
                ->find($this->rolloverSourceId)
            : null;

        return view('livewire.lms.academic-years.index', [
            'years' => AcademicYear::query()
                ->where('school_id', $this->schoolId())
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->withCount(['terms', 'classes'])
                ->orderByDesc('starts_at')
                ->paginate(15),
            'rolloverSource' => $rolloverSource,
            'rolloverCandidate' => $rolloverCandidate,
        ]);
    }
}
