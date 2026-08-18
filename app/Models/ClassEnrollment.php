<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ClassEnrollment extends Model { protected $fillable=['school_class_id','student_id','enrolled_at','left_at','status']; protected $casts=['enrolled_at'=>'date','left_at'=>'date']; public function schoolClass():BelongsTo{return $this->belongsTo(SchoolClass::class);} public function student():BelongsTo{return $this->belongsTo(Student::class);} }
