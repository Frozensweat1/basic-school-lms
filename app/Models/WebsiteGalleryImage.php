<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WebsiteGalleryImage extends Model
{
    protected $fillable = ['album_id', 'path', 'caption', 'sort_order'];

    protected $appends = ['url'];

    public function album(): BelongsTo
    {
        return $this->belongsTo(WebsiteGalleryAlbum::class, 'album_id');
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->path ? Storage::disk('public')->url($this->path) : null,
        );
    }
}
