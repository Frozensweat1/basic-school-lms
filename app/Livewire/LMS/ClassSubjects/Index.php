<?php

namespace App\Livewire\LMS\ClassSubjects;

use App\Models\ClassSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $schoolClassId = '';
    public string $subjectId = '';
    public string $teacherId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ClassSubject::class);
    }

    public function create(): void
    {
        $this->authorize('create', ClassSubject::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function edit(ClassSubject $classSubject): void
    {
        $this->authorize('update', $classSubject);
        $this->editingId = $classSubject->id;
        $this->schoolClassId = (string) $classSubject->school_class_id;
        $this->subjectId = (string) $classSubject->subject_id;
        $this->teacherId = (string) ($classSubject->teacher_id ?? '');
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $record = $this->editingId ? ClassSubject::findOrFail($this->editingId) : null;
        $this->authorize($record ? 'update' : 'create', $record ?? ClassSubject::class);

        try {
            $data = $this->validate([
                'schoolClassId' => ['required', 'integer', Rule::exists('school_classes', 'id')],
                'subjectId' => ['required', 'integer', Rule::exists('subjects', 'id')],
                'teacherId' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
            ]);

            $schoolId = School::query()->value('id');
            abort_unless($schoolId, 422, 'Configure a school before allocating subjects.');

            $classIsInSchool = SchoolClass::query()
                ->whereKey($data['schoolClassId'])
                ->whereHas('academicYear', fn ($query) => $query->where('school_id', $schoolId))
                ->exists();
            $subjectIsInSchool = Subject::query()
                ->whereKey($data['subjectId'])
                ->where('school_id', $schoolId)
                ->exists();
            $teacherIsInSchool = blank($data['teacherId']) || Teacher::query()
                ->whereKey($data['teacherId'])
                ->where('school_id', $schoolId)
                ->exists();

            abort_unless($classIsInSchool && $subjectIsInSchool && $teacherIsInSchool, 422, 'Choose records belonging to this school.');

            $duplicate = ClassSubject::query()
                ->where('school_class_id', $data['schoolClassId'])
                ->where('subject_id', $data['subjectId'])
                ->when($record, fn ($query) => $query->whereKeyNot($record->id))
                ->exists();
            if ($duplicate) {
                $this->addError('subjectId', 'This subject is already assigned to the selected class.');

                return;
            }

            ClassSubject::updateOrCreate(
                ['id' => $record?->id],
                [
                    'school_class_id' => $data['schoolClassId'],
                    'subject_id' => $data['subjectId'],
                    'teacher_id' => filled($data['teacherId']) ? $data['teacherId'] : null,
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            LivewireAlert::title($record ? 'Class subject updated' : 'Subject allocated')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save allocation')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmDelete(ClassSubject $classSubject): void
    {
        $this->authorize('delete', $classSubject);
        $this->deletingId = $classSubject->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $classSubject = ClassSubject::findOrFail($this->deletingId);
        $this->authorize('delete', $classSubject);

        try {
            $classSubject->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            LivewireAlert::title('Class subject removed')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to remove allocation')->error()->asToast()->position('top-end')->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'schoolClassId', 'subjectId', 'teacherId']);
        $this->resetValidation();
    }

    public function render()
    {
        $schoolId = School::query()->value('id');

        return view('livewire.lms.class-subjects.index', [
            'classSubjects' => ClassSubject::with(['schoolClass.academicYear', 'subject', 'teacher'])
                ->when($schoolId, fn ($query) => $query->whereHas('schoolClass.academicYear', fn ($classes) => $classes->where('school_id', $schoolId)))
                ->orderBy('school_class_id')
                ->get(),
            'classes' => SchoolClass::with('academicYear')
                ->when($schoolId, fn ($query) => $query->whereHas('academicYear', fn ($years) => $years->where('school_id', $schoolId)))
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'subjects' => Subject::query()->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))->where('is_active', true)->orderBy('name')->get(),
            'teachers' => Teacher::query()->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))->where('status', 'active')->orderBy('last_name')->get(),
        ]);
    }
}
