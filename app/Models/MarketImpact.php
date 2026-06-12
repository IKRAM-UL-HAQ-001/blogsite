<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketImpact extends Model
{
    protected $fillable = [
        'raw_article_id',
        'economic_event_id',
        'geopolitical_event_id',
        'sentiment',
        'score',
        'impact_level',
        'affected_assets',
        'market_summary',
    ];

    protected $casts = [
        'affected_assets' => 'array',
    ];

    public function rawArticle(): BelongsTo
    {
        return $this->belongsTo(RawArticle::class);
    }

    public function economicEvent(): BelongsTo
    {
        return $this->belongsTo(EconomicEvent::class);
    }

    public function geopoliticalEvent(): BelongsTo
    {
        return $this->belongsTo(GeopoliticalEvent::class);
    }

    public function article(): HasOne
    {
        return $this->hasOne(Article::class);
    }
}
