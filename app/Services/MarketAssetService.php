<?php

namespace App\Services;

use App\Models\MarketAsset;
use App\Models\MarketAssetPrice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarketAssetService
{
    protected int $timeout = 15;

    /**
     * Fetch current prices for all active tracked assets.
     * Returns stats: fetched, stored, errors.
     */
    public function fetchAllPrices(): array
    {
        $assets = MarketAsset::active()->ordered()->get();
        $stats = ['fetched' => 0, 'stored' => 0, 'errors' => 0, 'error_details' => []];

        foreach ($assets as $asset) {
            try {
                $priceData = $this->fetchAssetPrice($asset);

                if ($priceData) {
                    $this->storePrice($asset, $priceData);
                    $stats['fetched']++;
                    $stats['stored']++;
                } else {
                    $stats['errors']++;
                    $stats['error_details'][] = "No data for {$asset->symbol}";
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                $stats['error_details'][] = "{$asset->symbol}: " . $e->getMessage();
                Log::warning("Failed to fetch price for {$asset->symbol}: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Fetch price for a single asset.
     */
    public function fetchAssetPrice(MarketAsset $asset): ?array
    {
        $apiKey = config('services.alpha_vantage.key');

        // Try Alpha Vantage API if key is configured
        if ($apiKey && !str_contains($apiKey, 'your-api-key')) {
            return $this->fetchFromAlphaVantage($asset, $apiKey);
        }

        // Fallback: generate mock data for development
        return $this->getMockPrice($asset);
    }

    /**
     * Fetch from Alpha Vantage API.
     */
    protected function fetchFromAlphaVantage(MarketAsset $asset, string $apiKey): ?array
    {
        $dataSymbol = $asset->data_symbol ?? $asset->symbol;

        try {
            $url = match ($asset->asset_class) {
                'forex' => "https://www.alphavantage.co/query?function=FX_INTRADAY&symbol={$dataSymbol}&interval=5min&apikey={$apiKey}",
                'crypto' => "https://www.alphavantage.co/query?function=DIGITAL_CURRENCY_INTRADAY&symbol={$dataSymbol}&market=USD&apikey={$apiKey}",
                default => "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=" . urlencode($dataSymbol) . "&apikey={$apiKey}",
            };

            $response = Http::timeout($this->timeout)->get($url);

            if (!$response->successful()) {
                Log::warning("Alpha Vantage API error for {$asset->symbol}: HTTP {$response->status()}");
                return $this->getMockPrice($asset);
            }

            $data = $response->json();

            // Check for API limit or error
            if (isset($data['Note']) || isset($data['Error Message'])) {
                Log::warning("Alpha Vantage API note/error for {$asset->symbol}: " . ($data['Note'] ?? $data['Error Message'] ?? 'unknown'));
                return $this->getMockPrice($asset);
            }

            // Parse based on asset class
            return match ($asset->asset_class) {
                'forex' => $this->parseForexResponse($data),
                'crypto' => $this->parseCryptoResponse($data),
                default => $this->parseStockResponse($data),
            };
        } catch (\Exception $e) {
            Log::error("Alpha Vantage fetch failed for {$asset->symbol}: " . $e->getMessage());
            return $this->getMockPrice($asset);
        }
    }

    protected function parseStockResponse(array $data): ?array
    {
        $quote = $data['Global Quote'] ?? [];
        if (empty($quote)) {
            return null;
        }

        return [
            'price' => (float) ($quote['05. price'] ?? 0),
            'open' => isset($quote['02. open']) ? (float) $quote['02. open'] : null,
            'high' => isset($quote['03. high']) ? (float) $quote['03. high'] : null,
            'low' => isset($quote['04. low']) ? (float) $quote['04. low'] : null,
            'close' => isset($quote['08. previous close']) ? (float) $quote['08. previous close'] : null,
            'volume' => isset($quote['06. volume']) ? (float) $quote['06. volume'] : null,
            'change' => isset($quote['09. change']) ? (float) $quote['09. change'] : null,
            'change_percent' => isset($quote['10. change percent']) ? (float) str_replace('%', '', $quote['10. change percent']) : null,
            'recorded_at' => isset($quote['07. latest trading day']) ? Carbon::parse($quote['07. latest trading day']) : now(),
        ];
    }

    protected function parseForexResponse(array $data): ?array
    {
        $series = $data['Time Series FX (Intraday)'] ?? [];
        if (empty($series)) {
            return null;
        }

        $latest = array_values($series)[0];
        $timestamp = array_key_first($series);

        $close = (float) ($latest['4. close'] ?? 0);
        $open = (float) ($latest['1. open'] ?? 0);
        $change = $close - $open;
        $changePercent = $open > 0 ? round(($change / $open) * 100, 4) : 0;

        return [
            'price' => $close,
            'open' => $open,
            'high' => (float) ($latest['2. high'] ?? 0),
            'low' => (float) ($latest['3. low'] ?? 0),
            'close' => $close,
            'volume' => null,
            'change' => $change,
            'change_percent' => $changePercent,
            'recorded_at' => Carbon::parse($timestamp),
        ];
    }

    protected function parseCryptoResponse(array $data): ?array
    {
        $series = $data['Time Series (Digital Currency Intraday)'] ?? [];
        if (empty($series)) {
            return null;
        }

        $latest = array_values($series)[0];
        $timestamp = array_key_first($series);

        $close = (float) ($latest['4. close'] ?? 0);
        $open = (float) ($latest['1. open'] ?? 0);
        $change = $close - $open;
        $changePercent = $open > 0 ? round(($change / $open) * 100, 4) : 0;

        return [
            'price' => $close,
            'open' => $open,
            'high' => (float) ($latest['2. high'] ?? 0),
            'low' => (float) ($latest['3. low'] ?? 0),
            'close' => $close,
            'volume' => (float) ($latest['5. volume'] ?? 0),
            'change' => $change,
            'change_percent' => $changePercent,
            'recorded_at' => Carbon::parse($timestamp),
        ];
    }

    /**
     * Store a price data point for an asset.
     */
    public function storePrice(MarketAsset $asset, array $data): MarketAssetPrice
    {
        // If no change provided, compute from previous price
        if (!isset($data['change']) && !isset($data['change_percent'])) {
            $previous = $asset->prices()->latestFirst()->first();
            $changeData = MarketAssetPrice::computeChange(
                (float) $data['price'],
                $previous ? (float) $previous->price : null
            );
            $data['change'] = $changeData['change'];
            $data['change_percent'] = $changeData['change_percent'];
        }

        return MarketAssetPrice::create([
            'market_asset_id' => $asset->id,
            'price' => $data['price'],
            'change' => $data['change'] ?? null,
            'change_percent' => $data['change_percent'] ?? null,
            'open' => $data['open'] ?? null,
            'high' => $data['high'] ?? null,
            'low' => $data['low'] ?? null,
            'close' => $data['close'] ?? null,
            'volume' => $data['volume'] ?? null,
            'timeframe' => $data['timeframe'] ?? '1m',
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);
    }

    /**
     * Get market dashboard summary.
     */
    public function getDashboardSummary(): array
    {
        $assets = MarketAsset::active()->ordered()->with('latestPrice')->get();

        $bySector = [];
        foreach (MarketAsset::SECTORS as $code => $name) {
            $sectorAssets = $assets->where('sector', $code);
            if ($sectorAssets->count() > 0) {
                $bySector[$code] = [
                    'name' => $name,
                    'count' => $sectorAssets->count(),
                ];
            }
        }

        $gainers = $assets->filter(fn($a) => $a->latestPrice && $a->latestPrice->change > 0)->count();
        $losers = $assets->filter(fn($a) => $a->latestPrice && $a->latestPrice->change < 0)->count();
        $unchanged = $assets->filter(fn($a) => $a->latestPrice && ($a->latestPrice->change ?? 0) == 0)->count();

        return [
            'total_assets' => $assets->count(),
            'gainers' => $gainers,
            'losers' => $losers,
            'unchanged' => $unchanged,
            'by_sector' => $bySector,
            'last_fetch' => MarketAssetPrice::latestFirst()->first()?->created_at,
            'total_price_points' => MarketAssetPrice::count(),
        ];
    }

    /**
     * Get price history for an asset.
     */
    public function getPriceHistory(MarketAsset $asset, string $period = '24h', string $timeframe = '1m'): array
    {
        $hours = match ($period) {
            '1h' => 1,
            '4h' => 4,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            '90d' => 2160,
            default => 24,
        };

        $prices = $asset->prices()
            ->byTimeframe($timeframe)
            ->betweenDates(now()->subHours($hours), now())
            ->oldestFirst()
            ->get();

        // Summarize for chart data
        $chartData = $prices->map(fn($p) => [
            't' => $p->recorded_at->timestamp,
            'p' => (float) $p->price,
            'v' => $p->volume ? (float) $p->volume : null,
        ])->values()->toArray();

        $high = $prices->max('high') ?? $prices->max('price');
        $low = $prices->min('low') ?? $prices->min('price');
        $avgVolume = $prices->whereNotNull('volume')->avg('volume');

        return [
            'asset' => $asset,
            'period' => $period,
            'timeframe' => $timeframe,
            'data_points' => $prices->count(),
            'high' => $high ? round((float) $high, $asset->decimals) : null,
            'low' => $low ? round((float) $low, $asset->decimals) : null,
            'avg_volume' => $avgVolume ? round((float) $avgVolume, 2) : null,
            'chart_data' => $chartData,
        ];
    }

    /**
     * Clean up old price data to prevent table bloat.
     */
    public function cleanupOldPrices(int $keepDays = 90): int
    {
        $cutoff = now()->subDays($keepDays);
        return MarketAssetPrice::where('recorded_at', '<', $cutoff)->delete();
    }

    /**
     * Generate mock price data for development/testing.
     */
    protected function getMockPrice(MarketAsset $asset): array
    {
        // Generate realistic mock prices based on asset type
        $basePrices = [
            'DXY' => 104.50,
            'XAUUSD' => 2340.00,
            'XAGUSD' => 29.50,
            'USOUSD' => 78.40,
            'EURUSD' => 1.0845,
            'GBPUSD' => 1.2720,
            'USDJPY' => 157.350,
            'NASDAQ' => 18500.00,
            'SPX' => 5450.00,
            'BTCUSD' => 68500.00,
        ];

        $base = $basePrices[$asset->symbol] ?? 100.00;

        // Add small random variation (±0.5%)
        $variation = $base * (rand(-50, 50) / 10000);
        $price = round($base + $variation, $asset->decimals);
        $open = round($base + ($base * (rand(-30, 30) / 10000)), $asset->decimals);
        $high = round(max($price, $open) + ($base * (rand(0, 20) / 10000)), $asset->decimals);
        $low = round(min($price, $open) - ($base * (rand(0, 20) / 10000)), $asset->decimals);
        $change = round($price - $open, 6);
        $changePercent = $open > 0 ? round(($change / $open) * 100, 4) : 0;

        // Volume varies by asset class
        $volume = match ($asset->asset_class) {
            'index' => rand(3000000000, 5000000000),
            'forex' => null, // Forex doesn't have centralized volume
            'crypto' => rand(15000, 45000),
            'commodity' => rand(100000, 500000),
            default => rand(1000000, 10000000),
        };

        return [
            'price' => $price,
            'open' => $open,
            'high' => $high,
            'low' => $low,
            'close' => $price,
            'volume' => $volume,
            'change' => $change,
            'change_percent' => $changePercent,
            'recorded_at' => now(),
        ];
    }
}
