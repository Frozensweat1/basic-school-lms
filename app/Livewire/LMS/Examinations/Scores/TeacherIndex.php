<?php

namespace App\Livewire\LMS\Examinations\Scores;

use App\Models\Examination;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.lms')]
class TeacherIndex extends Index
{
    public function mount(Examination $examination): void
    {
        $user = Auth::user();
        abort_unless(
            $user instanceof User
            && $user->hasRole('teacher')
            && $user->teacher
            && (int) $examination->teacher_id === (int) $user->teacher->id,
            403,
        );
        parent::mount($examination);
    }
}
