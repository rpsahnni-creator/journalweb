<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InitialScreening = 'initial_screening';
    case UnderReview = 'under_review';
    case RevisionRequired = 'revision_required';
    case Resubmitted = 'resubmitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Copyediting = 'copyediting';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Withdrawn = 'withdrawn';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    public function isAuthorEditable(): bool
    {
        return $this === self::Draft || $this === self::RevisionRequired;
    }

    public function canBeScreened(): bool
    {
        return in_array($this, [self::Submitted, self::Resubmitted, self::InitialScreening], true);
    }

    public function canReceiveReviewers(): bool
    {
        return $this === self::UnderReview;
    }

    public function canReceiveEditorialDecision(): bool
    {
        return in_array($this, [self::UnderReview, self::Resubmitted], true);
    }

    public function isInEditorialQueue(): bool
    {
        return in_array($this, [
            self::Submitted,
            self::InitialScreening,
            self::UnderReview,
            self::Resubmitted,
        ], true);
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::Withdrawn],
            self::Submitted => [self::InitialScreening, self::UnderReview, self::Rejected, self::Withdrawn],
            self::InitialScreening => [self::UnderReview, self::Rejected, self::Withdrawn],
            self::UnderReview => [self::RevisionRequired, self::Accepted, self::Rejected, self::Withdrawn],
            self::RevisionRequired => [self::Resubmitted, self::Withdrawn],
            self::Resubmitted => [
                self::InitialScreening,
                self::UnderReview,
                self::RevisionRequired,
                self::Accepted,
                self::Rejected,
                self::Withdrawn,
            ],
            self::Accepted => [self::Copyediting, self::Scheduled, self::Published, self::Withdrawn],
            self::Rejected => [],
            self::Copyediting => [self::Scheduled, self::Accepted, self::Published],
            self::Scheduled => [self::Published, self::Accepted, self::Copyediting],
            self::Published => [self::Scheduled],
            self::Withdrawn => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return $this === $to || in_array($to, $this->allowedTransitions(), true);
    }

    public function canBeScheduledForPublication(): bool
    {
        return in_array($this, [self::Accepted, self::Copyediting, self::Scheduled], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::InitialScreening => 'Initial screening',
            self::UnderReview => 'Under review',
            self::RevisionRequired => 'Revision required',
            self::Resubmitted => 'Resubmitted',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Copyediting => 'Copyediting',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
