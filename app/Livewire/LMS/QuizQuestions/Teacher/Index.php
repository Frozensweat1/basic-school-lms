<?php
namespace App\Livewire\LMS\QuizQuestions\Teacher;
use App\Livewire\LMS\QuizQuestions\Index as Shared; use App\Models\Quiz; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount(Quiz $quiz):void{$this->authorize('update',$quiz);abort_unless(auth()->user()->hasRole('teacher')&&$quiz->teacher_id===auth()->user()->teacher?->id,403);$this->quiz=$quiz;} }
