<?php

namespace App\Livewire\Website;

use App\Models\WebsiteGalleryAlbum;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Gallery extends Component
{
    public function render()
    {
        return view('livewire.website.gallery', ['albums' => WebsiteGalleryAlbum::with('images')->latest()->get()]);
    }
}
