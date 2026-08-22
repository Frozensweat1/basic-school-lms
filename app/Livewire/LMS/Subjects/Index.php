<?php

namespace App\Livewire\LMS\Subjects;

use App\Models\School;
use App\Models\Subject;
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

    public bool $showForm = false;
    public bool $showDeleteConfirmation = false;
    public bool $isActive = true;

    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $search = '';
    public string $name = '';
    public string $code = '';
    public string $description = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Subject::class);
    }

    public function create(): void
    {
        $this->authorize('create', Subject::class);
        $this->ensureSchoolConfigured();

        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function edit(Subject $subject): void
    {
        $this->ensureSchoolRecord($subject);
        $this->authorize('update', $subject);

        $this->editingId = $subject->id;
        $this->name = $subject->name;
        $this->code = $subject->code ?? '';
        $this->description = $subject->description ?? '';
        $this->isActive = $subject->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $subject = $this->editingId ? Subject::findOrFail($this->editingId) : null;

        if ($subject) {
            $this->ensureSchoolRecord($subject);
        }

        $this->authorize($subject ? 'update' : 'create', $subject ?? Subject::class);
        $schoolId = $this->ensureSchoolConfigured();

        try {
            $data = $this->validate([
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('subjects', 'name')
                        ->where('school_id', $schoolId)
                        ->ignore($subject?->id),
                ],
                'code' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('subjects', 'code')
                        ->where('school_id', $schoolId)
                        ->ignore($subject?->id),
                ],
                'description' => ['nullable', 'string', 'max:2000'],
                'isActive' => ['boolean'],
            ]);

            Subject::updateOrCreate(
                ['id' => $subject?->id],
                [
                    'school_id' => $schoolId,
                    'name' => $data['name'],
                    'code' => filled($data['code']) ? $data['code'] : null,
                    'description' => filled($data['description']) ? $data['description'] : null,
                    'is_active' => $data['isActive'],
                ],
            );

            $this->showForm = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($subject ? 'Subject updated' : 'Subject created')
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
            LivewireAlert::title('Unable to save subject')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmDelete(Subject $subject): void
    {
        $this->ensureSchoolRecord($subject);
        $this->authorize('delete', $subject);

        $this->deletingId = $subject->id;
        $this->showDeleteConfirmation = true;
    }

    public function delete(): void
    {
        $subject = Subject::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($subject);
        $this->authorize('delete', $subject);

        if ($subject->classSubjects()->exists()) {
            $this->addError('delete', 'Subjects assigned to classes cannot be deleted. Archive the subject instead.');
            LivewireAlert::title('Subject cannot be deleted')
                ->text('Remove it from classes or keep it archived for historical records.')
                ->warning()
                ->asToast()
                ->position('top-end')
                ->show();

            return;
        }

        try {
            $subject->delete();
            $this->showDeleteConfirmation = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Subject deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete subject')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
        $this->resetErrorBag();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirmation = false;
        $this->deletingId = null;
        $this->resetErrorBag();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'deletingId', 'name', 'code', 'description', 'isActive']);
        $this->isActive = true;
        $this->resetValidation();
    }

    private function schoolId(): int
    {
        return (int) School::query()->value('id');
    }

    private function ensureSchoolConfigured(): int
    {
        $schoolId = $this->schoolId();
        abort_unless($schoolId, 422, 'Configure a school before managing subjects.');

        return $schoolId;
    }

    private function ensureSchoolRecord(Subject $subject): void
    {
        abort_unless($subject->school_id === $this->schoolId(), 404);
    }

    public function render()
    {
        $search = trim($this->search);
        $statusSearch = match (strtolower($search)) {
            'active' => true,
            'archived', 'inactive' => false,
            default => null,
        };

        return view('livewire.lms.subjects.index', [
            'subjects' => Subject::query()
                ->where('school_id', $this->schoolId())
                ->withCount('classSubjects')
                ->when($search !== '', function ($query) use ($search, $statusSearch): void {
                    $query->where(function ($subjects) use ($search, $statusSearch): void {
                        $subjects->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");

                        if ($statusSearch !== null) {
                            $subjects->orWhere('is_active', $statusSearch);
                        }
                    });
                })
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
