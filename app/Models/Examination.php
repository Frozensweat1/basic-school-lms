<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Examination extends Model
{
    public const MANAGEABLE_STATUSES = ['draft', 'scheduled', 'completed', 'cancelled'];

    // `published` is retained temporarily so existing seeded and historic records remain visible.
    public const LEARNER_VISIBLE_STATUSES = ['scheduled', 'completed', 'published'];

    protected $fillable = ['school_id', 'academic_year_id', 'term_id', 'class_subject_id', 'teacher_id', 'title', 'description', 'exam_date', 'duration_minutes', 'max_score', 'status'];

    protected $casts = [
        'exam_date' => 'date',
        'max_score' => 'decimal:2',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
