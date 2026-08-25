<?php

namespace App\Livewire\Website;

use App\Support\PublicWebsiteData;
use Livewire\Component;

class Academics extends Component
{
    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('academics');

        return view('livewire.website.academics', [
            'branding' => $site->branding(),
            'page' => $page,
        ])->layout('layouts.website', $site->metadata('Academics', $page, route('website.academics')));
    }
}
