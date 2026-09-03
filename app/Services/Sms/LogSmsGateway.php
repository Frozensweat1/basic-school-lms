<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogSmsGateway implements SmsGateway
{
    public function send(
        string $recipient,
        string $message,
        ?string $senderId = null,
        ?string $idempotencyKey = null,
    ): SmsSendResult {
        $messageId = 'log-'.Str::uuid();
        $channel = (string) config('sms.connections.log.channel', 'stack');

        Log::channel($channel)->info('SMS accepted by log gateway.', [
            'provider_message_id' => $messageId,
            'recipient_hash' => hash('sha256', $recipient),
            'sender_id' => $senderId,
            'character_count' => mb_strlen($message),
            'idempotency_key' => $idempotencyKey,
        ]);

        return new SmsSendResult($messageId, metadata: ['driver' => 'log']);
    }
}
