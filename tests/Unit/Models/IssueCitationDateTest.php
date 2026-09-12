<?php

namespace Tests\Unit\Models;

use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IssueCitationDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_citation_publication_date_formats_month_year_as_yyyy_mm(): void
    {
        $issue = new Issue([
            'publication_month_year' => 'June 2026',
            'published_at' => now()->setDate(2025, 1, 15),
        ]);

        $this->assertSame('2026/06', $issue->citationPublicationDate());
    }

    public function test_citation_publication_date_falls_back_to_published_at(): void
    {
        $issue = new Issue([
            'publication_month_year' => null,
            'published_at' => now()->setDate(2025, 3, 1),
        ]);

        $this->assertSame('2025/03', $issue->citationPublicationDate());
    }
}
