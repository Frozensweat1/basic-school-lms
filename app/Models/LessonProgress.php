<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LessonProgress extends Model { protected $fillable=['lesson_id','student_id','completed_at']; protected $casts=['completed_at'=>'datetime']; public function lesson():BelongsTo{return $this->belongsTo(Lesson::class);} public function student():BelongsTo{return $this->belongsTo(Student::class);} }
