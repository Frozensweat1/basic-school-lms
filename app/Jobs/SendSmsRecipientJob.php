<?php

namespace App\Jobs;

use App\Contracts\SmsGateway;
use App\Models\SmsRecipient;
use App\Services\Sms\SmsCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SendSmsRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public bool $failOnTimeout = true;

    public function __construct(public int $recipientId) {}

    public function backoff(): array { return [30, 120, 300]; }

    public function handle(SmsGateway $gateway, SmsCampaignService $service): void
    {
        $claimed = SmsRecipient::query()->whereKey($this->recipientId)->where('status', SmsRecipient::STATUS_QUEUED)->update(['status' => SmsRecipient::STATUS_SENDING, 'attempts' => DB::raw('attempts + 1'), 'last_error' => null, 'updated_at' => now()]);
        if ($claimed !== 1) return;
        $recipient = SmsRecipient::query()->with('campaign')->find($this->recipientId);
        if (! $recipient || ! $recipient->campaign || ! $recipient->normalized_phone) {
            $this->markFailed('Recipient or campaign is no longer available.', $service);
            return;
        }
        try {
            $result = $gateway->send($recipient->normalized_phone, $recipient->campaign->message, $recipient->campaign->sender_id, 'sms-recipient-'.$recipient->id);
            $recipient->forceFill(['status' => SmsRecipient::STATUS_SENT, 'provider_message_id' => $result->messageId, 'last_error' => null, 'failed_at' => null, 'sent_at' => now()])->save();
            $service->refreshStatus($recipient->sms_campaign_id);
        } catch (Throwable $exception) {
            $recipient->forceFill(['status' => SmsRecipient::STATUS_QUEUED, 'last_error' => Str::limit($exception->getMessage(), 2000)])->save();
            $service->refreshStatus($recipient->sms_campaign_id);
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed(Str::limit($exception->getMessage(), 2000), app(SmsCampaignService::class));
    }

    private function markFailed(string $message, SmsCampaignService $service): void
    {
        $recipient = SmsRecipient::query()->find($this->recipientId);
        if (! $recipient || in_array($recipient->status, [SmsRecipient::STATUS_SENT, SmsRecipient::STATUS_SKIPPED], true)) return;
        $recipient->forceFill(['status' => SmsRecipient::STATUS_FAILED, 'last_error' => $message, 'failed_at' => now()])->save();
        $service->refreshStatus($recipient->sms_campaign_id);
    }
}
