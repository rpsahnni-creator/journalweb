<?php

namespace App\Models;

use App\Enums\ReviewRecommendation;
use App\Enums\SubmissionReviewStatus;
use Database\Factories\SubmissionReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SubmissionReview extends Model
{
    /** @use HasFactory<SubmissionReviewFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'reviewer_id',
        'recommendation',
        'comments_to_editor',
        'comments_to_author',
        'status',
        'round',
        'assigned_at',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommendation' => ReviewRecommendation::class,
            'status' => SubmissionReviewStatus::class,
            'round' => 'integer',
            'assigned_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function isAssignedTo(User $user): bool
    {
        return $this->reviewer_id === $user->id;
    }

    public function isSubmitted(): bool
    {
        return $this->status === SubmissionReviewStatus::Submitted;
    }

    public function markInProgress(): void
    {
        if ($this->status === SubmissionReviewStatus::Assigned) {
            $this->update(['status' => SubmissionReviewStatus::InProgress]);
        }
    }

    public function dueAt(): ?Carbon
    {
        if ($this->assigned_at === null) {
            return null;
        }

        $days = (int) config('submissions.review_due_days', 21);

        return $this->assigned_at->copy()->addDays($days);
    }

    public function isOverdue(): bool
    {
        if ($this->isSubmitted()) {
            return false;
        }

        $dueAt = $this->dueAt();

        return $dueAt !== null && $dueAt->isPast();
    }

    public function dueStatusLabel(): string
    {
        if ($this->isSubmitted()) {
            return 'Submitted';
        }

        $dueAt = $this->dueAt();

        if ($dueAt === null) {
            return 'Assigned';
        }

        if ($this->isOverdue()) {
            return 'Overdue';
        }

        return 'Due '.$dueAt->toFormattedDateString();
    }
}
