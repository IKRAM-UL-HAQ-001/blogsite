<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RawArticle extends Model
{
    protected $fillable = ['news_source_id', 'title', 'url', 'body', 'published_at', 'status'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function marketImpact(): HasOne
    {
        return $this->hasOne(MarketImpact::class);
    }
}
