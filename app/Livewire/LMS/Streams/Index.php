<?php
namespace App\Livewire\LMS\Streams;
use App\Models\School; use App\Models\Stream; use Illuminate\Foundation\Auth\Access\AuthorizesRequests; use Illuminate\Validation\Rule; use Illuminate\Validation\ValidationException; use Jantinnerezo\LivewireAlert\Facades\LivewireAlert; use Livewire\Attributes\Layout; use Livewire\Component; use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    public bool $showFormModal=false,$showDeleteModal=false,$isActive=true;
    public ?int $editingId=null,$deletingId=null;
    public string $name='', $description='';
    public function mount():void{$this->authorize('viewAny',Stream::class);}
    public function create():void{$this->authorize('create',Stream::class);$this->resetForm();$this->showFormModal=true;}
    public function edit(Stream $stream):void{$this->authorize('update',$stream);$this->editingId=$stream->id;$this->name=$stream->name;$this->description=$stream->description??'';$this->isActive=$stream->is_active;$this->showFormModal=true;}
    public function save():void{$stream=$this->editingId?Stream::findOrFail($this->editingId):null;$this->authorize($stream?'update':'create',$stream??Stream::class);$schoolId=$stream?->school_id??School::query()->value('id');abort_unless($schoolId,422,'Configure a school before creating a stream.');try{$data=$this->validate(['name'=>['required','string','max:100',Rule::unique('streams','name')->where('school_id',$schoolId)->ignore($stream?->id)],'description'=>['nullable','string','max:500'],'isActive'=>['boolean']]);Stream::updateOrCreate(['id'=>$stream?->id],['school_id'=>$schoolId,'name'=>$data['name'],'description'=>$data['description']?:null,'is_active'=>$data['isActive']]);$this->showFormModal=false;$this->resetForm();LivewireAlert::title($stream?'Stream updated':'Stream created')->success()->asToast()->position('top-end')->show();}catch(ValidationException $e){LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show();throw $e;}catch(Throwable $e){report($e);LivewireAlert::title('Unable to save stream')->error()->asToast()->position('top-end')->show();}}
    public function confirmDelete(Stream $stream):void{$this->authorize('delete',$stream);$this->deletingId=$stream->id;$this->showDeleteModal=true;}
    public function delete():void{$stream=Stream::findOrFail($this->deletingId);$this->authorize('delete',$stream);if($stream->classes()->exists()){$this->addError('delete','Streams assigned to classes cannot be deleted. Archive the stream instead.');LivewireAlert::title('Stream cannot be deleted')->warning()->asToast()->position('top-end')->show();return;}try{$stream->delete();$this->showDeleteModal=false;$this->deletingId=null;LivewireAlert::title('Stream deleted')->success()->asToast()->position('top-end')->show();}catch(Throwable $e){report($e);LivewireAlert::title('Unable to delete stream')->error()->asToast()->position('top-end')->show();}}
    public function closeModals():void{$this->showFormModal=false;$this->showDeleteModal=false;$this->resetForm();$this->resetErrorBag();}
    private function resetForm():void{$this->reset(['editingId','deletingId','name','description','isActive']);$this->isActive=true;$this->resetValidation();}
    public function render(){return view('livewire.lms.streams.index',['streams'=>Stream::withCount('classes')->orderBy('name')->get()]);}
}
