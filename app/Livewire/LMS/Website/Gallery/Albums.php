<?php
namespace App\Livewire\LMS\Website\Gallery;

use App\Models\{WebsiteGalleryAlbum, WebsiteGalleryImage};
use Illuminate\Support\Facades\Storage;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.lms')]
class Albums extends Component
{
    use WithFileUploads;
    public string $title='', $description=''; public $images=[]; public bool $showFormModal=false; public ?int $editingId=null;
    public function mount(): void { $this->guard(); }
    private function guard(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'),403); }
    public function create(): void { $this->guard(); $this->reset(['editingId','title','description','images']); $this->showFormModal=true; }
    public function edit(WebsiteGalleryAlbum $album): void { $this->guard(); $this->editingId=$album->id; $this->title=$album->title; $this->description=$album->description ?? ''; $this->showFormModal=true; }
    public function save(): void { $this->guard(); $data=$this->validate(['title'=>['required','string','max:255'],'description'=>['nullable','string','max:1000'],'images.*'=>['nullable','image','max:4096']]); $album=$this->editingId?WebsiteGalleryAlbum::findOrFail($this->editingId):WebsiteGalleryAlbum::create(['title'=>$data['title'],'description'=>$data['description']]); if($this->editingId)$album->update(['title'=>$data['title'],'description'=>$data['description']]); foreach(($this->images??[]) as $index=>$image) WebsiteGalleryImage::create(['album_id'=>$album->id,'path'=>$image->store('website/gallery','public'),'caption'=>$album->title.' image','sort_order'=>$album->images()->count()+$index]); $this->showFormModal=false; $this->reset(['editingId','title','description','images']); LivewireAlert::title('Gallery saved')->success()->asToast()->show(); }
    public function deleteAlbum(int $id): void { $this->guard(); $album=WebsiteGalleryAlbum::with('images')->findOrFail($id); foreach($album->images as $image)if($image->path)Storage::disk('public')->delete($image->path); $album->delete(); LivewireAlert::title('Album deleted')->success()->asToast()->show(); }
    public function closeModal(): void { $this->showFormModal=false; $this->reset(['editingId','title','description','images']); }
    public function render(){return view('livewire.lms.website.gallery.albums',['albums'=>WebsiteGalleryAlbum::with('images')->latest()->get()]);}
}
