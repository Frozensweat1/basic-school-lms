<?php
namespace App\Livewire\LMS\QuizQuestions\Admin;
use App\Livewire\LMS\QuizQuestions\Index as Shared; use App\Models\Quiz; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount(Quiz $quiz):void{$this->authorize('update',$quiz);abort_unless(auth()->user()->hasAnyRole(['super_admin','school_admin']),403);$this->quiz=$quiz;} }
