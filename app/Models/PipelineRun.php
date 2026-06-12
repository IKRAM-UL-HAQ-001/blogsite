<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PipelineRun extends Model
{
    protected $fillable = [
        'pipeline',
        'batch_uuid',
        'started_at',
        'finished_at',
        'status',
        'items_received',
        'items_processed',
        'items_failed',
        'metadata_json',
        'error_message',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
        'metadata_json' => 'array',
    ];

    // ──────────────────────────────────────────────
    // Factory methods
    // ──────────────────────────────────────────────

    public static function start(string $pipeline, array $metadata = []): self
    {
        return self::create([
            'pipeline'   => $pipeline,
            'batch_uuid' => (string) Str::uuid(),
            'started_at' => now(),
            'status'     => 'running',
            'metadata_json' => $metadata ?: null,
        ]);
    }

    // ──────────────────────────────────────────────
    // State transitions
    // ──────────────────────────────────────────────

    public function complete(int $received, int $processed, int $failed = 0, array $metadata = []): void
    {
        $status = match (true) {
            $failed > 0 && $processed === 0 => 'failed',
            $failed > 0                     => 'partial',
            default                         => 'completed',
        };

        $this->update([
            'finished_at'    => now(),
            'status'         => $status,
            'items_received' => $received,
            'items_processed' => $processed,
            'items_failed'   => $failed,
            'metadata_json'  => array_merge($this->metadata_json ?? [], $metadata) ?: null,
        ]);
    }

    public function fail(string $error, array $metadata = []): void
    {
        $this->update([
            'finished_at'   => now(),
            'status'        => 'failed',
            'error_message' => $error,
            'metadata_json' => array_merge($this->metadata_json ?? [], $metadata) ?: null,
        ]);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeForPipeline($query, string $pipeline)
    {
        return $query->where('pipeline', $pipeline);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('started_at', '>=', now()->subHours($hours));
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ──────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────

    public function getDurationSecondsAttribute(): ?int
    {
        if (! $this->finished_at) {
            return null;
        }
        return (int) $this->started_at->diffInSeconds($this->finished_at);
    }

    public function getSuccessRateAttribute(): ?float
    {
        if (! $this->items_received) {
            return null;
        }
        return round(($this->items_processed / $this->items_received) * 100, 1);
    }
}
