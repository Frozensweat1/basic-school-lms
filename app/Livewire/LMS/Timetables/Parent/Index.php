<?php
namespace App\Livewire\LMS\Timetables\Parent;
use App\Models\{ParentGuardian,TimetableEntry}; use Livewire\Attributes\Layout; use Livewire\Component;
#[Layout('layouts.lms')]
class Index extends Component {
    public ParentGuardian $parent;
    public string $studentId='';
    public function mount():void{
        $this->parent=auth()->user()->parentGuardian;
        abort_unless(auth()->user()->hasRole('parent')&&$this->parent,403);
        $this->studentId=(string)($this->parent->students()->value('students.id')??'');
        }
        public function render()
        {
            $students=$this->parent->students()->where('status','active')->orderBy('last_name')->get();
            $student=$students->firstWhere('id',(int)$this->studentId);
            $classIds=$student?$student->enrollments()->where('status','active')->pluck('school_class_id'):collect();
            $entries=TimetableEntry::whereIn('school_class_id',$classIds)->whereHas('timetable',fn($q)=>$q->where('status','published'))->with(['classSubject.subject','teacher','schedulePeriod'])->orderBy('day_of_week')->get();
            return view('livewire.lms.timetables.parent.index',compact('students','entries'));
            }
            }
