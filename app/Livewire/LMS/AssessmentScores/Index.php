<?php

namespace App\Livewire\LMS\AssessmentScores;

use App\Models\Assessment;
use App\Models\AssessmentScore;
use App\Models\GradingScale;
use App\Models\School;
use App\Services\Results\SubjectResultCalculator;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /** @var int[] Student IDs that have a persisted non-null score */
    public array $gradedStudentIds = [];

    public string $search = '';

    public function mount(Assessment $assessment): void
    {
        $this->authorize('update', $assessment);

        $assessment->loadMissing([
            'classSubject.schoolClass.academicYear',
            'classSubject.subject',
            'term.academicYear',
            'component',
            'teacher',
        ]);

        abort_unless(
            (int) $assessment->classSubject->schoolClass->academicYear->school_id === $this->schoolId(),
            403,
        );

        $this->assessment = $assessment;

        $existingScores = $assessment->scores()->get()->keyBy('student_id');
        foreach ($this->enrollments()->get() as $enrollment) {
            $record = $existingScores->get($enrollment->student_id);
            $this->scores[$enrollment->student_id] = $record?->score ?? '';
            $this->comments[$enrollment->student_id] = $record?->comment ?? '';
            if ($record && $record->score !== null) {
                $this->gradedStudentIds[] = (int) $enrollment->student_id;
            }
        }
    }

    public function updatedSearch(): void
    {
        // Livewire re-renders the filtered score list immediately.
    }

    public function gradeFor(mixed $score, Collection $scales): ?string
    {
        if ($score === '' || $score === null || (float) $this->assessment->max_score <= 0) {
            return null;
        }

        $percentage = ((float) $score / (float) $this->assessment->max_score) * 100;

        return $scales
            ->first(fn (GradingScale $scale) => $percentage >= (float) $scale->minimum && $percentage <= (float) $scale->maximum)
            ?->grade;
    }

    public function save(): void
    {
        $this->authorize('update', $this->assessment);

        if ($this->assessment->status === 'locked') {
            $this->addError('locked', 'This assessment is locked. Unlock it before changing scores.');
            LivewireAlert::title('Assessment is locked')->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $this->validate([
                'scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.$this->assessment->max_score],
                'comments.*' => ['nullable', 'string', 'max:1000'],
            ]);

            $schoolId = $this->schoolId();
            $studentIds = $this->enrollments()->pluck('student_id')->map(fn (int $id) => (string) $id)->all();

            DB::transaction(function () use ($studentIds, $schoolId): void {
                foreach ($studentIds as $studentId) {
                    $score = $this->scores[(int) $studentId] ?? $this->scores[$studentId] ?? '';
                    $comment = $this->comments[(int) $studentId] ?? $this->comments[$studentId] ?? '';

                    $record = AssessmentScore::firstOrNew([
                        'assessment_id' => $this->assessment->id,
                        'student_id' => (int) $studentId,
                    ]);

                    $oldValues = $record->exists
                        ? ['score' => $record->score, 'comment' => $record->comment]
                        : [];

                    $record->fill([
                        'score' => $score === '' ? null : $score,
                        'comment' => filled($comment) ? $comment : null,
                    ]);
                    $record->save();

                    $newValues = ['score' => $record->score, 'comment' => $record->comment];
                    if ($oldValues !== $newValues) {
                        app(AuditLogger::class)->record(
                            'assessment_score.updated',
                            $record,
                            $oldValues,
                            $newValues,
                            $schoolId,
                        );
                    }

                    $this->syncGradedState((int) $studentId, $score);

                    if ($this->assessment->status === 'published') {
                        app(SubjectResultCalculator::class)->calculate(
                            (int) $studentId,
                            $this->assessment->class_subject_id,
                            $this->assessment->term_id,
                        );
                    }
                }
            });

            LivewireAlert::title($this->assessment->status === 'published' ? 'Scores and results saved' : 'Scores saved as draft')
                ->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the scores')
                ->text('Correct the highlighted values and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save scores')
                ->text('Please try again.')
                ->error()->asToast()->position('top-end')->show();
        }
    }

    /** Save a single student's score immediately (row-level action). */
    public function saveStudentScore(int $studentId): void
    {
        $this->authorize('update', $this->assessment);

        if ($this->assessment->status === 'locked') {
            LivewireAlert::title('Assessment is locked')->warning()->asToast()->position('top-end')->show();

            return;
        }

        abort_unless($this->enrollments()->where('student_id', $studentId)->exists(), 403);

        try {
            $this->validate([
                "scores.{$studentId}" => ['nullable', 'numeric', 'min:0', 'max:'.$this->assessment->max_score],
                "comments.{$studentId}" => ['nullable', 'string', 'max:1000'],
            ]);

            $score = $this->scores[$studentId] ?? '';
            $comment = $this->comments[$studentId] ?? '';
            $schoolId = $this->schoolId();

            $record = AssessmentScore::firstOrNew([
                'assessment_id' => $this->assessment->id,
                'student_id' => $studentId,
            ]);

            $oldValues = $record->exists ? ['score' => $record->score, 'comment' => $record->comment] : [];

            $record->fill([
                'score' => $score === '' ? null : $score,
                'comment' => filled($comment) ? $comment : null,
            ]);
            $record->save();

            $newValues = ['score' => $record->score, 'comment' => $record->comment];
            if ($oldValues !== $newValues) {
                app(AuditLogger::class)->record('assessment_score.updated', $record, $oldValues, $newValues, $schoolId);
            }

            $this->syncGradedState($studentId, $score);

            if ($this->assessment->status === 'published') {
                app(SubjectResultCalculator::class)->calculate(
                    $studentId,
                    $this->assessment->class_subject_id,
                    $this->assessment->term_id,
                );
            }

            LivewireAlert::title('Score saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save score')->error()->asToast()->position('top-end')->show();
        }
    }

    /** Save all students that currently have a score filled in — skips empty inputs. */
    public function bulkGrade(): void
    {
        $this->authorize('update', $this->assessment);

        if ($this->assessment->status === 'locked') {
            $this->addError('locked', 'This assessment is locked. Unlock it before changing scores.');
            LivewireAlert::title('Assessment is locked')->warning()->asToast()->position('top-end')->show();

            return;
        }

        try {
            $this->validate([
                'scores.*' => ['nullable', 'numeric', 'min:0', 'max:'.$this->assessment->max_score],
                'comments.*' => ['nullable', 'string', 'max:1000'],
            ]);

            $schoolId = $this->schoolId();
            $studentIds = $this->enrollments()->pluck('student_id')->map(fn (int $id) => (string) $id)->all();
            $count = 0;

            DB::transaction(function () use ($studentIds, $schoolId, &$count): void {
                foreach ($studentIds as $studentId) {
                    $score = $this->scores[(int) $studentId] ?? $this->scores[$studentId] ?? '';
                    if ($score === '' || $score === null) {
                        continue;
                    }

                    $comment = $this->comments[(int) $studentId] ?? $this->comments[$studentId] ?? '';

                    $record = AssessmentScore::firstOrNew([
                        'assessment_id' => $this->assessment->id,
                        'student_id' => (int) $studentId,
                    ]);

                    $oldValues = $record->exists ? ['score' => $record->score, 'comment' => $record->comment] : [];

                    $record->fill([
                        'score' => $score,
                        'comment' => filled($comment) ? $comment : null,
                    ]);
                    $record->save();

                    $newValues = ['score' => $record->score, 'comment' => $record->comment];
                    if ($oldValues !== $newValues) {
                        app(AuditLogger::class)->record('assessment_score.updated', $record, $oldValues, $newValues, $schoolId);
                    }

                    $this->syncGradedState((int) $studentId, $score);

                    if ($this->assessment->status === 'published') {
                        app(SubjectResultCalculator::class)->calculate(
                            (int) $studentId,
                            $this->assessment->class_subject_id,
                            $this->assessment->term_id,
                        );
                    }

                    $count++;
                }
            });

            $message = $count > 0
                ? ($this->assessment->status === 'published' ? "{$count} student(s) graded and results updated" : "{$count} student(s) graded as draft")
                : 'No scores were entered yet.';

            LivewireAlert::title($message)->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the scores')
                ->text('Correct the highlighted values and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Bulk grading failed')->error()->asToast()->position('top-end')->show();
        }
    }

    public function render()
    {
        $search = trim($this->search);
        $students = $this->enrollments()
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('student', function ($students) use ($search): void {
                    $students->where('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%")
                        ->orWhere('admission_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('student_id')
            ->get();

        $allStudentCount = $this->enrollments()->count();
        $enteredScoreCount = $this->assessment->scores()->whereNotNull('score')->count();
        $scales = GradingScale::query()
            ->where('school_id', $this->schoolId())
            ->orderBy('sequence')
            ->get();

        return view('livewire.lms.assessment-scores.index', [
            'students' => $students,
            'scales' => $scales,
            'allStudentCount' => $allStudentCount,
            'enteredScoreCount' => $enteredScoreCount,
            'assessmentListRouteName' => $this->managingAsTeacher()
                ? 'lms.assessments.teacher.index'
                : 'lms.assessments.admin.index',
        ]);
    }

    private function enrollments(): HasMany
    {
        return $this->assessment->classSubject
            ->schoolClass
            ->enrollments()
            ->with('student')
            ->where('status', 'active');
    }

    private function syncGradedState(int $studentId, mixed $score): void
    {
        $hasScore = $score !== '' && $score !== null;
        $inList = in_array($studentId, $this->gradedStudentIds, true);

        if ($hasScore && ! $inList) {
            $this->gradedStudentIds[] = $studentId;
        } elseif (! $hasScore && $inList) {
            $this->gradedStudentIds = array_values(
                array_filter($this->gradedStudentIds, fn (int $id) => $id !== $studentId),
            );
        }
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before entering scores.');

        return (int) $schoolId;
    }

    private function managingAsTeacher(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (! $user instanceof \App\Models\User) {
            return false;
        }

        return $user->hasRole('teacher') && ! $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
