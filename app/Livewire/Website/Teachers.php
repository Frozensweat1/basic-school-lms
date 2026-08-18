<?php

namespace App\Livewire\Website;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Teachers extends Component
{
    public function render()
    {
        return view('livewire.website.teachers');
    }
}