<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTrafficSource extends Model
{
    protected $table = 'article_traffic_sources';

    protected $fillable = [
        'article_id',
        'source',
        'medium',
        'referrer',
        'host',
        'views',
        'clicks',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'views' => 'integer',
        'clicks' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
