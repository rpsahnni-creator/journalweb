<?php

namespace Tests\Feature\Models;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\AuditLog;
use App\Models\EditorialBoardMember;
use App\Models\EditorialDecision;
use App\Models\Issue;
use App\Models\IssueArticle;
use App\Models\Journal;
use App\Models\JournalNotification;
use App\Models\JournalPolicy;
use App\Models\JournalSetting;
use App\Models\Permission;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\Revision;
use App\Models\Role;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalDomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_be_assigned_roles_and_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['slug' => 'editor']);
        $permission = Permission::factory()->create(['slug' => 'articles.decide']);

        $role->givePermissionTo($permission);
        $user->assignRole($role);

        $user->refresh();

        $this->assertTrue($user->hasRole('editor'));
        $this->assertTrue($user->hasPermission('articles.decide'));
        $this->assertFalse($user->hasPermission('journals.manage'));
        $this->assertTrue($role->users->contains($user));
        $this->assertTrue($permission->roles->contains($role));
    }

    public function test_a_journal_owns_settings_volumes_issues_and_policies(): void
    {
        $journal = Journal::factory()->create();
        $setting = JournalSetting::factory()->create([
            'journal_id' => $journal->id,
            'key' => 'submission_email',
            'value' => 'editorial@example.com',
        ]);
        $volume = Volume::factory()->create([
            'journal_id' => $journal->id,
            'number' => 1,
            'year' => 2026,
        ]);
        $issue = Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
        ]);
        $policy = JournalPolicy::factory()->create([
            'journal_id' => $journal->id,
            'type' => 'author_guidelines',
        ]);

        $this->assertTrue($journal->settings->contains($setting));
        $this->assertSame('editorial@example.com', $journal->setting('submission_email'));
        $this->assertTrue($journal->volumes->contains($volume));
        $this->assertTrue($volume->issues->contains($issue));
        $this->assertTrue($issue->volume->is($volume));
        $this->assertTrue($journal->policies->contains($policy));
        $this->assertTrue($policy->journal->is($journal));
    }

    public function test_articles_keep_unpublished_manuscripts_private(): void
    {
        $article = Article::factory()->create(['status' => ArticleStatus::Submitted]);
        $file = ArticleFile::factory()->create([
            'article_id' => $article->id,
            'uploaded_by' => $article->corresponding_author_id,
            'is_public' => false,
        ]);

        $this->assertFalse($article->isPubliclyVisible());
        $this->assertFalse($file->is_public);
        $this->assertFalse($file->isVisibleToPublic());
        $this->assertTrue($article->files->contains($file));
        $this->assertTrue($file->article->is($article));
    }

    public function test_published_articles_can_expose_public_files(): void
    {
        $article = Article::factory()->published()->create();
        $file = ArticleFile::factory()->public()->create([
            'article_id' => $article->id,
            'uploaded_by' => $article->corresponding_author_id,
        ]);

        $this->assertTrue($article->isPubliclyVisible());
        $this->assertTrue($file->fresh()->isVisibleToPublic());
    }

    public function test_article_authors_revisions_and_editorial_decisions_are_related(): void
    {
        $article = Article::factory()->create();
        $author = ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $article->id,
            'user_id' => $article->corresponding_author_id,
            'name' => $article->correspondingAuthor->name,
            'sequence' => 1,
        ]);
        $revision = Revision::factory()->create([
            'article_id' => $article->id,
            'submitted_by' => $article->corresponding_author_id,
            'version' => 1,
        ]);
        $decision = EditorialDecision::factory()->create([
            'article_id' => $article->id,
            'revision_id' => $revision->id,
        ]);

        $this->assertTrue($article->authors->contains($author));
        $this->assertTrue($article->revisions->contains($revision));
        $this->assertTrue($article->currentRevision->is($revision));
        $this->assertTrue($article->editorialDecisions->contains($decision));
        $this->assertTrue($decision->editor()->exists());
        $this->assertTrue($decision->revision->is($revision));
        $this->assertSame(ArticleStatus::Draft, $article->status);
        $this->assertNotEmpty($article->submission_number);
    }

    public function test_reviewer_assignments_have_reviews(): void
    {
        $review = Review::factory()->create();

        $this->assertInstanceOf(ReviewerAssignment::class, $review->assignment);
        $this->assertTrue($review->assignment->review->is($review));
        $this->assertTrue($review->article->is($review->assignment->article));
        $this->assertTrue($review->revision->is($review->assignment->revision));
        $this->assertTrue($review->reviewer->is($review->assignment->reviewer));
    }

    public function test_issues_can_contain_articles_from_the_same_journal(): void
    {
        $issueArticle = IssueArticle::factory()->create();

        $this->assertTrue($issueArticle->issue->articles->contains($issueArticle->article));
        $this->assertSame($issueArticle->issue->journal_id, $issueArticle->article->journal_id);
    }

    public function test_editorial_board_members_are_private_until_published(): void
    {
        $member = EditorialBoardMember::factory()->create();

        $this->assertFalse($member->is_public);
        $this->assertFalse(
            EditorialBoardMember::query()->public()->whereKey($member->id)->exists()
        );

        $publicMember = EditorialBoardMember::factory()->public()->create();

        $this->assertTrue(
            EditorialBoardMember::query()->public()->whereKey($publicMember->id)->exists()
        );
    }

    public function test_notifications_and_audit_logs_morph_to_articles(): void
    {
        $notification = JournalNotification::factory()->create();
        $log = AuditLog::factory()->create();

        $this->assertInstanceOf(Article::class, $notification->related);
        $this->assertInstanceOf(Article::class, $log->auditable);
        $this->assertTrue($notification->user()->exists());
        $this->assertNull($notification->read_at);

        $notification->markAsRead();

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame('created', $log->action);
        $this->assertIsArray($log->new_values);
    }
}
