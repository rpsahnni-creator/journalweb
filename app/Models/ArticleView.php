<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'type',
        'ip_hash',
        'user_agent',
        'referer',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Record an article page view or PDF download, avoiding duplicate counts
     * from the same IP within a 30-minute window.
     */
    public static function record(Article $article, string $type, ?string $ip = null, ?string $userAgent = null, ?string $referer = null): void
    {
        $ipHash = $ip ? hash('sha256', $ip.$article->id) : null;

        // Deduplicate: same article + type + IP within 30 minutes.
        if ($ipHash !== null) {
            $recentExists = static::query()
                ->where('article_id', $article->id)
                ->where('type', $type)
                ->where('ip_hash', $ipHash)
                ->where('viewed_at', '>=', now()->subMinutes(30))
                ->exists();

            if ($recentExists) {
                return;
            }
        }

        static::create([
            'article_id' => $article->id,
            'type' => $type,
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent ? str($userAgent)->limit(512, '')->toString() : null,
            'referer' => $referer ? str($referer)->limit(512, '')->toString() : null,
            'viewed_at' => now(),
        ]);

        // Increment cached counter on the article.
        $column = $type === 'pdf_download' ? 'download_count' : 'view_count';
        $article->increment($column);
    }
}
