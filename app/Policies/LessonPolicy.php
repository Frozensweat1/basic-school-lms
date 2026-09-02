<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\School;
use App\Models\Student;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->hasRole('student')) {
            $student = $user->student;

            return $student instanceof Student
                && Lesson::query()
                    ->publishedForStudent($student)
                    ->whereKey($lesson->id)
                    ->exists();
        }

        return $this->viewAny($user) && $this->sameSchool($user, $lesson);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $this->sameSchool($user, $lesson)
            && ($user->hasAnyRole(['super_admin', 'school_admin'])
                || ($user->hasRole('teacher') && $lesson->teacher_id === $user->teacher?->id));
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }

    private function sameSchool(User $user, Lesson $lesson): bool
    {
        $lessonSchoolId = (int) $lesson->topic?->classSubject?->schoolClass?->academicYear?->school_id;
        $userSchoolId = (int) ($user->student?->school_id
            ?? $user->teacher?->school_id
            ?? $user->parentGuardian?->school_id
            ?? School::query()->value('id'));

        return $lessonSchoolId > 0 && $lessonSchoolId === $userSchoolId;
    }
}
