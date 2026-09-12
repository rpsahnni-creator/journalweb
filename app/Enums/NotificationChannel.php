<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case Mail = 'mail';
    case Database = 'database';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
