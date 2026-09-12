<?php

namespace App\Enums;

enum ReviewerAssignmentStatus: string
{
    case Invited = 'invited';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Overdue = 'overdue';

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
            self::Invited => 'Invited',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Completed => 'Completed',
            self::Withdrawn => 'Withdrawn',
            self::Overdue => 'Overdue',
        };
    }
}
