<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin', 'teacher']);
    }

    public function view(User $user, Topic $topic): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->hasAnyRole(['super_admin', 'school_admin'])
            || ($user->hasRole('teacher') && $topic->classSubject->teacher_id === $user->teacher?->id);
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $this->update($user, $topic);
    }
}
