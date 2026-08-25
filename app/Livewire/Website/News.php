<?php

namespace App\Livewire\Website;

use App\Models\WebsiteNewsPost;
use App\Support\PublicWebsiteData;
use Livewire\Component;
use Livewire\WithPagination;

class News extends Component
{
    use WithPagination;

    public ?int $selectedId = null;

    public function open(int $id): void
    {
        $this->selectedId = WebsiteNewsPost::query()->published()->whereKey($id)->value('id');
    }

    public function close(): void
    {
        $this->selectedId = null;
    }

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('news');
        $posts = WebsiteNewsPost::query()->published()->latest('published_at')->paginate(9);
        $selected = $this->selectedId ? WebsiteNewsPost::query()->published()->find($this->selectedId) : null;

        return view('livewire.website.news', [
            'branding' => $site->branding(),
            'page' => $page,
            'posts' => $posts,
            'selected' => $selected,
        ])->layout('layouts.website', $site->metadata('News', $page, route('website.news')));
    }
}
