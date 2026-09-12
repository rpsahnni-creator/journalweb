<?php

namespace App\Enums;

enum SubmissionReviewStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';

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
            self::Assigned => 'Assigned',
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Assigned => 'bg-slate-100 text-slate-700 ring-slate-400/20',
            self::InProgress => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            self::Submitted => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        };
    }

    public function isSubmitted(): bool
    {
        return $this === self::Submitted;
    }
}
