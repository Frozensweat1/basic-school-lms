<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Announcement extends Model { use SoftDeletes; protected $fillable=['school_id','school_class_id','subject_id','created_by','title','content','audience','published_at','expires_at']; protected $casts=['published_at'=>'datetime','expires_at'=>'datetime']; public function school():BelongsTo{return $this->belongsTo(School::class);} public function author():BelongsTo{return $this->belongsTo(User::class,'created_by');} }
