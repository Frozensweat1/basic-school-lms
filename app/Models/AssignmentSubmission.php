<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentSubmission extends Model
{
    protected $fillable = ['assignment_id', 'student_id', 'submission_text', 'status', 'submitted_at', 'score', 'feedback', 'graded_by', 'graded_at'];
    protected $casts = ['submitted_at' => 'datetime', 'graded_at' => 'datetime'];
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class);
    }
}
