<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatus;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssuePublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_an_issue_and_mark_it_current(): void
    {
        Journal::factory()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->post(route('admin.issues.store'), [
                'volume_number' => 1,
                'issue_number' => 1,
                'title' => 'Inaugural issue',
                'publication_month_year' => 'June 2026',
            ])
            ->assertRedirect(route('admin.issues.index'));

        $first = Issue::query()->firstOrFail();
        $this->assertSame(1, $first->volume_number);
        $this->assertSame(1, $first->number);
        $this->assertSame('June 2026', $first->publication_month_year);
        $this->assertFalse($first->is_current);

        $this->actingAs($editor)
            ->post(route('admin.issues.store'), [
                'volume_number' => 1,
                'issue_number' => 2,
                'title' => 'Second issue',
                'publication_month_year' => 'December 2026',
            ])
            ->assertRedirect(route('admin.issues.index'));

        $second = Issue::query()->where('number', 2)->firstOrFail();

        $this->actingAs($editor)
            ->post(route('admin.issues.current', $second))
            ->assertRedirect(route('admin.issues.index'));

        $this->assertTrue($second->fresh()->is_current);
        $this->assertFalse($first->fresh()->is_current);
        $this->assertSame(1, Issue::query()->where('is_current', true)->count());
    }

    public function test_editor_can_mark_an_issue_as_a_special_issue(): void
    {
        Journal::factory()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->post(route('admin.issues.store'), [
                'volume_number' => 3,
                'issue_number' => 1,
                'title' => 'Thematic issue',
                'publication_month_year' => 'June 2027',
                'is_special_issue' => '1',
                'special_issue_theme' => 'Education and Regional Development',
            ])
            ->assertRedirect(route('admin.issues.index'));

        $issue = Issue::query()->where('number', 1)->where('volume_number', 3)->firstOrFail();
        $this->assertTrue($issue->is_special_issue);
        $this->assertSame('Education and Regional Development', $issue->special_issue_theme);

        $this->actingAs($editor)
            ->get(route('admin.issues.index'))
            ->assertOk()
            ->assertSee('Special Issue', false)
            ->assertSee('Education and Regional Development', false);

        $this->actingAs($editor)
            ->put(route('admin.issues.update', $issue), [
                'volume_number' => 3,
                'issue_number' => 1,
                'title' => 'Thematic issue',
                'publication_month_year' => 'June 2027',
                'special_issue_theme' => 'Should be cleared',
            ])
            ->assertRedirect(route('admin.issues.index'));

        $this->assertFalse($issue->fresh()->is_special_issue);
        $this->assertNull($issue->fresh()->special_issue_theme);
    }

    public function test_accepted_submission_can_be_published_to_an_issue(): void
    {
        Storage::fake('publications');

        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author, [
            'name' => 'Ada Author',
            'affiliation' => 'SRT College',
        ]);
        $editor = User::factory()->editor()->create();
        $volume = Volume::factory()->create([
            'journal_id' => $journal->id,
            'number' => 3,
            'year' => 2026,
        ]);
        $issue = Issue::factory()->published()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'volume_number' => 3,
            'number' => 2,
            'publication_month_year' => 'June 2026',
            'is_current' => true,
        ]);
        $submission = Submission::factory()->accepted()->create([
            'user_id' => $author->id,
            'title' => 'Village education and local memory',
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'education, society, culture, history',
            'co_authors' => 'Jane Researcher, SRT College',
        ]);

        $this->actingAs($editor)
            ->from(route('admin.submissions.show', $submission))
            ->post(route('admin.submissions.publish', $submission), [
                'issue_id' => $issue->id,
                'page_start' => 11,
                'page_end' => 24,
                'pdf' => UploadedFile::fake()->create('formatted.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.submissions.show', $submission));

        $submission->refresh();
        $article = Article::query()->findOrFail($submission->article_id);

        $this->assertSame(SubmissionStatus::Published, $submission->status);
        $this->assertSame(ArticleStatus::Published, $article->status);
        $this->assertFalse($article->is_demo);
        $this->assertSame($issue->id, $article->issue_id);
        $this->assertNotNull($article->published_at);
        $this->assertNotNull($article->slug);
        $this->assertTrue($article->authors()->where('name', 'Ada Author')->where('is_corresponding', true)->exists());
        $this->assertTrue($article->authors()->where('name', 'Jane Researcher')->exists());
        $this->assertTrue(Storage::disk('publications')->exists($article->pdf_path));

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertSee('Village education and local memory', false)
            ->assertSee('Ada Author', false)
            ->assertSee('Jane Researcher', false)
            ->assertSee('Vol. 3, No. 2, June 2026', false)
            ->assertSee('pp. 11–24', false)
            ->assertSee('Download PDF', false)
            ->assertSee('How to cite this article', false)
            ->assertSee('Ada Author, Jane Researcher. ('.$article->published_at->format('Y').'). Village education and local memory. SRT Journal of Multidisciplinary Research, 3(2).', false);

        $this->get(route('articles.pdf', $article))
            ->assertOk()
            ->assertHeader('content-disposition');

        foreach (range(2, 5) as $number) {
            $other = Article::factory()->published()->create([
                'journal_id' => $journal->id,
                'issue_id' => $issue->id,
                'is_demo' => false,
                'title' => "Other research paper {$number}",
            ]);
            $issue->articles()->attach($other->id, [
                'sort_order' => $number,
                'article_number' => (string) $number,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Village education and local memory', false)
            ->assertDontSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false);

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('Previous Issues', false)
            ->assertSee('Vol. 3 No. 2', false);
    }

    public function test_homepage_shows_call_for_papers_when_no_current_issue_has_published_articles(): void
    {
        Journal::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertSee('Submit Your Article', false);
    }

    public function test_authors_cannot_publish_or_manage_issues(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author);
        $submission = Submission::factory()->accepted()->create(['user_id' => $author->id]);

        $this->actingAs($author)->get(route('admin.issues.index'))->assertForbidden();
        $this->actingAs($author)->post(route('admin.submissions.publish', $submission), [
            'issue_id' => 1,
        ])->assertForbidden();
    }
}
