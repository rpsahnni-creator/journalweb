<?php

namespace App\Enums;

enum ArticleType: string
{
    case ResearchArticle = 'research_article';
    case Review = 'review';
    case ShortCommunication = 'short_communication';
    case CaseStudy = 'case_study';
    case Editorial = 'editorial';
    case Letter = 'letter';

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
            self::ResearchArticle => 'Research article',
            self::Review => 'Review',
            self::ShortCommunication => 'Short communication',
            self::CaseStudy => 'Case study',
            self::Editorial => 'Editorial',
            self::Letter => 'Letter to the editor',
        };
    }
}
