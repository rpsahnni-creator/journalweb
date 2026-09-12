<?php

namespace Tests\Feature\Author;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleFile;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManuscriptSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_and_readers_cannot_open_the_author_portal(): void
    {
        $this->get(route('author.dashboard'))->assertRedirect(route('login'));

        $reader = $this->createUserWithRole(RoleSlug::Reader);

        $this->actingAs($reader)->get(route('author.dashboard'))->assertForbidden();
        $this->actingAs($reader)->get(route('author.manuscripts.create'))->assertForbidden();
    }

    public function test_author_can_update_profile_affiliation(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => null]);

        $this->actingAs($author)
            ->from(route('author.profile.edit'))
            ->put(route('author.profile.update'), [
                'name' => $author->name,
                'email' => $author->email,
                'affiliation' => 'Department of Biology',
            ])
            ->assertRedirect(route('author.profile.edit'))
            ->assertSessionHas('status');

        $this->assertSame('Department of Biology', $author->fresh()->affiliation);
    }

    public function test_author_can_create_and_edit_a_draft_with_multiple_authors(): void
    {
        $author = $this->createAuthor();
        $this->createJournal();

        $this->actingAs($author)
            ->post(route('author.manuscripts.store'), [
                'title' => 'Draft study of sample collection methods',
                'article_type' => ArticleType::ResearchArticle->value,
                'abstract' => 'An initial abstract that will be expanded before submission.',
                'keywords' => 'methods, sampling, biology',
            ])
            ->assertRedirect();

        $manuscript = Article::query()->firstOrFail();

        $this->assertSame(ArticleStatus::Draft, $manuscript->status);
        $this->assertNotEmpty($manuscript->submission_number);
        $this->assertTrue($manuscript->authors()->where('is_corresponding', true)->exists());

        $this->actingAs($author)
            ->from(route('author.manuscripts.edit', $manuscript))
            ->followingRedirects()
            ->put(route('author.manuscripts.update', $manuscript), $this->payload($author, [
                'title' => 'Updated study of sample collection methods',
                'corresponding_index' => 1,
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
            ]))
            ->assertOk()
            ->assertSee('Draft manuscript saved.', false)
            ->assertSee('Coauthor Researcher', false);

        $manuscript->refresh();
        $this->assertSame('Updated study of sample collection methods', $manuscript->title);
        $this->assertSame('Coauthor Researcher', $manuscript->authors()->where('is_corresponding', true)->value('name'));
        $this->assertCount(2, $manuscript->authors);
        $this->assertSame(ArticleStatus::Draft, $manuscript->status);
    }

    public function test_author_can_upload_validated_private_files_with_random_server_names(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $manuscript = $this->makeDraft($author);

        $upload = UploadedFile::fake()->create('my-paper.pdf', 120, 'application/pdf');

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => $upload,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $file = $manuscript->files()->firstOrFail();

        $this->assertFalse($file->is_public);
        $this->assertSame('manuscripts', $file->disk);
        $this->assertSame('my-paper.pdf', $file->original_filename);
        $this->assertSame('application/pdf', $file->mime_type);
        $this->assertStringNotContainsString('my-paper', $file->path);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}\.pdf$/', basename($file->path));
        Storage::disk('manuscripts')->assertExists($file->path);

        $this->actingAs($author)
            ->get(route('author.manuscripts.files.download', [$manuscript, $file]))
            ->assertOk()
            ->assertDownload('my-paper.pdf');

        $this->get('/storage/'.$file->path)->assertNotFound();
        $this->get('/storage/manuscripts/'.$file->path)->assertNotFound();
        $this->get(route('articles.show', $manuscript->slug))->assertNotFound();
    }

    public function test_file_uploads_reject_invalid_types_mime_types_and_oversized_files(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $manuscript = $this->makeDraft($author);

        $this->actingAs($author)
            ->from(route('author.manuscripts.edit', $manuscript))
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('malware.exe', 20, 'application/x-msdownload'),
            ])
            ->assertRedirect(route('author.manuscripts.edit', $manuscript))
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
                'file' => UploadedFile::fake()->create('paper.pdf', 30000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, $manuscript->files()->count());
    }

    public function test_author_can_add_a_supplementary_file(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $manuscript = $this->makeDraft($author);

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Supplementary->value,
                'file' => UploadedFile::fake()->create('dataset.csv', 40, 'text/csv'),
            ])
            ->assertSessionHas('status');

        $this->assertSame(ArticleFileType::Supplementary, $manuscript->files()->first()?->type);
    }

    public function test_submitted_manuscripts_cannot_be_edited_unless_revision_is_requested(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $manuscript = $this->makeCompleteDraft($author);

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript))
            ->assertRedirect(route('author.manuscripts.show', $manuscript))
            ->assertSessionHas('status');

        $manuscript->refresh();
        $this->assertSame(ArticleStatus::Submitted, $manuscript->status);
        $this->assertNotNull($manuscript->submitted_at);
        $this->assertSame(1, $manuscript->revisions()->count());
        $this->assertDatabaseHas('article_status_events', [
            'article_id' => $manuscript->id,
            'to_status' => ArticleStatus::Submitted->value,
        ]);

        $this->actingAs($author)
            ->get(route('author.manuscripts.edit', $manuscript))
            ->assertForbidden();

        $this->actingAs($author)
            ->put(route('author.manuscripts.update', $manuscript), $this->payload($author, ['title' => 'Changed after submit']))
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('revision.pdf', 80, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertSame('A complete study of controlled sampling methods', $manuscript->fresh()->title);

        $manuscript->update(['status' => ArticleStatus::RevisionRequired]);

        $this->actingAs($author)
            ->get(route('author.manuscripts.edit', $manuscript))
            ->assertOk();

        $this->actingAs($author)
            ->put(route('author.manuscripts.update', $manuscript), $this->payload($author, ['title' => 'Revised study of controlled sampling methods']))
            ->assertRedirect(route('author.manuscripts.edit', $manuscript));

        $this->assertSame('Revised study of controlled sampling methods', $manuscript->fresh()->title);
    }

    public function test_submit_requires_files_declarations_and_affiliation(): void
    {
        $author = $this->createAuthor(['affiliation' => null]);
        $manuscript = $this->makeDraft($author);

        $this->actingAs($author)
            ->from(route('author.manuscripts.show', $manuscript))
            ->post(route('author.manuscripts.submit', $manuscript))
            ->assertRedirect(route('author.manuscripts.show', $manuscript))
            ->assertSessionHas('error');

        $this->assertSame(ArticleStatus::Draft, $manuscript->fresh()->status);
    }

    public function test_other_authors_cannot_view_or_download_private_files(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $stranger = $this->createUserWithRole(RoleSlug::Author, ['affiliation' => 'Other Lab']);
        $manuscript = $this->makeCompleteDraft($author);
        $file = $manuscript->files()->firstOrFail();

        $this->actingAs($stranger)->get(route('author.manuscripts.show', $manuscript))->assertForbidden();
        $this->actingAs($stranger)->get(route('author.manuscripts.files.download', [$manuscript, $file]))->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->get(route('author.manuscripts.files.download', [$manuscript, $file]))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_lists_and_filters_the_authors_manuscripts(): void
    {
        $author = $this->createAuthor();
        $mine = $this->makeDraft($author, ['title' => 'Visible draft manuscript']);
        Article::factory()->create(['title' => 'Someone else manuscript']);

        $this->actingAs($author)
            ->get(route('author.dashboard', ['q' => 'Visible draft']))
            ->assertOk()
            ->assertSee('Visible draft manuscript', false)
            ->assertSee($mine->submission_number, false)
            ->assertDontSee('Someone else manuscript', false);
    }

    public function test_resubmitting_after_revision_records_status_history(): void
    {
        Storage::fake('manuscripts');
        $author = $this->createAuthor();
        $manuscript = $this->makeCompleteDraft($author);
        $this->actingAs($author)->post(route('author.manuscripts.submit', $manuscript));

        $manuscript->update(['status' => ArticleStatus::RevisionRequired]);

        $this->actingAs($author)
            ->post(route('author.manuscripts.files.store', $manuscript), [
                'type' => ArticleFileType::Manuscript->value,
                'file' => UploadedFile::fake()->create('revised-manuscript.pdf', 80, 'application/pdf'),
            ])
            ->assertSessionHas('status');

        $originalPath = $manuscript->files()->whereNotNull('revision_id')->firstOrFail()->path;

        $this->actingAs($author)
            ->post(route('author.manuscripts.submit', $manuscript), [
                'author_response' => 'We revised the sampling protocol and added the requested replicates.',
            ])
            ->assertRedirect(route('author.manuscripts.show', $manuscript));

        $this->assertSame(ArticleStatus::Resubmitted, $manuscript->fresh()->status);
        $this->assertSame(2, $manuscript->revisions()->count());
        $this->assertDatabaseHas('article_status_events', [
            'article_id' => $manuscript->id,
            'to_status' => ArticleStatus::Resubmitted->value,
        ]);
        Storage::disk('manuscripts')->assertExists($originalPath);
        $this->assertSame(2, $manuscript->files()->where('type', ArticleFileType::Manuscript)->count());
    }

    private function createAuthor(array $attributes = []): User
    {
        return $this->createUserWithRole(RoleSlug::Author, array_merge([
            'affiliation' => 'Institute of Plant Science',
        ], $attributes));
    }

    private function createJournal(): Journal
    {
        return Journal::factory()->create();
    }

    private function makeDraft(User $author, array $attributes = []): Article
    {
        $journal = Journal::query()->first() ?? $this->createJournal();

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

        $manuscript->recordStatusChange(null, ArticleStatus::Draft, $author, 'Draft created.');

        return $manuscript->fresh(['authors']);
    }

    private function makeCompleteDraft(User $author): Article
    {
        $manuscript = $this->makeDraft($author);

        ArticleFile::factory()->create([
            'article_id' => $manuscript->id,
            'uploaded_by' => $author->id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => 'manuscript.pdf',
            'disk' => 'manuscripts',
            'path' => $manuscript->id.'/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'is_public' => false,
        ]);

        Storage::disk('manuscripts')->put($manuscript->files()->first()->path, 'pdf-bytes');

        return $manuscript->fresh(['authors', 'files', 'correspondingAuthor']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(User $author, array $overrides = []): array
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
