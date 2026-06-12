<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngestionLog extends Model
{
    protected $fillable = [
        'news_source_id',
        'source_type',
        'status',
        'fetched_count',
        'duplicates_skipped',
        'stored_count',
        'error_count',
        'error_message',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    /**
     * Create a new ingestion log entry in 'running' state.
     */
    public static function start(int $sourceId, string $sourceType): self
    {
        return self::create([
            'news_source_id' => $sourceId,
            'source_type' => $sourceType,
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the log as completed with stats.
     */
    public function complete(int $fetched, int $duplicates, int $stored, int $errors = 0, ?string $errorMessage = null): self
    {
        $status = $errors > 0 ? 'partial' : 'completed';

        $this->update([
            'status' => $status,
            'fetched_count' => $fetched,
            'duplicates_skipped' => $duplicates,
            'stored_count' => $stored,
            'error_count' => $errors,
            'error_message' => $errorMessage,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'duration_ms' => $this->started_at
                    ? $this->started_at->diffInMilliseconds(now())
                    : null,
            ]),
        ]);

        return $this;
    }

    /**
     * Mark the log as failed.
     */
    public function fail(string $errorMessage): self
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'error_count' => ($this->error_count ?? 0) + 1,
            'completed_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'duration_ms' => $this->started_at
                    ? $this->started_at->diffInMilliseconds(now())
                    : null,
            ]),
        ]);

        return $this;
    }

    /**
     * Scope: only completed runs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: only failed runs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: recent logs.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}
