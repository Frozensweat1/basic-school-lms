<?php

namespace App\Contracts;

use App\Services\Sms\SmsSendResult;

interface SmsGateway
{
    public function send(
        string $recipient,
        string $message,
        ?string $senderId = null,
        ?string $idempotencyKey = null,
    ): SmsSendResult;
}
