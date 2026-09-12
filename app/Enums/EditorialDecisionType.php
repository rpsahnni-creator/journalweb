<?php

namespace App\Enums;

enum EditorialDecisionType: string
{
    case SendToReview = 'send_to_review';
    case Accept = 'accept';
    case MinorRevision = 'minor_revision';
    case MajorRevision = 'major_revision';
    case Reject = 'reject';
    case Withdraw = 'withdraw';
    case Schedule = 'schedule';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SendToReview => 'Send to peer review',
            self::Accept => 'Accept',
            self::MinorRevision => 'Minor revision',
            self::MajorRevision => 'Major revision',
            self::Reject => 'Reject',
            self::Withdraw => 'Withdraw',
            self::Schedule => 'Schedule',
        };
    }

    public function isAuthorFacing(): bool
    {
        return in_array($this, [
            self::Accept,
            self::MinorRevision,
            self::MajorRevision,
            self::Reject,
        ], true);
    }

    public function requiresRevisionDeadline(): bool
    {
        return $this === self::MinorRevision || $this === self::MajorRevision;
    }

    /**
     * @return list<self>
     */
    public static function postReviewCases(): array
    {
        return [
            self::Accept,
            self::MinorRevision,
            self::MajorRevision,
            self::Reject,
        ];
    }

    public function resultingStatus(): ?ArticleStatus
    {
        return match ($this) {
            self::SendToReview => ArticleStatus::UnderReview,
            self::MinorRevision, self::MajorRevision => ArticleStatus::RevisionRequired,
            self::Accept => ArticleStatus::Accepted,
            self::Reject => ArticleStatus::Rejected,
            self::Withdraw => ArticleStatus::Withdrawn,
            self::Schedule => ArticleStatus::Scheduled,
        };
    }
}
