<?php

namespace App\Livewire\LMS\Roles;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.lms.roles.index');
    }
}
