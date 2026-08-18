<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentGuardian extends Model
{
    use SoftDeletes;

    protected $table = 'parents';

    protected $fillable = [
        'user_id',
        'school_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot(['relationship', 'is_primary_contact'])
            ->withTimestamps();
    }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
}
