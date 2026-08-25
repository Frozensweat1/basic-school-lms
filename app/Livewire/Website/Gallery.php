<?php

namespace App\Livewire\Website;

use App\Models\WebsiteGalleryAlbum;
use App\Support\PublicWebsiteData;
use Livewire\Component;
use Livewire\WithPagination;

class Gallery extends Component
{
    use WithPagination;

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('gallery');
        $albums = WebsiteGalleryAlbum::query()
            ->withCount('images')
            ->with(['images' => fn ($query) => $query
                ->select(['id', 'album_id', 'path', 'caption', 'sort_order'])
                ->limit(8)])
            ->latest()
            ->paginate(6);

        return view('livewire.website.gallery', [
            'branding' => $site->branding(),
            'page' => $page,
            'albums' => $albums,
        ])->layout('layouts.website', $site->metadata('Gallery', $page, route('website.gallery')));
    }
}
