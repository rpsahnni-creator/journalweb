<?php

namespace App\Models;

use App\Enums\ReviewRecommendation;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reviewer_assignment_id',
        'article_id',
        'revision_id',
        'reviewer_id',
        'recommendation',
        'comments_to_author',
        'comments_to_editor',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommendation' => ReviewRecommendation::class,
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ReviewerAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ReviewerAssignment::class, 'reviewer_assignment_id');
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
