<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\School;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']); }
    public function view(User $user, Lesson $lesson): bool { return $this->viewAny($user) && $this->sameSchool($lesson); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, Lesson $lesson): bool { return $this->sameSchool($lesson) && ($user->hasAnyRole(['super_admin', 'school_admin']) || ($user->hasRole('teacher') && $lesson->teacher_id === $user->teacher?->id)); }
    public function delete(User $user, Lesson $lesson): bool { return $this->update($user, $lesson); }

    private function sameSchool(Lesson $lesson): bool
    {
        return (int) $lesson->topic?->classSubject?->schoolClass?->academicYear?->school_id === (int) School::query()->value('id');
    }
}
