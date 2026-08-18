<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class AssessmentComponent extends Model { protected $fillable=['term_id','name','weight','sequence']; public function term():BelongsTo{return $this->belongsTo(Term::class);} public function assessments():HasMany{return $this->hasMany(Assessment::class);} }
