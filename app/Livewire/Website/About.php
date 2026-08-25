<?php

namespace App\Livewire\Website;

use App\Support\PublicWebsiteData;
use Livewire\Component;

class About extends Component
{
    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('about');

        return view('livewire.website.about', [
            'branding' => $site->branding(),
            'page' => $page,
        ])->layout('layouts.website', $site->metadata('About', $page, route('website.about')));
    }
}
