<?php

namespace App\Livewire\Website;

use App\Models\WebsiteNewsPost;
use App\Support\SchoolBranding;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.website')]
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
        $posts = WebsiteNewsPost::query()->published()->latest('published_at')->paginate(9);
        $selected = $this->selectedId ? WebsiteNewsPost::query()->published()->find($this->selectedId) : null;

        return view('livewire.website.news', ['posts' => $posts, 'selected' => $selected]);
    }
}
