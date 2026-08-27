<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteGalleryAlbum extends Model
{
    protected $fillable = ['title', 'description'];

    public function images(): HasMany
    {
        return $this->hasMany(WebsiteGalleryImage::class, 'album_id')
            ->whereNotNull('path')
            ->where('path', '!=', '')
            ->orderBy('sort_order');
    }
}
