<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherReferee extends Model
{
    protected $fillable = ['teacher_id', 'name', 'contact', 'place_of_work', 'position'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
