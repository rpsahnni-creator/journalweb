<?php

namespace Tests\Feature\PeerReview;

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
use App\Models\ReviewerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PeerReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_authors_cannot_use_editorial_or_reviewer_actions(): void
    {
        Storage::fake('manuscripts');

        $author = $this->createAuthor();
        $manuscript = $this->submitManuscript($author);
        $file = $manuscript->files()->where('type', ArticleFileType::Manuscript)->firstOrFail();

        $this->app['auth']->forgetGuards();

        $this->get(route('editorial.manuscripts.index'))->assertRedirect(route('login'));
        $this->get(route('editorial.manuscripts.show', $manuscript))->assertRedirect(route('login'));
        $this->post(route('editorial.manuscripts.screen', $manuscript), ['outcome' => 'send_to_review'])->assertRedirect(route('login'));
        $this->get(route('reviewer.dashboard'))->assertRedirect(route('login'));

        $this->actingAs($author)->get(route('editorial.dashboard'))->assertForbidden();
        $this->actingAs($author)->get(route('editorial.manuscripts.index'))->assertForbidden();
        $this->actingAs($author)->get(route('editorial.manuscripts.show', $manuscript))->assertForbidden();
        $this->actingAs($author)->post(route('editorial.manuscripts.screen', $manuscript), [
            'outcome' => 'send_to_review',
        ])->assertForbidden();
        $this->actingAs($author)->get(route('editorial.manuscripts.files.download', [$manuscript, $file]))->assertForbidden();
        $this->actingAs($author)->get(route('reviewer.dashboard'))->assertForbidden();
    }

    public function test_editor_sees_submitted_manuscripts_and_copyeditor_cannot_screen_or_assign(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $copyeditor = $this->createUserWithRole(RoleSlug::Copyeditor);
        $author = $this->createAuthor();
        $manuscript = $this->submitManuscript($author, ['title' => 'Queued study of editorial screening']);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.index'))
            ->assertOk()
            ->assertSee('Queued study of editorial screening', false)
            ->assertSee($manuscript->submission_number, false);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Initial screening', false)
            ->assertSee('Desk reject', false);

        $this->actingAs($copyeditor)
            ->get(route('editorial.manuscripts.index'))
            ->assertOk()
            ->assertSee('Queued study of editorial screening', false);

        $this->actingAs($copyeditor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertDontSee('Save screening', false)
            ->assertDontSee('Search reviewers', false);

        $this->actingAs($copyeditor)
            ->post(route('editorial.manuscripts.screen', $manuscript), ['outcome' => 'send_to_review'])
            ->assertForbidden();

        $this->actingAs($copyeditor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $this->createUserWithRole(RoleSlug::Reviewer)->id,
                'due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_journal_manager_cannot_desk_reject(): void
    {
        Storage::fake('manuscripts');

        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $author = $this->createAuthor();
        $manuscript = $this->submitManuscript($author);

        $this->actingAs($manager)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertDontSee('Desk reject', false);

        $this->actingAs($manager)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'reject',
                'comments_to_author' => 'This submission is outside the journal scope.',
            ])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::Submitted, $manuscript->fresh()->status);
    }

    public function test_editor_can_screen_assign_and_reviewer_can_complete_a_review(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $author = $this->createAuthor(['name' => 'Secret Corresponding Author']);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer, [
            'name' => 'Assigned Reviewer',
            'affiliation' => 'Alpine Glacier Review Lab',
        ]);
        $otherReviewer = $this->createUserWithRole(RoleSlug::Reviewer, [
            'affiliation' => 'Unrelated Review Institute',
        ]);

        $manuscript = $this->submitManuscript($author, ['title' => 'Peer review workflow sample']);
        $manuscriptFile = $manuscript->files()->where('type', ArticleFileType::Manuscript)->firstOrFail();
        $coverLetter = $this->attachFile($manuscript, $author, ArticleFileType::CoverLetter, 'hidden-cover-letter.pdf');
        $supplementary = $this->attachFile($manuscript, $author, ArticleFileType::Supplementary, 'reviewer-visible-data.csv', 'text/csv');

        $this->actingAs($editor)
            ->get(route('editorial.notifications.index'))
            ->assertOk()
            ->assertSee('Manuscript '.$manuscript->submission_number.' submitted', false);

        $this->actingAs($editor)
            ->from(route('editorial.manuscripts.show', $manuscript))
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'in_progress',
                'internal_notes' => 'Checking scope and completeness.',
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::InitialScreening, $manuscript->fresh()->status);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'send_to_review',
                'internal_notes' => 'Ready for external review.',
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $manuscript->refresh();
        $this->assertSame(ArticleStatus::UnderReview, $manuscript->status);
        $this->assertDatabaseHas('editorial_decisions', [
            'article_id' => $manuscript->id,
            'editor_id' => $editor->id,
            'decision' => EditorialDecisionType::SendToReview->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'screened',
            'auditable_id' => $manuscript->id,
        ]);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', ['manuscript' => $manuscript, 'q' => 'Alpine Glacier']))
            ->assertOk()
            ->assertSee('Assigned Reviewer', false)
            ->assertSee('Alpine Glacier Review Lab', false)
            ->assertDontSee($otherReviewer->email, false);

        $dueAt = now()->addDays(21)->toDateString();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => $dueAt,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $assignment = ReviewerAssignment::query()->firstOrFail();
        $this->assertSame(ReviewerAssignmentStatus::Invited, $assignment->status);
        $this->assertSame($dueAt, $assignment->due_at?->toDateString());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $reviewer->id,
            'type' => 'review.invited',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'assigned',
            'auditable_id' => $assignment->id,
        ]);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Invited', false)
            ->assertSee($dueAt, false);

        $this->actingAs($reviewer)
            ->get(route('reviewer.dashboard'))
            ->assertOk()
            ->assertSee('Peer review workflow sample', false)
            ->assertSee('Invited', false)
            ->assertSee($dueAt, false);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.show', $assignment))
            ->assertOk()
            ->assertDontSee('Secret Corresponding Author', false)
            ->assertDontSee('SECRET_COVER_LETTER_FOR_EDITORS_ONLY', false)
            ->assertDontSee('SECRET_COI_STATEMENT_FOR_EDITORS', false)
            ->assertDontSee('hidden-cover-letter.pdf', false)
            ->assertDontSee('reviewer-visible-manuscript.pdf', false);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $manuscriptFile]))
            ->assertForbidden();

        $this->actingAs($otherReviewer)
            ->get(route('reviewer.assignments.show', $assignment))
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('reviewer.assignments.accept', $assignment))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.accept', $assignment))
            ->assertRedirect(route('reviewer.assignments.show', $assignment));

        $this->assertSame(ReviewerAssignmentStatus::Accepted, $assignment->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $editor->id,
            'type' => 'review.accepted',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'accepted']);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.show', $assignment))
            ->assertOk()
            ->assertSee('reviewer-visible-manuscript.pdf', false)
            ->assertSee('reviewer-visible-data.csv', false)
            ->assertDontSee('hidden-cover-letter.pdf', false)
            ->assertDontSee('Secret Corresponding Author', false);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $manuscriptFile]))
            ->assertOk()
            ->assertDownload('reviewer-visible-manuscript.pdf');

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $supplementary]))
            ->assertOk()
            ->assertDownload('reviewer-visible-data.csv');

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $coverLetter]))
            ->assertNotFound();

        $this->actingAs($otherReviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $manuscriptFile]))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('reviewer.assignments.files.download', [$assignment, $manuscriptFile]))
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.files.download', [$manuscript, $coverLetter]))
            ->assertOk()
            ->assertDownload('hidden-cover-letter.pdf');

        $this->get('/storage/'.$manuscriptFile->path)->assertNotFound();
        $this->get('/storage/manuscripts/'.$manuscriptFile->path)->assertNotFound();
        $this->get(route('articles.show', $manuscript->slug))->assertNotFound();

        $confidential = 'SECRET_CONFIDENTIAL_EDITOR_COMMENTS about methods.';
        $toAuthor = 'Please clarify the sampling protocol and replicate counts.';

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.review.store', $assignment), [
                'recommendation' => ReviewRecommendation::MinorRevision->value,
                'comments_to_author' => $toAuthor,
                'comments_to_editor' => $confidential,
            ])
            ->assertRedirect(route('reviewer.assignments.show', $assignment));

        $this->assertSame(ReviewerAssignmentStatus::Completed, $assignment->fresh()->status);
        $this->assertDatabaseHas('reviews', [
            'reviewer_assignment_id' => $assignment->id,
            'recommendation' => ReviewRecommendation::MinorRevision->value,
            'comments_to_author' => $toAuthor,
            'comments_to_editor' => $confidential,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $editor->id,
            'type' => 'review.submitted',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'reviewed']);

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.review.store', $assignment), [
                'recommendation' => ReviewRecommendation::Accept->value,
                'comments_to_author' => 'A second review submission should be rejected by authorization.',
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Completed', false)
            ->assertSee('Minor revision', false)
            ->assertSee($toAuthor, false)
            ->assertSee($confidential, false);

        $this->actingAs($editor)
            ->get(route('editorial.notifications.index'))
            ->assertOk()
            ->assertSee('Review submitted for '.$manuscript->submission_number, false);

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertDontSee($confidential, false)
            ->assertDontSee('Confidential comments to editor', false);
    }

    public function test_reviewer_can_decline_and_editor_can_reinvite_but_cannot_assign_an_author(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $author = $this->createAuthor();
        $author->assignRole(RoleSlug::Reviewer);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer, [
            'affiliation' => 'Coastal Methods Laboratory',
        ]);
        $manuscript = $this->sendToReview($editor, $this->submitManuscript($author));

        $this->actingAs($editor)
            ->from(route('editorial.manuscripts.show', $manuscript))
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $author->id,
                'due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript))
            ->assertSessionHas('error');

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertSessionHas('status');

        $assignment = ReviewerAssignment::query()->firstOrFail();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.decline', $assignment), [
                'response_note' => 'Too close to my own research.',
            ])
            ->assertRedirect(route('reviewer.dashboard'));

        $this->assertSame(ReviewerAssignmentStatus::Declined, $assignment->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $editor->id,
            'type' => 'review.declined',
        ]);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Declined', false)
            ->assertSee('Too close to my own research.', false);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $manuscript->files()->first()]))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertSessionHas('status');

        $this->assertSame(1, ReviewerAssignment::query()->count());
        $this->assertSame(ReviewerAssignmentStatus::Invited, $assignment->fresh()->status);
        $this->assertNull($assignment->fresh()->response_note);
    }

    public function test_accepted_reviews_show_overdue_without_changing_stored_status(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $manuscript = $this->sendToReview($editor, $this->submitManuscript($this->createAuthor()));

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDay()->toDateString(),
            ])
            ->assertSessionHas('status');

        $assignment = ReviewerAssignment::query()->firstOrFail();

        $this->actingAs($reviewer)->post(route('reviewer.assignments.accept', $assignment));

        $this->travel(3)->days();

        $this->assertTrue($assignment->fresh()->isOverdue());
        $this->assertSame(ReviewerAssignmentStatus::Accepted, $assignment->fresh()->status);
        $this->assertSame(ReviewerAssignmentStatus::Overdue, $assignment->fresh()->displayStatus());

        $this->actingAs($reviewer)
            ->get(route('reviewer.dashboard'))
            ->assertOk()
            ->assertSee('Overdue', false);

        $this->actingAs($editor)
            ->get(route('editorial.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Overdue', false);
    }

    public function test_editor_can_desk_reject_during_screening(): void
    {
        Storage::fake('manuscripts');

        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $manuscript = $this->submitManuscript($this->createAuthor());

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'reject',
                'comments_to_author' => 'The manuscript is outside the aims and scope of the journal.',
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Rejected, $manuscript->fresh()->status);
        $this->assertDatabaseHas('editorial_decisions', [
            'article_id' => $manuscript->id,
            'decision' => EditorialDecisionType::Reject->value,
        ]);
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
            'cover_letter' => 'SECRET_COVER_LETTER_FOR_EDITORS_ONLY',
            'originality_confirmed' => true,
            'conflict_of_interest_declared' => true,
            'conflict_of_interest_statement' => 'SECRET_COI_STATEMENT_FOR_EDITORS',
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

        $this->attachFile($manuscript, $author, ArticleFileType::Manuscript, 'reviewer-visible-manuscript.pdf');

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript))
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        return $manuscript->fresh(['authors', 'files', 'currentRevision']);
    }

    private function sendToReview(User $editor, Article $manuscript): Article
    {
        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'send_to_review',
            ])
            ->assertRedirect();

        return $manuscript->fresh();
    }

    private function attachFile(
        Article $manuscript,
        User $uploader,
        ArticleFileType $type,
        string $filename,
        string $mime = 'application/pdf',
    ): ArticleFile {
        $path = $manuscript->id.'/'.fake()->uuid().'.'.pathinfo($filename, PATHINFO_EXTENSION);

        Storage::disk('manuscripts')->put($path, 'file-bytes');

        return ArticleFile::factory()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $manuscript->currentRevision?->id,
            'uploaded_by' => $uploader->id,
            'type' => $type,
            'original_filename' => $filename,
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => $mime,
            'is_public' => false,
        ]);
    }
}
