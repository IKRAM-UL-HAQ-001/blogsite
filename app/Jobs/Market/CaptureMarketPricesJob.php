<?php

namespace App\Jobs\Market;

use App\Models\PipelineRun;
use App\Services\MarketAssetService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Captures a price snapshot for every active market asset.
 * Runs every 15 minutes. uniqueFor prevents overlap.
 */
final class CaptureMarketPricesJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries     = 2;
    public int $timeout   = 120;
    public int $uniqueFor = 900; // 15 minutes

    public function __construct()
    {
        $this->onQueue('market-data');
    }

    public function handle(MarketAssetService $service): void
    {
        $run = PipelineRun::start('capture_market_prices');

        try {
            $stats = $service->fetchAllPrices();

            $run->complete(
                $stats['fetched'],
                $stats['stored'],
                $stats['errors'],
                ['error_details' => $stats['error_details']],
            );

            Log::info("CaptureMarketPricesJob: stored {$stats['stored']}, errors {$stats['errors']}.");
        } catch (\Throwable $e) {
            $run->fail($e->getMessage());
            Log::error('CaptureMarketPricesJob failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('CaptureMarketPricesJob permanently failed: ' . $e->getMessage());
    }
}
