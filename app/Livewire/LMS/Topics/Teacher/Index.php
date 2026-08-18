<?php
namespace App\Livewire\LMS\Topics\Teacher;
use App\Livewire\LMS\Topics\Concerns\ManagesTopics; use App\Models\{ClassSubject,Topic}; use Illuminate\Database\Eloquent\Builder; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends ManagesTopics {public function mount():void{$this->authorize('viewAny',Topic::class);abort_unless(auth()->user()->hasRole('teacher')&&auth()->user()->teacher,403);}protected function classSubjects():Builder{return ClassSubject::with(['schoolClass.academicYear','subject'])->where('teacher_id',auth()->user()->teacher->id)->whereHas('schoolClass.academicYear',fn(Builder $q)=>$q->where('school_id',$this->schoolId()))->orderBy('school_class_id');}protected function componentView():string{return 'livewire.lms.topics.teacher.index';}}
