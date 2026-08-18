<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LessonResource extends Model { protected $fillable=['lesson_id','title','type','disk','path','external_url','size','uploaded_by']; public function lesson():BelongsTo{return $this->belongsTo(Lesson::class);} public function uploader():BelongsTo{return $this->belongsTo(User::class,'uploaded_by');} }
