<?php
namespace App\Livewire\LMS\AssessmentScores\Teacher;
use App\Livewire\LMS\AssessmentScores\Index as Shared; use App\Models\Assessment; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount(Assessment $assessment):void{$this->authorize('update',$assessment);abort_unless(auth()->user()->hasRole('teacher')&&$assessment->teacher_id===auth()->user()->teacher?->id,403);parent::mount($assessment);} }
