<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'parent']);
    }

    public function view(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'parent']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
