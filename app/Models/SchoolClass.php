<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'school_classes';

    protected $fillable = [
        'academic_year_id',
        'name',
        'stream_id',
        'code',
        'status',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'class_enrollments')
            ->withPivot(['status', 'enrolled_at', 'left_at'])
            ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subjects')
            ->withTimestamps();
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'class_teachers')
            ->withTimestamps();
    }

    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function classSubjects(): HasMany { return $this->hasMany(ClassSubject::class); }
    public function enrollments(): HasMany { return $this->hasMany(ClassEnrollment::class); }
    public function attendanceRecords(): HasMany { return $this->hasMany(AttendanceRecord::class); }
}
