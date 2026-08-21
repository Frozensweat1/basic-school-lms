<?php
namespace App\Livewire\LMS\Website\Events;

use App\Models\WebsiteEvent;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.lms')]
class Index extends Component
{
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $title = '', $description = '', $startsAt = '', $endsAt = '', $location = '';
    public bool $isPublished = true;

    public function mount(): void { $this->guard(); }
    private function guard(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403); }
    public function create(): void { $this->guard(); $this->resetForm(); $this->showFormModal = true; }
    public function edit(WebsiteEvent $event): void { $this->guard(); $this->editingId=$event->id; $this->title=$event->title; $this->description=$event->description ?? ''; $this->startsAt=$event->starts_at?->format('Y-m-d\TH:i') ?? ''; $this->endsAt=$event->ends_at?->format('Y-m-d\TH:i') ?? ''; $this->location=$event->location ?? ''; $this->isPublished=$event->is_published; $this->showFormModal=true; }
    public function save(): void
    {
        $this->guard();
        $data=$this->validate(['title'=>['required','string','max:255'],'description'=>['nullable','string','max:5000'],'startsAt'=>['required','date'],'endsAt'=>['nullable','date','after_or_equal:startsAt'],'location'=>['nullable','string','max:255'],'isPublished'=>['boolean']]);
        $event=$this->editingId ? WebsiteEvent::findOrFail($this->editingId) : new WebsiteEvent(['created_by'=>auth()->id()]);
        $event->fill(['title'=>$data['title'],'slug'=>Str::slug($data['title']).'-'.Str::lower(Str::random(4)),'description'=>$data['description'],'starts_at'=>$data['startsAt'],'ends_at'=>$data['endsAt'] ?: null,'location'=>$data['location'],'is_published'=>$data['isPublished']]);
        $event->save(); $this->showFormModal=false; $this->resetForm(); LivewireAlert::title('Event saved')->success()->asToast()->show();
    }
    public function delete(int $id): void { $this->guard(); WebsiteEvent::findOrFail($id)->delete(); LivewireAlert::title('Event deleted')->success()->asToast()->show(); }
    public function closeModal(): void { $this->showFormModal=false; $this->resetForm(); }
    private function resetForm(): void { $this->reset(['editingId','title','description','startsAt','endsAt','location']); $this->isPublished=true; $this->resetValidation(); }
    public function render() { return view('livewire.lms.website.events.index', ['events'=>WebsiteEvent::latest('starts_at')->get()]); }
}
