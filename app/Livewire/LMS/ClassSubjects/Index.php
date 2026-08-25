<?php

namespace App\Livewire\LMS\ClassSubjects;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
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

    public string $schoolClassId = '';

    public string $subjectId = '';

    public string $teacherId = '';

    public string $search = '';

    public string $filterAcademicYearId = '';

    public string $filterClassId = '';

    public string $filterSubjectId = '';

    public string $filterTeacherId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ClassSubject::class);

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
        $this->filterClassId = '';
        $this->resetPage();
    }

    public function updatedFilterClassId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterSubjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTeacherId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAcademicYearId', 'filterClassId', 'filterSubjectId', 'filterTeacherId']);
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', ClassSubject::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(ClassSubject $classSubject): void
    {
        $this->assertVisible($classSubject);
        $this->authorize('update', $classSubject);

        $this->editingId = $classSubject->id;
        $this->schoolClassId = (string) $classSubject->school_class_id;
        $this->subjectId = (string) $classSubject->subject_id;
        $this->teacherId = (string) ($classSubject->teacher_id ?? '');
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $record = $this->editingId
            ? $this->scopedClassSubjects()->findOrFail($this->editingId)
            : null;

        $this->authorize($record ? 'update' : 'create', $record ?? ClassSubject::class);

        try {
            $data = $this->validate([
                'schoolClassId' => ['required', 'integer', Rule::exists('school_classes', 'id')],
                'subjectId' => ['required', 'integer', Rule::exists('subjects', 'id')],
                'teacherId' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
            ]);

            $schoolId = $this->schoolId();

            $schoolClass = SchoolClass::query()
                ->whereKey($data['schoolClassId'])
                ->whereHas('academicYear', fn (Builder $query) => $query->where('school_id', $schoolId))
                ->firstOrFail();

            $subject = Subject::query()
                ->whereKey($data['subjectId'])
                ->where('school_id', $schoolId)
                ->firstOrFail();

            $teacherId = filled($data['teacherId']) ? (int) $data['teacherId'] : null;
            if ($teacherId !== null) {
                Teacher::query()
                    ->whereKey($teacherId)
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->firstOrFail();
            }

            $duplicate = $this->scopedClassSubjects()
                ->where('school_class_id', $schoolClass->id)
                ->where('subject_id', $subject->id)
                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->id))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'subjectId' => 'This subject is already assigned to the selected class.',
                ]);
            }

            ClassSubject::updateOrCreate(
                ['id' => $record?->id],
                [
                    'school_class_id' => $schoolClass->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($record ? 'Class subject updated' : 'Subject allocated')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the allocation form')
                ->text('Correct the highlighted fields and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save allocation')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(ClassSubject $classSubject): void
    {
        $this->assertVisible($classSubject);
        $this->authorize('delete', $classSubject);

        $this->deletingId = $classSubject->id;
        $this->resetErrorBag('delete');
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $classSubject = $this->deletingId
            ? $this->scopedClassSubjects()->findOrFail($this->deletingId)
            : null;

        abort_unless($classSubject, 404);
        $this->authorize('delete', $classSubject);

        if ($this->hasDependentRecords($classSubject)) {
            $this->addError('delete', 'This allocation is already used by teaching, assessment, timetable, or examination records and cannot be removed. Reassign the teacher or archive the class instead.');
            LivewireAlert::title('Allocation cannot be removed')
                ->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $classSubject->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Class subject removed')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove allocation')
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

        $allocationQuery = $this->scopedClassSubjects();
        $filteredAllocations = (clone $allocationQuery)
            ->with(['schoolClass.academicYear', 'subject', 'teacher'])
            ->withCount(['topics', 'assignments', 'quizzes', 'assessments', 'examinations', 'timetableEntries'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $allocations) use ($search): void {
                    $allocations->whereHas('schoolClass', fn (Builder $classes) => $classes->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('schoolClass.academicYear', fn (Builder $years) => $years->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('subject', fn (Builder $subjects) => $subjects->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('teacher', fn (Builder $teachers) => $teachers->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                });
            })
            ->when(filled($this->filterAcademicYearId), fn (Builder $query) => $query->whereHas('schoolClass', fn (Builder $classes) => $classes->where('academic_year_id', $this->filterAcademicYearId)))
            ->when(filled($this->filterClassId), fn (Builder $query) => $query->where('school_class_id', $this->filterClassId))
            ->when(filled($this->filterSubjectId), fn (Builder $query) => $query->where('subject_id', $this->filterSubjectId))
            ->when($this->filterTeacherId === 'unassigned', fn (Builder $query) => $query->whereNull('teacher_id'))
            ->when(filled($this->filterTeacherId) && $this->filterTeacherId !== 'unassigned', fn (Builder $query) => $query->where('teacher_id', $this->filterTeacherId))
            ->orderByDesc('school_class_id')
            ->orderBy('subject_id');

        $formClasses = SchoolClass::query()
            ->with('academicYear')
            ->where('status', 'active')
            ->whereHas('academicYear', fn (Builder $years) => $years->where('school_id', $schoolId))
            ->when($teacher, fn (Builder $query) => $query->whereHas('classSubjects', fn (Builder $allocations) => $allocations->where('teacher_id', $teacher->id)))
            ->orderBy('name')
            ->get();

        $classOptions = $formClasses
            ->when(filled($this->filterAcademicYearId), fn ($classes) => $classes->where('academic_year_id', (int) $this->filterAcademicYearId))
            ->values();

        $subjectOptions = Subject::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $teacherOptions = $teacher
            ? collect([$teacher])
            : Teacher::query()->where('school_id', $schoolId)->where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get();

        return view('livewire.lms.class-subjects.index', [
            'classSubjects' => $filteredAllocations->paginate(15),
            'years' => AcademicYear::query()->where('school_id', $schoolId)->orderByDesc('starts_at')->get(),
            'classes' => $classOptions,
            'formClasses' => $formClasses,
            'subjects' => $subjectOptions,
            'teachers' => $teacherOptions,
            'allocationCount' => (clone $allocationQuery)->count(),
            'unassignedCount' => (clone $allocationQuery)->whereNull('teacher_id')->count(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'schoolClassId', 'subjectId', 'teacherId']);
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before managing class subjects.');

        return (int) $schoolId;
    }

    private function currentTeacher(): ?Teacher
    {
        return auth()->user()->hasRole('teacher') ? auth()->user()->teacher : null;
    }

    private function scopedClassSubjects(): Builder
    {
        $schoolId = $this->schoolId();
        $teacher = $this->currentTeacher();

        return ClassSubject::query()
            ->whereHas('schoolClass.academicYear', fn (Builder $years) => $years->where('school_id', $schoolId))
            ->when($teacher, fn (Builder $query) => $query->where('teacher_id', $teacher->id));
    }

    private function assertVisible(ClassSubject $classSubject): void
    {
        abort_unless($this->scopedClassSubjects()->whereKey($classSubject->id)->exists(), 404);
    }

    private function hasDependentRecords(ClassSubject $classSubject): bool
    {
        return $classSubject->topics()->exists()
            || $classSubject->assignments()->exists()
            || $classSubject->quizzes()->exists()
            || $classSubject->assessments()->exists()
            || $classSubject->examinations()->exists()
            || $classSubject->timetableEntries()->exists();
    }
}
