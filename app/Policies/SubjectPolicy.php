<?php

namespace App\Policies;

use App\Models\Subject;
use App\Models\User;

class SubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student']);
    }

    public function view(User $user, Subject $subject): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, Subject $subject): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function delete(User $user, Subject $subject): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
