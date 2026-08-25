<?php

namespace App\Livewire\Website;

use App\Support\PublicWebsiteData;
use Livewire\Component;

class Events extends Component
{
    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('events');
        $upcoming = $site->upcomingEvents(12);
        $past = $site->pastEvents(6);

        return view('livewire.website.events', [
            'branding' => $site->branding(),
            'page' => $page,
            'upcoming' => $upcoming,
            'past' => $past,
        ])->layout('layouts.website', $site->metadata('Events', $page, route('website.events')));
    }
}
