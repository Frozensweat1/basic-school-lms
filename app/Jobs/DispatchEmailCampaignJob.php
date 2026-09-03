<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Models\EmailRecipient;
use App\Services\Emails\EmailCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $campaignId) {}

    public function handle(EmailCampaignService $service): void
    {
        if (! EmailCampaign::query()->whereKey($this->campaignId)->exists()) {
            return;
        }

        $service->markProcessing($this->campaignId);

        EmailRecipient::query()
            ->where('email_campaign_id', $this->campaignId)
            ->where('status', EmailRecipient::STATUS_QUEUED)
            ->select('id')
            ->chunkById(250, function ($recipients): void {
                foreach ($recipients as $recipient) {
                    SendEmailRecipientJob::dispatch($recipient->id)->onQueue('default');
                }
            });

        $service->refreshStatus($this->campaignId);
    }
}
