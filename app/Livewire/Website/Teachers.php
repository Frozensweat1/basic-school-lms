<?php

namespace App\Livewire\Website;

use App\Models\Teacher;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.website')]
class Teachers extends Component
{
    public function render()
    {
        $schoolId = School::query()->value('id');
        $teachers = Teacher::query()
            ->with('subjects:id,name')
            ->where('status', 'active')
            ->where('is_featured_on_website', true)
            ->orderBy('website_display_order')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('livewire.website.teachers', compact('teachers'));
    }
}
