<?php

namespace App\Policies;

use App\Models\SchoolClass;
use App\Models\User;

class SchoolClassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
    }

    public function view(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function delete(User $user, SchoolClass $schoolClass): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
