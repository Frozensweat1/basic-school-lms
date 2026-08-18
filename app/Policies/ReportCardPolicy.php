<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\User;

class ReportCardPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super_admin', 'school_admin']); }
    public function view(User $user, ReportCard $reportCard): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, ReportCard $reportCard): bool { return $this->viewAny($user); }
}
