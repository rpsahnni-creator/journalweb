<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DirectArticlePublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_non_editors_cannot_open_the_direct_article_form(): void
    {
        $this->get(route('admin.articles.create'))->assertRedirect(route('login'));

        $author = $this->createUserWithRole(RoleSlug::Author);
        $adminWithoutFlag = $this->createUserWithRole(RoleSlug::Admin, ['is_editor' => false]);

        $this->actingAs($author)->get(route('admin.articles.create'))->assertForbidden();
        $this->actingAs($adminWithoutFlag)->get(route('admin.articles.create'))->assertForbidden();
    }

    public function test_editor_can_publish_an_article_directly_to_the_current_issue(): void
    {
        Storage::fake('publications');

        $journal = Journal::factory()->create([
            'name' => 'SRT Journal of Multidisciplinary Research',
        ]);
        $editor = User::factory()->editor()->create();
        $issue = $this->makeCurrentIssue($journal);

        $this->actingAs($editor)
            ->get(route('admin.issues.index'))
            ->assertOk()
            ->assertSee('Add Article Directly', false)
            ->assertSee('Publish to Issue', false);

        $this->actingAs($editor)
            ->get(route('admin.articles.create'))
            ->assertOk()
            ->assertSee('I confirm this manuscript has been reviewed by the editorial team and is free of plagiarism.', false)
            ->assertSee('Publish to Issue', false);

        $this->actingAs($editor)
            ->from(route('admin.articles.create'))
            ->post(route('admin.articles.store'), $this->validPayload($issue, [
                'title' => 'Regional memory and village schooling',
            ]))
            ->assertRedirect(route('admin.issues.index'))
            ->assertSessionHas('status');

        $article = Article::query()->where('title', 'Regional memory and village schooling')->firstOrFail();

        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertFalse($article->is_demo);
        $this->assertSame($issue->id, $article->issue_id);
        $this->assertNotNull($article->slug);
        $this->assertNotNull($article->published_at);
        $this->assertSame($editor->id, $article->editor_id);
        $this->assertNotNull($article->confirmed_at);
        $this->assertTrue($article->authors()->where('name', 'Ada Author')->where('is_corresponding', true)->exists());
        $this->assertTrue($article->authors()->where('name', 'Grace Coauthor')->exists());
        $this->assertTrue(Storage::disk('publications')->exists($article->pdf_path));
        $this->assertTrue($issue->articles()->whereKey($article->id)->exists());

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('Regional memory and village schooling', false)
            ->assertSee('Ada Author', false)
            ->assertSee('How to cite this article', false)
            ->assertSee('<meta name="citation_title" content="Regional memory and village schooling">', false)
            ->assertSee('<meta name="citation_author" content="Ada Author">', false)
            ->assertSee('<meta name="citation_author" content="Grace Coauthor">', false)
            ->assertSee('<meta name="citation_journal_title" content="SRT Journal of Multidisciplinary Research">', false)
            ->assertSee('<meta name="citation_volume" content="1">', false)
            ->assertSee('<meta name="citation_issue" content="1">', false)
            ->assertSee('<meta name="citation_pdf_url" content="'.route('articles.pdf', $article).'">', false);
    }

    public function test_direct_publish_requires_the_editorial_confirmation(): void
    {
        Storage::fake('publications');

        $journal = Journal::factory()->create();
        $editor = User::factory()->editor()->create();
        $issue = $this->makeCurrentIssue($journal);

        $payload = $this->validPayload($issue);
        unset($payload['editorial_confirmation']);

        $this->actingAs($editor)
            ->from(route('admin.articles.create'))
            ->post(route('admin.articles.store'), $payload)
            ->assertRedirect(route('admin.articles.create'))
            ->assertSessionHasErrors('editorial_confirmation');

        $this->assertSame(0, Article::query()->count());
    }

    public function test_five_direct_articles_replace_the_call_for_papers_on_the_current_issue(): void
    {
        Storage::fake('publications');

        $journal = Journal::factory()->create();
        $editor = User::factory()->editor()->create();
        $issue = $this->makeCurrentIssue($journal);

        $this->actingAs($editor)
            ->post(route('admin.articles.store'), $this->validPayload($issue, [
                'title' => 'Fifth inaugural research article',
            ]))
            ->assertRedirect(route('admin.issues.index'));

        foreach (range(1, 4) as $number) {
            $other = Article::factory()->published()->create([
                'journal_id' => $journal->id,
                'issue_id' => $issue->id,
                'is_demo' => false,
                'title' => "Companion research article {$number}",
            ]);
            $issue->articles()->attach($other->id, [
                'sort_order' => $number,
                'article_number' => (string) $number,
            ]);
        }

        $this->assertSame(5, $issue->fresh()->publishedNonDemoArticleCount());

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Fifth inaugural research article', false)
            ->assertDontSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertDontSee('currently accepting submissions for its inaugural issue', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Fifth inaugural research article', false)
            ->assertDontSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false);
    }

    private function makeCurrentIssue(Journal $journal): Issue
    {
        $volume = Volume::factory()->create([
            'journal_id' => $journal->id,
            'number' => 1,
            'year' => 2026,
        ]);

        return Issue::factory()->published()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'volume_number' => 1,
            'number' => 1,
            'publication_month_year' => 'June 2026',
            'is_current' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Issue $issue, array $overrides = []): array
    {
        return array_merge([
            'title' => 'A complete regional research article',
            'abstract' => implode(' ', array_fill(0, 80, 'word')),
            'authors' => 'Ada Author, Grace Coauthor',
            'keywords' => 'history, education, society, culture',
            'issue_id' => $issue->id,
            'page_start' => 11,
            'page_end' => 24,
            'pdf' => UploadedFile::fake()->create('formatted.pdf', 80, 'application/pdf'),
            'editorial_confirmation' => '1',
        ], $overrides);
    }
}
