<?php

namespace App\Livewire\LMS\Examinations;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Examination;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Term;
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

    public string $academicYearId = '';

    public string $termId = '';

    public string $classSubjectId = '';

    public string $teacherId = '';

    public string $title = '';

    public string $description = '';

    public string $examDate = '';

    public string $durationMinutes = '';

    public string $maxScore = '100';

    public string $status = 'draft';

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterTermId = '';

    public string $filterClassSubjectId = '';

    public string $filterStatus = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Examination::class);

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
        $this->resetPage();
    }

    public function updatedFilterTermId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClassSubjectId(): void
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
        $this->classSubjectId = '';
        $this->teacherId = '';
    }

    public function updatedClassSubjectId(): void
    {
        if (blank($this->classSubjectId)) {
            $this->teacherId = '';

            return;
        }

        $classSubject = $this->formClassSubjects()->find((int) $this->classSubjectId);
        abort_unless($classSubject, 422, 'Choose a class subject for the selected academic year.');

        $this->teacherId = (string) ($classSubject->teacher_id ?? '');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAcademicYearId', 'filterTermId', 'filterClassSubjectId', 'filterStatus']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Examination::class);
        $this->resetForm();
        $this->academicYearId = (string) AcademicYear::query()
            ->where('school_id', $this->schoolId())
            ->where('is_active', true)
            ->value('id');
        $this->showFormModal = true;
    }

    public function edit(Examination $examination): void
    {
        $this->assertVisible($examination);
        $this->authorize('update', $examination);

        $this->editingId = $examination->id;
        $this->academicYearId = (string) $examination->academic_year_id;
        $this->termId = (string) $examination->term_id;
        $this->classSubjectId = (string) $examination->class_subject_id;
        $this->teacherId = (string) $examination->teacher_id;
        $this->title = $examination->title;
        $this->description = $examination->description ?? '';
        $this->examDate = $examination->exam_date->format('Y-m-d');
        $this->durationMinutes = (string) ($examination->duration_minutes ?? '');
        $this->maxScore = (string) $examination->max_score;
        $this->status = $examination->status === 'published' ? 'scheduled' : $examination->status;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $examination = $this->editingId
            ? $this->scopedExaminations()->findOrFail($this->editingId)
            : null;

        $this->authorize($examination ? 'update' : 'create', $examination ?? Examination::class);

        try {
            $data = $this->validate([
                'academicYearId' => ['required', 'integer', Rule::exists('academic_years', 'id')],
                'termId' => ['required', 'integer', Rule::exists('terms', 'id')],
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'teacherId' => ['required', 'integer', Rule::exists('teachers', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'examDate' => ['required', 'date'],
                'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:600'],
                'maxScore' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
                'status' => ['required', Rule::in(Examination::MANAGEABLE_STATUSES)],
            ]);

            $schoolId = $this->schoolId();
            $academicYear = AcademicYear::query()
                ->whereKey($data['academicYearId'])
                ->where('school_id', $schoolId)
                ->firstOrFail();

            $term = Term::query()
                ->whereKey($data['termId'])
                ->where('academic_year_id', $academicYear->id)
                ->firstOrFail();

            $classSubject = $this->scopedClassSubjects()
                ->where('school_classes.academic_year_id', $academicYear->id)
                ->findOrFail($data['classSubjectId']);

            $teacher = Teacher::query()
                ->whereKey($data['teacherId'])
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->firstOrFail();

            if (auth()->user()->hasRole('teacher')) {
                abort_unless((int) $classSubject->teacher_id === (int) $teacher->id, 403, 'You can only manage examinations for your assigned class subjects.');
            }

            Examination::updateOrCreate(
                ['id' => $examination?->id],
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYear->id,
                    'term_id' => $term->id,
                    'class_subject_id' => $classSubject->id,
                    'teacher_id' => $teacher->id,
                    'title' => $data['title'],
                    'description' => filled($data['description']) ? $data['description'] : null,
                    'exam_date' => $data['examDate'],
                    'duration_minutes' => filled($data['durationMinutes']) ? $data['durationMinutes'] : null,
                    'max_score' => $data['maxScore'],
                    'status' => $data['status'],
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($examination ? 'Examination updated' : 'Examination scheduled')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the examination form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save examination')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(Examination $examination): void
    {
        $this->assertVisible($examination);
        $this->authorize('delete', $examination);

        $this->deletingId = $examination->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $examination = $this->deletingId
            ? $this->scopedExaminations()->findOrFail($this->deletingId)
            : null;

        abort_unless($examination, 404);
        $this->authorize('delete', $examination);

        try {
            $examination->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Examination deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete examination')
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
        $teacher = $this->currentTeacher();
        $search = trim($this->search);

        $baseQuery = $this->scopedExaminations();
        $examinations = (clone $baseQuery)
            ->with(['academicYear', 'term', 'classSubject.schoolClass', 'classSubject.subject', 'teacher'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $exams) use ($search): void {
                    $exams->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('classSubject.schoolClass', fn (Builder $classes) => $classes->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('classSubject.subject', fn (Builder $subjects) => $subjects->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('teacher', fn (Builder $teachers) => $teachers->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterAcademicYearId), fn (Builder $query) => $query->where('academic_year_id', $this->filterAcademicYearId))
            ->when(filled($this->filterTermId), fn (Builder $query) => $query->where('term_id', $this->filterTermId))
            ->when(filled($this->filterClassSubjectId), fn (Builder $query) => $query->where('class_subject_id', $this->filterClassSubjectId))
            ->when(filled($this->filterStatus), fn (Builder $query) => $query->where('status', $this->filterStatus))
            ->orderBy('exam_date')
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
            ->with(['schoolClass', 'subject'])
            ->orderBy('school_class_id')
            ->orderBy('subject_id')
            ->get();

        $formClassSubjects = $this->formClassSubjects()
            ->with(['schoolClass', 'subject', 'teacher'])
            ->orderBy('school_class_id')
            ->orderBy('subject_id')
            ->get();

        return view('livewire.lms.examinations.index', [
            'examinations' => $examinations,
            'years' => $years,
            'terms' => $terms,
            'classSubjects' => $classSubjects,
            'formClassSubjects' => $formClassSubjects,
            'teachers' => $teacher
                ? collect([$teacher])
                : Teacher::query()->where('school_id', $schoolId)->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get(),
            'scheduledCount' => (clone $baseQuery)->whereIn('status', Examination::LEARNER_VISIBLE_STATUSES)->count(),
            'draftCount' => (clone $baseQuery)->where('status', 'draft')->count(),
            'upcomingCount' => (clone $baseQuery)->whereIn('status', Examination::LEARNER_VISIBLE_STATUSES)->whereDate('exam_date', '>=', today())->count(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'academicYearId', 'termId', 'classSubjectId', 'teacherId',
            'title', 'description', 'examDate', 'durationMinutes', 'maxScore', 'status',
        ]);
        $this->maxScore = '100';
        $this->status = 'draft';
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing examinations.');

        return (int) $schoolId;
    }

    private function currentTeacher(): ?Teacher
    {
        return auth()->user()->hasRole('teacher') ? auth()->user()->teacher : null;
    }

    private function scopedExaminations(): Builder
    {
        $teacher = $this->currentTeacher();

        return Examination::query()
            ->where('school_id', $this->schoolId())
            ->when($teacher, fn (Builder $query) => $query->where('teacher_id', $teacher->id));
    }

    private function scopedClassSubjects(): Builder
    {
        $teacher = $this->currentTeacher();

        return ClassSubject::query()
            ->join('school_classes', 'school_classes.id', '=', 'class_subjects.school_class_id')
            ->whereHas('schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $this->schoolId()))
            ->when($teacher, fn (Builder $query) => $query->where('class_subjects.teacher_id', $teacher->id))
            ->select('class_subjects.*');
    }

    private function formClassSubjects(): Builder
    {
        return $this->scopedClassSubjects()
            ->when(filled($this->academicYearId), fn (Builder $query) => $query->where('school_classes.academic_year_id', $this->academicYearId));
    }

    private function assertVisible(Examination $examination): void
    {
        abort_unless($this->scopedExaminations()->whereKey($examination->id)->exists(), 404);
    }
}
