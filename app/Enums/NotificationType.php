<?php

namespace App\Enums;

enum NotificationType: string
{
    case SubmissionReceived = 'manuscript.submitted';
    case RevisionSubmitted = 'manuscript.revised';
    case ReviewerInvitation = 'review.invited';
    case ReviewerAccepted = 'review.accepted';
    case ReviewerDeclined = 'review.declined';
    case ReviewReminder = 'review.reminder';
    case ReviewSubmitted = 'review.submitted';
    case RevisionRequested = 'decision.revision';
    case ArticleAccepted = 'decision.accepted';
    case ArticleRejected = 'decision.rejected';
    case ArticlePublished = 'article.published';
    case PasswordReset = 'auth.password_reset';
    case MailTest = 'mail.test';

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
            self::SubmissionReceived => 'New submission',
            self::RevisionSubmitted => 'Revision submitted',
            self::ReviewerInvitation => 'Reviewer invitation',
            self::ReviewerAccepted => 'Reviewer accepted assignment',
            self::ReviewerDeclined => 'Reviewer declined assignment',
            self::ReviewReminder => 'Review reminder',
            self::ReviewSubmitted => 'Review submitted',
            self::RevisionRequested => 'Revision requested',
            self::ArticleAccepted => 'Article accepted',
            self::ArticleRejected => 'Article rejected',
            self::ArticlePublished => 'Article published',
            self::PasswordReset => 'Password reset',
            self::MailTest => 'Development mail test',
        };
    }
}
