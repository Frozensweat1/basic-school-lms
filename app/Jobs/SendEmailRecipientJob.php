<?php

namespace App\Jobs;

use App\Mail\SchoolBroadcastMail;
use App\Models\EmailRecipient;
use App\Services\Emails\EmailCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SendEmailRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public bool $failOnTimeout = true;

    public function __construct(public int $recipientId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(EmailCampaignService $service): void
    {
        $claimed = EmailRecipient::query()
            ->whereKey($this->recipientId)
            ->where('status', EmailRecipient::STATUS_QUEUED)
            ->update([
                'status' => EmailRecipient::STATUS_SENDING,
                'attempts' => DB::raw('attempts + 1'),
                'last_error' => null,
                'updated_at' => now(),
            ]);

        if ($claimed !== 1) {
            return;
        }

        $recipient = EmailRecipient::query()
            ->with(['campaign.school'])
            ->find($this->recipientId);

        if (! $recipient || ! $recipient->campaign || ! $recipient->email) {
            $this->markFailed('Recipient or campaign is no longer available.', $service);

            return;
        }

        try {
            Mail::to($recipient->email, $recipient->recipient_name)
                ->send(new SchoolBroadcastMail($recipient->campaign, $recipient));

            $recipient->forceFill([
                'status' => EmailRecipient::STATUS_SENT,
                'last_error' => null,
                'failed_at' => null,
                'sent_at' => now(),
            ])->save();

            $service->refreshStatus($recipient->email_campaign_id);
        } catch (Throwable $exception) {
            $recipient->forceFill([
                'status' => EmailRecipient::STATUS_QUEUED,
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            $service->refreshStatus($recipient->email_campaign_id);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed(Str::limit($exception->getMessage(), 2000), app(EmailCampaignService::class));
    }

    private function markFailed(string $message, EmailCampaignService $service): void
    {
        $recipient = EmailRecipient::query()->find($this->recipientId);
        if (! $recipient || in_array($recipient->status, [EmailRecipient::STATUS_SENT, EmailRecipient::STATUS_SKIPPED], true)) {
            return;
        }

        $recipient->forceFill([
            'status' => EmailRecipient::STATUS_FAILED,
            'last_error' => $message,
            'failed_at' => now(),
        ])->save();

        $service->refreshStatus($recipient->email_campaign_id);
    }
}
