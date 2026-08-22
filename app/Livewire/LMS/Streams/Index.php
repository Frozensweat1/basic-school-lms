<?php

namespace App\Livewire\LMS\Streams;

use App\Models\School;
use App\Models\Stream;
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
    public string $description = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Stream::class);
    }

    public function create(): void
    {
        $this->authorize('create', Stream::class);
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

    public function edit(Stream $stream): void
    {
        $this->ensureSchoolRecord($stream);
        $this->authorize('update', $stream);

        $this->editingId = $stream->id;
        $this->name = $stream->name;
        $this->description = $stream->description ?? '';
        $this->isActive = $stream->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $stream = $this->editingId ? Stream::findOrFail($this->editingId) : null;

        if ($stream) {
            $this->ensureSchoolRecord($stream);
        }

        $this->authorize($stream ? 'update' : 'create', $stream ?? Stream::class);
        $schoolId = $this->ensureSchoolConfigured();

        try {
            $data = $this->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('streams', 'name')
                        ->where('school_id', $schoolId)
                        ->ignore($stream?->id),
                ],
                'description' => ['nullable', 'string', 'max:500'],
                'isActive' => ['boolean'],
            ]);

            Stream::updateOrCreate(
                ['id' => $stream?->id],
                [
                    'school_id' => $schoolId,
                    'name' => $data['name'],
                    'description' => filled($data['description']) ? $data['description'] : null,
                    'is_active' => $data['isActive'],
                ],
            );

            $this->showForm = false;
            $this->resetForm();
            $this->resetPage();
            LivewireAlert::title($stream ? 'Stream updated' : 'Stream created')
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
            LivewireAlert::title('Unable to save stream')
                ->text('Please try again.')
                ->error()
                ->asToast()
                ->position('top-end')
                ->show();
        }
    }

    public function confirmDelete(Stream $stream): void
    {
        $this->ensureSchoolRecord($stream);
        $this->authorize('delete', $stream);

        $this->deletingId = $stream->id;
        $this->showDeleteConfirmation = true;
    }

    public function delete(): void
    {
        $stream = Stream::findOrFail($this->deletingId);
        $this->ensureSchoolRecord($stream);
        $this->authorize('delete', $stream);

        if ($stream->classes()->exists()) {
            $this->addError('delete', 'Streams assigned to classes cannot be deleted. Archive the stream instead.');
            LivewireAlert::title('Stream cannot be deleted')
                ->text('Remove it from classes or keep it archived for historical records.')
                ->warning()
                ->asToast()
                ->position('top-end')
                ->show();

            return;
        }

        try {
            $stream->delete();
            $this->showDeleteConfirmation = false;
            $this->deletingId = null;
            $this->resetPage();
            LivewireAlert::title('Stream deleted')->success()->asToast()->position('top-end')->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete stream')
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
        $this->reset(['editingId', 'deletingId', 'name', 'description', 'isActive']);
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
        abort_unless($schoolId, 422, 'Configure a school before managing streams.');

        return $schoolId;
    }

    private function ensureSchoolRecord(Stream $stream): void
    {
        abort_unless($stream->school_id === $this->schoolId(), 404);
    }

    public function render()
    {
        $search = trim($this->search);
        $statusSearch = match (strtolower($search)) {
            'active' => true,
            'archived', 'inactive' => false,
            default => null,
        };

        return view('livewire.lms.streams.index', [
            'streams' => Stream::query()
                ->where('school_id', $this->schoolId())
                ->withCount('classes')
                ->when($search !== '', function ($query) use ($search, $statusSearch): void {
                    $query->where(function ($streams) use ($search, $statusSearch): void {
                        $streams->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");

                        if ($statusSearch !== null) {
                            $streams->orWhere('is_active', $statusSearch);
                        }
                    });
                })
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
