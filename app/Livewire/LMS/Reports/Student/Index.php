<?php
namespace App\Livewire\LMS\Reports\Student;
use App\Models\{ReportCard,Student}; use Livewire\Attributes\Layout; use Livewire\Component;
#[Layout('layouts.lms')] class Index extends Component { public Student $student; public function mount():void{$this->student=auth()->user()->student;abort_unless(auth()->user()->hasRole('student')&&$this->student,403);} public function render(){return view('livewire.lms.reports.student.index',['reports'=>ReportCard::with(['term','academicYear','schoolClass'])->where('student_id',$this->student->id)->where('status','published')->latest('published_at')->get()]);}}
