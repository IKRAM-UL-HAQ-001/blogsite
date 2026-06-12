<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RawArticle extends Model
{
    protected $fillable = [
        'news_source_id',
        'external_id',
        'title',
        'url',
        'content_hash',
        'body',
        'summary',
        'author',
        'language',
        'published_at',
        'fetched_at',
        'status',
        'raw_payload_json',
    ];

    protected $casts = [
        'published_at'    => 'datetime',
        'fetched_at'      => 'datetime',
        'raw_payload_json' => 'array',
    ];

    /**
     * Compute the SHA-256 content hash used for exact-duplicate detection.
     */
    public static function makeContentHash(string $title, string $body = ''): string
    {
        return hash('sha256', mb_strtolower(trim($title) . '|' . trim($body)));
    }

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function marketImpact(): HasOne
    {
        return $this->hasOne(MarketImpact::class);
    }
}
