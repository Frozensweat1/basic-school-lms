<?php

namespace App\Policies;

use App\Models\User;
use App\Models\School;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $this->canManage($user, $managedUser);
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $managedUser->id !== $user->id && $this->canManage($user, $managedUser);
    }

    private function canManage(User $user, User $managedUser): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->hasRole('school_admin') || $managedUser->hasRole('super_admin')) {
            return false;
        }

        $schoolId = (int) School::query()->value('id');
        $profileSchoolIds = collect([$managedUser->student?->school_id, $managedUser->teacher?->school_id, $managedUser->parentGuardian?->school_id])->filter();

        return $profileSchoolIds->isEmpty() || $profileSchoolIds->every(fn ($profileSchoolId) => (int) $profileSchoolId === $schoolId);
    }
}
