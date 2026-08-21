<?php

namespace App\Livewire\Website;

use App\Models\WebsitePage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Admissions extends Component
{
    public function render()
    {
        return view('livewire.website.admissions', ['page' => WebsitePage::where('slug', 'admissions')->first()]);
    }
}
