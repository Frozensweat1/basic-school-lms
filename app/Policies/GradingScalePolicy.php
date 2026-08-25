<?php

namespace App\Policies;

use App\Models\GradingScale;
use App\Models\School;
use App\Models\User;

class GradingScalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, GradingScale $scale): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentSchool($scale);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, GradingScale $scale): bool
    {
        return $this->create($user) && $this->belongsToCurrentSchool($scale);
    }

    public function delete(User $user, GradingScale $scale): bool
    {
        return $this->update($user, $scale);
    }

    private function belongsToCurrentSchool(GradingScale $scale): bool
    {
        return (int) $scale->school_id === (int) School::query()->value('id');
    }
}
