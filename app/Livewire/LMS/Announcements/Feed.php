<?php
namespace App\Livewire\LMS\Announcements;
use App\Models\Announcement; use Illuminate\Foundation\Auth\Access\AuthorizesRequests; use Livewire\Attributes\Layout;
#[Layout('layouts.lms')] class Feed extends Index { public function mount():void{$this->authorize('viewAny',Announcement::class);abort_unless(auth()->user()->hasAnyRole(['student','parent']),403);} }
