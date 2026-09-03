<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'school_id',
        'created_by',
        'school_class_id',
        'mode',
        'audiences',
        'filters',
        'message',
        'sender_id',
        'provider',
        'encoding',
        'character_count',
        'segment_count',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'queued_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'filters' => 'array',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }
    public function recipients(): HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }
}
