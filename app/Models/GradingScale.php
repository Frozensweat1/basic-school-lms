<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingScale extends Model
{
    protected $fillable = [
        'school_id',
        'grade',
        'minimum',
        'maximum',
        'remark',
        'sequence',
    ];

    protected $casts = [
        'minimum' => 'decimal:2',
        'maximum' => 'decimal:2',
        'sequence' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function subjectResults(): HasMany
    {
        return $this->hasMany(SubjectResult::class);
    }
}
