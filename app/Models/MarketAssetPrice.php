<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketAssetPrice extends Model
{
    protected $fillable = [
        'market_asset_id',
        'price',
        'change',
        'change_percent',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'timeframe',
        'recorded_at',
    ];

    protected $casts = [
        'price' => 'decimal:6',
        'change' => 'decimal:6',
        'change_percent' => 'decimal:4',
        'open' => 'decimal:6',
        'high' => 'decimal:6',
        'low' => 'decimal:6',
        'close' => 'decimal:6',
        'volume' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function marketAsset(): BelongsTo
    {
        return $this->belongsTo(MarketAsset::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeForAsset($query, int $assetId)
    {
        return $query->where('market_asset_id', $assetId);
    }

    public function scopeByTimeframe($query, string $timeframe)
    {
        return $query->where('timeframe', $timeframe);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', now()->toDateString());
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('recorded_at', 'asc');
    }

    public function scopeBetweenDates($query, $start, $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    public function scopePositive($query)
    {
        return $query->where('change', '>', 0);
    }

    public function scopeNegative($query)
    {
        return $query->where('change', '<', 0);
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getIsBullishAttribute(): bool
    {
        return ($this->change ?? 0) > 0;
    }

    public function getIsBearishAttribute(): bool
    {
        return ($this->change ?? 0) < 0;
    }

    public function getDirectionAttribute(): string
    {
        if (($this->change ?? 0) > 0) {
            return 'up';
        }
        if (($this->change ?? 0) < 0) {
            return 'down';
        }
        return 'flat';
    }

    public function getFormattedPriceAttribute(): string
    {
        $asset = $this->marketAsset;
        $decimals = $asset ? $asset->decimals : 2;
        return number_format((float) $this->price, $decimals);
    }

    public function getFormattedChangeAttribute(): string
    {
        $asset = $this->marketAsset;
        $decimals = $asset ? $asset->decimals : 2;
        $prefix = $this->change >= 0 ? '+' : '';
        return $prefix . number_format((float) $this->change, $decimals);
    }

    public function getFormattedChangePercentAttribute(): string
    {
        $prefix = ($this->change_percent ?? 0) >= 0 ? '+' : '';
        return $prefix . number_format((float) ($this->change_percent ?? 0), 2) . '%';
    }

    public function getFormattedVolumeAttribute(): string
    {
        if ($this->volume === null) {
            return '—';
        }
        $vol = (float) $this->volume;
        if ($vol >= 1e9) {
            return number_format($vol / 1e9, 2) . 'B';
        }
        if ($vol >= 1e6) {
            return number_format($vol / 1e6, 2) . 'M';
        }
        if ($vol >= 1e3) {
            return number_format($vol / 1e3, 1) . 'K';
        }
        return number_format($vol, 0);
    }

    // ──────────────────────────────────────────────
    // Business Logic
    // ──────────────────────────────────────────────

    /**
     * Compute change and change_percent from a previous price.
     */
    public static function computeChange(float $currentPrice, ?float $previousPrice): array
    {
        if ($previousPrice === null || $previousPrice == 0) {
            return ['change' => null, 'change_percent' => null];
        }

        $change = round($currentPrice - $previousPrice, 6);
        $changePercent = round(($change / $previousPrice) * 100, 4);

        return ['change' => $change, 'change_percent' => $changePercent];
    }
}
