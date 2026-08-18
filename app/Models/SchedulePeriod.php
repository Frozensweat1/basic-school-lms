<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class SchedulePeriod extends Model { protected $fillable=['school_id','name','starts_at','ends_at','sequence']; public function school():BelongsTo{return $this->belongsTo(School::class);} public function timetableEntries():HasMany{return $this->hasMany(TimetableEntry::class);} }
