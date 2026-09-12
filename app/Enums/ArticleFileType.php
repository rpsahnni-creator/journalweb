<?php

namespace App\Enums;

enum ArticleFileType: string
{
    case Manuscript = 'manuscript';
    case Supplementary = 'supplementary';
    case CoverLetter = 'cover_letter';
    case Figure = 'figure';
    case Table = 'table';
    case Revision = 'revision';
    case CameraReady = 'camera_ready';

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
            self::Manuscript => 'Manuscript',
            self::Supplementary => 'Supplementary file',
            self::CoverLetter => 'Cover letter',
            self::Figure => 'Figure',
            self::Table => 'Table',
            self::Revision => 'Revision file',
            self::CameraReady => 'Camera-ready file',
        };
    }
}
