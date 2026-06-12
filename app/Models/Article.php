<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    protected $fillable = [
        'market_impact_id',
        'title',
        'slug',
        'body',
        'excerpt',
        'seo_title',
        'seo_description',
        'focus_keywords',
        'canonical_url',
        'schema_markup',
        'status',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function marketImpact(): BelongsTo
    {
        return $this->belongsTo(MarketImpact::class);
    }

    public function featuredImage(): HasOne
    {
        return $this->hasOne(FeaturedImage::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
