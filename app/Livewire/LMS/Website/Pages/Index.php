<?php
namespace App\Livewire\LMS\Website\Pages;

use App\Models\WebsitePage;
use App\Support\ContentSanitizer;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public ?int $editingId=null; public string $slug='', $heroTitle='', $heroSubtitle='', $content='';
    public function mount(): void { $this->guard(); }
    private function guard(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'),403); }
    public function edit(WebsitePage $page): void { $this->guard(); $this->editingId=$page->id; $this->slug=$page->slug; $this->heroTitle=$page->hero_title ?? ''; $this->heroSubtitle=$page->hero_subtitle ?? ''; $this->content=$page->content['body'] ?? ''; }
    public function save(): void { $this->guard(); $data=$this->validate(['heroTitle'=>['nullable','string','max:255'],'heroSubtitle'=>['nullable','string','max:1000'],'content'=>['nullable','string','max:50000']]); $page=WebsitePage::findOrFail($this->editingId); $page->update(['hero_title'=>$data['heroTitle'],'hero_subtitle'=>$data['heroSubtitle'],'content'=>['body'=>app(ContentSanitizer::class)->clean($data['content'])],'updated_by'=>auth()->id()]); LivewireAlert::title('Page updated')->success()->asToast()->show(); }
    public function render() { return view('livewire.lms.website.pages.index',['pages'=>WebsitePage::orderBy('slug')->get()]); }
}
