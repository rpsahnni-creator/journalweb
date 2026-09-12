<?php

namespace App\Models;

use App\Enums\ArticleFileType;
use Database\Factories\ArticleFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleFile extends Model
{
    /** @use HasFactory<ArticleFileFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'revision_id',
        'uploaded_by',
        'type',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ArticleFileType::class,
            'size_bytes' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ArticleFile>  $query
     * @return Builder<ArticleFile>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function isVisibleToPublic(): bool
    {
        return $this->is_public && $this->article?->isPubliclyVisible();
    }

    public function isWorkingCopy(): bool
    {
        return $this->revision_id === null;
    }

    public function isImmutable(): bool
    {
        return ! $this->isWorkingCopy();
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return BelongsTo<Revision, $this>
     */
    public function revision(): BelongsTo
    {
        return $this->belongsTo(Revision::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
