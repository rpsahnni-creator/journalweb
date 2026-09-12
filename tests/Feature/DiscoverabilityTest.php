<?php

namespace Tests\Feature;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiscoverabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_emits_google_scholar_and_dublin_core_tags_from_article_data(): void
    {
        Storage::fake('manuscripts');

        $journal = Journal::factory()->create([
            'name' => 'SRT Journal of Multidisciplinary Research',
            'publisher' => 'Sri Raghunandan Tiwari College (S.R.T. College)',
            'issn' => null,
        ]);
        $journal->settings()->updateOrCreate(['key' => 'issn'], ['value' => '2345-6789']);

        $volume = Volume::factory()->create([
            'journal_id' => $journal->id,
            'number' => 4,
        ]);
        $issue = Issue::factory()->published()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'volume_number' => 4,
            'number' => 2,
            'publication_month_year' => 'June 2026',
        ]);

        $article = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Open access indexing for regional research',
            'abstract' => 'An abstract describing the published regional study.',
            'page_start' => 11,
            'page_end' => 28,
            'is_demo' => false,
            'published_at' => now()->subDays(3),
        ]);

        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $article->id,
            'name' => 'Ada Author',
            'sequence' => 1,
        ]);
        ArticleAuthor::factory()->create([
            'article_id' => $article->id,
            'name' => 'Grace Coauthor',
            'sequence' => 2,
        ]);

        $issue->articles()->attach($article->id, [
            'sort_order' => 1,
            'article_number' => '1',
        ]);

        $path = $article->id.'/version-of-record.pdf';
        Storage::disk('manuscripts')->put($path, '%PDF-1.4 sample');
        ArticleFile::factory()->create([
            'article_id' => $article->id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => 'version-of-record.pdf',
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => true,
        ]);

        $html = $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('<meta name="citation_title" content="Open access indexing for regional research">', false)
            ->assertSee('<meta name="citation_author" content="Ada Author">', false)
            ->assertSee('<meta name="citation_author" content="Grace Coauthor">', false)
            ->assertSee('<meta name="citation_publication_date" content="2026/06">', false)
            ->assertSee('<meta name="citation_journal_title" content="SRT Journal of Multidisciplinary Research">', false)
            ->assertSee('<meta name="citation_issn" content="2345-6789">', false)
            ->assertSee('<meta name="citation_volume" content="4">', false)
            ->assertSee('<meta name="citation_issue" content="2">', false)
            ->assertSee('<meta name="citation_firstpage" content="11">', false)
            ->assertSee('<meta name="citation_lastpage" content="28">', false)
            ->assertSee('<meta name="citation_pdf_url" content="'.route('articles.pdf', $article).'">', false)
            ->assertSee('<meta name="citation_abstract_html_url" content="'.$article->publicUrl().'">', false)
            ->assertSee('<meta name="DC.Title" content="Open access indexing for regional research">', false)
            ->assertSee('<meta name="DC.Creator" content="Ada Author">', false)
            ->content();

        $this->assertSame(1, substr_count($html, 'citation_title'));
        $this->assertSame(2, substr_count($html, 'name="citation_author"'));
    }

    public function test_citation_issn_is_omitted_until_journal_settings_have_an_issn(): void
    {
        $journal = Journal::factory()->create(['issn' => null]);
        $journal->settings()->updateOrCreate(['key' => 'issn'], ['value' => null]);
        $article = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'is_demo' => false,
        ]);
        ArticleAuthor::factory()->create([
            'article_id' => $article->id,
            'name' => 'Solo Author',
        ]);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('<meta name="citation_title" content="'.$article->title.'">', false)
            ->assertDontSee('citation_issn', false);
    }

    public function test_sitemap_lists_public_pages_published_issues_and_non_demo_articles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $journal = Journal::query()->firstOrFail();
        $issue = Issue::query()->where('journal_id', $journal->id)->published()->firstOrFail();

        $published = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Indexed inaugural research article',
            'slug' => 'indexed-inaugural-research-article',
            'is_demo' => false,
            'published_at' => now()->subDay(),
        ]);
        $issue->articles()->attach($published->id, ['sort_order' => 20, 'article_number' => '9']);

        $draft = Article::factory()->create([
            'journal_id' => $journal->id,
            'status' => ArticleStatus::UnderReview,
            'slug' => 'confidential-under-review',
            'is_demo' => false,
            'published_at' => null,
        ]);

        $xml = $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->content();

        $this->assertStringContainsString('<loc>'.route('home').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('about').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('aims-and-scope').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('editorial-board').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('author-guidelines').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('plagiarism-policy').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('conflict-of-interest').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('review-process').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('issues.index').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.$issue->publicUrl().'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.$published->publicUrl().'</loc>', $xml);
        $this->assertStringContainsString('<lastmod>'.$published->published_at->toDateString().'</lastmod>', $xml);
        $this->assertStringNotContainsString($draft->publicUrl(), $xml);
        $this->assertStringNotContainsString('sample-article-keeping-unpublished-manuscripts-private', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
        $this->assertStringNotContainsString('/submissions', $xml);
        $this->assertStringNotContainsString('/reviews', $xml);
    }

    public function test_robots_txt_allows_public_pages_and_blocks_private_areas(): void
    {
        $response = $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8');

        $body = $response->getContent();

        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Allow: /', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /submissions', $body);
        $this->assertStringContainsString('Disallow: /reviews', $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }

    public function test_register_login_and_submission_create_routes_are_throttled(): void
    {
        $this->assertContains('throttle:10,1', $this->middlewareFor('register', 'GET'));
        $this->assertContains('throttle:10,1', $this->middlewareFor('register', 'POST'));
        $this->assertContains('throttle:10,1', $this->middlewareFor('login', 'GET'));
        $this->assertTrue(
            collect($this->middlewareFor('login', 'POST'))->contains(fn ($middleware): bool => str_starts_with((string) $middleware, 'throttle:'))
        );
        $this->assertContains('throttle:10,1', Route::getRoutes()->getByName('submissions.create')->gatherMiddleware());
        $this->assertContains('throttle:10,1', Route::getRoutes()->getByName('submissions.store')->gatherMiddleware());
    }

    /**
     * @return list<string>
     */
    private function middlewareFor(string $uri, string $method): array
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route): bool => $route->uri() === $uri && in_array($method, $route->methods(), true)
        );

        $this->assertNotNull($route);

        return $route->gatherMiddleware();
    }
}
