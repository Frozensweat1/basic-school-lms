<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class Timetable extends Model { protected $fillable=['academic_year_id','term_id','name','status']; public function academicYear():BelongsTo{return $this->belongsTo(AcademicYear::class);} public function term():BelongsTo{return $this->belongsTo(Term::class);} public function entries():HasMany{return $this->hasMany(TimetableEntry::class);} }
