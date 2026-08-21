<?php
namespace App\Livewire\LMS\Website\News;
use App\Models\WebsiteNewsPost; use App\Support\ContentSanitizer; use Illuminate\Support\Str; use Illuminate\Validation\ValidationException; use Jantinnerezo\LivewireAlert\Facades\LivewireAlert; use Livewire\Attributes\Layout; use Livewire\Component; use Throwable;
#[Layout('layouts.lms')]
class Index extends Component {
 public bool $showFormModal=false,$showDeleteModal=false; public ?int $editingId=null,$deletingId=null; public string $title='',$excerpt='',$body='',$publishedAt='';
 public function mount():void{$this->authorizeWebsite();} private function authorizeWebsite():void{abort_unless(auth()->user()->hasPermissionTo('manage website content'),403);}
 public function create():void{$this->authorizeWebsite();$this->resetForm();$this->showFormModal=true;}
 public function edit(WebsiteNewsPost $post):void{$this->authorizeWebsite();$this->editingId=$post->id;$this->title=$post->title;$this->excerpt=$post->excerpt??'';$this->body=$post->body;$this->publishedAt=$post->published_at?->format('Y-m-d\TH:i')??'';$this->showFormModal=true;}
 public function save():void{$this->authorizeWebsite();try{$data=$this->validate(['title'=>['required','string','max:255'],'excerpt'=>['nullable','string','max:500'],'body'=>['required','string','max:50000'],'publishedAt'=>['nullable','date']]);$post=$this->editingId?WebsiteNewsPost::findOrFail($this->editingId):null;WebsiteNewsPost::updateOrCreate(['id'=>$post?->id],['title'=>$data['title'],'slug'=>Str::slug($data['title']).($post?'':'-'.Str::random(4)),'excerpt'=>$data['excerpt'],'body'=>app(ContentSanitizer::class)->clean($data['body']),'published_at'=>$data['publishedAt']?:now(),'created_by'=>$post?->created_by??auth()->id()]);$this->showFormModal=false;$this->resetForm();LivewireAlert::title($post?'News updated':'News published')->success()->asToast()->show();}catch(ValidationException $e){throw $e;}catch(Throwable $e){report($e);LivewireAlert::title('Unable to save news')->error()->asToast()->show();}}
 public function confirmDelete(WebsiteNewsPost $post):void{$this->authorizeWebsite();$this->deletingId=$post->id;$this->showDeleteModal=true;} public function delete():void{$this->authorizeWebsite();WebsiteNewsPost::findOrFail($this->deletingId)->delete();$this->showDeleteModal=false;$this->deletingId=null;LivewireAlert::title('News archived')->success()->asToast()->show();}
 public function closeModals():void{$this->showFormModal=false;$this->showDeleteModal=false;$this->resetForm();} private function resetForm():void{$this->reset(['editingId','deletingId','title','excerpt','body','publishedAt']);$this->resetValidation();}
 public function render(){return view('livewire.lms.website.news.index',['posts'=>WebsiteNewsPost::latest()->get()]);}
}
