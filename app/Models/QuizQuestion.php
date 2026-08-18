<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class QuizQuestion extends Model { protected $fillable=['quiz_id','question_id','sequence']; public function quiz():BelongsTo{return $this->belongsTo(Quiz::class);} public function question():BelongsTo{return $this->belongsTo(Question::class);} }
