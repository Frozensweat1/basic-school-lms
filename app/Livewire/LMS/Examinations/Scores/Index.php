<?php

namespace App\Livewire\LMS\Examinations\Scores;

use App\Models\Examination;
use App\Models\ExaminationScore;
use App\Models\GradingScale;
use App\Models\School;
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

    public Examination $examination;

    /** @var array<int|string, string|null> */
    public array $scores = [];

    /** @var array<int|string, string> */
    public array $comments = [];

    /** @var int[] Student IDs that have a persisted non-null score */
    public array $gradedStudentIds = [];

    public string $search = '';

    public function mount(Examination $examination): void
    {
        $this->authorize('update', $examination);

        $examination->loadMissing([
            'classSubject.schoolClass.academicYear',
            'classSubject.subject',
            'term',
            'teacher',
        ]);

        abort_unless(
            (int) $examination->classSubject->schoolClass->academicYear->school_id === $this->schoolId(),
            403,
        );

        $this->examination = $examination;

        $existingScores = $examination->scores()->get()->keyBy('student_id');
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
        // Reactive re-filter — no extra work needed.
    }

    public function gradeFor(mixed $score, Collection $scales): ?string
    {
        if ($score === '' || $score === null || (float) $this->examination->max_score <= 0) {
            return null;
        }

        $percentage = ((float) $score / (float) $this->examination->max_score) * 100;

        return $scales
            ->first(fn (GradingScale $scale) => $percentage >= (float) $scale->minimum && $percentage <= (float) $scale->maximum)
            ?->grade;
    }

    /** Save a single student's score immediately. */
    public function saveStudentScore(int $studentId): void
    {
        $this->authorize('update', $this->examination);

        abort_unless($this->enrollments()->where('student_id', $studentId)->exists(), 403);

        try {
            $this->validate([
                "scores.{$studentId}" => ['nullable', 'numeric', 'min:0', 'max:'.$this->examination->max_score],
                "comments.{$studentId}" => ['nullable', 'string', 'max:1000'],
            ]);

            $score = $this->scores[$studentId] ?? '';
            $comment = $this->comments[$studentId] ?? '';

            $record = ExaminationScore::firstOrNew([
                'examination_id' => $this->examination->id,
                'student_id' => $studentId,
            ]);

            $oldValues = $record->exists ? ['score' => $record->score, 'comment' => $record->comment] : [];

            $record->fill([
                'score' => $score === '' ? null : $score,
                'comment' => filled($comment) ? $comment : null,
                'graded_at' => $score !== '' && $score !== null ? now() : null,
            ]);
            $record->save();

            app(AuditLogger::class)->record(
                'examination_score.updated',
                $record,
                $oldValues,
                ['score' => $record->score, 'comment' => $record->comment],
                $this->schoolId(),
            );

            $this->syncGradedState($studentId, $score);

            LivewireAlert::title('Score saved')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save score')->error()->asToast()->position('top-end')->show();
        }
    }

    /** Save all students that currently have a score entered in the form. */
    public function bulkGrade(): void
    {
        $this->authorize('update', $this->examination);

        try {
            $studentIds = $this->enrollments()->pluck('student_id')->map(fn ($id) => (string) $id)->all();

            $rules = [];
            foreach ($studentIds as $sid) {
                $rules["scores.{$sid}"] = ['nullable', 'numeric', 'min:0', 'max:'.$this->examination->max_score];
                $rules["comments.{$sid}"] = ['nullable', 'string', 'max:1000'];
            }
            $this->validate($rules);

            $schoolId = $this->schoolId();
            $count = 0;

            DB::transaction(function () use ($studentIds, $schoolId, &$count): void {
                foreach ($studentIds as $sid) {
                    $studentId = (int) $sid;
                    $score = $this->scores[$studentId] ?? $this->scores[$sid] ?? '';
                    if ($score === '' || $score === null) {
                        continue;
                    }

                    $comment = $this->comments[$studentId] ?? $this->comments[$sid] ?? '';

                    $record = ExaminationScore::firstOrNew([
                        'examination_id' => $this->examination->id,
                        'student_id' => $studentId,
                    ]);

                    $oldValues = $record->exists ? ['score' => $record->score, 'comment' => $record->comment] : [];

                    $record->fill([
                        'score' => $score,
                        'comment' => filled($comment) ? $comment : null,
                        'graded_at' => now(),
                    ]);
                    $record->save();

                    app(AuditLogger::class)->record(
                        'examination_score.updated',
                        $record,
                        $oldValues,
                        ['score' => $record->score, 'comment' => $record->comment],
                        $schoolId,
                    );

                    $this->syncGradedState($studentId, $score);
                    $count++;
                }
            });

            $message = $count > 0 ? "{$count} student(s) graded" : 'No scores were entered yet.';
            LivewireAlert::title($message)->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            LivewireAlert::title('Check the scores')
                ->text('Correct the highlighted values and try again.')
                ->error()->asToast()->position('top-end')->show();

            throw $e;
        } catch (Throwable $e) {
            report($e);
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
        $gradedCount = $this->examination->scores()->whereNotNull('score')->count();
        $scales = GradingScale::query()->where('school_id', $this->schoolId())->orderBy('sequence')->get();

        $this->examination->loadMissing(['questions.question']);
        $questionItems = $this->examination->questions;

        return view('livewire.lms.examinations.scores.index', [
            'students' => $students,
            'scales' => $scales,
            'allStudentCount' => $allStudentCount,
            'gradedCount' => $gradedCount,
            'questionItems' => $questionItems,
            'listRouteName' => $this->isTeacher()
                ? 'lms.examinations.teacher.index'
                : 'lms.examinations.admin.index',
            'questionsRouteName' => $this->isTeacher()
                ? 'lms.examinations.teacher.questions.index'
                : 'lms.examinations.admin.questions.index',
        ]);
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

    private function enrollments(): HasMany
    {
        return $this->examination->classSubject
            ->schoolClass
            ->enrollments()
            ->with('student')
            ->where('status', 'active');
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school before entering scores.');

        return (int) $schoolId;
    }

    private function isTeacher(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (! $user instanceof \App\Models\User) {
            return false;
        }

        return $user->hasRole('teacher') && ! $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
