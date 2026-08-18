<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'student_id',
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'admission_date',
        'school_id',
        'status',
        'photo_path',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentGuardian::class, 'parent_student', 'student_id', 'parent_id')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_enrollments')
            ->withPivot(['status', 'enrolled_at', 'left_at'])
            ->withTimestamps();
    }
    public function enrollments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(ClassEnrollment::class); }
    public function assignmentSubmissions(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(AssignmentSubmission::class); }
    public function quizAttempts(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(QuizAttempt::class); }
    public function attendanceRecords(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(AttendanceRecord::class); }
    public function subjectResults(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SubjectResult::class); }
}
