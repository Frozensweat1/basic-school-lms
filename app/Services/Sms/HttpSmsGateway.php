<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HttpSmsGateway implements SmsGateway
{
    public function send(
        string $recipient,
        string $message,
        ?string $senderId = null,
        ?string $idempotencyKey = null,
    ): SmsSendResult {
        $configuration = (array) config('sms.connections.http', []);
        $endpoint = trim((string) ($configuration['endpoint'] ?? ''));
        $token = trim((string) ($configuration['token'] ?? ''));
        $senderId = trim((string) ($senderId ?: config('sms.sender_id', '')));

        if ($endpoint === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('The HTTP SMS gateway endpoint is not configured correctly.');
        }

        if ($token === '') {
            throw new RuntimeException('The HTTP SMS gateway token is not configured.');
        }

        $recipientField = $this->requiredFieldName($configuration, 'recipient_field');
        $messageField = $this->requiredFieldName($configuration, 'message_field');
        $senderField = trim((string) ($configuration['sender_field'] ?? 'sender'));
        $messageIdPath = trim((string) ($configuration['message_id_path'] ?? 'message_id'));

        if ($messageIdPath === '') {
            throw new RuntimeException('The HTTP SMS message ID response path is not configured.');
        }

        $payload = array_merge((array) ($configuration['extra_payload'] ?? []), [
            $recipientField => $recipient,
            $messageField => $message,
        ]);

        if ($senderField !== '' && $senderId !== '') {
            $payload[$senderField] = $senderId;
        }

        $tokenHeader = trim((string) ($configuration['token_header'] ?? 'Authorization'));
        $tokenPrefix = trim((string) ($configuration['token_prefix'] ?? 'Bearer'));
        $headers = [
            $tokenHeader => trim($tokenPrefix.' '.$token),
        ];

        $idempotencyHeader = trim((string) ($configuration['idempotency_header'] ?? 'Idempotency-Key'));
        if ($idempotencyHeader !== '' && filled($idempotencyKey)) {
            $headers[$idempotencyHeader] = $idempotencyKey;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders($headers)
                ->connectTimeout((int) ($configuration['connect_timeout'] ?? 5))
                ->timeout((int) ($configuration['timeout'] ?? 15))
                ->withOptions(['verify' => (bool) ($configuration['verify_tls'] ?? true)])
                ->post($endpoint, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The SMS gateway could not be reached.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('The SMS gateway rejected the request with HTTP '.$response->status().'.');
        }

        $responseData = $response->json();
        if (! is_array($responseData)) {
            throw new RuntimeException('The SMS gateway returned an invalid JSON response.');
        }

        $messageId = trim((string) data_get($responseData, $messageIdPath, ''));
        if ($messageId === '') {
            throw new RuntimeException('The SMS gateway response did not include a message ID.');
        }

        $statusPath = trim((string) ($configuration['status_path'] ?? 'status'));
        $status = $statusPath !== ''
            ? trim((string) data_get($responseData, $statusPath, 'accepted'))
            : 'accepted';

        return new SmsSendResult(
            messageId: $messageId,
            status: $status ?: 'accepted',
            metadata: Arr::whereNotNull([
                'driver' => 'http',
                'http_status' => $response->status(),
            ]),
        );
    }

    /** @param array<string, mixed> $configuration */
    private function requiredFieldName(array $configuration, string $key): string
    {
        $value = trim((string) ($configuration[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException("The HTTP SMS {$key} is not configured.");
        }

        return $value;
    }
}
