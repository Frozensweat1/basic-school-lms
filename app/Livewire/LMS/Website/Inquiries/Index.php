<?php
namespace App\Livewire\LMS\Website\Inquiries;

use App\Models\WebsiteInquiry;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.lms')]
class Index extends Component
{
    use WithPagination;

    public function mount(): void { $this->guard(); }
    private function guard(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'),403); }
    public function markRead(int $id): void { $this->guard(); WebsiteInquiry::findOrFail($id)->update(['is_read'=>true]); LivewireAlert::title('Inquiry marked as read')->success()->asToast()->show(); }
    public function delete(int $id): void { $this->guard(); WebsiteInquiry::findOrFail($id)->delete(); LivewireAlert::title('Inquiry deleted')->success()->asToast()->show(); }
    public function render() { return view('livewire.lms.website.inquiries.index',['inquiries'=>WebsiteInquiry::latest()->paginate(15)]); }
}
