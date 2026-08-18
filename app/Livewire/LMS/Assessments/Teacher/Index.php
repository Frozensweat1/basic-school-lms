<?php
namespace App\Livewire\LMS\Assessments\Teacher;
use App\Livewire\LMS\Assessments\Index as Shared; use App\Models\Assessment; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount():void{$this->authorize('viewAny',Assessment::class);abort_unless(auth()->user()->hasRole('teacher')&&auth()->user()->teacher,403);} }
