<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EconomicEvent extends Model
{
    protected $fillable = [
        'event_name',
        'indicator_type',
        'country',
        'actual',
        'forecast',
        'previous',
        'surprise',
        'surprise_direction',
        'importance',
        'release_time',
        'status',
    ];

    protected $casts = [
        'release_time' => 'datetime',
        'surprise' => 'decimal:4',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function marketImpact(): HasOne
    {
        return $this->hasOne(MarketImpact::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(EconomicIndicator::class, 'indicator_type', 'code');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeByIndicator($query, string $type)
    {
        return $query->where('indicator_type', $type);
    }

    public function scopeHighImpact($query)
    {
        return $query->where('importance', 'high');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAnalyzed($query)
    {
        return $query->where('status', 'analyzed');
    }

    public function scopeBeat($query)
    {
        return $query->where('surprise_direction', 'beat');
    }

    public function scopeMiss($query)
    {
        return $query->where('surprise_direction', 'miss');
    }

    public function scopeInline($query)
    {
        return $query->where('surprise_direction', 'inline');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('release_time', '>=', now())->orderBy('release_time');
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('release_time', '>=', now()->subDays($days))->orderBy('release_time', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('release_time', now()->toDateString());
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getIndicatorLabelAttribute(): string
    {
        if ($this->indicator_type && isset(EconomicIndicator::INDICATORS[$this->indicator_type])) {
            return EconomicIndicator::INDICATORS[$this->indicator_type]['name'];
        }

        return $this->event_name;
    }

    public function getIndicatorCategoryAttribute(): string
    {
        if ($this->indicator_type && isset(EconomicIndicator::INDICATORS[$this->indicator_type])) {
            return EconomicIndicator::INDICATORS[$this->indicator_type]['category'];
        }

        return 'other';
    }

    public function getSurpriseLabelAttribute(): string
    {
        return match ($this->surprise_direction) {
            'beat' => 'Beat Forecast',
            'miss' => 'Missed Forecast',
            'inline' => 'In Line',
            default => 'Pending',
        };
    }

    public function getIsHighImpactAttribute(): bool
    {
        return $this->importance === 'high';
    }

    public function getIsSurpriseAttribute(): bool
    {
        return in_array($this->surprise_direction, ['beat', 'miss']);
    }

    // ──────────────────────────────────────────────
    // Business Logic
    // ──────────────────────────────────────────────

    /**
     * Classify this event by indicator type and compute surprise.
     */
    public function process(): self
    {
        // Classify indicator type from event name
        if (empty($this->indicator_type)) {
            $this->indicator_type = EconomicIndicator::classify($this->event_name);
        }

        // Compute surprise from actual vs forecast
        $this->computeSurprise();

        $this->save();

        return $this;
    }

    /**
     * Compute the surprise value and direction from actual vs forecast.
     */
    public function computeSurprise(): void
    {
        if (empty($this->actual) || empty($this->forecast)) {
            $this->surprise = null;
            $this->surprise_direction = null;
            return;
        }

        $actual = $this->parseNumericValue($this->actual);
        $forecast = $this->parseNumericValue($this->forecast);

        if ($actual === null || $forecast === null) {
            $this->surprise = null;
            $this->surprise_direction = null;
            return;
        }

        $surprise = round($actual - $forecast, 4);
        $this->surprise = $surprise;

        // Determine direction: for most indicators, higher = beat.
        // For unemployment claims, higher = miss (bad for economy).
        if (abs($surprise) < 0.0001) {
            $this->surprise_direction = 'inline';
        } elseif ($this->indicator_type === 'unemployment_claims') {
            // Higher unemployment claims is bad (miss)
            $this->surprise_direction = $surprise > 0 ? 'miss' : 'beat';
        } else {
            // Higher than forecast is generally a beat (good for economy)
            $this->surprise_direction = $surprise > 0 ? 'beat' : 'miss';
        }
    }

    /**
     * Parse a value string like "3.1%", "175K", "5.50%" into a float.
     */
    protected function parseNumericValue(string $value): ?float
    {
        // Remove common suffixes/prefixes
        $cleaned = preg_replace('/[%KMBkmb,\s]/', '', trim($value));

        // Handle negative values in parentheses like (0.1)
        if (preg_match('/^\((.+)\)$/', $cleaned, $matches)) {
            $cleaned = '-' . $matches[1];
        }

        if (is_numeric($cleaned)) {
            return (float) $cleaned;
        }

        return null;
    }

    /**
     * Get the previous event with the same indicator type and country
     * for historical comparison.
     */
    public function previousRelease(): ?self
    {
        return self::where('indicator_type', $this->indicator_type)
            ->where('country', $this->country)
            ->where('release_time', '<', $this->release_time)
            ->orderBy('release_time', 'desc')
            ->first();
    }
}
