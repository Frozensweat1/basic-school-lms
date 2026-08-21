<?php

namespace App\Livewire\LMS\AssessmentScores;

use App\Models\{Assessment, AssessmentScore, GradingScale, School};
use App\Services\Results\SubjectResultCalculator;
use App\Support\AuditLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;

    public Assessment $assessment;
    public array $scores = [];
    public array $comments = [];

    public function mount(Assessment $assessment): void
    {
        $this->authorize('update', $assessment);
        abort_unless((int) $assessment->classSubject->schoolClass->academicYear->school_id === (int) School::query()->value('id'), 403);
        $this->assessment = $assessment;
        foreach ($this->enrollments() as $enrollment) {
            $record = $assessment->scores()->where('student_id', $enrollment->student_id)->first();
            $this->scores[$enrollment->student_id] = $record?->score ?? '';
            $this->comments[$enrollment->student_id] = $record?->comment ?? '';
        }
    }

    public function gradeFor(mixed $score): ?string
    {
        if ($score === '' || $score === null) return null;
        $percentage = ((float) $score / (float) $this->assessment->max_score) * 100;
        return GradingScale::where('school_id', $this->assessment->classSubject->schoolClass->academicYear->school_id)->where('minimum', '<=', $percentage)->where('maximum', '>=', $percentage)->orderBy('sequence')->value('grade');
    }

    public function save(): void
    {
        $this->authorize('update', $this->assessment);
        try {
            $this->validate(['scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.$this->assessment->max_score], 'comments.*' => ['nullable', 'string', 'max:1000']]);
            $allowedIds = $this->enrollments()->pluck('student_id')->map(fn ($id) => (string) $id)->all();
            $calculator = app(SubjectResultCalculator::class);
            foreach ($allowedIds as $studentId) {
                $score = $this->scores[(int) $studentId] ?? $this->scores[$studentId] ?? '';
                $comment = $this->comments[(int) $studentId] ?? $this->comments[$studentId] ?? null;
                $record = AssessmentScore::firstOrNew(['assessment_id' => $this->assessment->id, 'student_id' => (int) $studentId]);
                $oldValues = $record->exists ? ['score' => $record->score, 'comment' => $record->comment] : [];
                $record->fill(['score' => $score === '' ? null : $score, 'comment' => $comment]);
                $record->save();
                $newValues = ['score' => $record->score, 'comment' => $record->comment];
                if ($oldValues !== $newValues) app(AuditLogger::class)->record('assessment_score.updated', $record, $oldValues, $newValues, (int) $this->assessment->classSubject->schoolClass->academicYear->school_id);
                if ($this->assessment->status === 'published') $calculator->calculate((int) $studentId, $this->assessment->class_subject_id, $this->assessment->term_id);
            }
            LivewireAlert::title($this->assessment->status === 'published' ? 'Scores and results saved' : 'Scores saved as draft')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the scores')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save scores')->error()->asToast()->position('top-end')->show(); }
    }

    public function render()
    {
        return view('livewire.lms.assessment-scores.index', ['students' => $this->enrollments(), 'scales' => GradingScale::where('school_id', $this->assessment->classSubject->schoolClass->academicYear->school_id)->orderBy('sequence')->get()]);
    }

    private function enrollments() { return $this->assessment->classSubject->schoolClass->enrollments()->with('student')->where('status', 'active')->get(); }
}
