<?php
namespace App\Policies;
use App\Models\AttendanceRecord; use App\Models\User;
class AttendanceRecordPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function create(User $user):bool{return $this->viewAny($user);} public function update(User $user,AttendanceRecord $record):bool{return $this->viewAny($user);} }
