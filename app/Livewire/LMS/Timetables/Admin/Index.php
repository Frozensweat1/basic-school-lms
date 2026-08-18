<?php
namespace App\Livewire\LMS\Timetables\Admin;
use App\Livewire\LMS\Timetables\Index as Shared; use App\Models\Timetable; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Index extends Shared { public function mount():void{$this->authorize('viewAny',Timetable::class);abort_unless(auth()->user()->hasAnyRole(['super_admin','school_admin']),403);} }
