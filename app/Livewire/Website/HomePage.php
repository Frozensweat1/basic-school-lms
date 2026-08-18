<?php

namespace App\Livewire\Website;

use Livewire\Component;

class HomePage extends Component
{
    public function render()
    {
        return view('livewire.website.home-page')
            ->layout('layouts.website');
    }
}
