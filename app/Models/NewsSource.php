<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\IngestionLog;

class NewsSource extends Model
{
    protected $fillable = ['name', 'url', 'type', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
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
}
