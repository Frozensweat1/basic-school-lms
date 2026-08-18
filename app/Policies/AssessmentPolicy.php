<?php
namespace App\Policies;
use App\Models\Assessment; use App\Models\User;
class AssessmentPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,Assessment $assessment):bool{return $this->viewAny($user);} public function create(User $user):bool{return $this->viewAny($user);} public function update(User $user,Assessment $assessment):bool{return $user->hasAnyRole(['super_admin','school_admin'])||($user->hasRole('teacher')&&$assessment->teacher_id===$user->teacher?->id);} public function delete(User $user,Assessment $assessment):bool{return $this->update($user,$assessment);} }
