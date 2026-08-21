<?php

namespace App\Livewire\Website;

use App\Models\WebsiteEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Events extends Component
{
    public function render()
    {
        $upcoming = WebsiteEvent::where('is_published', true)->where('starts_at', '>=', now())->orderBy('starts_at')->limit(12)->get();
        $past = WebsiteEvent::where('is_published', true)->where('starts_at', '<', now())->latest('starts_at')->limit(6)->get();
        return view('livewire.website.events', compact('upcoming', 'past'));
    }
}
