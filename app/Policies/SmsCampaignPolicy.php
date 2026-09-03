<?php

namespace App\Policies;

use App\Models\SmsCampaign;
use App\Models\School;
use App\Models\User;

class SmsCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }
    public function view(User $user, SmsCampaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
    public function update(User $user, SmsCampaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }
    public function delete(User $user, SmsCampaign $campaign): bool
    {
        return $this->canManage($user, $campaign);
    }

    private function canManage(User $user, SmsCampaign $campaign): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('school_admin')
            && (int) $campaign->school_id === (int) School::query()->value('id');
    }
}
