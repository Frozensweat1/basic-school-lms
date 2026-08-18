<?php
namespace App\Livewire\LMS\Timetables\Teacher;
use App\Livewire\LMS\Timetables\Index as Shared; use App\Models\Timetable; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount():void{$this->authorize('viewAny',Timetable::class);abort_unless(auth()->user()->hasRole('teacher'),403);} }
