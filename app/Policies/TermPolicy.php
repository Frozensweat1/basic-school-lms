<?php

namespace App\Policies;

use App\Models\Term;
use App\Models\User;

class TermPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super_admin', 'school_admin']); }
    public function view(User $user, Term $term): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, Term $term): bool { return $this->viewAny($user); }
    public function delete(User $user, Term $term): bool { return $this->viewAny($user); }
}
