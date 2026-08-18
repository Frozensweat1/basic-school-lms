<?php

namespace App\Livewire\Website;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Academics extends Component
{
    public function render()
    {
        return view('livewire.website.academics');
    }
}