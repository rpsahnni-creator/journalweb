<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\NotificationType;
use App\Enums\ReviewerAssignmentStatus;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalNotification;
use App\Models\ReviewerAssignment;
use App\Models\User;
use App\Models\Volume;
use App\Notifications\ArticlePublishedNotification;
use App\Notifications\NewSubmissionNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\ReviewerInvitationNotification;
use App\Notifications\ReviewReminderNotification;
use App\Notifications\TestMailNotification;
use App\Support\IssuePublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_submission_notifies_the_author_and_editors_and_writes_logs(): void
    {
        Notification::fake();

        $journal = Journal::factory()->create();
        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Test Lab']);
        $article = $this->makeSubmittedArticle($journal, $author);

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $article))
            ->assertRedirect();

        Notification::assertSentTo($author, NewSubmissionNotification::class);
        Notification::assertSentTo($editor, NewSubmissionNotification::class);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $author->id,
            'type' => NotificationType::SubmissionReceived->value,
            'channel' => 'mail',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $editor->id,
            'type' => NotificationType::SubmissionReceived->value,
            'channel' => 'database',
        ]);
    }

    public function test_reviewer_invitation_is_mailed_and_logged(): void
    {
        Notification::fake();

        $journal = Journal::factory()->create();
        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Test Lab']);
        $article = $this->makeSubmittedArticle($journal, $author);

        $this->actingAs($author)->post(route('author.manuscripts.submit', $article))->assertRedirect();
        $this->actingAs($editor)->post(route('editorial.manuscripts.screen', $article), [
            'outcome' => 'send_to_review',
        ])->assertRedirect();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $article->fresh()), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(10)->toDateString(),
            ])
            ->assertRedirect();

        Notification::assertSentTo($reviewer, ReviewerInvitationNotification::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $reviewer->id,
            'type' => NotificationType::ReviewerInvitation->value,
            'channel' => 'mail',
        ]);
    }

    public function test_review_reminders_are_sent_for_due_assignments_and_not_repeated_immediately(): void
    {
        Notification::fake();

        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $assignment = ReviewerAssignment::factory()->create([
            'reviewer_id' => $reviewer->id,
            'status' => ReviewerAssignmentStatus::Accepted,
            'due_at' => now()->addDay(),
        ]);

        Artisan::call('reviews:remind');

        Notification::assertSentTo($reviewer, ReviewReminderNotification::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $reviewer->id,
            'type' => NotificationType::ReviewReminder->value,
        ]);

        Notification::fake();
        Artisan::call('reviews:remind');
        Notification::assertNotSentTo($reviewer, ReviewReminderNotification::class);

        $this->assertNotNull($assignment->fresh());
    }

    public function test_publishing_an_article_notifies_the_corresponding_author(): void
    {
        Notification::fake();

        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author);
        $editor = $this->createUserWithRole(RoleSlug::EditorInChief);
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 1]);
        $issue = Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
        ]);
        $article = Article::factory()->accepted()->create([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $author->id,
            'title' => 'A published notification sample',
        ]);
        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $article->id,
            'user_id' => $author->id,
            'name' => $author->name,
        ]);

        $publisher = app(IssuePublisher::class);
        $publisher->assign($issue, $article, $editor);
        $publisher->publish($issue->fresh(), $editor);

        Notification::assertSentTo($author, ArticlePublishedNotification::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $author->id,
            'type' => NotificationType::ArticlePublished->value,
            'channel' => 'mail',
        ]);
        $this->assertSame(ArticleStatus::Published, $article->fresh()->status);
    }

    public function test_password_reset_uses_the_journal_notification_and_does_not_log_the_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $log = JournalNotification::query()
            ->where('user_id', $user->id)
            ->where('type', NotificationType::PasswordReset->value)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame([], $log->data);
        $this->assertStringNotContainsString('token', strtolower((string) json_encode($log->data)));
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9]{40,}/', (string) $log->body);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_development_mail_test_command_and_editorial_action(): void
    {
        Notification::fake();
        config(['mail.testing.enabled' => true]);

        $user = $this->createUserWithRole(RoleSlug::Editor, ['email' => 'editor-mail-test@example.com']);

        $this->artisan('mail:test', ['email' => $user->email])
            ->assertSuccessful();

        Notification::assertSentTo($user, TestMailNotification::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => NotificationType::MailTest->value,
        ]);

        $this->actingAs($user)
            ->post(route('editorial.notifications.test-mail'))
            ->assertRedirect();

        $this->assertNotNull(config('mail.mailers.smtp.host'));
        $this->assertTrue(config('mail.mailers.smtp.password') === null || config('mail.mailers.smtp.password') === '');
    }

    public function test_mail_test_is_blocked_when_disabled(): void
    {
        config(['mail.testing.enabled' => false]);
        $editor = $this->createUserWithRole(RoleSlug::Editor);

        $this->artisan('mail:test', ['email' => $editor->email])->assertFailed();

        $this->actingAs($editor)
            ->post(route('editorial.notifications.test-mail'))
            ->assertNotFound();
    }

    private function makeSubmittedArticle(Journal $journal, User $author): Article
    {
        $article = Article::factory()->create([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $author->id,
            'status' => ArticleStatus::Draft,
            'title' => 'Notification structure sample manuscript',
            'abstract' => str_repeat('Abstract for notification tests. ', 6),
            'keywords' => ['mail', 'notification', 'journal'],
            'article_type' => ArticleType::ResearchArticle,
            'cover_letter' => 'Please consider this manuscript for review.',
            'originality_confirmed' => true,
            'conflict_of_interest_declared' => true,
            'conflict_of_interest_statement' => 'The authors declare no conflict of interest.',
        ]);

        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $article->id,
            'user_id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
            'affiliation' => $author->affiliation,
        ]);

        Storage::fake('manuscripts');
        $path = $article->id.'/manuscript.pdf';
        Storage::disk('manuscripts')->put($path, 'pdf');
        ArticleFile::factory()->create([
            'article_id' => $article->id,
            'uploaded_by' => $author->id,
            'path' => $path,
            'disk' => 'manuscripts',
            'original_filename' => 'manuscript.pdf',
            'mime_type' => 'application/pdf',
        ]);

        return $article->fresh();
    }
}
