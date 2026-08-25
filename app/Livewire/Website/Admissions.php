<?php

namespace App\Livewire\Website;

use App\Support\PublicWebsiteData;
use Livewire\Component;

class Admissions extends Component
{
    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('admissions');

        return view('livewire.website.admissions', [
            'branding' => $site->branding(),
            'page' => $page,
        ])->layout('layouts.website', $site->metadata('Admissions', $page, route('website.admissions')));
    }
}
