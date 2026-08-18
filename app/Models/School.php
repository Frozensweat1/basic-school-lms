<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = ['name', 'code', 'motto', 'email', 'phone', 'address', 'logo_path'];

    public function academicYears(): HasMany { return $this->hasMany(AcademicYear::class); }
    public function students(): HasMany { return $this->hasMany(Student::class); }
    public function teachers(): HasMany { return $this->hasMany(Teacher::class); }
    public function parents(): HasMany { return $this->hasMany(ParentGuardian::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
}
