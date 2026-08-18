<?php

namespace App\Policies;

use App\Models\ClassSubject;
use App\Models\User;

class ClassSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, ClassSubject $classSubject): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, ClassSubject $classSubject): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ClassSubject $classSubject): bool
    {
        return $this->create($user);
    }
}
