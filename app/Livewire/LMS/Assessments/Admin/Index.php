<?php

namespace App\Livewire\LMS\Assessments\Admin;

use App\Livewire\LMS\Assessments\Index as Shared;
use App\Models\Assessment;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')] 
class Index extends Shared
{
    public function mount(): void
    {
        $this->authorize('viewAny', Assessment::class);
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'school_admin']), 403);
    }
}
