<?php
namespace App\Policies;
use App\Models\ParentGuardian; use App\Models\User;
class ParentGuardianPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,ParentGuardian $parent):bool{return $this->viewAny($user);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin']);} public function update(User $user,ParentGuardian $parent):bool{return $this->create($user);} public function delete(User $user,ParentGuardian $parent):bool{return $this->create($user);} }
