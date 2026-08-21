<?php

namespace App\Policies;

use App\Models\School;
use App\Models\Timetable;
use App\Models\User;

class TimetablePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, Timetable $timetable): bool
    {
        return $this->sameSchool($timetable) && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin']);
    }

    public function update(User $user, Timetable $timetable): bool
    {
        return $this->sameSchool($timetable) && $this->create($user);
    }

    public function delete(User $user, Timetable $timetable): bool
    {
        return $this->update($user, $timetable);
    }

    private function sameSchool(Timetable $timetable): bool
    {
        return (int) $timetable->academicYear?->school_id === (int) School::query()->value('id');
    }
}
