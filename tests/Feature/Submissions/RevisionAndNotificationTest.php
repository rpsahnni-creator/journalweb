<?php

namespace Tests\Feature\Submissions;

use App\Enums\ReviewRecommendation;
use App\Enums\RoleSlug;
use App\Enums\SubmissionReviewStatus;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Notifications\ReviewerAssigned;
use App\Notifications\ReviewSubmitted;
use App\Notifications\RevisionResubmitted;
use App\Notifications\SubmissionDecisionMade;
use App\Notifications\SubmissionReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RevisionAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_submissions_create_version_one_and_notify_author_and_editors(): void
    {
        Storage::fake('submissions');
        Notification::fake();

        $author = $this->createUserWithRole(RoleSlug::Author);
        $editor = User::factory()->editor()->create();

        $this->actingAs($author)
            ->post(route('submissions.store'), $this->validPayload([
                'title' => 'Notified climate manuscript',
            ]))
            ->assertRedirect(route('submissions.index'));

        $submission = Submission::query()->where('title', 'Notified climate manuscript')->firstOrFail();

        $this->assertSame(1, $submission->versions()->count());
        $this->assertSame(1, $submission->latestVersion?->version_number);
        $this->assertSame($submission->manuscript_path, $submission->latestVersion?->manuscript_path);

        Notification::assertSentTo($author, SubmissionReceived::class, function (SubmissionReceived $notification) use ($submission): bool {
            return $notification->forAuthor === true
                && $notification->submission->is($submission);
        });
        Notification::assertSentTo($editor, SubmissionReceived::class, function (SubmissionReceived $notification) use ($submission): bool {
            return $notification->forAuthor === false
                && $notification->submission->is($submission);
        });
        Notification::assertNotSentTo($author, SubmissionReceived::class, fn (SubmissionReceived $notification): bool => $notification->forAuthor === false);
    }

    public function test_author_can_upload_a_revision_only_when_requested(): void
    {
        Storage::fake('submissions');
        Notification::fake();

        $author = $this->createUserWithRole(RoleSlug::Author);
        $editor = User::factory()->editor()->create();
        $reviewer = User::factory()->reviewer()->create();

        $this->actingAs($author)
            ->post(route('submissions.store'), $this->validPayload([
                'title' => 'Revision workflow manuscript',
            ]));

        $submission = Submission::query()->where('title', 'Revision workflow manuscript')->firstOrFail();

        $this->actingAs($author)
            ->from(route('submissions.index'))
            ->post(route('submissions.revisions.store', $submission), [
                'manuscript' => UploadedFile::fake()->create('revised.pdf', 80, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('admin.submissions.reviews.store', $submission), [
                'reviewer_ids' => [$reviewer->id],
            ])
            ->assertRedirect(route('admin.submissions.show', $submission));

        $review = SubmissionReview::query()->where('submission_id', $submission->id)->firstOrFail();

        Notification::assertSentTo($reviewer, ReviewerAssigned::class, function (ReviewerAssigned $notification) use ($review): bool {
            return $notification->review->is($review)
                && $notification->actionUrl() === route('reviews.show', $review);
        });

        $comments = 'Please expand the methods section with additional citations.';

        $this->actingAs($reviewer)
            ->put(route('reviews.update', $review), [
                'recommendation' => ReviewRecommendation::MajorRevision->value,
                'comments_to_author' => $comments,
                'comments_to_editor' => 'Confidential editor-only referee note',
            ])
            ->assertRedirect(route('reviews.show', $review));

        Notification::assertSentTo($editor, ReviewSubmitted::class);

        $this->actingAs($editor)
            ->put(route('admin.submissions.update', $submission), [
                'status' => SubmissionStatus::RevisionRequested->value,
                'editor_notes' => 'Internal referee note that authors must not receive',
            ])
            ->assertRedirect(route('admin.submissions.show', $submission));

        Notification::assertSentTo($author, SubmissionDecisionMade::class, function (SubmissionDecisionMade $notification) use ($comments): bool {
            return $notification->decision === SubmissionStatus::RevisionRequested
                && str_contains($notification->message(), $comments)
                && ! str_contains($notification->message(), 'Internal referee note that authors must not receive');
        });

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Upload Revised Manuscript', false)
            ->assertSee($comments, false);

        $this->actingAs($author)
            ->from(route('submissions.index'))
            ->post(route('submissions.revisions.store', $submission), [
                'manuscript' => UploadedFile::fake()->create('revised.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('submissions.show', $submission));

        $submission->refresh();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertSame(2, $submission->review_round);
        $this->assertSame(2, $submission->versions()->count());
        $this->assertSame(2, $submission->latestVersion?->version_number);
        $this->assertSame($submission->manuscript_path, $submission->latestVersion?->manuscript_path);
        $this->assertTrue(Storage::disk('submissions')->exists($submission->manuscript_path));
        $this->assertSame(1, $review->fresh()->round);
        $this->assertSame(SubmissionReviewStatus::Submitted, $review->fresh()->status);

        Notification::assertSentTo($editor, RevisionResubmitted::class, function (RevisionResubmitted $notification) use ($submission): bool {
            return $notification->submission->is($submission)
                && $notification->version->version_number === 2;
        });

        $this->actingAs($editor)
            ->get(route('admin.submissions.show', $submission))
            ->assertOk()
            ->assertSee('Version history', false)
            ->assertSee('Version 1', false)
            ->assertSee('Version 2', false)
            ->assertSee('Review round 1', false)
            ->assertDontSee('Reviews complete', false);

        $this->actingAs($editor)
            ->post(route('admin.submissions.reviews.store', $submission), [
                'reviewer_ids' => [$reviewer->id],
            ])
            ->assertRedirect(route('admin.submissions.show', $submission));

        $this->assertDatabaseHas('submission_reviews', [
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'round' => 2,
            'status' => SubmissionReviewStatus::Assigned->value,
        ]);
        $this->assertSame(2, SubmissionReview::query()->where('submission_id', $submission->id)->count());
        $this->assertSame(SubmissionStatus::UnderReview, $submission->fresh()->status);
    }

    public function test_in_app_notifications_are_listed_and_marked_read_on_click(): void
    {
        Storage::fake('submissions');

        $author = $this->createUserWithRole(RoleSlug::Author);
        User::factory()->editor()->create();

        $this->actingAs($author)
            ->post(route('submissions.store'), $this->validPayload([
                'title' => 'Inbox confirmation manuscript',
            ]))
            ->assertRedirect(route('submissions.index'));

        $this->assertSame(1, $author->unreadNotifications()->count());

        $this->actingAs($author)
            ->get(route('submissions.index'))
            ->assertOk()
            ->assertSee('Notifications', false)
            ->assertSee('1 unread', false);

        $this->actingAs($author)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('We received your manuscript', false)
            ->assertSee('Inbox confirmation manuscript', false);

        $notification = $author->unreadNotifications()->firstOrFail();

        $this->actingAs($author)
            ->get(route('notifications.show', $notification))
            ->assertRedirect(route('submissions.index'));

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(0, $author->unreadNotifications()->count());
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
