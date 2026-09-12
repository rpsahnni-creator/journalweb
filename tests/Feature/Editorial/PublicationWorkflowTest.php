<?php

namespace Tests\Feature\Editorial;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\IssueArticle;
use App\Models\Journal;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_manager_can_create_volumes_and_issues_but_cannot_publish(): void
    {
        $journal = Journal::factory()->create();
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);

        $this->actingAs($manager)
            ->post(route('editorial.volumes.store'), [
                'number' => 4,
                'year' => 2026,
                'title' => 'Methods volume',
            ])
            ->assertRedirect(route('editorial.volumes.index'));

        $volume = Volume::query()->where('journal_id', $journal->id)->where('number', 4)->firstOrFail();

        $this->actingAs($manager)
            ->post(route('editorial.issues.store'), [
                'volume_id' => $volume->id,
                'number' => 1,
                'title' => 'Spring issue',
                'description' => 'Accepted articles from the spring cycle.',
                'published_at' => '2026-03-01',
            ])
            ->assertRedirect();

        $issue = Issue::query()->where('volume_id', $volume->id)->where('number', 1)->firstOrFail();

        $this->assertSame(IssueStatus::Draft, $issue->status);
        $this->assertSame('Accepted articles from the spring cycle.', $issue->description);

        $article = $this->acceptedArticle($journal, 'An accepted study of publication order');

        $this->actingAs($manager)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $article->id,
                'article_number' => 'e4',
                'page_start' => 11,
                'page_end' => 20,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(ArticleStatus::Scheduled, $article->fresh()->status);
        $this->assertSame(11, $article->fresh()->page_start);
        $this->assertSame('e4', $issue->fresh()->issueArticles()->first()->article_number);

        $this->actingAs($manager)
            ->post(route('editorial.issues.publish', $issue))
            ->assertForbidden();

        $this->assertSame(IssueStatus::Draft, $issue->fresh()->status);
        $this->get(route('issues.show', ['volume' => 4, 'issue' => 1]))->assertNotFound();
        $this->get(route('articles.show', $article))->assertNotFound();
    }

    public function test_copyeditor_cannot_create_volumes_but_can_publish_and_unpublish_an_issue(): void
    {
        Storage::fake('manuscripts');

        $journal = Journal::factory()->create();
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $copyeditor = $this->createUserWithRole(RoleSlug::Copyeditor);
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 5, 'year' => 2026]);
        $issue = Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 2,
            'title' => 'Copyediting issue',
            'description' => 'Issue prepared for publication.',
            'published_at' => now()->subDay(),
        ]);
        $article = $this->acceptedArticle($journal, 'Published after copyediting');
        $privateFile = $this->attachManuscript($article, 'working-copy.pdf');
        $publicCandidate = $this->attachManuscript($article, 'version-of-record.pdf');

        $this->actingAs($copyeditor)
            ->get(route('editorial.volumes.index'))
            ->assertForbidden();

        $this->actingAs($copyeditor)
            ->post(route('editorial.volumes.store'), [
                'number' => 9,
                'year' => 2026,
            ])
            ->assertForbidden();

        $this->actingAs($copyeditor)
            ->post(route('editorial.issues.store'), [
                'volume_id' => $volume->id,
                'number' => 3,
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $article->id,
                'article_number' => 'A-12',
                'page_start' => 101,
                'page_end' => 118,
            ])
            ->assertRedirect();

        $this->actingAs($copyeditor)
            ->get(route('editorial.issues.show', $issue))
            ->assertOk()
            ->assertSee('Published after copyediting', false)
            ->assertDontSee('Assign article', false);

        $this->actingAs($copyeditor)
            ->post(route('editorial.issues.publish', $issue))
            ->assertRedirect();

        $issue->refresh();
        $article->refresh();
        $publicCandidate->refresh();
        $privateFile->refresh();

        $this->assertSame(IssueStatus::Published, $issue->status);
        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertNotNull($article->published_at);
        $this->assertTrue($publicCandidate->is_public);
        $this->assertFalse($privateFile->is_public);

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Published after copyediting', false)
            ->assertSee('Issue prepared for publication', false);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('Published after copyediting', false)
            ->assertSee('How to cite', false)
            ->assertSee('A-12', false)
            ->assertSee('101–118', false)
            ->assertSee($article->authors->first()->affiliation, false)
            ->assertDontSee('ISSN', false)
            ->assertDontSee('Scopus', false)
            ->assertDontSee('doi.org', false);

        $this->get(route('articles.pdf', $article))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($copyeditor)
            ->post(route('editorial.issues.unpublish', $issue))
            ->assertRedirect();

        $this->assertSame(IssueStatus::Draft, $issue->fresh()->status);
        $this->assertSame(ArticleStatus::Scheduled, $article->fresh()->status);
        $this->assertNull($article->fresh()->published_at);
        $this->assertFalse($publicCandidate->fresh()->is_public);
        $this->get(route('articles.show', $article))->assertNotFound();
        $this->get(route('articles.pdf', $article))->assertNotFound();
        $this->get(route('issues.show', ['volume' => 5, 'issue' => 2]))->assertNotFound();
    }

    public function test_authors_and_section_editors_cannot_manage_issues(): void
    {
        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author);
        $sectionEditor = $this->createUserWithRole(RoleSlug::SectionEditor);
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 6]);
        $issue = Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
        ]);

        $this->actingAs($author)->get(route('editorial.issues.index'))->assertForbidden();
        $this->actingAs($author)->post(route('editorial.issues.publish', $issue))->assertForbidden();
        $this->actingAs($sectionEditor)->get(route('editorial.volumes.index'))->assertForbidden();
        $this->actingAs($sectionEditor)->get(route('editorial.issues.index'))->assertForbidden();
    }

    public function test_empty_issues_cannot_be_published_and_articles_keep_their_order(): void
    {
        $journal = Journal::factory()->create();
        $editorInChief = $this->createUserWithRole(RoleSlug::EditorInChief);
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 7]);
        $issue = Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
        ]);

        $this->actingAs($editorInChief)
            ->post(route('editorial.issues.publish', $issue))
            ->assertRedirect()
            ->assertSessionHas('error');

        $first = $this->acceptedArticle($journal, 'First assigned article');
        $second = $this->acceptedArticle($journal, 'Second assigned article');

        $this->actingAs($editorInChief)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $first->id,
                'sort_order' => 2,
            ])
            ->assertRedirect();

        $this->actingAs($editorInChief)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $second->id,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($editorInChief)
            ->put(route('editorial.issues.articles.reorder', $issue), [
                'order' => [
                    $first->id => 1,
                    $second->id => 2,
                ],
            ])
            ->assertRedirect();

        $this->assertSame(
            [$first->id, $second->id],
            $issue->fresh()->issueArticles()->orderBy('sort_order')->pluck('article_id')->all()
        );

        $underReview = Article::factory()->create([
            'journal_id' => $journal->id,
            'status' => ArticleStatus::UnderReview,
            'title' => 'Still under review',
        ]);

        $this->actingAs($editorInChief)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $underReview->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($editorInChief)
            ->post(route('editorial.issues.publish', $issue))
            ->assertRedirect();

        $this->actingAs($editorInChief)
            ->delete(route('editorial.issues.articles.destroy', [$issue, IssueArticle::query()->where('article_id', $first->id)->firstOrFail()]))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->get(route('articles.show', $first->fresh()))
            ->assertOk();
        $this->get(route('articles.show', $second->fresh()))
            ->assertOk();
        $this->assertNotSame($first->slug, $second->slug);
    }

    public function test_a_volume_with_issues_cannot_be_deleted(): void
    {
        $journal = Journal::factory()->create();
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 8]);
        Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
        ]);

        $this->actingAs($manager)
            ->delete(route('editorial.volumes.destroy', $volume))
            ->assertForbidden();

        $this->assertTrue($volume->exists());
    }

    private function acceptedArticle(Journal $journal, string $title): Article
    {
        $author = User::factory()->create([
            'name' => 'Priya Raman',
            'affiliation' => 'Centre for Editorial Studies',
        ]);

        $article = Article::factory()->accepted()->create([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $author->id,
            'title' => $title,
            'abstract' => 'Abstract text describing the accepted article for the public record after publication.',
            'keywords' => ['publication', 'archive', $title],
        ]);

        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $article->id,
            'user_id' => $author->id,
            'name' => $author->name,
            'affiliation' => $author->affiliation,
            'sequence' => 1,
        ]);

        return $article->fresh(['authors']);
    }

    private function attachManuscript(Article $article, string $filename): ArticleFile
    {
        $path = $article->id.'/'.$filename;
        Storage::disk('manuscripts')->put($path, '%PDF-1.4 test-file');

        return ArticleFile::factory()->create([
            'article_id' => $article->id,
            'uploaded_by' => $article->corresponding_author_id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => $filename,
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);
    }
}
