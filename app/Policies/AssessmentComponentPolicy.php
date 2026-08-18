<?php
namespace App\Policies;
use App\Models\AssessmentComponent; use App\Models\User;
class AssessmentComponentPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,AssessmentComponent $component):bool{return $this->viewAny($user);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin']);} public function update(User $user,AssessmentComponent $component):bool{return $this->create($user);} public function delete(User $user,AssessmentComponent $component):bool{return $this->create($user);} }
