<?php

namespace App\Services\Sms;

final readonly class SmsSendResult
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $messageId,
        public string $status = 'accepted',
        public array $metadata = [],
    ) {}
}
