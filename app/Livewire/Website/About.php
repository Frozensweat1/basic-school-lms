<?php

namespace App\Livewire\Website;

use App\Models\WebsitePage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class About extends Component
{
    public function render()
    {
        return view('livewire.website.about', ['page' => WebsitePage::where('slug', 'about')->first()]);
    }
}
