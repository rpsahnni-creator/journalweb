<?php

namespace App\Models;

use App\Enums\ReviewerAssignmentStatus;
use Database\Factories\ReviewerAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewerAssignment extends Model
{
    /** @use HasFactory<ReviewerAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'article_id',
        'revision_id',
        'reviewer_id',
        'assigned_by',
        'status',
        'invited_at',
        'responded_at',
        'due_at',
        'completed_at',
        'response_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReviewerAssignmentStatus::class,
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return HasOne<Review, $this>
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function isPendingInvitation(): bool
    {
        return $this->status === ReviewerAssignmentStatus::Invited;
    }

    public function isAccepted(): bool
    {
        return $this->status === ReviewerAssignmentStatus::Accepted;
    }

    public function isCompleted(): bool
    {
        return $this->status === ReviewerAssignmentStatus::Completed;
    }

    public function canBeRespondedTo(): bool
    {
        return $this->isPendingInvitation();
    }

    public function canAccessManuscript(): bool
    {
        return in_array($this->status, [
            ReviewerAssignmentStatus::Accepted,
            ReviewerAssignmentStatus::Completed,
        ], true);
    }

    public function canSubmitReview(): bool
    {
        return $this->isAccepted() && $this->review()->doesntExist();
    }

    public function isOverdue(): bool
    {
        if ($this->due_at === null || $this->isCompleted() || $this->status === ReviewerAssignmentStatus::Declined || $this->status === ReviewerAssignmentStatus::Withdrawn) {
            return false;
        }

        return $this->due_at->isPast();
    }

    public function displayStatus(): ReviewerAssignmentStatus
    {
        if ($this->isOverdue() && in_array($this->status, [
            ReviewerAssignmentStatus::Invited,
            ReviewerAssignmentStatus::Accepted,
        ], true)) {
            return ReviewerAssignmentStatus::Overdue;
        }

        return $this->status;
    }
}
