<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketAsset extends Model
{
    protected $fillable = [
        'symbol',
        'name',
        'asset_class',
        'sector',
        'exchange',
        'currency',
        'data_source',
        'data_symbol',
        'is_active',
        'display_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    // ──────────────────────────────────────────────
    // 10 Tracked Market Assets
    // ──────────────────────────────────────────────

    public const TRACKED_ASSETS = [
        'DXY' => [
            'symbol' => 'DXY',
            'name' => 'US Dollar Index',
            'asset_class' => 'index',
            'sector' => 'forex',
            'exchange' => 'ICE',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'DX-Y.NYB',
            'display_order' => 1,
            'metadata' => ['decimals' => 3, 'unit' => 'index'],
        ],
        'XAUUSD' => [
            'symbol' => 'XAUUSD',
            'name' => 'Gold',
            'asset_class' => 'commodity',
            'sector' => 'commodity',
            'exchange' => 'OTC',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'XAUUSD',
            'display_order' => 2,
            'metadata' => ['decimals' => 2, 'unit' => 'USD/oz'],
        ],
        'XAGUSD' => [
            'symbol' => 'XAGUSD',
            'name' => 'Silver',
            'asset_class' => 'commodity',
            'sector' => 'commodity',
            'exchange' => 'OTC',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'XAGUSD',
            'display_order' => 3,
            'metadata' => ['decimals' => 3, 'unit' => 'USD/oz'],
        ],
        'USOUSD' => [
            'symbol' => 'USOUSD',
            'name' => 'Crude Oil (WTI)',
            'asset_class' => 'commodity',
            'sector' => 'commodity',
            'exchange' => 'NYMEX',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'CL=F',
            'display_order' => 4,
            'metadata' => ['decimals' => 2, 'unit' => 'USD/bbl'],
        ],
        'EURUSD' => [
            'symbol' => 'EURUSD',
            'name' => 'EUR/USD',
            'asset_class' => 'forex',
            'sector' => 'forex',
            'exchange' => 'OTC',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'EURUSD',
            'display_order' => 5,
            'metadata' => ['decimals' => 5, 'pip_size' => 0.0001, 'unit' => 'rate'],
        ],
        'GBPUSD' => [
            'symbol' => 'GBPUSD',
            'name' => 'GBP/USD',
            'asset_class' => 'forex',
            'sector' => 'forex',
            'exchange' => 'OTC',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'GBPUSD',
            'display_order' => 6,
            'metadata' => ['decimals' => 5, 'pip_size' => 0.0001, 'unit' => 'rate'],
        ],
        'USDJPY' => [
            'symbol' => 'USDJPY',
            'name' => 'USD/JPY',
            'asset_class' => 'forex',
            'sector' => 'forex',
            'exchange' => 'OTC',
            'currency' => 'JPY',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'USDJPY',
            'display_order' => 7,
            'metadata' => ['decimals' => 3, 'pip_size' => 0.01, 'unit' => 'rate'],
        ],
        'NASDAQ' => [
            'symbol' => 'NASDAQ',
            'name' => 'NASDAQ Composite',
            'asset_class' => 'index',
            'sector' => 'indices',
            'exchange' => 'NASDAQ',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => '^IXIC',
            'display_order' => 8,
            'metadata' => ['decimals' => 2, 'unit' => 'index'],
        ],
        'SPX' => [
            'symbol' => 'SPX',
            'name' => 'S&P 500',
            'asset_class' => 'index',
            'sector' => 'indices',
            'exchange' => 'NYSE',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => '^GSPC',
            'display_order' => 9,
            'metadata' => ['decimals' => 2, 'unit' => 'index'],
        ],
        'BTCUSD' => [
            'symbol' => 'BTCUSD',
            'name' => 'Bitcoin',
            'asset_class' => 'crypto',
            'sector' => 'crypto',
            'exchange' => 'OTC',
            'currency' => 'USD',
            'data_source' => 'alpha_vantage',
            'data_symbol' => 'BTCUSD',
            'display_order' => 10,
            'metadata' => ['decimals' => 2, 'unit' => 'USD'],
        ],
    ];

    public const ASSET_CLASSES = [
        'index' => 'Index',
        'forex' => 'Forex',
        'commodity' => 'Commodity',
        'crypto' => 'Cryptocurrency',
        'stock' => 'Stock',
    ];

    public const SECTORS = [
        'forex' => 'Foreign Exchange',
        'commodity' => 'Commodities',
        'indices' => 'Market Indices',
        'crypto' => 'Cryptocurrency',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function prices(): HasMany
    {
        return $this->hasMany(MarketAssetPrice::class);
    }

    public function latestPrice()
    {
        return $this->hasOne(MarketAssetPrice::class)->latestOfMany('recorded_at');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_market_asset');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByClass($query, string $assetClass)
    {
        return $query->where('asset_class', $assetClass);
    }

    public function scopeBySector($query, string $sector)
    {
        return $query->where('sector', $sector);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getAssetClassLabelAttribute(): string
    {
        return self::ASSET_CLASSES[$this->asset_class] ?? ucfirst($this->asset_class);
    }

    public function getSectorLabelAttribute(): string
    {
        return self::SECTORS[$this->sector] ?? ucfirst($this->sector ?? '');
    }

    public function getDecimalsAttribute(): int
    {
        return $this->metadata['decimals'] ?? 2;
    }

    public function getUnitAttribute(): string
    {
        return $this->metadata['unit'] ?? '';
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = $this->latestPrice?->price;
        if ($price === null) {
            return '—';
        }
        return number_format((float) $price, $this->decimals);
    }

    // ──────────────────────────────────────────────
    // Seed
    // ──────────────────────────────────────────────

    /**
     * Seed the 10 tracked assets into the database.
     */
    public static function seedDefaults(): int
    {
        $count = 0;
        foreach (self::TRACKED_ASSETS as $symbol => $data) {
            self::updateOrCreate(
                ['symbol' => $symbol],
                [
                    'name' => $data['name'],
                    'asset_class' => $data['asset_class'],
                    'sector' => $data['sector'],
                    'exchange' => $data['exchange'],
                    'currency' => $data['currency'],
                    'data_source' => $data['data_source'],
                    'data_symbol' => $data['data_symbol'],
                    'is_active' => true,
                    'display_order' => $data['display_order'],
                    'metadata' => $data['metadata'],
                ]
            );
            $count++;
        }
        return $count;
    }
}
