<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleAnalytics extends Model
{
    protected $table = 'article_analytics';

    protected $fillable = [
        'article_id',
        'views',
        'unique_visitors',
        'average_time_spent',
        'bounce_count',
        'impressions',
        'clicks',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'unique_visitors' => 'integer',
        'average_time_spent' => 'integer',
        'bounce_count' => 'integer',
        'impressions' => 'integer',
        'clicks' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
