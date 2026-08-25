<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany;
class Topic extends Model { protected $fillable=['class_subject_id','title','description','sequence']; public function classSubject():BelongsTo{return $this->belongsTo(ClassSubject::class);} public function lessons():HasMany{return $this->hasMany(Lesson::class);} public function questions():HasMany{return $this->hasMany(Question::class);} }
