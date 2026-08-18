<?php
namespace App\Livewire\LMS\Reports;
use App\Models\{ReportCard,SubjectResult}; use Illuminate\Foundation\Auth\Access\AuthorizesRequests; use Livewire\Attributes\Layout; use Livewire\Component;
#[Layout('layouts.lms')]
class Show extends Component { use AuthorizesRequests; public ReportCard $reportCard; public function mount(ReportCard $reportCard):void{$this->authorize('view',$reportCard);$this->reportCard=$reportCard;} public function render(){return view('livewire.lms.reports.show',['results'=>SubjectResult::with(['classSubject.subject','gradingScale'])->where('student_id',$this->reportCard->student_id)->where('term_id',$this->reportCard->term_id)->get()]);} }
