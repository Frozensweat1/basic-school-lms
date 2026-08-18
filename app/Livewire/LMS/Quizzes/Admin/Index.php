<?php
namespace App\Livewire\LMS\Quizzes\Admin;
use App\Livewire\LMS\Quizzes\Index as Shared; use App\Models\Quiz; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount():void{$this->authorize('viewAny',Quiz::class);abort_unless(auth()->user()->hasAnyRole(['super_admin','school_admin']),403);} }
