<?php
namespace App\Policies;
use App\Models\GradingScale; use App\Models\User;
class GradingScalePolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin']);} public function update(User $user,GradingScale $scale):bool{return $this->create($user);} public function delete(User $user,GradingScale $scale):bool{return $this->create($user);} }
