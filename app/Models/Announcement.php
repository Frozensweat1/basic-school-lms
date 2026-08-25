<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    public const AUDIENCES = ['school', 'teachers', 'class', 'subject'];

    protected $fillable = [
        'school_id',
        'school_class_id',
        'subject_id',
        'created_by',
        'title',
        'content',
        'audience',
        'published_at',
        'expires_at',
        'notified_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $items) => $items->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function publicationState(): string
    {
        if (! $this->published_at) {
            return 'draft';
        }
        if ($this->published_at->isFuture()) {
            return 'scheduled';
        }
        if ($this->expires_at?->isPast()) {
            return 'expired';
        }

        return 'published';
    }

    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'school' => 'School-wide',
            'teachers' => 'Teachers',
            'class' => $this->schoolClass?->name ?? 'Selected class',
            'subject' => $this->subject?->name ?? 'Selected subject',
            default => ucfirst($this->audience),
        };
    }
}
