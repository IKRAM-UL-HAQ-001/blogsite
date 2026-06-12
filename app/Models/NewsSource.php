<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\IngestionLog;

class NewsSource extends Model
{
    protected $fillable = [
        'name',
        'url',
        'type',
        'is_active',
        'provider_class',
        'poll_interval_minutes',
        'last_fetched_at',
        'reliability_score',
        'configuration_json',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'last_fetched_at'       => 'datetime',
        'reliability_score'     => 'decimal:2',
        'configuration_json'    => 'array',
        'poll_interval_minutes' => 'integer',
    ];

    public const TYPES = [
        'economic_calendar' => 'Economic Calendar',
        'financial' => 'Financial News',
        'geopolitical' => 'Geopolitical News',
        'commodity' => 'Commodity News',
        'market' => 'Market News',
    ];

    public function rawArticles(): HasMany
    {
        return $this->hasMany(RawArticle::class);
    }

    public function ingestionLogs(): HasMany
    {
        return $this->hasMany(IngestionLog::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    // ──────────────────────────────────────────────
    // Pipeline scheduling
    // ──────────────────────────────────────────────

    /**
     * Whether this source is due for another fetch based on poll_interval_minutes.
     */
    public function isDue(): bool
    {
        if ($this->last_fetched_at === null) {
            return true;
        }

        return $this->last_fetched_at->addMinutes($this->poll_interval_minutes ?? 30)->isPast();
    }

    /**
     * Record a successful fetch.
     */
    public function markFetched(): void
    {
        $config = $this->configuration_json ?? [];
        $config['consecutive_failures'] = 0;

        $this->update([
            'last_fetched_at'    => now(),
            'configuration_json' => $config,
        ]);
    }

    /**
     * Record a failed fetch attempt.
     */
    public function markFailed(string $reason = ''): void
    {
        $config = $this->configuration_json ?? [];
        $failures = ($config['consecutive_failures'] ?? 0) + 1;
        $config['consecutive_failures'] = $failures;
        $config['last_failure_reason']  = $reason;
        $config['last_failure_at']      = now()->toISOString();

        $updates = ['configuration_json' => $config];

        // After 10 consecutive failures disable the source automatically
        if ($failures >= 10) {
            $updates['is_active'] = false;
        }

        $this->update($updates);
    }

    /**
     * True when too many consecutive failures have been recorded (circuit open).
     * Threshold is 5 — source stays queryable but jobs won't dispatch until it recovers.
     */
    public function isCircuitOpen(): bool
    {
        $failures = $this->configuration_json['consecutive_failures'] ?? 0;
        return $failures >= 5;
    }
}
