<?php

namespace App\Livewire\LMS\Topics\Concerns;

use App\Models\ClassSubject;
use App\Models\School;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

abstract class ManagesTopics extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $search = '';
    public string $filterClassSubjectId = '';
    public string $filterLessonState = '';

    public string $classSubjectId = '';
    public string $title = '';
    public string $description = '';
    public string $sequence = '0';

    abstract protected function classSubjects(): Builder;

    abstract protected function componentView(): string;

    public function create(): void
    {
        $this->authorize('create', Topic::class);
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterClassSubjectId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterLessonState(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterClassSubjectId', 'filterLessonState']);
        $this->resetPage();
    }

    public function edit(Topic $topic): void
    {
        $this->authorize('update', $topic);
        $this->managedClassSubject($topic->class_subject_id);

        $this->editingId = $topic->id;
        $this->classSubjectId = (string) $topic->class_subject_id;
        $this->title = $topic->title;
        $this->description = $topic->description ?? '';
        $this->sequence = (string) $topic->sequence;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $topic = $this->editingId ? Topic::findOrFail($this->editingId) : null;
        $this->authorize($topic ? 'update' : 'create', $topic ?? Topic::class);

        try {
            if ($topic) {
                $this->managedClassSubject($topic->class_subject_id);
            }

            $data = $this->validate([
                'classSubjectId' => ['required', 'integer', Rule::exists('class_subjects', 'id')],
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'sequence' => ['required', 'integer', 'min:0', 'max:9999'],
            ]);

            $classSubject = $this->managedClassSubject((int) $data['classSubjectId']);
            $duplicate = Topic::query()
                ->where('class_subject_id', $classSubject->id)
                ->whereRaw('lower(title) = ?', [mb_strtolower($data['title'])])
                ->when($topic, fn ($query) => $query->whereKeyNot($topic->id))
                ->exists();

            if ($duplicate) {
                $this->addError('title', 'This topic already exists for the selected class subject.');

                return;
            }

            Topic::updateOrCreate(
                ['id' => $topic?->id],
                [
                    'class_subject_id' => $classSubject->id,
                    'title' => $data['title'],
                    'description' => filled($data['description']) ? $data['description'] : null,
                    'sequence' => $data['sequence'],
                ],
            );

            $this->showFormModal = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($topic ? 'Topic updated' : 'Topic created')
                ->success()
                ->asToast()
                ->position('top-end')
                ->show();
        } catch (ValidationException $exception) {
            LivewireAlert::title('Check the form')
                ->text('Correct the highlighted fields and try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to save topic')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmDelete(Topic $topic): void
    {
        $this->authorize('delete', $topic);
        $this->managedClassSubject($topic->class_subject_id);

        $this->deletingId = $topic->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $topic = Topic::findOrFail($this->deletingId);
        $this->authorize('delete', $topic);
        $this->managedClassSubject($topic->class_subject_id);

        try {
            $topic->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Topic deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete topic')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
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
        $classSubjects = $this->classSubjects()->get();
        $classSubjectIds = $classSubjects->pluck('id');
        $search = trim($this->search);

        return view($this->componentView(), [
            'topics' => Topic::query()
                ->with(['classSubject.schoolClass', 'classSubject.subject'])
                ->whereIn('class_subject_id', $classSubjectIds)
                ->withCount('lessons')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($topics) use ($search): void {
                        $topics->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('classSubject.subject', fn ($subjects) => $subjects->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('classSubject.schoolClass', fn ($classes) => $classes->where('name', 'like', "%{$search}%"));
                    });
                })
                ->when(filled($this->filterClassSubjectId), fn ($query) => $query->where('class_subject_id', $this->filterClassSubjectId))
                ->when($this->filterLessonState === 'with_lessons', fn ($query) => $query->has('lessons'))
                ->when($this->filterLessonState === 'without_lessons', fn ($query) => $query->doesntHave('lessons'))
                ->orderBy('sequence')
                ->orderBy('title')
                ->paginate(15),
            'classSubjects' => $classSubjects,
        ]);
    }

    protected function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    protected function managedClassSubject(int $id): ClassSubject
    {
        return $this->classSubjects()->whereKey($id)->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'classSubjectId', 'title', 'description', 'sequence']);
        $this->sequence = '0';
        $this->resetValidation();
    }
}
