<?php
namespace App\Policies;
use App\Models\Timetable; use App\Models\User;
class TimetablePolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,Timetable $timetable):bool{return $this->viewAny($user);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin']);} public function update(User $user,Timetable $timetable):bool{return $this->create($user);} public function delete(User $user,Timetable $timetable):bool{return $this->create($user);} }
