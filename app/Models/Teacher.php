<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'photo_path',
        'gender',
        'date_of_birth',
        'nationality',
        'phone',
        'postal_address',
        'residential_address',
        'gps_address',
        'marital_status',
        'religion',
        'emergency_contact_name',
        'emergency_contact_phone',
        'ssnit_number',
        'ghana_card_number',
        'email',
        'employment_date',
        'status',
        'is_featured_on_website',
        'public_bio',
        'website_display_order',
    ];

    protected $casts = [
        'employment_date' => 'date',
        'date_of_birth' => 'date',
        'is_featured_on_website' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dependants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherDependant::class);
    }

    public function qualifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherQualification::class);
    }

    public function workExperiences(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherWorkExperience::class);
    }

    public function referees(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TeacherReferee::class);
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
