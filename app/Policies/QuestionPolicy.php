<?php
namespace App\Policies;
use App\Models\{Question, School, User};
class QuestionPolicy { public function viewAny(User $user):bool{return $user->hasAnyRole(['super_admin','school_admin','teacher']);} public function view(User $user,Question $question):bool{return $this->viewAny($user)&&((int)$question->school_id===(int)School::query()->value('id'));} public function create(User $user):bool{return $this->viewAny($user);} public function update(User $user,Question $question):bool{return $this->view($user,$question)&&($user->hasAnyRole(['super_admin','school_admin'])||$question->created_by===$user->id);} public function delete(User $user,Question $question):bool{return $this->update($user,$question);} }
