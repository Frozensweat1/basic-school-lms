<?php
namespace App\Livewire\LMS\Website\Teachers;

use App\Models\Teacher;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public function mount(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'),403); }
    public function toggle(int $id): void { $teacher=Teacher::findOrFail($id); $teacher->update(['is_featured_on_website'=>!$teacher->is_featured_on_website]); LivewireAlert::title('Teacher spotlight updated')->success()->asToast()->show(); }
    public function render() { return view('livewire.lms.website.teachers.index',['teachers'=>Teacher::with('user')->where('status','active')->orderBy('website_display_order')->orderBy('last_name')->get()]); }
}
