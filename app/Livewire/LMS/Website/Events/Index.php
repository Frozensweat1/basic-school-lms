<?php

namespace App\Livewire\LMS\Website\Events;

use App\Models\WebsiteEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showFormModal = false;
    public bool $showDeleteModal = false;
    public bool $isPublished = true;
    public bool $removeFeaturedImage = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $title = '';
    public string $description = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $location = '';
    public string $search = '';
    public string $statusFilter = 'all';
    public string $currentFeaturedImagePath = '';
    public $featuredImage;

    public function mount(): void
    {
        $this->guard();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->guard();
        $this->resetForm();
        $this->startsAt = now()->addWeek()->startOfHour()->format('Y-m-d\TH:i');
        $this->showFormModal = true;
    }

    public function edit(WebsiteEvent $event): void
    {
        $this->guard();
        $this->resetForm();
        $this->editingId = $event->id;
        $this->title = $event->title;
        $this->description = (string) ($event->description ?? '');
        $this->startsAt = $event->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->location = (string) ($event->location ?? '');
        $this->isPublished = $event->is_published;
        $this->currentFeaturedImagePath = (string) ($event->featured_image_path ?? '');
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->guard();
        $newImagePath = null;

        try {
            $data = $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
                'startsAt' => ['required', 'date'],
                'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
                'location' => ['nullable', 'string', 'max:255'],
                'isPublished' => ['boolean'],
                'featuredImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'removeFeaturedImage' => ['boolean'],
            ]);
            $event = $this->editingId ? WebsiteEvent::query()->findOrFail($this->editingId) : new WebsiteEvent;
            $oldImagePath = $event->featured_image_path;
            $imagePath = $oldImagePath;

            if ($this->featuredImage) {
                $newImagePath = $this->featuredImage->store('website/events', 'public');
                $imagePath = $newImagePath;
            } elseif ($data['removeFeaturedImage']) {
                $imagePath = null;
            }

            $event->fill([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title'], $event->exists ? $event->id : null),
                'description' => $data['description'] ?: null,
                'starts_at' => $data['startsAt'],
                'ends_at' => $data['endsAt'] ?: null,
                'location' => $data['location'] ?: null,
                'featured_image_path' => $imagePath,
                'is_published' => $data['isPublished'],
                'created_by' => $event->created_by ?: auth()->id(),
            ])->save();

            if ($oldImagePath && $oldImagePath !== $imagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            $this->closeModals();
            LivewireAlert::title('Event saved')->success()->asToast()->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            report($exception);
            LivewireAlert::title('Unable to save the event')->error()->asToast()->show();
        }
    }

    public function confirmDelete(WebsiteEvent $event): void
    {
        $this->guard();
        $this->deletingId = $event->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->guard();

        try {
            $event = WebsiteEvent::query()->findOrFail($this->deletingId);
            $imagePath = $event->featured_image_path;
            $event->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $this->closeModals();
            LivewireAlert::title('Event deleted')->success()->asToast()->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete the event')->error()->asToast()->show();
        }
    }

    public function closeModals(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $events = WebsiteEvent::query()
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn (Builder $nested) => $nested
                    ->where('title', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('description', 'like', $search));
            })
            ->when($this->statusFilter === 'upcoming', fn (Builder $query) => $query->where('is_published', true)->where('starts_at', '>=', now()))
            ->when($this->statusFilter === 'past', fn (Builder $query) => $query->where('is_published', true)->where('starts_at', '<', now()))
            ->when($this->statusFilter === 'draft', fn (Builder $query) => $query->where('is_published', false))
            ->latest('starts_at')
            ->paginate(15);

        return view('livewire.lms.website.events.index', ['events' => $events]);
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'title', 'description', 'startsAt', 'endsAt', 'location',
            'featuredImage', 'currentFeaturedImagePath', 'removeFeaturedImage',
        ]);
        $this->isPublished = true;
        $this->resetValidation();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'school-event';
        $slug = $base;
        $suffix = 2;

        while (WebsiteEvent::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
