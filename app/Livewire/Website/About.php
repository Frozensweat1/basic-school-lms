<?php

namespace App\Livewire\Website;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class About extends Component
{
    public function render()
    {
        return view('livewire.website.about');
    }
}