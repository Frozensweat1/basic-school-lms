<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Models\SmsRecipient;
use App\Services\Sms\SmsCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $campaignId) {}

    public function handle(SmsCampaignService $service): void
    {
        if (! SmsCampaign::query()->whereKey($this->campaignId)->exists()) return;
        $service->markProcessing($this->campaignId);
        SmsRecipient::query()->where('sms_campaign_id', $this->campaignId)->where('status', SmsRecipient::STATUS_QUEUED)->select('id')->chunkById(250, function ($recipients): void {
            foreach ($recipients as $recipient) SendSmsRecipientJob::dispatch($recipient->id)->onQueue('default');
        });
        $service->refreshStatus($this->campaignId);
    }
}
