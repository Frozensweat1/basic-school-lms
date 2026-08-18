<?php

namespace App\Livewire\LMS\Permissions;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public function render()
    {
        return view('livewire.lms.permissions.index');
    }
}
