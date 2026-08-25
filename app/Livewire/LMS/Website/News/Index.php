<?php

namespace App\Livewire\LMS\Website\News;

use App\Models\WebsiteNewsPost;
use App\Support\ContentSanitizer;
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
    public bool $removeFeaturedImage = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;
    public string $title = '';
    public string $excerpt = '';
    public string $body = '';
    public string $publishedAt = '';
    public string $search = '';
    public string $statusFilter = 'all';
    public string $currentFeaturedImagePath = '';
    public $featuredImage;

    public function mount(): void
    {
        $this->authorizeWebsite();
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
        $this->authorizeWebsite();
        $this->resetForm();
        $this->publishedAt = now()->format('Y-m-d\TH:i');
        $this->showFormModal = true;
    }

    public function edit(WebsiteNewsPost $post): void
    {
        $this->authorizeWebsite();
        $this->resetForm();
        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->excerpt = (string) ($post->excerpt ?? '');
        $this->body = $post->body;
        $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
        $this->currentFeaturedImagePath = (string) ($post->featured_image_path ?? '');
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $this->authorizeWebsite();
        $newImagePath = null;

        try {
            $data = $this->validate([
                'title' => ['required', 'string', 'max:255'],
                'excerpt' => ['nullable', 'string', 'max:500'],
                'body' => ['required', 'string', 'max:50000'],
                'publishedAt' => ['nullable', 'date'],
                'featuredImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
                'removeFeaturedImage' => ['boolean'],
            ]);
            $post = $this->editingId ? WebsiteNewsPost::query()->findOrFail($this->editingId) : new WebsiteNewsPost;
            $oldImagePath = $post->featured_image_path;
            $imagePath = $oldImagePath;

            if ($this->featuredImage) {
                $newImagePath = $this->featuredImage->store('website/news', 'public');
                $imagePath = $newImagePath;
            } elseif ($data['removeFeaturedImage']) {
                $imagePath = null;
            }

            $post->fill([
                'title' => $data['title'],
                'slug' => $this->uniqueSlug($data['title'], $post->exists ? $post->id : null),
                'excerpt' => $data['excerpt'] ?: null,
                'body' => app(ContentSanitizer::class)->clean($data['body']),
                'featured_image_path' => $imagePath,
                'published_at' => $data['publishedAt'] ?: null,
                'created_by' => $post->created_by ?: auth()->id(),
            ])->save();

            if ($oldImagePath && $oldImagePath !== $imagePath) {
                Storage::disk('public')->delete($oldImagePath);
            }

            $wasEditing = (bool) $this->editingId;
            $this->closeModals();
            LivewireAlert::title($wasEditing ? 'News post updated' : 'News post created')->success()->asToast()->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            report($exception);
            LivewireAlert::title('Unable to save news')->error()->asToast()->show();
        }
    }

    public function confirmDelete(WebsiteNewsPost $post): void
    {
        $this->authorizeWebsite();
        $this->deletingId = $post->id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $this->authorizeWebsite();

        try {
            $post = WebsiteNewsPost::query()->findOrFail($this->deletingId);
            $imagePath = $post->featured_image_path;
            $post->delete();

            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $this->closeModals();
            LivewireAlert::title('News post deleted')->success()->asToast()->show();
        } catch (Throwable $exception) {
            report($exception);
            LivewireAlert::title('Unable to delete the news post')->error()->asToast()->show();
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
        $posts = WebsiteNewsPost::query()
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(fn (Builder $nested) => $nested
                    ->where('title', 'like', $search)
                    ->orWhere('excerpt', 'like', $search));
            })
            ->when($this->statusFilter === 'published', fn (Builder $query) => $query->whereNotNull('published_at')->where('published_at', '<=', now()))
            ->when($this->statusFilter === 'scheduled', fn (Builder $query) => $query->where('published_at', '>', now()))
            ->when($this->statusFilter === 'draft', fn (Builder $query) => $query->whereNull('published_at'))
            ->latest('created_at')
            ->paginate(15);

        return view('livewire.lms.website.news.index', ['posts' => $posts]);
    }

    private function authorizeWebsite(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'deletingId', 'title', 'excerpt', 'body', 'publishedAt',
            'featuredImage', 'currentFeaturedImagePath', 'removeFeaturedImage',
        ]);
        $this->resetValidation();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'news-post';
        $slug = $base;
        $suffix = 2;

        while (WebsiteNewsPost::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
