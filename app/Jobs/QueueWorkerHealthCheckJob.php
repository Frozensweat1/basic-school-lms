<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class QueueWorkerHealthCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public string $token) {}

    public function handle(): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'completed',
            'processed_at' => now()->toIso8601String(),
        ], now()->addMinutes(10));
    }

    public function failed(Throwable $exception): void
    {
        Cache::put($this->cacheKey(), [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], now()->addMinutes(10));
    }

    private function cacheKey(): string
    {
        return 'queue-health-check:'.$this->token;
    }
}
