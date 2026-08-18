<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    protected $fillable = [
        'academic_year_id',
        'name',
        'sequence',
        'starts_at',
        'ends_at',
        'is_active',
        'is_locked',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function assessments(): HasMany { return $this->hasMany(Assessment::class); }
    public function attendanceRecords(): HasMany { return $this->hasMany(AttendanceRecord::class); }
}
