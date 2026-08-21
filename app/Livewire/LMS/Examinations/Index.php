<?php

namespace App\Livewire\LMS\Examinations;

use App\Models\{AcademicYear, ClassSubject, Examination, School, Teacher, Term};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $academicYearId = '', $termId = '', $classSubjectId = '', $teacherId = '', $title = '', $description = '', $examDate = '', $durationMinutes = '', $maxScore = '100', $status = 'draft';

    public function mount(): void
    {
        $this->authorize('viewAny', Examination::class);
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin', 'teacher']), 403);
    }

    public function create(): void { $this->authorize('create', Examination::class); $this->resetForm(); $this->showFormModal = true; }

    public function edit(Examination $examination): void
    {
        $this->authorize('update', $examination);
        $this->editingId = $examination->id; $this->academicYearId = (string) $examination->academic_year_id; $this->termId = (string) $examination->term_id; $this->classSubjectId = (string) $examination->class_subject_id; $this->teacherId = (string) $examination->teacher_id; $this->title = $examination->title; $this->description = $examination->description ?? ''; $this->examDate = $examination->exam_date->format('Y-m-d'); $this->durationMinutes = (string) ($examination->duration_minutes ?? ''); $this->maxScore = (string) $examination->max_score; $this->status = $examination->status; $this->showFormModal = true;
    }

    public function save(): void
    {
        $exam = $this->editingId ? Examination::findOrFail($this->editingId) : null;
        $this->authorize($exam ? 'update' : 'create', $exam ?? Examination::class);
        try {
            $data = $this->validate(['academicYearId' => ['required', 'integer', Rule::exists('academic_years', 'id')], 'termId' => ['required', 'integer', Rule::exists('terms', 'id')], 'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')], 'teacherId' => [auth()->user()->hasRole('teacher') ? 'nullable' : 'required', 'integer', Rule::exists('teachers', 'id')], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'examDate' => ['required', 'date'], 'durationMinutes' => ['nullable', 'integer', 'min:1', 'max:600'], 'maxScore' => ['required', 'numeric', 'min:.01'], 'status' => ['required', Rule::in(['draft', 'scheduled', 'completed', 'cancelled'])]]);
            $schoolId = (int) School::query()->value('id');
            $year = AcademicYear::whereKey($data['academicYearId'])->where('school_id', $schoolId)->firstOrFail();
            $term = Term::whereKey($data['termId'])->where('academic_year_id', $year->id)->firstOrFail();
            $classSubject = ClassSubject::with('schoolClass')->whereHas('schoolClass.academicYear', fn ($q) => $q->where('school_id', $schoolId))->findOrFail($data['classSubjectId']);
            $teacher = auth()->user()->hasRole('teacher') ? auth()->user()->teacher : Teacher::findOrFail($data['teacherId']);
            abort_unless($teacher && $teacher->school_id === $schoolId, 422, 'Choose a teacher from this school.');
            if (auth()->user()->hasRole('teacher')) abort_unless($classSubject->teacher_id === $teacher->id, 403, 'You can only schedule exams for your assigned class subjects.');
            abort_unless($classSubject->schoolClass->academic_year_id === $year->id, 422, 'Choose a class subject from the selected academic year.');
            Examination::updateOrCreate(['id' => $exam?->id], ['school_id' => $schoolId, 'academic_year_id' => $year->id, 'term_id' => $term->id, 'class_subject_id' => $classSubject->id, 'teacher_id' => $teacher->id, 'title' => $data['title'], 'description' => $data['description'] ?: null, 'exam_date' => $data['examDate'], 'duration_minutes' => $data['durationMinutes'] ?: null, 'max_score' => $data['maxScore'], 'status' => $data['status']]);
            $this->showFormModal = false; $this->resetForm(); LivewireAlert::title($exam ? 'Examination updated' : 'Examination scheduled')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the examination form')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save examination')->error()->asToast()->position('top-end')->show(); }
    }

    public function delete(int $id): void
    {
        $exam = Examination::findOrFail($id); $this->authorize('delete', $exam); $exam->delete(); LivewireAlert::title('Examination deleted')->success()->asToast()->position('top-end')->show();
    }

    public function closeModals(): void { $this->showFormModal = false; $this->resetForm(); }

    public function render()
    {
        $schoolId = (int) School::query()->value('id'); $teacher = auth()->user()->hasRole('teacher') ? auth()->user()->teacher : null;
        $years = AcademicYear::where('school_id', $schoolId)->latest('starts_at')->get();
        $classSubjects = ClassSubject::with(['schoolClass', 'subject'])->whereHas('schoolClass.academicYear', fn ($q) => $q->where('school_id', $schoolId))->when($teacher, fn ($q) => $q->where('teacher_id', $teacher->id))->get();
        return view('livewire.lms.examinations.index', ['examinations' => Examination::with(['classSubject.subject', 'teacher', 'term'])->where('school_id', $schoolId)->when($teacher, fn ($q) => $q->where('teacher_id', $teacher->id))->latest('exam_date')->get(), 'years' => $years, 'terms' => Term::whereIn('academic_year_id', $years->pluck('id'))->orderBy('sequence')->get(), 'classSubjects' => $classSubjects, 'teachers' => $teacher ? collect([$teacher]) : Teacher::where('school_id', $schoolId)->where('status', 'active')->get()]);
    }

    private function resetForm(): void { $this->reset(['editingId', 'academicYearId', 'termId', 'classSubjectId', 'teacherId', 'title', 'description', 'examDate', 'durationMinutes', 'maxScore', 'status']); $this->maxScore = '100'; $this->status = 'draft'; $this->resetValidation(); }
}
