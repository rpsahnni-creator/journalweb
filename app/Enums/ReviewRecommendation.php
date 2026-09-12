<?php

namespace App\Enums;

enum ReviewRecommendation: string
{
    case Accept = 'accept';
    case MinorRevision = 'minor_revision';
    case MajorRevision = 'major_revision';
    case Reject = 'reject';

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
            ->mapWithKeys(fn (self $recommendation): array => [$recommendation->value => $recommendation->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Accept => 'Accept',
            self::MinorRevision => 'Minor revision',
            self::MajorRevision => 'Major revision',
            self::Reject => 'Reject',
        };
    }
}
