<?php
namespace App\Policies;
use App\Models\Announcement; use App\Models\User;
class AnnouncementPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher','student','parent']);} public function create(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function update(User $user,Announcement $announcement):bool{return $user->hasAnyRole(['super_admin','school_admin'])||$announcement->created_by===$user->id;} public function delete(User $user,Announcement $announcement):bool{return $this->update($user,$announcement);} }
