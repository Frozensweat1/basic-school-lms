<?php

namespace App\Livewire\Website;

use App\Models\{Student, Teacher, WebsiteEvent, WebsiteNewsPost, WebsitePage};
use App\Support\SchoolBranding;
use Livewire\Component;

class HomePage extends Component
{
    public function render()
    {
        $page = WebsitePage::where('slug', 'home')->first();
        $stats = ['Years of learning' => (string) (now()->year - 2005), 'Active learners' => (string) Student::where('status', 'active')->count(), 'Dedicated teachers' => (string) Teacher::where('status', 'active')->count()];
        return view('livewire.website.home-page', [
            'branding' => app(SchoolBranding::class)->data(),
            'page' => $page,
            'stats' => $stats,
            'programs' => $page?->programs ?? [],
            'articles' => WebsiteNewsPost::whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at')->limit(3)->get(),
            'events' => WebsiteEvent::where('is_published', true)->where('starts_at', '>=', now())->orderBy('starts_at')->limit(3)->get(),
        ])
            ->layout('layouts.website');
    }
}
