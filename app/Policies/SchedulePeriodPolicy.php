<?php

namespace App\Policies;

use App\Models\SchedulePeriod;
use App\Models\School;
use App\Models\User;

class SchedulePeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, SchedulePeriod $period): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentSchool($period);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, SchedulePeriod $period): bool
    {
        return $this->create($user) && $this->belongsToCurrentSchool($period);
    }

    public function delete(User $user, SchedulePeriod $period): bool
    {
        return $this->update($user, $period);
    }

    private function belongsToCurrentSchool(SchedulePeriod $period): bool
    {
        return (int) $period->school_id === (int) School::query()->value('id');
    }
}
