<?php

namespace App\Policies;

use App\Models\EmailCampaign;
use App\Models\School;
use App\Models\User;

class EmailCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function view(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->canManage($user, $emailCampaign);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->canManage($user, $emailCampaign);
    }

    public function delete(User $user, EmailCampaign $emailCampaign): bool
    {
        return $this->canManage($user, $emailCampaign);
    }

    private function canManage(User $user, EmailCampaign $emailCampaign): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('school_admin')
            && (int) $emailCampaign->school_id === (int) School::query()->value('id');
    }
}
