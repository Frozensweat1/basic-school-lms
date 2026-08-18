<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $casts = [
        'employment_date' => 'date',
    ];

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'photo_path',
        'phone',
        'email',
        'employment_date',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teachers')
            ->withTimestamps();
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'teacher_id', 'subject_id')->withTimestamps();
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function classSubjects(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(ClassSubject::class); }
}
