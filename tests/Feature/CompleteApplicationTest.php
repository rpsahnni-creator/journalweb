<?php

namespace Tests\Feature;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\EditorialDecisionType;
use App\Enums\IssueStatus;
use App\Enums\ReviewerAssignmentStatus;
use App\Enums\ReviewRecommendation;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Volume;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompleteApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_login(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $this->post(route('register'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ])->assertSessionHasErrors(['name', 'email', 'password']);

        $this->post(route('register'), [
            'name' => 'Ada Author',
            'email' => 'ada@example.com',
            'affiliation' => 'Example University',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice'));

        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotSame('password', $user->password);
        $this->assertTrue($user->hasRole(RoleSlug::Reader));
        $this->assertTrue($user->hasRole(RoleSlug::Author));
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();

        $this->from(route('login'))->post(route('login'), [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])->assertRedirect(route('author.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('author.dashboard'))->assertRedirect(route('verification.notice'));

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user->fresh())
            ->get(route('author.dashboard'))
            ->assertOk();
    }

    public function test_roles_and_permissions(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $copyeditor = $this->createUserWithRole(RoleSlug::Copyeditor);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $author = $this->createUserWithRole(RoleSlug::Author);
        $reader = $this->createUserWithRole(RoleSlug::Reader);

        $this->assertGreaterThanOrEqual(18, Permission::query()->count());
        $this->assertSame(9, Role::query()->count());

        $this->assertTrue($admin->hasPermission('users.manage'));
        $this->assertTrue($admin->hasPermission('articles.publish'));
        $this->assertTrue($manager->hasPermission('volumes.manage'));
        $this->assertFalse($manager->hasPermission('articles.publish'));
        $this->assertTrue($editor->hasPermission('articles.decide'));
        $this->assertTrue($editor->hasPermission('reviews.assign'));
        $this->assertFalse($copyeditor->hasPermission('reviews.assign'));
        $this->assertTrue($copyeditor->hasPermission('articles.publish'));
        $this->assertTrue($reviewer->hasPermission('reviews.submit'));
        $this->assertFalse($reviewer->hasPermission('articles.submit'));
        $this->assertTrue($author->hasPermission('articles.submit'));
        $this->assertFalse($author->hasPermission('articles.view_all'));
        $this->assertFalse($reader->hasPermission('articles.submit'));
        $this->assertFalse($reader->hasPermission('users.manage'));
    }

    public function test_public_page_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'home',
            'about',
            'aims-and-scope',
            'editorial-board',
            'author-guidelines',
            'peer-review-policy',
            'publication-ethics',
            'plagiarism-policy',
            'copyright-and-license',
            'conflict-of-interest',
            'corrections-and-retractions',
            'complaints-and-appeals',
            'reviewer-guidelines',
            'review-process',
            'submission-checklist',
            'manuscript-preparation',
            'issues.current',
            'archive',
            'articles.index',
            'contact',
            'health',
            'login',
            'register',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('author.dashboard'))->assertRedirect(route('login'));
        $this->get(route('editorial.dashboard'))->assertRedirect(route('login'));
        $this->get(route('reviewer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_access(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $author = $this->createUserWithRole(RoleSlug::Author);
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();

        $this->actingAs($author)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($author)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Blocked',
            'email' => 'blocked-admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    public function test_complete_editorial_workflow_from_submission_to_public_url(): void
    {
        Storage::fake('manuscripts');

        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author, [
            'name' => 'Ada Author',
            'affiliation' => 'Institute of Plant Science',
        ]);
        $stranger = $this->createUserWithRole(RoleSlug::Author, [
            'name' => 'Other Author',
            'affiliation' => 'Other Laboratory',
        ]);
        $reader = $this->createUserWithRole(RoleSlug::Reader);
        $editor = $this->createUserWithRole(RoleSlug::EditorInChief);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer, [
            'affiliation' => 'Coastal Methods Laboratory',
        ]);

        $this->actingAs($author)
            ->post(route('author.manuscripts.store'), [
                'title' => 'A complete study of controlled sampling methods',
                'article_type' => ArticleType::ResearchArticle->value,
                'abstract' => str_repeat('Abstract text for the manuscript. ', 6),
                'keywords' => 'sampling, methods, biology',
            ])
            ->assertRedirect();

        $manuscript = Article::query()->firstOrFail();
        $this->assertSame(ArticleStatus::Draft, $manuscript->status);
        $this->assertSame($journal->id, $manuscript->journal_id);

        $this->actingAs($author)
            ->put(route('author.manuscripts.update', $manuscript), $this->authorPayload($author, [
                'authors' => [
                    [
                        'name' => $author->name,
                        'email' => $author->email,
                        'affiliation' => $author->affiliation,
                    ],
                    [
                        'name' => 'Coauthor Researcher',
                        'email' => 'coauthor@example.com',
                        'affiliation' => 'Partner Laboratory',
                    ],
                ],
                'corresponding_index' => 0,
            ]))
            ->assertRedirect(route('author.manuscripts.edit', $manuscript));

        $this->assertCount(2, $manuscript->fresh()->authors);
        $this->assertTrue($manuscript->authors()->where('name', 'Coauthor Researcher')->exists());
        $this->assertTrue($manuscript->authors()->where('is_corresponding', true)->where('email', $author->email)->exists());

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('malware.exe', 20, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('paper.pdf', 80, 'text/plain'),
            ])
            ->assertSessionHasErrors('file');

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('sampling-methods.pdf', 120, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        $file = $manuscript->files()->firstOrFail();
        $this->assertFalse($file->is_public);
        $this->assertSame('manuscripts', $file->disk);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.pdf$/', basename($file->path));

        $this->actingAs($author)
            ->get(route('author.manuscripts.files.download', [$manuscript, $file]))
            ->assertOk()
            ->assertDownload('sampling-methods.pdf');

        $this->actingAs($stranger)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertForbidden();
        $this->actingAs($stranger)
            ->get(route('author.manuscripts.files.download', [$manuscript, $file]))
            ->assertForbidden();
        $this->actingAs($reader)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertForbidden();
        $this->get('/storage/'.$file->path)->assertNotFound();
        $this->get(route('articles.show', $manuscript->slug))->assertNotFound();

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript))
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Submitted, $manuscript->fresh()->status);

        $this->actingAs($author)
            ->put(route('author.manuscripts.update', $manuscript), $this->authorPayload($author))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::Accept->value,
                'comments_to_author' => 'This manuscript is accepted without completing peer review.',
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.screen', $manuscript), [
                'outcome' => 'send_to_review',
            ])
            ->assertRedirect();

        $this->assertSame(ArticleStatus::UnderReview, $manuscript->fresh()->status);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.reviewers.store', $manuscript), [
                'reviewer_id' => $reviewer->id,
                'due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $assignment = $manuscript->fresh()->reviewerAssignments()->firstOrFail();
        $this->assertSame(ReviewerAssignmentStatus::Invited, $assignment->status);

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $file]))
            ->assertForbidden();

        $this->actingAs($stranger)
            ->get(route('reviewer.assignments.show', $assignment))
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.accept', $assignment))
            ->assertRedirect(route('reviewer.assignments.show', $assignment));

        $this->actingAs($reviewer)
            ->get(route('reviewer.assignments.files.download', [$assignment, $file]))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('reviewer.assignments.review.store', $assignment), [
                'recommendation' => ReviewRecommendation::MinorRevision->value,
                'comments_to_author' => 'Please clarify the sampling protocol and replicate counts.',
                'comments_to_editor' => 'CONFIDENTIAL_EDITOR_COMMENTS',
            ])
            ->assertRedirect(route('reviewer.assignments.show', $assignment));

        $this->assertSame(ReviewerAssignmentStatus::Completed, $assignment->fresh()->status);

        $this->actingAs($author)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertOk()
            ->assertDontSee('CONFIDENTIAL_EDITOR_COMMENTS', false)
            ->assertDontSee($reviewer->name, false);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::MinorRevision->value,
                'comments_to_author' => 'Please revise the methods and submit a point-by-point response.',
                'revision_due_at' => now()->addDays(30)->toDateString(),
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::RevisionRequired, $manuscript->fresh()->status);

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('revised-sampling-methods.pdf', 90, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        $originalPath = $file->path;

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript), [
                'author_response' => 'We revised the sampling protocol and added the requested replicates.',
            ])
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Resubmitted, $manuscript->fresh()->status);
        $this->assertSame(2, $manuscript->revisions()->count());
        Storage::disk('manuscripts')->assertExists($originalPath);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::Accept->value,
                'comments_to_author' => 'The revised manuscript is accepted for publication.',
            ])
            ->assertRedirect(route('editorial.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Accepted, $manuscript->fresh()->status);

        $this->actingAs($editor)
            ->post(route('editorial.manuscripts.decision.store', $manuscript), [
                'decision' => EditorialDecisionType::MinorRevision->value,
                'comments_to_author' => 'Trying to reopen an accepted manuscript for revision.',
                'revision_due_at' => now()->addDays(21)->toDateString(),
            ])
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.volumes.store'), [
                'number' => 12,
                'year' => 2026,
                'title' => 'Complete workflow volume',
            ])
            ->assertRedirect(route('editorial.volumes.index'));

        $volume = Volume::query()->where('journal_id', $journal->id)->where('number', 12)->firstOrFail();

        $this->actingAs($editor)
            ->post(route('editorial.issues.store'), [
                'volume_id' => $volume->id,
                'number' => 1,
                'title' => 'Complete workflow issue',
                'description' => 'Accepted articles from the complete application test.',
                'published_at' => '2026-09-01',
            ])
            ->assertRedirect();

        $issue = Issue::query()->where('volume_id', $volume->id)->where('number', 1)->firstOrFail();

        $this->actingAs($author)
            ->post(route('editorial.issues.publish', $issue))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $manuscript->id,
                'article_number' => 'e1',
                'page_start' => 1,
                'page_end' => 12,
            ])
            ->assertRedirect();

        $this->actingAs($editor)
            ->post(route('editorial.issues.publish', $issue))
            ->assertRedirect();

        $manuscript->refresh();
        $issue->refresh();

        $this->assertSame(IssueStatus::Published, $issue->status);
        $this->assertSame(ArticleStatus::Published, $manuscript->status);
        $this->assertNotNull($manuscript->published_at);
        $this->assertNotEmpty($manuscript->slug);

        $public = $this->get(route('articles.show', $manuscript));
        $public->assertOk()
            ->assertSee('A complete study of controlled sampling methods', false)
            ->assertSee('Coauthor Researcher', false)
            ->assertSee($manuscript->publicUrl(), false);

        $this->assertSame(url('/articles/'.$manuscript->slug), $manuscript->publicUrl());
        $this->get(route('articles.pdf', $manuscript))->assertOk();
        $this->get(route('issues.show', ['volume' => 12, 'issue' => 1]))
            ->assertOk()
            ->assertSee('A complete study of controlled sampling methods', false);
    }

    public function test_unauthorized_access_and_invalid_status_transitions(): void
    {
        Storage::fake('manuscripts');

        $journal = Journal::factory()->create();
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Institute of Plant Science']);
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);
        $editor = $this->createUserWithRole(RoleSlug::Editor);
        $reader = $this->createUserWithRole(RoleSlug::Reader);

        $this->actingAs($author)
            ->post(route('author.manuscripts.store'), [
                'title' => 'Unauthorized access manuscript',
                'article_type' => ArticleType::ResearchArticle->value,
                'abstract' => str_repeat('Abstract text for the manuscript. ', 6),
            ])
            ->assertRedirect();

        $draft = Article::query()->firstOrFail();

        $this->actingAs($reader)->get(route('author.manuscripts.edit', $draft))->assertForbidden();
        $this->actingAs($reviewer)->post(route('editorial.manuscripts.screen', $draft), [
            'outcome' => 'send_to_review',
        ])->assertForbidden();
        $this->actingAs($editor)->post(route('editorial.manuscripts.screen', $draft), [
            'outcome' => 'send_to_review',
        ])->assertForbidden();

        $this->assertFalse(ArticleStatus::Draft->canTransitionTo(ArticleStatus::Accepted));
        $this->assertFalse(ArticleStatus::Draft->canTransitionTo(ArticleStatus::Published));
        $this->assertFalse(ArticleStatus::Submitted->canTransitionTo(ArticleStatus::RevisionRequired));
        $this->assertFalse(ArticleStatus::UnderReview->canTransitionTo(ArticleStatus::Submitted));
        $this->assertFalse(ArticleStatus::Accepted->canTransitionTo(ArticleStatus::RevisionRequired));
        $this->assertFalse(ArticleStatus::Rejected->canTransitionTo(ArticleStatus::UnderReview));
        $this->assertFalse(ArticleStatus::Published->canTransitionTo(ArticleStatus::Draft));

        $this->actingAs($author)
            ->put(route('author.manuscripts.update', $draft), $this->authorPayload($author))
            ->assertRedirect();

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $draft), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('draft.pdf', 80, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $draft))
            ->assertRedirect();

        $this->assertSame(ArticleStatus::Submitted, $draft->fresh()->status);

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $draft))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('editorial.volumes.store'), [
                'number' => 3,
                'year' => 2026,
            ])
            ->assertRedirect();

        $volume = Volume::query()->where('journal_id', $journal->id)->firstOrFail();

        $this->actingAs($editor)
            ->post(route('editorial.issues.store'), [
                'volume_id' => $volume->id,
                'number' => 1,
                'title' => 'Cannot publish unpublished work',
            ])
            ->assertRedirect();

        $issue = Issue::query()->where('volume_id', $volume->id)->firstOrFail();

        $this->actingAs($editor)
            ->post(route('editorial.issues.articles.store', $issue), [
                'article_id' => $draft->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(ArticleStatus::Submitted, $draft->fresh()->status);
        $this->get(route('articles.show', $draft->fresh()->slug))->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function authorPayload(User $author, array $overrides = []): array
    {
        return array_merge([
            'title' => 'A complete study of controlled sampling methods',
            'article_type' => ArticleType::ResearchArticle->value,
            'abstract' => str_repeat('Abstract text for the manuscript. ', 6),
            'keywords' => 'sampling, methods, biology',
            'cover_letter' => 'Please consider this manuscript for review.',
            'originality_confirmed' => '1',
            'conflict_of_interest_declared' => '1',
            'conflict_of_interest_statement' => 'The authors declare no conflict of interest.',
            'corresponding_index' => 0,
            'authors' => [
                [
                    'name' => $author->name,
                    'email' => $author->email,
                    'affiliation' => $author->affiliation,
                ],
            ],
        ], $overrides);
    }
}
