<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super_admin', 'school_admin']); }
    public function view(User $user, User $managedUser): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, User $managedUser): bool { return $this->viewAny($user); }
    public function delete(User $user, User $managedUser): bool { return $this->viewAny($user) && $user->id !== $managedUser->id; }
}
