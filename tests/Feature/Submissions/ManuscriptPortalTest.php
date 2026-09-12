<?php

namespace Tests\Feature\Submissions;

use App\Enums\ArticleStatus;
use App\Enums\RoleSlug;
use App\Enums\SubmissionStatus;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManuscriptPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_submission_portal(): void
    {
        $this->get(route('submissions.index'))->assertRedirect(route('login'));
        $this->get(route('submissions.create'))->assertRedirect(route('login'));
        $this->post(route('submissions.store'))->assertRedirect(route('login'));
    }

    public function test_author_can_submit_a_manuscript_and_see_only_their_own_status(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author);
        $other = $this->createUserWithRole(RoleSlug::Author);
        $foreign = Submission::factory()->create([
            'user_id' => $other->id,
            'title' => 'Foreign confidential manuscript',
        ]);

        $this->actingAs($author)
            ->get(route('submissions.create'))
            ->assertOk()
            ->assertSee('Submit manuscript', false);

        $this->actingAs($author)
            ->from(route('submissions.create'))
            ->post(route('submissions.store'), $this->validPayload([
                'title' => 'Regional education study',
            ]))
            ->assertRedirect(route('submissions.index'))
            ->assertSessionHas('status');

        $submission = Submission::query()->where('title', 'Regional education study')->firstOrFail();

        $this->assertSame($author->id, $submission->user_id);
        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertNotNull($submission->submitted_at);
        $this->assertTrue(Storage::disk('submissions')->exists($submission->manuscript_path));
        $this->assertSame(1, $submission->versions()->count());
        $this->assertSame(1, $submission->latestVersion?->version_number);
        $this->assertSame($submission->manuscript_path, $submission->latestVersion?->manuscript_path);

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Regional education study', false)
            ->assertSee('Submitted', false)
            ->assertDontSee('Foreign confidential manuscript', false)
            ->assertDontSee('Internal referee note', false);

        $this->actingAs($author)
            ->get(route('submissions.download', $foreign))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('admin.submissions.index'))
            ->assertForbidden();
    }

    public function test_submission_validates_abstract_keywords_and_file_type(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author);

        $this->actingAs($author)
            ->from(route('submissions.create'))
            ->post(route('submissions.store'), [
                'title' => 'Too short',
                'abstract' => 'Only a few words',
                'keywords' => 'one, two',
                'manuscript' => UploadedFile::fake()->create('notes.txt', 20, 'text/plain'),
            ])
            ->assertRedirect(route('submissions.create'))
            ->assertSessionHasErrors(['abstract', 'keywords', 'manuscript']);

        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_non_editors_cannot_open_the_admin_submissions_queue(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author);
        $adminWithoutFlag = $this->createUserWithRole(RoleSlug::Admin, ['is_editor' => false]);
        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'editor_notes' => 'Keep this note internal.',
        ]);

        $this->actingAs($author)
            ->get(route('admin.submissions.show', $submission))
            ->assertForbidden();

        $this->actingAs($adminWithoutFlag)
            ->get(route('admin.submissions.index'))
            ->assertForbidden();
    }

    public function test_editor_can_filter_update_status_and_download_a_private_file(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author, ['name' => 'Ada Author']);
        $editor = User::factory()->editor()->create();
        $path = 'portal/accepted.pdf';
        Storage::disk('submissions')->put($path, 'private-manuscript-bytes');

        $underReview = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Queued for review',
            'status' => SubmissionStatus::UnderReview,
            'manuscript_path' => $path,
            'original_filename' => 'study.pdf',
        ]);
        Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Already accepted paper',
            'status' => SubmissionStatus::Accepted,
        ]);

        $this->actingAs($editor)
            ->get(route('admin.submissions.index', ['status' => SubmissionStatus::UnderReview->value]))
            ->assertOk()
            ->assertSee('Queued for review', false)
            ->assertSee('Ada Author', false)
            ->assertDontSee('Already accepted paper', false);

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $underReview))
            ->assertOk()
            ->assertSee('Queued for review', false)
            ->assertSee($underReview->abstract, false)
            ->assertDontSee(route('admin.submissions.convert', $underReview), false);

        $this->actingAs($editor)
            ->put(route('admin.submissions.update', $underReview), [
                'status' => SubmissionStatus::Accepted->value,
                'editor_notes' => 'Accept after minor formatting.',
            ])
            ->assertRedirect(route('admin.submissions.show', $underReview));

        $this->assertSame(SubmissionStatus::Accepted, $underReview->fresh()->status);
        $this->assertSame('Accept after minor formatting.', $underReview->fresh()->editor_notes);

        $this->actingAs($editor)
            ->get(route('admin.submissions.download', $underReview))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Accepted', false)
            ->assertDontSee('Accept after minor formatting.', false);
    }

    public function test_accepted_submission_converts_to_an_unpublished_article(): void
    {
        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author, [
            'name' => 'Corresponding Author',
            'affiliation' => 'SRT College',
        ]);
        $editor = User::factory()->editor()->create();
        $submission = Submission::factory()->accepted()->create([
            'user_id' => $author->id,
            'title' => 'Accepted regional study',
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'history, education, society, culture',
        ]);

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Convert to Article', false);

        $this->actingAs($editor)
            ->post(route('admin.submissions.convert', $submission))
            ->assertRedirect(route('admin.submissions.show', $submission));

        $submission->refresh();
        $article = Article::query()->findOrFail($submission->article_id);

        $this->assertSame($journal->id, $article->journal_id);
        $this->assertSame('Accepted regional study', $article->title);
        $this->assertSame($author->id, $article->corresponding_author_id);
        $this->assertFalse($article->is_demo);
        $this->assertSame(ArticleStatus::Accepted, $article->status);
        $this->assertNull($article->published_at);
        $this->assertTrue($article->authors()->where('is_corresponding', true)->where('user_id', $author->id)->exists());

        $this->get(route('articles.show', $article))->assertNotFound();
        $this->assertSame(0, Issue::query()->count());

        $this->actingAs($editor)
            ->post(route('admin.submissions.convert', $submission))
            ->assertForbidden();

        $this->assertSame(1, Article::query()->where('title', 'Accepted regional study')->count());
    }

    public function test_author_can_view_and_download_past_manuscript_versions_on_submission_page(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author);
        $otherAuthor = $this->createUserWithRole(RoleSlug::Author);

        $path1 = 'submissions/test-v1.pdf';
        $path2 = 'submissions/test-v2.pdf';
        Storage::disk('submissions')->put($path1, 'v1-content-data');
        Storage::disk('submissions')->put($path2, 'v2-content-data');

        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Multi-version manuscript',
            'manuscript_path' => $path2,
        ]);

        $v1 = $submission->recordVersion($path1);
        $v2 = $submission->recordVersion($path2);

        $this->actingAs($author)
            ->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('Manuscript versions', false)
            ->assertSee('Version 1', false)
            ->assertSee('Version 2', false)
            ->assertSee(route('submissions.versions.download', [$submission, $v1]), false)
            ->assertSee(route('submissions.versions.download', [$submission, $v2]), false);

        $this->actingAs($author)
            ->get(route('submissions.versions.download', [$submission, $v1]))
            ->assertOk();

        $this->actingAs($otherAuthor)
            ->get(route('submissions.versions.download', [$submission, $v1]))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'A complete regional research manuscript',
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'history, education, society, culture',
            'co_authors' => 'Jane Researcher, SRT College',
            'manuscript' => UploadedFile::fake()->create('manuscript.pdf', 120, 'application/pdf'),
        ], $overrides);
    }
}
