<?php

namespace App\Policies;

use App\Models\ReportCard;
use App\Models\School;
use App\Models\User;

class ReportCardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function view(User $user, ReportCard $reportCard): bool
    {
        if ($this->viewAny($user)) {
            return $user->hasRole('super_admin') || $this->belongsToCurrentSchool($reportCard);
        }

        if (! $reportCard->isPublished()) {
            return false;
        }

        if ($user->hasRole('student')) {
            return (int) $reportCard->student_id === (int) $user->student?->id
                && (int) $reportCard->student?->school_id === (int) $user->student?->school_id;
        }

        return $user->hasRole('parent')
            && (int) $user->parentGuardian?->school_id === (int) $reportCard->student?->school_id
            && $user->parentGuardian?->students()->whereKey($reportCard->student_id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ReportCard $reportCard): bool
    {
        return $this->viewAny($user)
            && ($user->hasRole('super_admin') || $this->belongsToCurrentSchool($reportCard));
    }

    private function belongsToCurrentSchool(ReportCard $reportCard): bool
    {
        return (int) $reportCard->student?->school_id === (int) School::query()->value('id');
    }
}
