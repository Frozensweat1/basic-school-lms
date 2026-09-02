<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use SoftDeletes;

    protected $fillable = ['topic_id', 'teacher_id', 'title', 'summary', 'content', 'objectives', 'sequence', 'status', 'published_at'];

    protected $casts = ['objectives' => 'array', 'published_at' => 'datetime'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Limit lessons to published content assigned to the student's active class.
     */
    public function scopePublishedForStudent(Builder $query, Student $student): Builder
    {
        return $query
            ->where('lessons.status', 'published')
            ->whereHas('topic.classSubject.schoolClass.academicYear', fn (Builder $years) => $years
                ->where('school_id', $student->school_id))
            ->whereHas('topic.classSubject.schoolClass.enrollments', fn (Builder $enrollments) => $enrollments
                ->where('student_id', $student->id)
                ->where('status', ClassEnrollment::STATUS_ACTIVE));
    }
}
