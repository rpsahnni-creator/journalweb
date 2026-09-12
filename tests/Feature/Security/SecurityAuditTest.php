<?php

namespace Tests\Feature\Security;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\JournalPolicyType;
use App\Enums\ReviewerAssignmentStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\AuditLog;
use App\Models\Journal;
use App\Models\JournalPolicy;
use App\Models\ReviewerAssignment;
use App\Models\User;
use App\Support\Auditor;
use App\Support\AuthSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_production_requests_force_debug_off(): void
    {
        config(['app.debug' => true]);
        $this->app['env'] = 'production';

        $this->get(route('health'))->assertOk();

        $this->assertFalse((bool) config('app.debug'));
        $this->assertTrue((bool) config('session.secure'));
    }

    public function test_login_does_not_follow_an_external_intended_url(): void
    {
        $user = User::factory()->create();

        $this->withSession(['url.intended' => 'https://evil.example/phish'])
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route($user->dashboardRoute()));
    }

    public function test_json_ld_escapes_script_breakout_characters(): void
    {
        $journal = Journal::factory()->create();
        $article = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => '</script><script>alert(1)</script>',
            'abstract' => '</script><script>alert(2)</script>',
        ]);

        $json = $article->schemaOrgJson();

        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringContainsString('\u003C', $json);

        $this->get(route('articles.show', $article))
            ->assertOk()
            ->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_policy_html_is_escaped(): void
    {
        $this->seed(DatabaseSeeder::class);

        $policy = JournalPolicy::query()
            ->where('type', JournalPolicyType::About->value)
            ->firstOrFail();
        $policy->update([
            'body' => '<script>alert("xss")</script>Safe policy text',
            'is_published' => true,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('Safe policy text', false);
    }

    public function test_search_parameterizes_user_input_and_hides_unpublished_manuscripts(): void
    {
        $journal = Journal::factory()->create();
        Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Published sampling methods',
        ]);
        Article::factory()->create([
            'journal_id' => $journal->id,
            'status' => ArticleStatus::Submitted,
            'title' => 'Unpublished confidential manuscript',
        ]);

        $this->get(route('articles.index', ['q' => "' OR 1=1 --"]))
            ->assertOk()
            ->assertDontSee('Unpublished confidential manuscript', false)
            ->assertDontSee('Published sampling methods', false);

        $this->get(route('articles.index', ['q' => 'sampling methods']))
            ->assertOk()
            ->assertSee('Published sampling methods', false)
            ->assertDontSee('Unpublished confidential manuscript', false);
    }

    public function test_file_id_from_another_manuscript_cannot_be_downloaded(): void
    {
        Storage::fake('manuscripts');
        $owner = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab A']);
        $stranger = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab B']);
        $owned = $this->makeDraft($owner);
        $other = $this->makeCompleteDraft($stranger);
        $foreignFile = $other->files()->firstOrFail();

        $this->actingAs($owner)
            ->get(route('author.manuscripts.files.download', [$owned, $foreignFile]))
            ->assertNotFound();
    }

    public function test_reviewers_cannot_open_another_reviewers_assignment(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab A']);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $other = $this->createUserWithRole(RoleSlug::Reviewer);
        $manuscript = $this->makeCompleteDraft($author);
        $manuscript->update(['status' => ArticleStatus::UnderReview]);
        $revision = $manuscript->revisions()->create([
            'submitted_by' => $author->id,
            'version' => 1,
            'submitted_at' => now(),
        ]);

        $assignment = ReviewerAssignment::factory()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $revision->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $reviewer->id,
            'status' => ReviewerAssignmentStatus::Accepted,
        ]);

        $this->actingAs($other)
            ->get(route('reviewer.assignments.show', $assignment))
            ->assertForbidden();

        $file = $manuscript->files()->firstOrFail();

        $this->actingAs($other)
            ->get(route('reviewer.assignments.files.download', [$assignment, $file]))
            ->assertForbidden();
    }

    public function test_download_filenames_cannot_inject_response_headers(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab A']);
        $manuscript = $this->makeDraft($author);
        $path = $manuscript->id.'/'.fake()->uuid().'.pdf';
        Storage::disk('manuscripts')->put($path, '%PDF-1.4');

        $file = ArticleFile::factory()->create([
            'article_id' => $manuscript->id,
            'uploaded_by' => $author->id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => "paper.pdf\r\nX-Injected: yes",
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);

        $response = $this->actingAs($author)
            ->get(route('author.manuscripts.files.download', [$manuscript, $file]))
            ->assertOk();

        $this->assertNull($response->headers->get('X-Injected'));
        $this->assertStringNotContainsString("\r", (string) $response->headers->get('content-disposition'));
        $this->assertStringNotContainsString("\n", (string) $response->headers->get('content-disposition'));
    }

    public function test_executable_and_html_uploads_are_rejected(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab A']);
        $manuscript = $this->makeDraft($author);

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('payload.exe', 20, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Supplementary->value,
                'file' => UploadedFile::fake()->create('page.html', 20, 'text/html'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_authors_do_not_see_reviews_or_editor_identities_before_a_decision(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Lab A', 'name' => 'Visible Author']);
        $editor = $this->createUserWithRole(RoleSlug::Editor, ['name' => 'Hidden Editor Name']);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer, ['name' => 'Secret Reviewer Name']);
        $manuscript = $this->makeCompleteDraft($author);
        $manuscript->update(['status' => ArticleStatus::UnderReview]);
        $manuscript->recordStatusChange(ArticleStatus::Submitted, ArticleStatus::UnderReview, $editor, 'Sent to review.');
        $revision = $manuscript->revisions()->create([
            'submitted_by' => $author->id,
            'version' => 1,
            'submitted_at' => now(),
        ]);

        $assignment = ReviewerAssignment::factory()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $revision->id,
            'reviewer_id' => $reviewer->id,
            'assigned_by' => $editor->id,
            'status' => ReviewerAssignmentStatus::Completed,
        ]);
        $assignment->review()->create([
            'article_id' => $manuscript->id,
            'revision_id' => $revision->id,
            'reviewer_id' => $reviewer->id,
            'recommendation' => ReviewRecommendation::MinorRevision,
            'comments_to_author' => 'Please clarify the sampling protocol.',
            'comments_to_editor' => 'CONFIDENTIAL_EDITOR_ONLY',
            'submitted_at' => now(),
        ]);

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertDontSee('CONFIDENTIAL_EDITOR_ONLY', false)
            ->assertDontSee('Secret Reviewer Name', false)
            ->assertDontSee('Please clarify the sampling protocol.', false)
            ->assertDontSee('Hidden Editor Name', false)
            ->assertSee('Editorial office', false);

        $manuscript->editorialDecisions()->create([
            'editor_id' => $editor->id,
            'decision' => EditorialDecisionType::MinorRevision,
            'comments_to_author' => 'Please revise the methods.',
            'internal_notes' => 'INTERNAL_EDITOR_NOTE',
            'decided_at' => now(),
        ]);
        $manuscript->update(['status' => ArticleStatus::RevisionRequired]);

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertSee('Please clarify the sampling protocol.', false)
            ->assertSee('Please revise the methods.', false)
            ->assertDontSee('CONFIDENTIAL_EDITOR_ONLY', false)
            ->assertDontSee('INTERNAL_EDITOR_NOTE', false)
            ->assertDontSee('Secret Reviewer Name', false)
            ->assertDontSee('Hidden Editor Name', false);
    }

    public function test_audit_logs_redact_secrets_and_are_admin_only(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $author = $this->createUserWithRole(RoleSlug::Author);
        $user = User::factory()->create();

        Auditor::log('updated', $user, [
            'password' => 'plain-password',
            'remember_token' => 'secret-token',
            'reset_token' => 'reset-secret',
        ], [
            'name' => 'Updated',
            'api_key' => 'super-secret',
        ]);

        $log = AuditLog::query()->latest('id')->firstOrFail();

        $this->assertSame(['name' => 'Updated'], $log->new_values);
        $this->assertSame([], $log->old_values);
        $this->assertArrayNotHasKey('password', $log->old_values ?? []);
        $this->assertArrayNotHasKey('reset_token', $log->old_values ?? []);

        $this->actingAs($author)->get(route('admin.audit-logs.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_database_sessions_for_a_user_can_be_invalidated(): void
    {
        $user = User::factory()->create();
        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            'id' => 'keep-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('keep'),
            'last_activity' => time(),
        ]);
        DB::table('sessions')->insert([
            'id' => 'drop-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode('drop'),
            'last_activity' => time(),
        ]);

        AuthSession::invalidateOthers($user, 'keep-session');

        $this->assertDatabaseHas('sessions', ['id' => 'keep-session']);
        $this->assertDatabaseMissing('sessions', ['id' => 'drop-session']);
    }

    public function test_non_admin_cannot_access_admin_user_management(): void
    {
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);

        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    private function makeDraft(User $author): Article
    {
        $journal = Journal::query()->first() ?? Journal::factory()->create();

        $manuscript = Article::factory()->create([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $author->id,
            'status' => ArticleStatus::Draft,
        ]);

        ArticleAuthor::factory()->corresponding()->create([
            'article_id' => $manuscript->id,
            'user_id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
            'affiliation' => $author->affiliation,
        ]);

        return $manuscript->fresh(['authors']);
    }

    private function makeCompleteDraft(User $author): Article
    {
        $manuscript = $this->makeDraft($author);
        $path = $manuscript->id.'/'.fake()->uuid().'.pdf';
        Storage::disk('manuscripts')->put($path, 'pdf-bytes');

        ArticleFile::factory()->create([
            'article_id' => $manuscript->id,
            'uploaded_by' => $author->id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => 'manuscript.pdf',
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);

        return $manuscript->fresh(['authors', 'files']);
    }
}
