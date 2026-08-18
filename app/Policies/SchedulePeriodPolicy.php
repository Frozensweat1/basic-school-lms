<?php
namespace App\Policies;
use App\Models\SchedulePeriod; use App\Models\User;
class SchedulePeriodPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin']);} public function update(User $user,SchedulePeriod $period):bool{return $this->create($user);} public function delete(User $user,SchedulePeriod $period):bool{return $this->create($user);} }
