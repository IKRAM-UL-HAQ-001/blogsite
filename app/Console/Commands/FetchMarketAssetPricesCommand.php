<?php

namespace App\Console\Commands;

use App\Models\MarketAsset;
use App\Services\MarketAssetService;
use Illuminate\Console\Command;

class FetchMarketAssetPricesCommand extends Command
{
    protected $signature = 'market:fetch-prices
                            {--seed : Seed default market asset definitions before fetching}';

    protected $description = 'Fetch latest market asset prices and store time-series data.';

    public function handle(MarketAssetService $assetService): int
    {
        if ($this->option('seed')) {
            $count = MarketAsset::seedDefaults();
            $this->info("Seeded {$count} market assets.");
        }

        $this->info('Fetching latest market asset prices...');

        $stats = $assetService->fetchAllPrices();

        $this->info('');
        $this->info('=== Price Fetch Results ===');
        $this->line("  Assets fetched: {$stats['fetched']}");
        $this->line("  Prices stored: {$stats['stored']}");
        $this->line("  Errors: {$stats['errors']}");

        if (!empty($stats['error_details'])) {
            $this->line('');
            $this->error('Errors:');
            foreach ($stats['error_details'] as $detail) {
                $this->line("  - {$detail}");
            }
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
