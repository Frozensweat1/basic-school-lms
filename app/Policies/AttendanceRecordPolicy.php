<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\School;
use App\Models\User;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if (! $this->belongsToCurrentSchool($record)) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'school_admin'])
            || ($user->hasRole('teacher') && $user->teacher
                && $record->schoolClass?->classSubjects()->where('teacher_id', $user->teacher->id)->exists());
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        return $this->view($user, $record);
    }

    private function belongsToCurrentSchool(AttendanceRecord $record): bool
    {
        return (int) $record->schoolClass?->academicYear?->school_id === (int) School::query()->value('id');
    }
}
