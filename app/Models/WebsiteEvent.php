<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WebsiteEvent extends Model { protected $fillable=['title','slug','description','starts_at','ends_at','location','featured_image_path','is_published','created_by']; protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','is_published'=>'boolean']; public function author():BelongsTo{return $this->belongsTo(User::class,'created_by');} }
