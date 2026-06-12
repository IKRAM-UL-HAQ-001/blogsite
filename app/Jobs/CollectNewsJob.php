<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class CollectNewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('ingestion');
    }

    public function handle(): void
    {
        Log::info('CollectNewsJob started.');

        Bus::chain([
            new FetchFinancialNewsJob(),
            new FetchMarketNewsJob(),
            new FetchGeopoliticalNewsJob(),
        ])->dispatch();

        Log::info('CollectNewsJob completed and dispatching fetch jobs.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('CollectNewsJob failed: ' . $exception->getMessage());
    }
}
