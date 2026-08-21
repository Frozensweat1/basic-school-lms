<?php

namespace App\Policies;

use App\Models\Examination;
use App\Models\School;
use App\Models\User;

class ExaminationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, Examination $exam): bool
    {
        if (! $this->sameSchool($exam)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'school_admin'])
            || ($user->hasRole('teacher') && (int) $exam->teacher_id === (int) $user->teacher?->id);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function update(User $user, Examination $exam): bool
    {
        return $this->view($user, $exam);
    }

    public function delete(User $user, Examination $exam): bool
    {
        return $this->update($user, $exam);
    }

    private function sameSchool(Examination $exam): bool
    {
        return (int) $exam->school_id === (int) School::query()->value('id');
    }
}
