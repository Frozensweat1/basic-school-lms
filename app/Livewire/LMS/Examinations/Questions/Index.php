<?php

namespace App\Livewire\LMS\Examinations\Questions;

use App\Models\Examination;
use App\Models\ExaminationQuestion;
use App\Models\GradingScale;
use App\Models\Question;
use App\Models\School;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public Examination $examination;

    public string $bankSearch = '';
    public string $bankType = '';
    public string $bankSubjectId = '';
    public string $bankTopicId = '';

    public ?int $addingQuestionId = null;
    public string $addMarks = '1';
    public bool $showAddModal = false;

    public bool $showRemoveModal = false;
    public ?int $removingExamQuestionId = null;

    public function mount(Examination $examination): void
    {
        $this->authorize('update', $examination);
        $this->examination = $examination;
    }

    public function updatedBankSearch(): void { $this->resetPage(); }
    public function updatedBankType(): void { $this->resetPage(); }
    public function updatedBankSubjectId(): void { $this->bankTopicId = ''; $this->resetPage(); }
    public function updatedBankTopicId(): void { $this->resetPage(); }

    public function openAdd(int $questionId): void
    {
        $this->authorize('update', $this->examination);
        $question = $this->bankQuery()->findOrFail($questionId);

        $this->addingQuestionId = $question->id;
        $this->addMarks = (string) $question->max_score;
        $this->resetValidation('addMarks');
        $this->showAddModal = true;
    }

    public function addQuestion(): void
    {
        $this->authorize('update', $this->examination);

        try {
            $this->validate(['addMarks' => ['required', 'numeric', 'min:0.01', 'max:999']]);

            $question = $this->bankQuery()->findOrFail($this->addingQuestionId);

            if ($this->examination->questions()->where('question_id', $question->id)->exists()) {
                LivewireAlert::title('Already added')
                    ->text('This question is already on the examination.')
                    ->warning()->asToast()->position('top-end')->show();

                return;
            }

            $maxSeq = $this->examination->questions()->max('sequence') ?? 0;

            $this->examination->questions()->create([
                'question_id' => $question->id,
                'sequence' => $maxSeq + 1,
                'marks' => $this->addMarks,
            ]);

            $this->closeModals();
            LivewireAlert::title('Question added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to add question')->error()->asToast()->position('top-end')->show();
        }
    }

    public function confirmRemove(int $examQuestionId): void
    {
        $this->authorize('update', $this->examination);
        abort_unless(
            $this->examination->questions()->whereKey($examQuestionId)->exists(),
            404,
        );
        $this->removingExamQuestionId = $examQuestionId;
        $this->showRemoveModal = true;
    }

    public function removeQuestion(): void
    {
        $this->authorize('update', $this->examination);

        $examQuestion = $this->examination->questions()->findOrFail($this->removingExamQuestionId);
        $examQuestion->delete();

        $this->closeModals();
        LivewireAlert::title('Question removed')->success()->asToast()->position('top-end')->show();
    }

    public function closeModals(): void
    {
        $this->showAddModal = false;
        $this->showRemoveModal = false;
        $this->addingQuestionId = null;
        $this->removingExamQuestionId = null;
        $this->addMarks = '1';
        $this->resetValidation();
    }

    public function render()
    {
        $this->examination->loadMissing([
            'classSubject.schoolClass.academicYear',
            'classSubject.subject',
        ]);

        $attachedItems = $this->examination->questions()
            ->with(['question.subject', 'question.topic', 'question.options'])
            ->get();

        $attachedQuestionIds = $attachedItems->pluck('question_id')->all();
        $totalAttachedMarks = $attachedItems->sum('marks');

        $bankSearch = trim($this->bankSearch);
        $bankQuestions = $this->bankQuery()
            ->with(['subject', 'topic', 'options'])
            ->when($bankSearch !== '', function (Builder $q) use ($bankSearch): void {
                $q->where(function (Builder $inner) use ($bankSearch): void {
                    $inner->where('prompt', 'like', "%{$bankSearch}%")
                        ->orWhereHas('subject', fn (Builder $s) => $s->where('name', 'like', "%{$bankSearch}%"))
                        ->orWhereHas('topic', fn (Builder $t) => $t->where('name', 'like', "%{$bankSearch}%"));
                });
            })
            ->when(filled($this->bankType), fn (Builder $q) => $q->where('type', $this->bankType))
            ->when(filled($this->bankSubjectId), fn (Builder $q) => $q->where('subject_id', $this->bankSubjectId))
            ->when(filled($this->bankTopicId), fn (Builder $q) => $q->where('topic_id', $this->bankTopicId))
            ->orderBy('subject_id')
            ->orderBy('id')
            ->paginate(10, pageName: 'bankPage');

        $schoolId = $this->schoolId();

        return view('livewire.lms.examinations.questions.index', [
            'attachedItems' => $attachedItems,
            'attachedQuestionIds' => $attachedQuestionIds,
            'totalAttachedMarks' => $totalAttachedMarks,
            'marksMismatch' => $totalAttachedMarks > 0
                && abs((float) $totalAttachedMarks - (float) $this->examination->max_score) > 0.01,
            'bankQuestions' => $bankQuestions,
            'addingQuestion' => $this->addingQuestionId
                ? Question::with('options')->find($this->addingQuestionId)
                : null,
            'subjects' => Subject::query()->where('school_id', $schoolId)->orderBy('name')->get(),
            'topics' => filled($this->bankSubjectId)
                ? Topic::query()->where('subject_id', $this->bankSubjectId)->orderBy('name')->get()
                : collect(),
            'scoresRouteName' => $this->isTeacher()
                ? 'lms.examinations.teacher.scores.index'
                : 'lms.examinations.admin.scores.index',
            'listRouteName' => $this->isTeacher()
                ? 'lms.examinations.teacher.index'
                : 'lms.examinations.admin.index',
        ]);
    }

    private function bankQuery(): Builder
    {
        return Question::query()->where('school_id', $this->schoolId());
    }

    private function schoolId(): int
    {
        $schoolId = School::query()->value('id');
        abort_unless($schoolId, 422, 'Configure a school first.');

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
