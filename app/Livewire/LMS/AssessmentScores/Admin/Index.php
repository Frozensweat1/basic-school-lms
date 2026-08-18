<?php
namespace App\Livewire\LMS\AssessmentScores\Admin;
use App\Livewire\LMS\AssessmentScores\Index as Shared; use App\Models\Assessment; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount(Assessment $assessment):void{$this->authorize('update',$assessment);abort_unless(auth()->user()->hasAnyRole(['super_admin','school_admin']),403);parent::mount($assessment);} }
