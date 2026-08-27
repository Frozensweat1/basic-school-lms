<?php

namespace App\Livewire\LMS\Website\Gallery;

use App\Models\{WebsiteGalleryAlbum, WebsiteGalleryImage};
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.lms')]
class Albums extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $title = '', $description = '';
    public array $images = [];
    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public array $imagePreviews = [];

    public function mount(): void
    {
        $this->guard();
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403);
    }

    public function create(): void
    {
        $this->guard();
        $this->reset(['editingId', 'title', 'description', 'images', 'imagePreviews']);
        $this->showFormModal = true;
    }

    public function edit(WebsiteGalleryAlbum $album): void
    {
        $this->guard();
        $this->editingId = $album->id;
        $this->title = $album->title;
        $this->description = $album->description ?? '';
        $this->images = [];
        $this->imagePreviews = [];
        $this->showFormModal = true;
    }

    public function updated($property, $value): void
    {
        // Generate previews when images are selected
        if ($property === 'images' && is_array($value)) {
            $this->imagePreviews = [];
            foreach ($value as $image) {
                $this->imagePreviews[] = [
                    'preview' => $image->getClientOriginalName(),
                    'size' => round($image->getSize() / 1024, 2), // KB
                ];
            }
        }
    }

    public function removeImage(int $index): void
    {
        unset($this->images[$index]);
        $this->images = array_values($this->images);
        unset($this->imagePreviews[$index]);
        $this->imagePreviews = array_values($this->imagePreviews);
    }

    public function save(): void
    {
        $this->guard();

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'images.*' => ['nullable', 'image', 'max:4096'],
        ]);

        try {
            $album = $this->editingId
                ? WebsiteGalleryAlbum::findOrFail($this->editingId)
                : WebsiteGalleryAlbum::create([
                    'title' => $data['title'],
                    'description' => $data['description'],
                ]);

            if ($this->editingId) {
                $album->update([
                    'title' => $data['title'],
                    'description' => $data['description'],
                ]);
            }

            $uploadedCount = 0;
            foreach (($this->images ?? []) as $index => $image) {
                $path = $image->store('website/gallery', 'public');
                WebsiteGalleryImage::create([
                    'album_id' => $album->id,
                    'path' => $path,
                    'caption' => $album->title . ' image',
                    'sort_order' => $album->images()->count() + $index,
                ]);
                $uploadedCount++;
            }

            $this->showFormModal = false;
            $this->reset(['editingId', 'title', 'description', 'images', 'imagePreviews']);

            $message = $this->editingId
                ? "Album updated"
                : "Album created";

            if ($uploadedCount > 0) {
                $message .= " with {$uploadedCount} image" . ($uploadedCount > 1 ? 's' : '');
            }

            LivewireAlert::title($message)->success()->asToast()->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to save album')->error()->asToast()->show();
        }
    }

    public function confirmDelete(WebsiteGalleryAlbum $album): void
    {
        $this->guard();
        $this->deletingId = $album->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->guard();
        $album = WebsiteGalleryAlbum::with('images')->findOrFail($this->deletingId);

        try {
            foreach ($album->images as $image) {
                if ($image->path) {
                    Storage::disk('public')->delete($image->path);
                }
            }
            $album->delete();
            $this->showDeleteModal = false;
            $this->deletingId = null;
            LivewireAlert::title('Album deleted')->success()->asToast()->show();
        } catch (Throwable $e) {
            report($e);
            LivewireAlert::title('Unable to delete album')->error()->asToast()->show();
        }
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['editingId', 'title', 'description', 'images', 'imagePreviews']);
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function render()
    {
        return view('livewire.lms.website.gallery.albums', [
            'albums' => WebsiteGalleryAlbum::with('images')->latest()->paginate(15),
        ]);
    }
}
