<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use SoftDeletes;
    protected $fillable = ['class_subject_id', 'topic_id', 'lesson_id', 'teacher_id', 'title', 'instructions', 'time_limit_minutes', 'pass_mark', 'max_attempts', 'randomize_questions', 'opens_at', 'closes_at', 'status'];
    protected $casts = ['randomize_questions' => 'boolean', 'opens_at' => 'datetime', 'closes_at' => 'datetime'];
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
    public function getMaxScoreAttribute(): float
    {
        return (float)$this->quizQuestions()->with('question')->get()->sum(fn($item) => (float)($item->question?->max_score ?? 0));
    }
}
