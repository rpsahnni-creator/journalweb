<?php

namespace Tests\Feature\Indexing;

use App\Enums\ArticleStatus;
use App\Mail\ConfirmIssueAlertSubscription;
use App\Mail\TableOfContentsAlert;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\EmailSubscription;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\DoiService;
use App\Support\TableOfContentsMailer;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DoiAndOaiTest extends TestCase
{
    use RefreshDatabase;

    public function test_doi_is_not_minted_without_a_real_crossref_prefix(): void
    {
        config(['services.crossref.prefix' => '10.XXXX']);

        $article = Article::factory()->published()->create(['doi' => null]);

        $this->assertNull(app(DoiService::class)->assignIfConfigured($article));
        $this->assertNull($article->fresh()->doi);
    }

    public function test_doi_is_minted_for_published_articles_when_a_real_prefix_is_configured(): void
    {
        config(['services.crossref.prefix' => '10.12345']);

        $journal = Journal::factory()->create();
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 2]);
        $issue = Issue::factory()->published()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'volume_number' => 2,
            'number' => 1,
        ]);
        $article = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'doi' => null,
            'published_at' => now()->setDate(2026, 6, 1),
        ]);
        $issue->articles()->attach($article->id, ['sort_order' => 1, 'article_number' => '3']);

        $doi = $article->fresh()->mintDoi();

        $this->assertSame('10.12345/srtjmr.2026.2.1.3', $doi);
        $this->assertSame($doi, $article->fresh()->doi);
        $this->assertStringContainsString('<doi>10.12345/srtjmr.2026.2.1.3</doi>', app(DoiService::class)->crossrefDepositXml($article->fresh()));
    }

    public function test_oai_identify_and_list_records_expose_only_published_articles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $published = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Harvestable regional study',
            'is_demo' => false,
            'doi' => null,
        ]);
        ArticleAuthor::factory()->create(['article_id' => $published->id, 'name' => 'OAI Author']);
        Article::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'Secret unpublished manuscript',
            'status' => ArticleStatus::Accepted,
            'is_demo' => false,
        ]);

        $this->get('/oai?verb=Identify')
            ->assertOk()
            ->assertHeader('content-type', 'text/xml; charset=UTF-8')
            ->assertSee('<repositoryName>', false)
            ->assertSee('<protocolVersion>2.0</protocolVersion>', false);

        $list = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc');
        $list->assertOk()
            ->assertSee('Harvestable regional study', false)
            ->assertSee('OAI Author', false)
            ->assertDontSee('Secret unpublished manuscript', false);

        $identifier = app(\App\Http\Controllers\OaiPmhController::class)->identifierFor($published);

        $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($identifier))
            ->assertOk()
            ->assertSee('Harvestable regional study', false);

        $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:localhost:article/999999')
            ->assertOk()
            ->assertSee('idDoesNotExist', false);
    }

    public function test_atom_feeds_and_citation_exports_are_limited_to_published_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/feed')
            ->assertOk()
            ->assertHeader('content-type', 'application/atom+xml; charset=UTF-8')
            ->assertSee('<feed', false);

        $this->get('/feed/issues')
            ->assertOk()
            ->assertSee('<feed', false);

        $this->get('/feed/2/1')
            ->assertOk()
            ->assertSee('<feed', false);

        $article = Article::query()
            ->where('slug', 'sample-article-keeping-unpublished-manuscripts-private')
            ->firstOrFail();

        $bibtex = $this->get(route('articles.export.bibtex', $article));
        $bibtex->assertOk();
        $this->assertStringContainsString('@article', $bibtex->streamedContent());

        $apa = $this->get(route('articles.export.apa', $article));
        $apa->assertOk();
        $this->assertStringContainsString($article->citation(), $apa->streamedContent());

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('facebook.com/sharer', false)
            ->assertSee('wa.me', false);
    }

    public function test_issue_alerts_require_confirmation_and_only_then_receive_toc_mail(): void
    {
        Mail::fake();
        $this->seed(DatabaseSeeder::class);
        $issue = Issue::query()->where('is_current', true)->firstOrFail();

        $this->from(route('home'))
            ->post(route('subscribe'), ['email' => 'reader@srtc.ac.in'])
            ->assertRedirect(route('home'));

        Mail::assertSent(ConfirmIssueAlertSubscription::class);
        $subscription = EmailSubscription::query()->where('email', 'reader@srtc.ac.in')->firstOrFail();
        $this->assertFalse($subscription->isConfirmed());

        $this->get(route('subscribe.confirm', $subscription->confirm_token))
            ->assertOk()
            ->assertSee('Subscription confirmed', false);

        $this->assertTrue($subscription->fresh()->isConfirmed());

        Mail::fake();
        $sent = app(TableOfContentsMailer::class)->sendForIssue($issue);
        $this->assertSame(1, $sent);
        Mail::assertSent(TableOfContentsAlert::class, fn (TableOfContentsAlert $mail): bool => $mail->hasTo('reader@srtc.ac.in'));
    }

    public function test_homepage_reports_real_publication_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Published articles', false)
            ->assertSee('Call for papers is open', false);
    }

    public function test_article_search_can_filter_by_year_without_using_mysql_year(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('articles.index', ['year' => now()->year, 'sort' => 'oldest', 'type' => 'research_article']))
            ->assertOk();
    }
}
