<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsRecipient extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'sms_campaign_id', 'audience', 'recipient_type', 'recipient_id', 'user_id',
        'recipient_name', 'phone', 'normalized_phone', 'phone_source', 'status',
        'attempts', 'last_error', 'skip_reason', 'provider_message_id', 'sent_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'failed_at' => 'datetime'];
    }

    public function campaign(): BelongsTo { return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
