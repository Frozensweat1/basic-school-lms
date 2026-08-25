<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\School;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $this->sameSchool($user, $announcement) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function update(User $user, Announcement $announcement): bool
    {
        if (! $this->sameSchool($user, $announcement)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'school_admin'])
            || ($user->hasRole('teacher') && (int) $announcement->created_by === (int) $user->id && $announcement->audience !== 'school');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $this->update($user, $announcement);
    }

    private function sameSchool(User $user, Announcement $announcement): bool
    {
        $schoolId = $user->student?->school_id
            ?? $user->teacher?->school_id
            ?? $user->parentGuardian?->school_id
            ?? School::query()->value('id');

        return (int) $announcement->school_id === (int) $schoolId;
    }
}
