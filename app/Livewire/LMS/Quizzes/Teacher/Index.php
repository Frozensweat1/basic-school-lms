<?php
namespace App\Livewire\LMS\Quizzes\Teacher;
use App\Livewire\LMS\Quizzes\Index as Shared; use App\Models\Quiz; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount():void{$this->authorize('viewAny',Quiz::class);abort_unless(auth()->user()->hasRole('teacher')&&auth()->user()->teacher,403);} }
