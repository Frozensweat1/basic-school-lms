<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAnswer extends Model
{
    protected $fillable = ['quiz_attempt_id', 'question_id', 'answer', 'score', 'feedback', 'graded_by', 'graded_at'];
    protected $casts = ['answer' => 'array', 'graded_at' => 'datetime'];
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
