<?php

namespace App\Livewire\LMS\Assessments;

use App\Models\{Assessment, AssessmentComponent, ClassSubject, School, SubjectResult, Teacher, Term};
use App\Services\Results\SubjectResultCalculator;
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
    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public string $classSubjectId = '', $termId = '', $componentId = '', $teacherId = '', $title = '', $maxScore = '100', $assessedAt = '', $status = 'draft';

    public function mount(): void { $this->authorize('viewAny', Assessment::class); }
    public function create(): void { $this->authorize('create', Assessment::class); $this->resetForm(); $this->showFormModal = true; }
    public function edit(Assessment $assessment): void { $this->authorize('update', $assessment); $this->editingId = $assessment->id; $this->classSubjectId = (string) $assessment->class_subject_id; $this->termId = (string) $assessment->term_id; $this->componentId = (string) ($assessment->assessment_component_id ?? ''); $this->teacherId = (string) $assessment->teacher_id; $this->title = $assessment->title; $this->maxScore = (string) $assessment->max_score; $this->assessedAt = $assessment->assessed_at->format('Y-m-d'); $this->status = $assessment->status; $this->showFormModal = true; }

    public function save(SubjectResultCalculator $calculator): void
    {
        $assessment = $this->editingId ? Assessment::findOrFail($this->editingId) : null; $this->authorize($assessment ? 'update' : 'create', $assessment ?? Assessment::class);
        try {
            $data = $this->validate(['classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')], 'termId' => ['required', 'integer', Rule::exists('terms', 'id')], 'componentId' => ['nullable', 'integer', Rule::exists('assessment_components', 'id')], 'teacherId' => [auth()->user()->hasRole('teacher') ? 'nullable' : 'required', 'integer', Rule::exists('teachers', 'id')], 'title' => ['required', 'string', 'max:255'], 'maxScore' => ['required', 'numeric', 'min:.01'], 'assessedAt' => ['required', 'date'], 'status' => ['required', Rule::in(['draft', 'published', 'locked'])]]);
            $classSubject = ClassSubject::with('schoolClass.academicYear')->findOrFail($data['classSubjectId']); $term = Term::findOrFail($data['termId']); $schoolId = (int) School::query()->value('id'); abort_unless($classSubject->schoolClass->academicYear->school_id === $schoolId && $term->academic_year_id === $classSubject->schoolClass->academic_year_id, 422, 'Choose a class subject and term from the same academic year.');
            if (filled($data['componentId'])) abort_unless(AssessmentComponent::whereKey($data['componentId'])->where('term_id', $term->id)->exists(), 422, 'Choose a component for the selected term.');
            $teacherId = auth()->user()->hasRole('teacher') ? auth()->user()->teacher?->id : (filled($data['teacherId']) ? (int) $data['teacherId'] : $classSubject->teacher_id); abort_unless($teacherId && Teacher::whereKey($teacherId)->where('school_id', $schoolId)->exists(), 422, 'Assign a teacher belonging to this school.'); if (auth()->user()->hasRole('teacher')) abort_unless($classSubject->teacher_id === $teacherId, 403, 'You can only manage assessments for your assigned class subjects.');
            $saved = Assessment::updateOrCreate(['id' => $assessment?->id], ['class_subject_id' => $classSubject->id, 'term_id' => $term->id, 'assessment_component_id' => filled($data['componentId']) ? $data['componentId'] : null, 'teacher_id' => $teacherId, 'title' => $data['title'], 'max_score' => $data['maxScore'], 'assessed_at' => $data['assessedAt'], 'status' => $data['status']]);
            $studentIds = $classSubject->schoolClass->enrollments()->where('status', 'active')->pluck('student_id');
            if ($saved->status === 'published') foreach ($studentIds as $studentId) $calculator->calculate((int) $studentId, $saved->class_subject_id, $saved->term_id);
            else SubjectResult::where('class_subject_id', $saved->class_subject_id)->where('term_id', $saved->term_id)->update(['status' => 'draft']);
            $this->showFormModal = false; $this->resetForm(); LivewireAlert::title($assessment ? 'Assessment updated' : 'Assessment created')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the assessment')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save assessment')->error()->asToast()->position('top-end')->show(); }
    }

    public function confirmDelete(Assessment $assessment): void { $this->authorize('delete', $assessment); $this->deletingId = $assessment->id; $this->showDeleteModal = true; }
    public function delete(): void { $assessment = Assessment::findOrFail($this->deletingId); $this->authorize('delete', $assessment); try { $assessment->delete(); $this->showDeleteModal = false; $this->deletingId = null; LivewireAlert::title('Assessment deleted')->success()->asToast()->position('top-end')->show(); } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to delete assessment')->error()->asToast()->position('top-end')->show(); } }
    public function closeModals(): void { $this->showFormModal = false; $this->showDeleteModal = false; $this->resetForm(); $this->resetErrorBag(); }
    private function resetForm(): void { $this->reset(['editingId', 'deletingId', 'classSubjectId', 'termId', 'componentId', 'teacherId', 'title', 'maxScore', 'assessedAt', 'status']); $this->maxScore = '100'; $this->status = 'draft'; $this->resetValidation(); }
    public function render() { $schoolId = (int) School::query()->value('id'); $teacherId = auth()->user()->hasRole('teacher') ? auth()->user()->teacher?->id : null; $classSubjects = ClassSubject::with(['schoolClass', 'subject'])->whereHas('schoolClass.academicYear', fn ($q) => $q->where('school_id', $schoolId))->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))->get(); $terms = Term::whereHas('academicYear', fn ($q) => $q->where('school_id', $schoolId))->orderBy('sequence')->get(); return view('livewire.lms.assessments.index', ['assessments' => Assessment::with(['classSubject.schoolClass', 'classSubject.subject', 'term', 'component'])->when($classSubjects->isNotEmpty(), fn ($q) => $q->whereIn('class_subject_id', $classSubjects->pluck('id')))->when($classSubjects->isEmpty(), fn ($q) => $q->whereRaw('1=0'))->latest()->get(), 'classSubjects' => $classSubjects, 'terms' => $terms, 'components' => AssessmentComponent::whereIn('term_id', $terms->pluck('id'))->orderBy('sequence')->get(), 'teachers' => Teacher::where('school_id', $schoolId)->where('status', 'active')->get()]); }
}
