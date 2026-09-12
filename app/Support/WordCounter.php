<?php

namespace App\Support;

class WordCounter
{
    public static function count(?string $text): int
    {
        $trimmed = trim((string) $text);

        if ($trimmed === '') {
            return 0;
        }

        return str($trimmed)
            ->split('/\s+/u')
            ->filter(fn (string $word): bool => $word !== '')
            ->count();
    }

    /**
     * @return list<string>
     */
    public static function keywords(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
