<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WebsitePage extends Model { protected $fillable=['slug','hero_title','hero_subtitle','hero_image_path','content','stats','programs','updated_by']; protected $casts=['content'=>'array','stats'=>'array','programs'=>'array']; public function editor():BelongsTo{return $this->belongsTo(User::class,'updated_by');} }
