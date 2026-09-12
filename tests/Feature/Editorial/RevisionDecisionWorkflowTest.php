<?php

namespace Tests\Feature\Editorial;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\EditorialDecisionType;
use App\Enums\ReviewerAssignmentStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\Journal;
use App\Models\User;
use App\Notifications\ArticleAcceptedNotification;
use App\Notifications\ArticleRejectedNotification;
use App\Notifications\RevisionRequestedNotification;
use App\Notifications\RevisionSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisionDecisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthorized_users_cannot_issue_editorial_decisions(): void
    {
        Storage::fake('manuscripts');
        Notification::fake();

        $author = $this->createAuthor();
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $copyeditor = $this->createUserWithRole(RoleSlug::Copyeditor);
        $manuscript = $this->sendToReview($this->submitManuscript($author));

        $payload = [
            'decision' => EditorialDecisionType::MinorRevision->value,
            'comments_to_author' => 'Please clarify the methods and resubmit a tracked revision.',
            'revision_due_at' => now()->addDays(30)->toDateString(),
        ];

        $this->app['auth']->forgetGuards();
        $this->post(route('editorial.manuscripts.decision.store', $manuscript), $payload)
            ->assertRedirect(route('login'));

        $this->actingAs($author)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), $payload)
            ->assertForbidden();

        $this->actingAs($copyeditor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), $payload)
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), $payload)
            ->assertForbidden();

        $this->assertSame(ArticleStatus::UnderReview, $manuscript->fresh()->status);
    }

    public function test_editor_cannot_decide_before_the_manuscript_is_under_review_or_resubmitted(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $manuscript = $this->submitManuscript($this->createAuthor());

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::Accept->value,
                'comments_to_author' => 'This manuscript is accepted without peer review.',
            ])
            ->assertForbidden();
    }

    public function test_editor_can_request_a_revision_and_author_uploads_a_new_version_without_overwriting_files(): void
    {
        Storage::fake('manuscripts');
        Notification::fake();

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $author = $this->createAuthor();
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $manuscript = $this->sendToReview($this->submitManuscript($author, ['title' => 'Revision workflow sample']));
        $originalFile = $manuscript->files()->where('type', ArticleFileType::Manuscript)->firstOrFail();
        $originalPath = $originalFile->path;

        $assignment = $manuscript->reviewerAssignments()->create([
            'revision_id' => $manuscript->currentRevision->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $editor->id,
            'status' => ReviewerAssignmentStatus::Accepted,
            'invited_at' => now(),
            'responded_at' => now(),
            'due_at' => now()->addWeeks(3),
        ]);

        $assignment->review()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $manuscript->currentRevision->id,
            'reviewer_id' => $reviewer->id,
            'recommendation' => ReviewRecommendation::MinorRevision,
            'comments_to_author' => 'Please add the missing control experiment details.',
            'comments_to_editor' => 'SECRET_CONFIDENTIAL_EDITOR_ONLY_NOTES',
            'submitted_at' => now(),
        ]);

        $dueAt = now()->addDays(30)->toDateString();
        $letter = 'Please revise the methods and submit a point-by-point response.';

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Editorial decision', false)
            ->assertSee('SECRET_CONFIDENTIAL_EDITOR_ONLY_NOTES', false);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::MinorRevision->value,
                'comments_to_author' => $letter,
                'internal_notes' => 'Requesting a short methods revision.',
                'revision_due_at' => $dueAt,
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $manuscript->refresh();
        $this->assertSame(ArticleStatus::RevisionRequired, $manuscript->status);
        $this->assertSame($dueAt, $manuscript->revision_due_at?->toDateString());
        $this->assertDatabaseHas('editorial_decisions', [
            'article_id' => $manuscript->id,
            'decision' => EditorialDecisionType::MinorRevision->value,
            'comments_to_author' => $letter,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'decided']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $author->id,
            'type' => 'decision.revision',
            'channel' => 'database',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $author->id,
            'type' => 'decision.revision',
            'channel' => 'mail',
        ]);

        Notification::assertSentTo($author, RevisionRequestedNotification::class, function (RevisionRequestedNotification $notification) use ($manuscript): bool {
            return $notification->article->is($manuscript)
                && $notification->decision->decision === EditorialDecisionType::MinorRevision;
        });

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee($letter, false)
            ->assertSee('Please add the missing control experiment details.', false)
            ->assertSee($dueAt, false)
            ->assertDontSee('SECRET_CONFIDENTIAL_EDITOR_ONLY_NOTES', false)
            ->assertDontSee('Confidential comments to editor', false);

        $this->actingAs($author)
            ->delete(route('author.manuscripts.files.destroy', [$manuscript, $originalFile]))
            ->assertForbidden();

        Storage::disk('manuscripts')->assertExists($originalPath);

        $this->actingAs($author)
            ->from(route('author.manuscripts.show', $manuscript))
            ->post(route('author.manuscripts.submit', $manuscript), [
                'author_response' => 'We added the control experiment and updated figure 2.',
            ])
            ->assertRedirect(route('author.manuscripts.show', $manuscript))
            ->assertSessionHas('error');

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('revised-manuscript.pdf', 90, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        Storage::disk('manuscripts')->assertExists($originalPath);
        $this->assertSame(2, $manuscript->files()->where('type', ArticleFileType::Manuscript)->count());
        $this->assertTrue($originalFile->fresh()->isImmutable());

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript), [
                'author_response' => 'We added the control experiment and updated figure 2.',
            ])
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        $manuscript->refresh();
        $this->assertSame(ArticleStatus::Resubmitted, $manuscript->status);
        $this->assertNull($manuscript->revision_due_at);
        $this->assertSame(2, $manuscript->revisions()->count());
        $this->assertSame(
            'We added the control experiment and updated figure 2.',
            $manuscript->revisions()->where('version', 2)->value('author_response')
        );
        Storage::disk('manuscripts')->assertExists($originalPath);
        $this->assertSame($originalPath, $originalFile->fresh()->path);

        Notification::assertSentTo($editor, RevisionSubmittedNotification::class, function (RevisionSubmittedNotification $notification) use ($manuscript): bool {
            return $notification->article->is($manuscript)
                && $notification->revision->version === 2;
        });

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Version 1', false)
            ->assertSee('Version 2', false)
            ->assertSee('reviewer-visible-manuscript.pdf', false)
            ->assertSee('revised-manuscript.pdf', false)
            ->assertSee('We added the control experiment and updated figure 2.', false);

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('We added the control experiment and updated figure 2.', false)
            ->assertDontSee('SECRET_CONFIDENTIAL_EDITOR_ONLY_NOTES', false);
    }

    public function test_editor_can_accept_reject_or_request_another_revision_after_resubmission(): void
    {
        Storage::fake('manuscripts');
        Notification::fake();

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $author = $this->createAuthor();
        $manuscript = $this->resubmitRevision($editor, $author);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::MajorRevision->value,
                'comments_to_author' => 'A further analysis of the dataset is still required before acceptance.',
                'revision_due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::RevisionRequired, $manuscript->fresh()->status);

        $manuscript = $this->resubmitRevision($editor, $author, $manuscript->fresh(), 'We completed the additional analysis as requested.');

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::Accept->value,
                'comments_to_author' => 'The revised manuscript is accepted for publication.',
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Accepted, $manuscript->fresh()->status);
        $this->actingAs($author)->get(route('author.manuscripts.edit', $manuscript))->assertForbidden();
        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('too-late.pdf', 40, 'application/pdf'),
            ])
            ->assertForbidden();

        $other = $this->resubmitRevision($editor, $this->createAuthor());
        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $other), [
                'decision' => EditorialDecisionType::Reject->value,
                'comments_to_author' => 'The revision does not address the concerns raised by the reviewers.',
            ])
            ->assertRedirect();

        $this->assertSame(ArticleStatus::Rejected, $other->fresh()->status);
        Notification::assertSentTo($author, ArticleAcceptedNotification::class);
        Notification::assertSentTo($other->correspondingAuthor, ArticleRejectedNotification::class);
    }

    public function test_accepted_manuscripts_cannot_move_back_to_revision(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $manuscript = $this->sendToReview($this->submitManuscript($this->createAuthor()));
        $manuscript->update(['status' => ArticleStatus::Accepted]);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::MinorRevision->value,
                'comments_to_author' => 'Trying to reopen an accepted manuscript for revision.',
                'revision_due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::Accepted, $manuscript->fresh()->status);
    }

    private function createAuthor(array $attributes = []): User
    {
        return $this->createUserWithRole(RoleSlug::Author, array_merge([
            'affiliation' => 'Institute of Plant Science',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function submitManuscript(User $author, array $attributes = []): Article
    {
        $journal = Journal::query()->first() ?? Journal::factory()->create();

        $manuscript = Article::factory()->create(array_merge([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $author->id,
            'title' => 'A complete study of controlled sampling methods',
            'article_type' => ArticleType::ResearchArticle,
            'abstract' => str_repeat('Abstract text for the manuscript. ', 6),
            'keywords' => ['sampling', 'methods', 'biology'],
            'cover_letter' => 'Please consider this manuscript for review.',
            'originality_confirmed' => true,
            'conflict_of_interest_declared' => true,
            'conflict_of_interest_statement' => 'The authors declare no conflict of interest.',
            'status' => ArticleStatus::Draft,
        ], $attributes));

        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $manuscript->id,
            'user_id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
            'affiliation' => $author->affiliation,
            'sequence' => 1,
        ]);

        $this->attachFile($manuscript, $author, 'reviewer-visible-manuscript.pdf');

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript))
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        return $manuscript->fresh(['authors', 'files', 'currentRevision', 'correspondingAuthor']);
    }

    private function sendToReview(Article $manuscript): Article
    {
        $editor = $this->createUserWithRole(RoleSlug::Editor);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'send_to_review',
            ])
            ->assertRedirect();

        return $manuscript->fresh(['currentRevision', 'files', 'correspondingAuthor']);
    }

    private function resubmitRevision(User $editor, User $author, ?Article $manuscript = null, string $response = 'We addressed every reviewer comment in this revision.'): Article
    {
        $manuscript ??= $this->sendToReview($this->submitManuscript($author));
        $manuscript = $manuscript->fresh();

        if ($manuscript->status !== ArticleStatus::RevisionRequired) {
            $this->actingAs($editor)
                ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                    'decision' => EditorialDecisionType::MinorRevision->value,
                    'comments_to_author' => 'Please revise the manuscript and respond to the reviewers.',
                    'revision_due_at' => now()->addDays(30)->toDateString(),
                ])
                ->assertRedirect();
        }

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript->fresh()), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('revision-'.fake()->numerify('##').'.pdf', 80, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript->fresh()), [
                'author_response' => $response,
            ])
            ->assertRedirect();

        return $manuscript->fresh(['currentRevision', 'files', 'revisions', 'correspondingAuthor']);
    }

    private function attachFile(Article $manuscript, User $uploader, string $filename): ArticleFile
    {
        $path = $manuscript->id.'/'.fake()->uuid().'.pdf';
        Storage::disk('manuscripts')->put($path, 'file-bytes');

        return ArticleFile::factory()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $manuscript->currentRevision?->id,
            'uploaded_by' => $uploader->id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => $filename,
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);
    }
}
