<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }
}
