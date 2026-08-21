<?php

namespace App\Livewire\LMS\Lessons\Concerns;

use App\Models\{Lesson, LessonResource, School, Teacher, Topic};
use App\Support\ContentSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\{Rule, ValidationException};
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

abstract class ManagesLessons extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $showFormModal = false, $showDeleteModal = false;
    public ?int $editingId = null, $deletingId = null;
    public string $topicId = '', $teacherId = '', $title = '', $summary = '', $content = '', $objectives = '', $sequence = '0', $status = 'draft';
    public array $resourceFiles = [];

    abstract protected function topics(): Builder;
    abstract protected function teacherIdFor(array $data, Topic $topic): int;
    abstract protected function componentView(): string;

    public function create(): void { $this->authorize('create', Lesson::class); $this->resetForm(); $this->showFormModal = true; }
    public function edit(Lesson $lesson): void { $this->authorize('update', $lesson); $this->managedTopic($lesson->topic_id); $this->editingId=$lesson->id; $this->topicId=(string)$lesson->topic_id; $this->teacherId=(string)$lesson->teacher_id; $this->title=$lesson->title; $this->summary=$lesson->summary??''; $this->content=$lesson->content??''; $this->objectives=implode(PHP_EOL,$lesson->objectives??[]); $this->sequence=(string)$lesson->sequence; $this->status=$lesson->status; $this->showFormModal=true; }
    public function save(): void
    {
        $lesson=$this->editingId ? Lesson::findOrFail($this->editingId) : null; $this->authorize($lesson ? 'update' : 'create', $lesson ?? Lesson::class);
        try {
            if ($lesson) $this->managedTopic($lesson->topic_id);
            $data=$this->validate(['topicId'=>['required','integer',Rule::exists('topics','id')],'teacherId'=>['nullable','integer',Rule::exists('teachers','id')],'title'=>['required','string','max:255'],'summary'=>['nullable','string','max:2000'],'content'=>['nullable','string','max:50000'],'objectives'=>['nullable','string','max:5000'],'sequence'=>['required','integer','min:0','max:9999'],'status'=>['required',Rule::in(['draft','published','archived'])],'resourceFiles'=>['array'],'resourceFiles.*'=>['file','max:25600','mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip,mp4']]);
            $topic=$this->managedTopic((int)$data['topicId']); $teacherId=$this->teacherIdFor($data,$topic);
            abort_unless(Teacher::whereKey($teacherId)->where('school_id',$this->schoolId())->exists(),422,'Choose a teacher belonging to this school.');
            $duplicate=Lesson::where('topic_id',$topic->id)->whereRaw('lower(title)=?',[mb_strtolower($data['title'])])->when($lesson,fn($query)=>$query->whereKeyNot($lesson->id))->exists();
            if($duplicate){$this->addError('title','This lesson already exists under the selected topic.');return;}
            $savedLesson=Lesson::updateOrCreate(['id'=>$lesson?->id],['topic_id'=>$topic->id,'teacher_id'=>$teacherId,'title'=>$data['title'],'summary'=>$data['summary']?:null,'content'=>app(ContentSanitizer::class)->clean($data['content']?:''),'objectives'=>collect(preg_split('/\r\n|\r|\n/',$data['objectives']?:''))->map(fn($item)=>trim($item))->filter()->values()->all()?:null,'sequence'=>$data['sequence'],'status'=>$data['status'],'published_at'=>$data['status']==='published'?($lesson?->published_at??now()):null]);
            foreach ($data['resourceFiles'] ?? [] as $file) {
                $path = $file->store('lessons/resources/'.$savedLesson->id, 'local');
                LessonResource::create(['lesson_id'=>$savedLesson->id,'title'=>$file->getClientOriginalName(),'type'=>$file->getClientOriginalExtension(),'disk'=>'local','path'=>$path,'size'=>$file->getSize(),'uploaded_by'=>auth()->id()]);
            }
            $this->showFormModal=false; $this->resetForm(); LivewireAlert::title($lesson?'Lesson updated':'Lesson created')->success()->asToast()->position('top-end')->show();
        } catch(ValidationException $exception) { LivewireAlert::title('Check the form')->error()->asToast()->position('top-end')->show(); throw $exception; } catch(Throwable $exception) { report($exception); LivewireAlert::title('Unable to save lesson')->error()->asToast()->position('top-end')->show(); }
    }
    public function confirmDelete(Lesson $lesson): void { $this->authorize('delete',$lesson); $this->managedTopic($lesson->topic_id); $this->deletingId=$lesson->id; $this->showDeleteModal=true; }
    public function delete(): void { $lesson=Lesson::findOrFail($this->deletingId); $this->authorize('delete',$lesson); $this->managedTopic($lesson->topic_id); try{$lesson->delete();$this->showDeleteModal=false;$this->deletingId=null;LivewireAlert::title('Lesson archived')->success()->asToast()->position('top-end')->show();}catch(Throwable $exception){report($exception);LivewireAlert::title('Unable to archive lesson')->error()->asToast()->position('top-end')->show();} }
    public function closeModals(): void { $this->showFormModal=false;$this->showDeleteModal=false;$this->resetForm();$this->resetErrorBag(); }
    public function render() { $topics=$this->topics()->get(); return view($this->componentView(),['lessons'=>Lesson::with(['topic.classSubject.schoolClass','topic.classSubject.subject','teacher'])->whereIn('topic_id',$topics->pluck('id'))->orderBy('sequence')->get(),'topics'=>$topics,'teachers'=>Teacher::where('school_id',$this->schoolId())->where('status','active')->orderBy('last_name')->get()]); }
    protected function schoolId(): int { return (int)School::query()->value('id'); }
    protected function managedTopic(int $topicId): Topic { return $this->topics()->whereKey($topicId)->firstOrFail(); }
    private function resetForm(): void { $this->reset(['editingId','deletingId','topicId','teacherId','title','summary','content','objectives','sequence','status','resourceFiles']);$this->sequence='0';$this->status='draft';$this->resetValidation(); }
}
