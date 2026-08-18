<?php
namespace App\Policies;
use App\Models\Quiz; use App\Models\User;
class QuizPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,Quiz $quiz):bool{return $this->viewAny($user);} public function create(User $user):bool{return $this->viewAny($user);} public function update(User $user,Quiz $quiz):bool{return $user->hasAnyRole(['super_admin','school_admin'])||($user->hasRole('teacher')&&$quiz->teacher_id===$user->teacher?->id);} public function delete(User $user,Quiz $quiz):bool{return $this->update($user,$quiz);} }
