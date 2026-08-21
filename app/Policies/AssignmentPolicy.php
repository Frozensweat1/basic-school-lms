<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\School;
use App\Models\User;

class AssignmentPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']); }
    public function view(User $user, Assignment $assignment): bool { return $this->viewAny($user) && $this->sameSchool($assignment); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, Assignment $assignment): bool { return $this->sameSchool($assignment) && ($user->hasAnyRole(['super_admin', 'school_admin']) || ($user->hasRole('teacher') && $assignment->teacher_id === $user->teacher?->id)); }
    public function delete(User $user, Assignment $assignment): bool { return $this->update($user, $assignment); }

    private function sameSchool(Assignment $assignment): bool
    {
        return (int) $assignment->classSubject?->schoolClass?->academicYear?->school_id === (int) School::query()->value('id');
    }
}
