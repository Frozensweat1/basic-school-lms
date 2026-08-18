<?php

namespace App\Livewire\LMS;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.lms.dashboard');
    }
}
