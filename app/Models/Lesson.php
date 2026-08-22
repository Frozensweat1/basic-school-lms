<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    use SoftDeletes;
    protected $fillable = ['topic_id', 'teacher_id', 'title', 'summary', 'content', 'objectives', 'sequence', 'status', 'published_at'];
    protected $casts = ['objectives' => 'array', 'published_at' => 'datetime'];
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class);
    }
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
