<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use SoftDeletes;
    protected $fillable = ['class_subject_id', 'topic_id', 'lesson_id', 'teacher_id', 'title', 'instructions', 'max_score', 'opens_at', 'due_at', 'allow_late_submission', 'status'];
    protected $casts = ['opens_at' => 'datetime', 'due_at' => 'datetime', 'allow_late_submission' => 'boolean'];
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
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }
}
