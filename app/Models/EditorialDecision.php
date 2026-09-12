<?php

namespace App\Models;

use App\Enums\EditorialDecisionType;
use App\Notifications\ArticleAcceptedNotification;
use App\Notifications\ArticleRejectedNotification;
use App\Notifications\JournalMailNotification;
use App\Notifications\RevisionRequestedNotification;
use Database\Factories\EditorialDecisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialDecision extends Model
{
    /** @use HasFactory<EditorialDecisionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'revision_id',
        'editor_id',
        'decision',
        'comments_to_author',
        'internal_notes',
        'decided_at',
        'revision_due_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => EditorialDecisionType::class,
            'decided_at' => 'datetime',
            'revision_due_at' => 'datetime',
        ];
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
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function authorNotification(Article $article, string $actionUrl): ?JournalMailNotification
    {
        return match ($this->decision) {
            EditorialDecisionType::Accept => new ArticleAcceptedNotification($article, $this, $actionUrl),
            EditorialDecisionType::Reject => new ArticleRejectedNotification($article, $this, $actionUrl),
            EditorialDecisionType::MinorRevision, EditorialDecisionType::MajorRevision => new RevisionRequestedNotification($article, $this, $actionUrl),
            default => null,
        };
    }
}
