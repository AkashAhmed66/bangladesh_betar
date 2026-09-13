<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsArticleMedia extends Model
{
    protected $table = 'news_article_media';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'position' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }
}
