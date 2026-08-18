<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Question extends Model { protected $fillable=['school_id','created_by','type','prompt','grading_key','max_score']; protected $casts=['grading_key'=>'array']; public function school():BelongsTo{return $this->belongsTo(School::class);} public function author():BelongsTo{return $this->belongsTo(User::class,'created_by');} public function options():HasMany{return $this->hasMany(QuestionOption::class);} public function quizQuestions():HasMany{return $this->hasMany(QuizQuestion::class);} }
