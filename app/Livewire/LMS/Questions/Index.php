<?php

namespace App\Livewire\LMS\Questions;

use App\Models\{Question, School};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
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

    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public string $type = 'multiple_choice', $prompt = '', $maxScore = '1', $optionsText = '', $correctAnswer = '';

    public function mount(): void { $this->authorize('viewAny', Question::class); }
    public function create(): void { $this->authorize('create', Question::class); $this->resetForm(); $this->showFormModal = true; }

    public function edit(Question $question): void
    {
        $question = $this->scopedQuestions()->findOrFail($question->id); $this->authorize('update', $question); $this->editingId = $question->id; $this->type = $question->type; $this->prompt = $question->prompt; $this->maxScore = (string) $question->max_score; $this->optionsText = $question->options->pluck('label')->implode(PHP_EOL); $this->correctAnswer = (string) ($question->grading_key['answer'] ?? ''); $this->showFormModal = true;
    }

    public function save(): void
    {
        $question = $this->editingId ? $this->scopedQuestions()->findOrFail($this->editingId) : null; $this->authorize($question ? 'update' : 'create', $question ?? Question::class);
        try {
            $data = $this->validate(['type' => ['required', Rule::in(['multiple_choice', 'true_false', 'short_answer', 'essay'])], 'prompt' => ['required', 'string', 'max:10000'], 'maxScore' => ['required', 'numeric', 'min:.01', 'max:999999'], 'optionsText' => ['nullable', 'string', 'max:10000'], 'correctAnswer' => ['nullable', 'string', 'max:5000']]);
            $options = collect(preg_split('/\r\n|\r|\n/', $data['optionsText'] ?? ''))->map(fn ($item) => trim($item))->filter()->values();
            if ($data['type'] === 'true_false') $options = collect(['True', 'False']);
            if ($data['type'] === 'multiple_choice' && $options->count() < 2) { $this->addError('optionsText', 'Add at least two options.'); return; }
            if ($data['type'] !== 'essay' && blank($data['correctAnswer'])) { $this->addError('correctAnswer', 'Provide the correct answer.'); return; }
            if ($data['type'] === 'multiple_choice' && ! $options->contains(fn ($option) => mb_strtolower($option) === mb_strtolower(trim($data['correctAnswer'])))) { $this->addError('correctAnswer', 'The correct answer must match one of the options.'); return; }
            if ($data['type'] === 'true_false' && ! in_array(mb_strtolower(trim($data['correctAnswer'])), ['true', 'false'], true)) { $this->addError('correctAnswer', 'Use True or False as the correct answer.'); return; }
            $record = Question::updateOrCreate(['id' => $question?->id], ['school_id' => $this->schoolId(), 'created_by' => $question?->created_by ?? auth()->id(), 'type' => $data['type'], 'prompt' => strip_tags($data['prompt'], '<p><br><strong><em><u><ol><ul><li>'), 'max_score' => $data['maxScore'], 'grading_key' => $data['type'] === 'essay' ? null : ['answer' => trim($data['correctAnswer'])]]);
            $record->options()->delete(); foreach ($options as $sequence => $label) $record->options()->create(['label' => $label, 'is_correct' => mb_strtolower($label) === mb_strtolower(trim($data['correctAnswer'])), 'sequence' => $sequence]);
            $this->showFormModal = false; $this->resetForm(); LivewireAlert::title($question ? 'Question updated' : 'Question added')->success()->asToast()->position('top-end')->show();
        } catch (ValidationException $exception) { LivewireAlert::title('Check the question')->error()->asToast()->position('top-end')->show(); throw $exception; } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to save question')->error()->asToast()->position('top-end')->show(); }
    }

    public function confirmDelete(Question $question): void { $question = $this->scopedQuestions()->findOrFail($question->id); $this->authorize('delete', $question); $this->deletingId = $question->id; $this->showDeleteModal = true; }
    public function delete(): void { $question = $this->scopedQuestions()->findOrFail($this->deletingId); $this->authorize('delete', $question); try { $question->delete(); $this->showDeleteModal = false; $this->deletingId = null; LivewireAlert::title('Question deleted')->success()->asToast()->position('top-end')->show(); } catch (Throwable $exception) { report($exception); LivewireAlert::title('Unable to delete question')->error()->asToast()->position('top-end')->show(); } }
    public function closeModals(): void { $this->showFormModal = false; $this->showDeleteModal = false; $this->resetForm(); $this->resetErrorBag(); }
    private function resetForm(): void { $this->reset(['editingId', 'deletingId', 'type', 'prompt', 'maxScore', 'optionsText', 'correctAnswer']); $this->type = 'multiple_choice'; $this->maxScore = '1'; $this->resetValidation(); }
    private function schoolId(): int { return (int) School::query()->value('id'); }
    private function scopedQuestions() { return Question::where('school_id', $this->schoolId()); }
    public function render() { return view('livewire.lms.questions.index', ['questions' => $this->scopedQuestions()->with('options')->latest()->paginate(15)]); }
}
