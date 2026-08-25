<?php

namespace App\Livewire\LMS\Website\Pages;

use App\Models\WebsitePage;
use App\Support\ContentSanitizer;
use Illuminate\Support\Facades\Storage;
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
    public bool $removeHeroImage = false;
    public ?int $editingId = null;
    public string $slug = '';
    public string $heroTitle = '';
    public string $heroSubtitle = '';
    public string $contentBody = '';
    public string $mission = '';
    public string $vision = '';
    public string $currentHeroImagePath = '';
    public array $stats = [];
    public array $programs = [];
    public array $values = [];
    public array $approach = [];
    public array $steps = [];
    public array $requirements = [];
    public $heroImage;

    public function mount(): void
    {
        $this->guard();
    }

    public function edit(WebsitePage $page): void
    {
        $this->guard();
        $this->resetForm();

        $content = is_array($page->content) ? $page->content : [];

        $this->editingId = $page->id;
        $this->slug = $page->slug;
        $this->heroTitle = (string) ($page->hero_title ?? '');
        $this->heroSubtitle = (string) ($page->hero_subtitle ?? '');
        $this->contentBody = (string) ($content['body'] ?? '');
        $this->mission = (string) ($content['mission'] ?? '');
        $this->vision = (string) ($content['vision'] ?? '');
        $this->stats = $this->statRows($page->stats);
        $this->programs = $this->cardRows($page->programs);
        $this->values = $this->cardRows($content['values'] ?? []);
        $this->approach = $this->cardRows($content['approach'] ?? []);
        $this->steps = $this->textRows($content['steps'] ?? []);
        $this->requirements = $this->textRows($content['requirements'] ?? []);
        $this->currentHeroImagePath = (string) ($page->hero_image_path ?? '');
        $this->showFormModal = true;
    }

    public function addStructuredItem(string $collection): void
    {
        $this->guard();

        if (in_array($collection, ['stats', 'programs', 'values', 'approach'], true)) {
            $this->{$collection}[] = $collection === 'stats'
                ? ['label' => '', 'value' => '']
                : ['title' => '', 'description' => ''];

            return;
        }

        if (in_array($collection, ['steps', 'requirements'], true)) {
            $this->{$collection}[] = '';
        }
    }

    public function removeStructuredItem(string $collection, int $index): void
    {
        $this->guard();

        if (! in_array($collection, ['stats', 'programs', 'values', 'approach', 'steps', 'requirements'], true)) {
            return;
        }

        unset($this->{$collection}[$index]);
        $this->{$collection} = array_values($this->{$collection});
    }

    public function save(): void
    {
        $this->guard();
        $newHeroPath = null;

        try {
            $page = WebsitePage::findOrFail($this->editingId);
            $data = $this->validate($this->rulesFor($page->slug));
            $existingContent = is_array($page->content) ? $page->content : [];
            $content = array_merge($existingContent, [
                'body' => app(ContentSanitizer::class)->clean($data['contentBody'] ?? ''),
            ]);

            if ($page->slug === 'about') {
                $content = array_merge($content, [
                    'mission' => $data['mission'] ?? '',
                    'vision' => $data['vision'] ?? '',
                    'values' => $this->cleanCardRows($data['values'] ?? []),
                ]);
            } elseif ($page->slug === 'academics') {
                $content['approach'] = $this->cleanCardRows($data['approach'] ?? []);
            } elseif ($page->slug === 'admissions') {
                $content['steps'] = $this->cleanTextRows($data['steps'] ?? []);
                $content['requirements'] = $this->cleanTextRows($data['requirements'] ?? []);
            }

            $oldHeroPath = $page->hero_image_path;
            $heroPath = $oldHeroPath;

            if ($this->heroImage) {
                $newHeroPath = $this->heroImage->store('website/pages', 'public');
                $heroPath = $newHeroPath;
            } elseif ($data['removeHeroImage'] ?? false) {
                $heroPath = null;
            }

            $payload = [
                'hero_title' => $data['heroTitle'] ?: null,
                'hero_subtitle' => $data['heroSubtitle'] ?: null,
                'hero_image_path' => $heroPath,
                'content' => $content,
                'updated_by' => auth()->id(),
            ];

            if ($page->slug === 'home') {
                $payload['stats'] = $this->cleanStatRows($data['stats'] ?? []);
                $payload['programs'] = $this->cleanCardRows($data['programs'] ?? []);
            } elseif ($page->slug === 'academics') {
                $payload['programs'] = $this->cleanCardRows($data['programs'] ?? []);
            }

            $page->update($payload);

            if ($oldHeroPath && $oldHeroPath !== $heroPath) {
                Storage::disk('public')->delete($oldHeroPath);
            }

            $this->closeModal();
            LivewireAlert::title('Page content updated')->success()->asToast()->show();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            if ($newHeroPath) {
                Storage::disk('public')->delete($newHeroPath);
            }

            report($exception);
            LivewireAlert::title('Unable to update the page')->error()->asToast()->show();
        }
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.lms.website.pages.index', [
            'pages' => WebsitePage::query()->orderBy('slug')->paginate(12),
        ]);
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403);
    }

    private function rulesFor(string $slug): array
    {
        $rules = [
            'heroTitle' => ['nullable', 'string', 'max:255'],
            'heroSubtitle' => ['nullable', 'string', 'max:1000'],
            'contentBody' => ['nullable', 'string', 'max:50000'],
            'heroImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'removeHeroImage' => ['boolean'],
        ];

        if ($slug === 'home') {
            $rules += [
                'stats' => ['array', 'max:8'],
                'stats.*.label' => ['required', 'string', 'max:100'],
                'stats.*.value' => ['required', 'string', 'max:50'],
                'programs' => ['array', 'max:12'],
                'programs.*.title' => ['required', 'string', 'max:150'],
                'programs.*.description' => ['nullable', 'string', 'max:1000'],
            ];
        } elseif ($slug === 'about') {
            $rules += [
                'mission' => ['nullable', 'string', 'max:3000'],
                'vision' => ['nullable', 'string', 'max:3000'],
                'values' => ['array', 'max:12'],
                'values.*.title' => ['required', 'string', 'max:150'],
                'values.*.description' => ['nullable', 'string', 'max:1000'],
            ];
        } elseif ($slug === 'academics') {
            $rules += [
                'programs' => ['array', 'max:12'],
                'programs.*.title' => ['required', 'string', 'max:150'],
                'programs.*.description' => ['nullable', 'string', 'max:1000'],
                'approach' => ['array', 'max:12'],
                'approach.*.title' => ['required', 'string', 'max:150'],
                'approach.*.description' => ['nullable', 'string', 'max:1000'],
            ];
        } elseif ($slug === 'admissions') {
            $rules += [
                'steps' => ['array', 'max:12'],
                'steps.*' => ['required', 'string', 'max:500'],
                'requirements' => ['array', 'max:20'],
                'requirements.*' => ['required', 'string', 'max:500'],
            ];
        }

        return $rules;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'slug', 'heroTitle', 'heroSubtitle', 'contentBody', 'mission', 'vision',
            'currentHeroImagePath', 'stats', 'programs', 'values', 'approach', 'steps',
            'requirements', 'heroImage', 'removeHeroImage',
        ]);
        $this->resetValidation();
    }

    private function cardRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => [
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function statRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_array($row))
            ->map(fn ($row) => [
                'label' => (string) ($row['label'] ?? ''),
                'value' => (string) ($row['value'] ?? ''),
            ])
            ->values()
            ->all();
    }

    private function textRows(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->filter(fn ($row) => is_scalar($row))
            ->map(fn ($row) => (string) $row)
            ->values()
            ->all();
    }

    private function cleanCardRows(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ])
            ->filter(fn ($row) => $row['title'] !== '')
            ->values()
            ->all();
    }

    private function cleanStatRows(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
            ])
            ->filter(fn ($row) => $row['label'] !== '' && $row['value'] !== '')
            ->values()
            ->all();
    }

    private function cleanTextRows(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => trim((string) $row))
            ->filter()
            ->values()
            ->all();
    }
}
