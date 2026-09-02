<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherQualification extends Model
{
    protected $fillable = ['teacher_id', 'qualification', 'institution', 'program_of_study', 'year_of_graduation'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
