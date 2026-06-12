<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedImage extends Model
{
    protected $fillable = [
        'article_id',
        'file_path',
        'alt_text',
        'generation_prompt',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
