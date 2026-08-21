<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class WebsiteGalleryImage extends Model { protected $fillable=['album_id','path','caption','sort_order']; public function album():BelongsTo{return $this->belongsTo(WebsiteGalleryAlbum::class,'album_id');} }
