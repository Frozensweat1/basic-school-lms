<?php
namespace App\Livewire\LMS\Terms;
use App\Models\AcademicYear; use App\Models\Term; use Illuminate\Foundation\Auth\Access\AuthorizesRequests; use Illuminate\Support\Facades\DB; use Illuminate\Validation\Rule; use Illuminate\Validation\ValidationException; use Jantinnerezo\LivewireAlert\Facades\LivewireAlert; use Livewire\Attributes\Layout; use Livewire\Component; use Throwable;

#[Layout('layouts.lms')]
class Index extends Component
{
    use AuthorizesRequests;
    public bool $showFormModal=false, $showDeleteModal=false, $isActive=false, $isLocked=false;
    public ?int $editingId=null, $deletingId=null;
    public string $academicYearId='', $name='', $sequence='1', $startsAt='', $endsAt='';
    public function mount():void { $this->authorize('viewAny', Term::class); }
    public function create():void { $this->authorize('create', Term::class); $this->resetForm(); $this->academicYearId=(string)AcademicYear::where('is_active',true)->value('id'); $this->showFormModal=true; }
    public function edit(Term $term):void { $this->authorize('update',$term); $this->editingId=$term->id; $this->academicYearId=(string)$term->academic_year_id; $this->name=$term->name; $this->sequence=(string)$term->sequence; $this->startsAt=$term->starts_at->toDateString(); $this->endsAt=$term->ends_at->toDateString(); $this->isActive=$term->is_active; $this->isLocked=$term->is_locked; $this->resetValidation(); $this->showFormModal=true; }
    public function save():void {
        $term=$this->editingId?Term::findOrFail($this->editingId):null; $this->authorize($term?'update':'create',$term??Term::class);
        try {
            $data=$this->validate(['academicYearId'=>['required','integer',Rule::exists('academic_years','id')],'name'=>['required','string','max:100',Rule::unique('terms','name')->where('academic_year_id',$this->academicYearId)->ignore($term?->id)],'sequence'=>['required','integer','min:1','max:20'],'startsAt'=>['required','date','before:endsAt'],'endsAt'=>['required','date','after:startsAt'],'isActive'=>['boolean'],'isLocked'=>['boolean']]);
            $year=AcademicYear::findOrFail($data['academicYearId']);
            if($data['startsAt']<$year->starts_at->toDateString() || $data['endsAt']>$year->ends_at->toDateString()) throw ValidationException::withMessages(['startsAt'=>'Term dates must fall within the selected academic year.']);
            if($data['isActive'] && !$year->is_active) throw ValidationException::withMessages(['isActive'=>'Activate the academic year before activating one of its terms.']);
            DB::transaction(function()use($term,$data):void { if($data['isActive']) Term::where('academic_year_id',$data['academicYearId'])->whereKeyNot($term?->id)->update(['is_active'=>false]); Term::updateOrCreate(['id'=>$term?->id],['academic_year_id'=>$data['academicYearId'],'name'=>$data['name'],'sequence'=>$data['sequence'],'starts_at'=>$data['startsAt'],'ends_at'=>$data['endsAt'],'is_active'=>$data['isActive'],'is_locked'=>$data['isLocked']]); });
            $this->showFormModal=false; $this->resetForm(); LivewireAlert::title($term?'Term updated':'Term created')->success()->asToast()->position('top-end')->show();
        } catch(ValidationException $e) { LivewireAlert::title('Check the form')->text('Correct the highlighted fields and try again.')->error()->asToast()->position('top-end')->show(); throw $e; } catch(Throwable $e) { report($e); LivewireAlert::title('Unable to save term')->text('Please try again.')->error()->asToast()->position('top-end')->show(); }
    }
    public function confirmDelete(Term $term):void { $this->authorize('delete',$term); $this->deletingId=$term->id; $this->showDeleteModal=true; }
    public function delete():void { $term=Term::findOrFail($this->deletingId); $this->authorize('delete',$term); if($term->is_active||$term->assessments()->exists()||$term->attendanceRecords()->exists()){ $this->addError('delete','Only an inactive term without assessments or attendance records can be deleted.'); LivewireAlert::title('Term cannot be deleted')->warning()->asToast()->position('top-end')->show(); return;} try{$term->delete();$this->showDeleteModal=false;$this->deletingId=null;LivewireAlert::title('Term deleted')->success()->asToast()->position('top-end')->show();}catch(Throwable $e){report($e);LivewireAlert::title('Unable to delete term')->text('Please try again.')->error()->asToast()->position('top-end')->show();}}
    public function closeModals():void{$this->showFormModal=false;$this->showDeleteModal=false;$this->resetForm();$this->resetErrorBag();}
    private function resetForm():void{$this->reset(['editingId','deletingId','academicYearId','name','sequence','startsAt','endsAt','isActive','isLocked']);$this->sequence='1';$this->resetValidation();}
    public function render(){return view('livewire.lms.terms.index',['terms'=>Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('sequence')->get(),'years'=>AcademicYear::orderByDesc('starts_at')->get()]);}
}
