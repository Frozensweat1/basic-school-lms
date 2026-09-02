<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDependant extends Model
{
    protected $fillable = ['teacher_id', 'relation', 'name', 'date_of_birth', 'is_next_of_kin'];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_next_of_kin' => 'boolean',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
