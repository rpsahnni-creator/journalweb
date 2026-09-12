<?php

namespace Tests\Feature\Submissions;

use App\Enums\ReviewRecommendation;
use App\Enums\RoleSlug;
use App\Enums\SubmissionReviewStatus;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_reviewer_pages(): void
    {
        $review = SubmissionReview::factory()->create();

        $this->get(route('reviews.index'))->assertRedirect(route('login'));
        $this->get(route('reviews.show', $review))->assertRedirect(route('login'));
        $this->put(route('reviews.update', $review))->assertRedirect(route('login'));
        $this->get(route('reviews.manuscript', $review))->assertRedirect(route('login'));
    }

    public function test_non_reviewers_cannot_open_assigned_reviews(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author);
        $review = SubmissionReview::factory()->create();

        $this->actingAs($author)
            ->get(route('reviews.index'))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('reviews.show', $review))
            ->assertForbidden();
    }

    public function test_editor_can_assign_reviewers_and_cannot_assign_author_or_non_reviewer(): void
    {
        $author = User::factory()->reviewer()->create([
            'name' => 'Priya BlindAuthor',
            'affiliation' => 'Hidden University of Names',
        ]);
        $editor = User::factory()->editor()->create();
        $reviewer = User::factory()->reviewer()->create(['name' => 'Reviewer Alpha']);
        $bystander = User::factory()->create(['name' => 'Not A Reviewer']);
        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Blinded Climate Study For Review',
            'co_authors' => 'Secret Coauthor One',
            'status' => SubmissionStatus::Submitted,
        ]);

        $this->actingAs($editor)
            ->from(route('admin.submissions.show', $submission))
            ->post(route('admin.submissions.reviews.store', $submission), [
                'reviewer_ids' => [$author->id],
            ])
            ->assertRedirect(route('admin.submissions.show', $submission))
            ->assertSessionHasErrors('reviewer_ids.0');

        $this->actingAs($editor)
            ->from(route('admin.submissions.show', $submission))
            ->post(route('admin.submissions.reviews.store', $submission), [
                'reviewer_ids' => [$bystander->id],
            ])
            ->assertRedirect(route('admin.submissions.show', $submission))
            ->assertSessionHasErrors('reviewer_ids.0');

        $this->actingAs($editor)
            ->post(route('admin.submissions.reviews.store', $submission), [
                'reviewer_ids' => [$reviewer->id],
            ])
            ->assertRedirect(route('admin.submissions.show', $submission))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('submission_reviews', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'status' => SubmissionReviewStatus::Assigned->value,
        ]);
        $this->assertSame(SubmissionStatus::UnderReview, $submission->fresh()->status);

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Reviewer Alpha', false)
            ->assertSee('Assigned', false)
            ->assertDontSee('Reviews complete', false);
    }

    public function test_reviewer_only_sees_assigned_blinded_manuscripts(): void
    {
        Storage::fake('submissions');

        $author = User::factory()->create([
            'name' => 'Priya BlindAuthor',
            'affiliation' => 'Hidden University of Names',
        ]);
        $reviewer = User::factory()->reviewer()->create(['name' => 'Reviewer Alpha']);
        $otherReviewer = User::factory()->reviewer()->create(['name' => 'Reviewer Beta']);
        $path = 'portal/blinded.pdf';
        Storage::disk('submissions')->put($path, 'private-manuscript-bytes');

        $assigned = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Blinded Climate Study For Review',
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'climate, history, education, society',
            'co_authors' => 'Secret Coauthor One',
            'manuscript_path' => $path,
            'original_filename' => 'Priya-BlindAuthor-study.pdf',
        ]);
        $foreign = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Foreign confidential manuscript',
            'co_authors' => 'Another Hidden Name',
        ]);

        $review = SubmissionReview::factory()->create([
            'submission_id' => $assigned->id,
            'reviewer_id' => $reviewer->id,
        ]);
        $otherReview = SubmissionReview::factory()->create([
            'submission_id' => $foreign->id,
            'reviewer_id' => $otherReviewer->id,
        ]);

        $this->actingAs($reviewer)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('Blinded Climate Study For Review', false)
            ->assertSee('climate, history, education, society', false)
            ->assertDontSee('Priya BlindAuthor', false)
            ->assertDontSee('Hidden University of Names', false)
            ->assertDontSee('Secret Coauthor One', false)
            ->assertDontSee('Foreign confidential manuscript', false);

        $this->actingAs($reviewer)
            ->get(route('reviews.show', $review))
            ->assertOk()
            ->assertSee('Blinded Climate Study For Review', false)
            ->assertSee('Comments to the author', false)
            ->assertDontSee('Priya BlindAuthor', false)
            ->assertDontSee('Hidden University of Names', false)
            ->assertDontSee('Secret Coauthor One', false)
            ->assertDontSee('Priya-BlindAuthor-study.pdf', false);

        $this->assertSame(SubmissionReviewStatus::InProgress, $review->fresh()->status);

        $this->actingAs($reviewer)
            ->get(route('reviews.show', $otherReview))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->get(route('reviews.manuscript', $otherReview))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->get(route('reviews.manuscript', $review))
            ->assertOk()
            ->assertDownload('manuscript.pdf');
    }

    public function test_reviewer_can_submit_and_editor_sees_named_recommendations_when_complete(): void
    {
        $author = User::factory()->create([
            'name' => 'Priya BlindAuthor',
            'affiliation' => 'Hidden University of Names',
        ]);
        $editor = User::factory()->editor()->create();
        $first = User::factory()->reviewer()->create(['name' => 'Reviewer Alpha']);
        $second = User::factory()->reviewer()->create(['name' => 'Reviewer Beta']);
        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Blinded Climate Study For Review',
            'co_authors' => 'Secret Coauthor One',
            'status' => SubmissionStatus::UnderReview,
        ]);

        $firstReview = SubmissionReview::factory()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $first->id,
        ]);
        $secondReview = SubmissionReview::factory()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $second->id,
        ]);

        $authorComments = 'Please expand the methods section with additional citations.';
        $editorComments = 'Confidential editor-only referee note';

        $this->actingAs($first)
            ->put(route('reviews.update', $firstReview), [
                'recommendation' => ReviewRecommendation::MinorRevision->value,
                'comments_to_author' => $authorComments,
                'comments_to_editor' => $editorComments,
            ])
            ->assertRedirect(route('reviews.show', $firstReview));

        $firstReview->refresh();
        $this->assertSame(SubmissionReviewStatus::Submitted, $firstReview->status);
        $this->assertNotNull($firstReview->submitted_at);
        $this->assertSame(ReviewRecommendation::MinorRevision, $firstReview->recommendation);

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Reviewer Alpha', false)
            ->assertSee('Minor revision', false)
            ->assertSee($editorComments, false)
            ->assertDontSee('Reviews complete', false);

        $this->actingAs($second)
            ->put(route('reviews.update', $secondReview), [
                'recommendation' => ReviewRecommendation::Reject->value,
                'comments_to_author' => 'The argument is sound after the methods are expanded.',
                'comments_to_editor' => 'Second confidential note',
            ])
            ->assertRedirect(route('reviews.show', $secondReview));

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Reviews complete', false)
            ->assertSee('Reviewer Beta', false)
            ->assertSee('Reject', false);

        $this->actingAs($first)
            ->put(route('reviews.update', $firstReview), [
                'recommendation' => ReviewRecommendation::Accept->value,
                'comments_to_author' => 'Trying to change a submitted review.',
            ])
            ->assertForbidden();
    }

    public function test_author_sees_compiled_comments_only_after_revision_is_requested(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author, [
            'name' => 'Priya BlindAuthor',
            'affiliation' => 'Hidden University of Names',
        ]);
        $editor = User::factory()->editor()->create();
        $reviewer = User::factory()->reviewer()->create(['name' => 'Reviewer Alpha']);
        $submission = Submission::factory()->create([
            'user_id' => $author->id,
            'title' => 'Blinded Climate Study For Review',
            'status' => SubmissionStatus::UnderReview,
            'editor_notes' => 'Internal referee note',
        ]);

        SubmissionReview::factory()->submitted()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'recommendation' => ReviewRecommendation::MajorRevision,
            'comments_to_author' => 'Please expand the methods section with additional citations.',
            'comments_to_editor' => 'Confidential editor-only referee note',
        ]);

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Blinded Climate Study For Review', false)
            ->assertDontSee('Please expand the methods section with additional citations.', false)
            ->assertDontSee('Confidential editor-only referee note', false)
            ->assertDontSee('Reviewer Alpha', false)
            ->assertDontSee('Internal referee note', false);

        $this->actingAs($editor)
            ->put(route('admin.submissions.update', $submission), [
                'status' => SubmissionStatus::RevisionRequested->value,
                'editor_notes' => 'Internal referee note',
            ])
            ->assertRedirect(route('admin.submissions.show', $submission));

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Revision requested', false)
            ->assertSee('Please expand the methods section with additional citations.', false)
            ->assertDontSee('Confidential editor-only referee note', false)
            ->assertDontSee('Reviewer Alpha', false)
            ->assertDontSee('Internal referee note', false);

        $this->actingAs($author)
            ->get(route('submissions.show', $submission))
            ->assertOk()
            ->assertSee('Please expand the methods section with additional citations.', false)
            ->assertDontSee('Confidential editor-only referee note', false)
            ->assertDontSee('Reviewer Alpha', false);
    }

    public function test_reviewer_cannot_remove_or_browse_editorial_queue(): void
    {
        $reviewer = User::factory()->reviewer()->create();
        $submission = Submission::factory()->create();
        $review = SubmissionReview::factory()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
        ]);

        $this->actingAs($reviewer)
            ->get(route('admin.submissions.show', $submission))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->delete(route('admin.submissions.reviews.destroy', [$submission, $review]))
            ->assertForbidden();
    }
}
