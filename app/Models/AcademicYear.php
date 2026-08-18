<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $fillable = [
        'school_id',
        'name',
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

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function school(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function reportCards(): HasMany { return $this->hasMany(ReportCard::class); }
}
