<?php

namespace App\Livewire\Website;

use App\Livewire\Traits\HasNewsletterSubscription;
use App\Support\PublicWebsiteData;
use Livewire\Component;

class HomePage extends Component
{
    use HasNewsletterSubscription;

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('home');

        return view('livewire.website.home-page', [
            'branding' => $site->branding(),
            'page' => $page,
            'stats' => $site->homeStats(),
            'programs' => $page->programs ?? [],
            'testimonials' => $site->homeTestimonials(),
            'articles' => $site->latestNews(3),
            'events' => $site->upcomingEvents(3),
        ])
            ->layout('layouts.website', $site->metadata('Home', $page, route('home')));
    }
}
