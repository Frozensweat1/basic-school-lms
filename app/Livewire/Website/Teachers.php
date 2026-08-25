<?php

namespace App\Livewire\Website;

use App\Models\Teacher;
use App\Support\PublicWebsiteData;
use Livewire\Component;
use Livewire\WithPagination;

class Teachers extends Component
{
    use WithPagination;

    public function render()
    {
        $site = app(PublicWebsiteData::class);
        $page = $site->page('teachers');
        $schoolId = $site->schoolId();
        $teachers = Teacher::query()
            ->with('subjects:id,name')
            ->where('status', 'active')
            ->where('is_featured_on_website', true)
            ->orderBy('website_display_order')
            ->when(
                $schoolId,
                fn ($query) => $query->where('school_id', $schoolId),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(12);

        return view('livewire.website.teachers', [
            'branding' => $site->branding(),
            'page' => $page,
            'teachers' => $teachers,
        ])->layout('layouts.website', $site->metadata('Teachers', $page, route('website.teachers')));
    }
}
