<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Published = 'published';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::RevisionRequested => 'Revision requested',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Published => 'Published',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Submitted => 'bg-slate-100 text-slate-700 ring-slate-400/20',
            self::UnderReview => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::RevisionRequested => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Accepted => 'bg-teal-50 text-teal-700 ring-teal-600/20',
            self::Rejected => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            self::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        };
    }
}
