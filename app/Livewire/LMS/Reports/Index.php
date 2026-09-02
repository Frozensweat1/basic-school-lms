<?php

namespace App\Livewire\LMS\Reports;

use App\Jobs\GenerateReportCardJob;
use App\Models\ReportCard;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectResult;
use App\Models\Term;
use App\Services\Reports\ReportCardGenerator;
use App\Support\LmsNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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
    use AuthorizesRequests;
    use WithPagination;

    public string $search = '';

    public string $filterTermId = '';

    public string $filterClassId = '';

    public string $filterStatus = '';

    public string $generationTermId = '';

    public string $generationClassId = '';

    public string $generationStudentId = '';

    public bool $showPublishModal = false;

    public string $publishMode = 'single';

    public ?int $publishingId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', ReportCard::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->filterClassId = '';
        $this->resetPage();
    }

    public function updatedFilterClassId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedGenerationTermId(): void
    {
        $this->reset(['generationClassId', 'generationStudentId']);
        $this->resetErrorBag();
    }

    public function updatedGenerationClassId(): void
    {
        $this->generationStudentId = '';
        $this->resetErrorBag();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterTermId', 'filterClassId', 'filterStatus']);
        $this->resetPage();
    }

    public function generateSingle(ReportCardGenerator $generator): void
    {
        $this->authorize('create', ReportCard::class);

        try {
            [$term, $class] = $this->validatedGenerationScope(true);
            $student = $this->scopedStudents()
                ->whereKey((int) $this->generationStudentId)
                ->whereHas('enrollments', fn (Builder $query) => $query
                    ->where('school_class_id', $class->id)
                    ->enrolledDuring($term->starts_at, $term->ends_at))
                ->firstOrFail();

            $report = $generator->generate($student, $term, $class->id);
            $this->resetPage();
            LivewireAlert::title('Report card generated')
                ->text($student->first_name.' '.$student->last_name.' is ready for review.')
                ->success()->asToast()->position('top-end')->show();

            $this->redirectRoute('lms.reports.show', $report, navigate: true);
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the generation scope')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to generate report card')->error()->asToast()->position('top-end')->show();
        }
    }

    public function generateBulk(): void
    {
        $this->authorize('create', ReportCard::class);

        try {
            [$term, $class] = $this->validatedGenerationScope();
            $enrollments = $class->enrollments()
                ->with('student')
                ->enrolledDuring($term->starts_at, $term->ends_at)
                ->get();

            if ($enrollments->isEmpty()) {
                throw ValidationException::withMessages(['generationClassId' => 'The selected class had no enrolled students during this term.']);
            }

            foreach ($enrollments as $enrollment) {
                GenerateReportCardJob::dispatch($enrollment->student, $term, $class->id);
            }

            LivewireAlert::title('Class report cards queued')
                ->text($enrollments->count().' report '.str('card')->plural($enrollments->count()).' will be generated in the reports queue.')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the generation scope')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to queue class reports')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmPublish(int $reportCardId): void
    {
        $report = $this->scopedReportCards()->findOrFail($reportCardId);
        $this->authorize('update', $report);
        $this->publishingId = $report->id;
        $this->publishMode = 'single';
        $this->showPublishModal = true;
        $this->resetErrorBag('publish');
    }

    public function confirmPublishBulk(): void
    {
        $this->authorize('create', ReportCard::class);
        $this->validatedGenerationScope();
        $this->publishingId = null;
        $this->publishMode = 'bulk';
        $this->showPublishModal = true;
        $this->resetErrorBag('publish');
    }

    public function publishConfirmed(): void
    {
        if ($this->publishMode === 'bulk') {
            $this->publishBulk();

            return;
        }

        abort_unless($this->publishingId, 404);
        $this->publish($this->publishingId);
    }

    public function publish(int $reportCardId): void
    {
        $report = $this->scopedReportCards()
            ->with(['student.user', 'student.parents.user', 'term'])
            ->findOrFail($reportCardId);
        $this->authorize('update', $report);

        try {
            if (! $this->hasPublishedResults($report)) {
                throw ValidationException::withMessages(['publish' => 'Publish at least one subject result before publishing this report card.']);
            }

            $wasPublished = $report->isPublished();
            $report->publish();
            if (! $wasPublished) {
                $this->notifyPublished($report);
            }

            $this->closePublishModal();
            LivewireAlert::title('Report card published')
                ->text('The student and linked parents can now review it.')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Report card is not ready')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to publish report card')->error()->asToast()->position('top-end')->show();
        }
    }

    public function publishBulk(): void
    {
        $this->authorize('create', ReportCard::class);

        try {
            [$term, $class] = $this->validatedGenerationScope();
            $reports = $this->scopedReportCards()
                ->with(['student.user', 'student.parents.user', 'term'])
                ->where('term_id', $term->id)
                ->where('school_class_id', $class->id)
                ->where('status', 'draft')
                ->get();

            if ($reports->isEmpty()) {
                throw ValidationException::withMessages(['publish' => 'There are no draft report cards in this class scope.']);
            }

            [$ready, $notReady] = $reports->partition(fn (ReportCard $report) => $this->hasPublishedResults($report));
            if ($ready->isEmpty()) {
                throw ValidationException::withMessages(['publish' => 'Publish subject results before publishing these report cards.']);
            }

            DB::transaction(fn () => $ready->each->publish());
            $ready->each(fn (ReportCard $report) => $this->notifyPublished($report));
            $this->closePublishModal();

            $message = $ready->count().' report '.str('card')->plural($ready->count()).' published.';
            if ($notReady->isNotEmpty()) {
                $message .= ' '.$notReady->count().' skipped because they have no published results.';
            }
            LivewireAlert::title('Class reports published')->text($message)->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Class reports are not ready')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to publish class reports')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closePublishModal(): void
    {
        $this->showPublishModal = false;
        $this->publishingId = null;
        $this->publishMode = 'single';
        $this->resetErrorBag('publish');
    }

    public function render()
    {
        $terms = $this->scopedTerms()->with('academicYear')->orderByDesc('starts_at')->get();
        $classes = $this->scopedClasses()->with('academicYear')->orderBy('name')->get();
        $generationTerm = filled($this->generationTermId)
            ? $terms->firstWhere('id', (int) $this->generationTermId)
            : null;
        $generationClasses = $generationTerm
            ? $classes->where('academic_year_id', $generationTerm->academic_year_id)->values()
            : collect();
        $generationStudents = filled($this->generationClassId)
            ? $this->scopedStudents()
                ->whereHas('enrollments', fn (Builder $query) => $query
                    ->where('school_class_id', (int) $this->generationClassId)
                    ->when($generationTerm, fn (Builder $enrollments) => $enrollments
                        ->enrolledDuring($generationTerm->starts_at, $generationTerm->ends_at)))
                ->orderBy('last_name')->orderBy('first_name')->get()
            : collect();

        $search = trim($this->search);
        $filtered = $this->scopedReportCards()
            ->with(['student', 'term.academicYear', 'schoolClass'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $items) use ($search): void {
                    $items->whereHas('student', fn (Builder $students) => $students
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%"))
                        ->orWhereHas('schoolClass', fn (Builder $classes) => $classes->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('term', fn (Builder $terms) => $terms->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', (int) $this->filterTermId))
            ->when(filled($this->filterClassId), fn (Builder $query) => $query->where('school_class_id', (int) $this->filterClassId))
            ->when(filled($this->filterStatus), fn (Builder $query) => $query->where('status', $this->filterStatus));

        $reportCards = (clone $filtered)->latest('generated_at')->paginate(15);
        $pageReports = collect($reportCards->items());
        $publishedResults = SubjectResult::query()
            ->where('status', 'published')
            ->whereIn('student_id', $pageReports->pluck('student_id'))
            ->whereIn('term_id', $pageReports->pluck('term_id'))
            ->with('classSubject:id,school_class_id')
            ->get();
        $resultCounts = $pageReports->mapWithKeys(function (ReportCard $report) use ($publishedResults): array {
            $count = $publishedResults->where('student_id', $report->student_id)
                ->where('term_id', $report->term_id)
                ->filter(fn (SubjectResult $result) => $result->classSubject?->school_class_id === $report->school_class_id)
                ->count();

            return [$report->id => $count];
        });

        $matchingStudentIds = (clone $filtered)->pluck('student_id');
        $scoreQuery = SubjectResult::query()
            ->where('status', 'published')
            ->whereIn('student_id', $matchingStudentIds)
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', (int) $this->filterTermId))
            ->when(filled($this->filterClassId), fn (Builder $query) => $query->whereHas('classSubject', fn (Builder $subjects) => $subjects->where('school_class_id', (int) $this->filterClassId)));

        return view('livewire.lms.reports.index', [
            'terms' => $terms,
            'classes' => $classes,
            'generationClasses' => $generationClasses,
            'generationStudents' => $generationStudents,
            'reportCards' => $reportCards,
            'resultCounts' => $resultCounts,
            'metrics' => [
                'reports' => (clone $filtered)->count(),
                'published' => (clone $filtered)->where('status', 'published')->count(),
                'draft' => (clone $filtered)->where('status', 'draft')->count(),
                'attendance' => round((float) ((clone $filtered)->avg('attendance_percentage') ?? 0), 1),
                'averageScore' => round((float) ((clone $scoreQuery)->avg('total_score') ?? 0), 1),
                'atRisk' => (clone $scoreQuery)->where('total_score', '<', 50)->distinct()->count('student_id'),
            ],
        ]);
    }

    private function validatedGenerationScope(bool $requireStudent = false): array
    {
        $rules = [
            'generationTermId' => ['required', 'integer', Rule::exists('terms', 'id')],
            'generationClassId' => ['required', 'integer', Rule::exists('school_classes', 'id')],
        ];
        if ($requireStudent) {
            $rules['generationStudentId'] = ['required', 'integer', Rule::exists('students', 'id')];
        }
        $this->validate($rules);

        $term = $this->scopedTerms()->findOrFail((int) $this->generationTermId);
        $class = $this->scopedClasses()->findOrFail((int) $this->generationClassId);
        if ($term->academic_year_id !== $class->academic_year_id) {
            throw ValidationException::withMessages(['generationClassId' => 'Choose a class from the selected term academic year.']);
        }

        return [$term, $class];
    }

    private function hasPublishedResults(ReportCard $report): bool
    {
        return SubjectResult::query()
            ->where('student_id', $report->student_id)
            ->where('term_id', $report->term_id)
            ->where('status', 'published')
            ->whereHas('classSubject', fn (Builder $query) => $query->where('school_class_id', $report->school_class_id))
            ->exists();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing reports.');

        return (int) $schoolId;
    }

    private function scopedTerms(): Builder
    {
        return Term::query()->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $this->schoolId()));
    }

    private function scopedClasses(): Builder
    {
        return SchoolClass::query()->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $this->schoolId()));
    }

    private function scopedStudents(): Builder
    {
        return Student::query()->where('school_id', $this->schoolId());
    }

    private function scopedReportCards(): Builder
    {
        return ReportCard::query()->whereHas('student', fn (Builder $query) => $query->where('school_id', $this->schoolId()));
    }

    private function notifyPublished(ReportCard $reportCard): void
    {
        $recipients = collect([$reportCard->student?->user])
            ->merge($reportCard->student?->parents?->pluck('user') ?? collect())
            ->filter()->unique('id');
        LmsNotifier::send(
            $recipients,
            'Report card published',
            'Your report card for '.$reportCard->term?->name.' is now available.',
            route('lms.reports.show', $reportCard),
            'report',
        );
    }
}
