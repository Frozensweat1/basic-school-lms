<?php

namespace App\Livewire\LMS\Assessments;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentComponent;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SubjectResult;
use App\Models\Teacher;
use App\Models\Term;
use App\Services\Results\SubjectResultCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
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

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $classSubjectId = '';

    public string $termId = '';

    public string $componentId = '';

    public string $teacherId = '';

    public string $title = '';

    public string $maxScore = '100';

    public string $assessedAt = '';

    public string $status = 'draft';

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterTermId = '';

    public string $filterClassSubjectId = '';

    #[Url(as: 'component')]
    public string $filterComponentId = '';

    public string $filterStatus = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Assessment::class);

        if (auth()->user()->hasRole('teacher')) {
            abort_unless(auth()->user()->teacher, 403);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterAcademicYearId(): void
    {
        $this->filterTermId = '';
        $this->filterClassSubjectId = '';
        $this->filterComponentId = '';
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->filterComponentId = '';
        $this->resetPage();
    }

    public function updatedFilterClassSubjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterComponentId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedClassSubjectId(): void
    {
        if (blank($this->classSubjectId)) {
            $this->termId = '';
            $this->componentId = '';
            $this->teacherId = '';

            return;
        }

        $classSubject = $this->scopedClassSubjects()
            ->with('schoolClass')
            ->find((int) $this->classSubjectId);
        abort_unless($classSubject, 422, 'Choose a class subject belonging to this school.');

        $this->teacherId = (string) ($classSubject->teacher_id ?? '');
        $this->componentId = '';

        $termBelongsToClassYear = filled($this->termId)
            && Term::query()
                ->whereKey($this->termId)
                ->where('academic_year_id', $classSubject->schoolClass->academic_year_id)
                ->exists();

        if (! $termBelongsToClassYear) {
            $this->termId = (string) Term::query()
                ->where('academic_year_id', $classSubject->schoolClass->academic_year_id)
                ->where('is_active', true)
                ->value('id');
        }
    }

    public function updatedTermId(): void
    {
        if (blank($this->termId)) {
            $this->componentId = '';

            return;
        }

        $componentStillMatches = filled($this->componentId)
            && AssessmentComponent::query()
                ->whereKey($this->componentId)
                ->where('term_id', $this->termId)
                ->exists();

        if (! $componentStillMatches) {
            $this->componentId = '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search', 'filterAcademicYearId', 'filterTermId', 'filterClassSubjectId',
            'filterComponentId', 'filterStatus',
        ]);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Assessment::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(Assessment $assessment): void
    {
        $this->assertVisible($assessment);
        $this->authorize('update', $assessment);

        $this->editingId = $assessment->id;
        $this->classSubjectId = (string) $assessment->class_subject_id;
        $this->termId = (string) $assessment->term_id;
        $this->componentId = (string) ($assessment->assessment_component_id ?? '');
        $this->teacherId = (string) $assessment->teacher_id;
        $this->title = $assessment->title;
        $this->maxScore = (string) $assessment->max_score;
        $this->assessedAt = $assessment->assessed_at->format('Y-m-d');
        $this->status = $assessment->status;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(SubjectResultCalculator $calculator): void
    {
        $assessment = $this->editingId
            ? $this->scopedAssessments()->findOrFail($this->editingId)
            : null;

        $this->authorize($assessment ? 'update' : 'create', $assessment ?? Assessment::class);

        try {
            $data = $this->validate([
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'termId' => ['required', 'integer', Rule::exists('terms', 'id')],
                'componentId' => ['nullable', 'integer', Rule::exists('assessment_components', 'id')],
                'teacherId' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'maxScore' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
                'assessedAt' => ['required', 'date'],
                'status' => ['required', Rule::in(Assessment::STATUSES)],
            ]);

            $schoolId = $this->schoolId();
            $classSubject = $this->scopedClassSubjects()
                ->with('schoolClass.academicYear')
                ->findOrFail($data['classSubjectId']);
            $term = Term::query()
                ->whereKey($data['termId'])
                ->where('academic_year_id', $classSubject->schoolClass->academic_year_id)
                ->firstOrFail();

            $component = filled($data['componentId'])
                ? AssessmentComponent::query()->whereKey($data['componentId'])->where('term_id', $term->id)->firstOrFail()
                : null;

            $teacherId = $this->managingAsTeacher()
                ? auth()->user()->teacher?->id
                : (filled($data['teacherId']) ? (int) $data['teacherId'] : $classSubject->teacher_id);

            abort_unless($teacherId, 422, 'Assign a responsible teacher to this class subject before creating an assessment.');

            $teacher = Teacher::query()
                ->whereKey($teacherId)
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->firstOrFail();

            if ($this->managingAsTeacher()) {
                abort_unless((int) $classSubject->teacher_id === (int) $teacher->id, 403, 'You can only manage assessments for your assigned class subjects.');
            }

            $saved = Assessment::updateOrCreate(
                ['id' => $assessment?->id],
                [
                    'class_subject_id' => $classSubject->id,
                    'term_id' => $term->id,
                    'assessment_component_id' => $component?->id,
                    'teacher_id' => $teacher->id,
                    'title' => $data['title'],
                    'max_score' => $data['maxScore'],
                    'assessed_at' => $data['assessedAt'],
                    'status' => $data['status'],
                ],
            );

            $affectedScopes = [[$saved->class_subject_id, $saved->term_id]];
            if ($assessment?->status === 'published' || $saved->status === 'published') {
                $affectedScopes[] = [$assessment?->class_subject_id, $assessment?->term_id];
            }

            foreach (collect($affectedScopes)->filter(fn (array $scope) => $scope[0] && $scope[1])->unique() as [$classSubjectId, $termId]) {
                $this->refreshResultsFor((int) $classSubjectId, (int) $termId, $calculator);
            }

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($assessment ? 'Assessment updated' : 'Assessment created')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the assessment form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save assessment')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Assessment $assessment): void
    {
        $this->assertVisible($assessment);
        $this->authorize('delete', $assessment);

        $this->deletingId = $assessment->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $assessment = $this->deletingId
            ? $this->scopedAssessments()->with('classSubject.schoolClass')->findOrFail($this->deletingId)
            : null;

        abort_unless($assessment, 404);
        $this->authorize('delete', $assessment);

        try {
            $classSubject = $assessment->classSubject;
            $termId = $assessment->term_id;
            $wasPublished = $assessment->status === 'published';
            $assessment->delete();

            if ($wasPublished) {
                $this->refreshResultsFor((int) $classSubject->id, (int) $termId, app(SubjectResultCalculator::class));
            }

            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Assessment deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete assessment')
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
        $schoolId = $this->schoolId();
        $search = trim($this->search);
        $baseQuery = $this->scopedAssessments();

        $assessments = (clone $baseQuery)
            ->with([
                'classSubject.subject',
                'classSubject.schoolClass' => fn ($query) => $query->withCount([
                    'enrollments' => fn ($enrollments) => $enrollments->where('status', 'active'),
                ]),
                'term.academicYear',
                'component',
                'teacher',
            ])
            ->withCount([
                'scores as entered_scores_count' => fn ($query) => $query->whereNotNull('score'),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $items) use ($search): void {
                    $items->where('title', 'like', "%{$search}%")
                        ->orWhereHas('classSubject.schoolClass', fn (Builder $classes) => $classes->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('classSubject.subject', fn (Builder $subjects) => $subjects->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('component', fn (Builder $components) => $components->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('teacher', fn (Builder $teachers) => $teachers->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterAcademicYearId), fn (Builder $query) => $query->whereHas('classSubject.schoolClass', fn (Builder $classes) => $classes->where('academic_year_id', $this->filterAcademicYearId)))
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', $this->filterTermId))
            ->when(filled($this->filterClassSubjectId), fn (Builder $query) => $query->where('class_subject_id', $this->filterClassSubjectId))
            ->when($this->filterComponentId === 'unassigned', fn (Builder $query) => $query->whereNull('assessment_component_id'))
            ->when(filled($this->filterComponentId) && $this->filterComponentId !== 'unassigned', fn (Builder $query) => $query->where('assessment_component_id', $this->filterComponentId))
            ->when(filled($this->filterStatus), fn (Builder $query) => $query->where('status', $this->filterStatus))
            ->orderByDesc('assessed_at')
            ->orderBy('title')
            ->paginate(15);

        $years = AcademicYear::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('starts_at')
            ->get();
        $terms = Term::query()
            ->whereIn('academic_year_id', $years->pluck('id'))
            ->orderBy('academic_year_id')
            ->orderBy('sequence')
            ->get();
        $classSubjects = $this->scopedClassSubjects()
            ->with(['schoolClass.academicYear', 'subject', 'teacher'])
            ->orderBy('school_class_id')
            ->orderBy('subject_id')
            ->get();
        $components = AssessmentComponent::query()
            ->with('term')
            ->whereIn('term_id', $terms->pluck('id'))
            ->orderBy('term_id')
            ->orderBy('sequence')
            ->get();

        $formClassSubject = $this->classSubjectId
            ? $classSubjects->firstWhere('id', (int) $this->classSubjectId)
            : null;
        $formAcademicYearId = $formClassSubject?->schoolClass?->academic_year_id;

        return view('livewire.lms.assessments.index', [
            'assessments' => $assessments,
            'years' => $years,
            'terms' => $terms,
            'classSubjects' => $classSubjects,
            'components' => $components,
            'teachers' => $this->currentTeacher()
                ? collect([$this->currentTeacher()])
                : Teacher::query()->where('school_id', $schoolId)->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'formAcademicYearId' => $formAcademicYearId,
            'scoreRouteName' => $this->managingAsTeacher()
                ? 'lms.assessments.teacher.scores.index'
                : 'lms.assessments.admin.scores.index',
            'publishedCount' => (clone $baseQuery)->where('status', 'published')->count(),
            'draftCount' => (clone $baseQuery)->where('status', 'draft')->count(),
            'unscoredCount' => (clone $baseQuery)
                ->whereDoesntHave('scores', fn ($query) => $query->whereNotNull('score'))
                ->count(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'classSubjectId', 'termId', 'componentId', 'teacherId',
            'title', 'maxScore', 'assessedAt', 'status',
        ]);
        $this->maxScore = '100';
        $this->status = 'draft';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing assessments.');

        return (int) $schoolId;
    }

    private function currentTeacher(): ?Teacher
    {
        return $this->managingAsTeacher() ? auth()->user()->teacher : null;
    }

    private function managingAsTeacher(): bool
    {
        return auth()->user()->hasRole('teacher')
            && ! auth()->user()->hasAnyRole(['super_admin', 'school_admin']);
    }

    private function scopedClassSubjects(): Builder
    {
        $teacher = $this->currentTeacher();

        return ClassSubject::query()
            ->whereHas('schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()))
            ->when($teacher, fn (Builder $query) => $query->where('teacher_id', $teacher->id));
    }

    private function scopedAssessments(): Builder
    {
        $teacher = $this->currentTeacher();

        return Assessment::query()
            ->whereHas('classSubject.schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()))
            ->when($teacher, fn (Builder $query) => $query->where('teacher_id', $teacher->id));
    }

    private function assertVisible(Assessment $assessment): void
    {
        abort_unless($this->scopedAssessments()->whereKey($assessment->id)->exists(), 404);
    }

    private function activeStudentIds(ClassSubject $classSubject): array
    {
        return $classSubject->schoolClass
            ->enrollments()
            ->where('status', 'active')
            ->pluck('student_id')
            ->all();
    }

    private function refreshResultsFor(int $classSubjectId, int $termId, SubjectResultCalculator $calculator): void
    {
        $hasPublishedAssessments = Assessment::query()
            ->where('class_subject_id', $classSubjectId)
            ->where('term_id', $termId)
            ->where('status', 'published')
            ->exists();

        if (! $hasPublishedAssessments) {
            SubjectResult::query()
                ->where('class_subject_id', $classSubjectId)
                ->where('term_id', $termId)
                ->update(['status' => 'draft']);

            return;
        }

        $classSubject = ClassSubject::query()->with('schoolClass')->findOrFail($classSubjectId);
        foreach ($this->activeStudentIds($classSubject) as $studentId) {
            $calculator->calculate((int) $studentId, $classSubjectId, $termId);
        }
    }
}
