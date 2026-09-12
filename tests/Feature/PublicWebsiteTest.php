<?php

namespace Tests\Feature;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\JournalPolicyType;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\ContactMessage;
use App\Models\EditorialBoardMember;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Models\Volume;
use App\Support\JournalCopy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_pages_render_database_policy_content(): void
    {
        $this->seed(DatabaseSeeder::class);

        $pages = [
            'about' => JournalPolicyType::About->title(),
            'aims-and-scope' => JournalPolicyType::AimsAndScope->title(),
            'author-guidelines' => JournalPolicyType::AuthorGuidelines->title(),
            'peer-review-policy' => JournalPolicyType::PeerReview->title(),
            'publication-ethics' => JournalPolicyType::PublicationEthics->title(),
            'plagiarism-policy' => JournalPolicyType::Plagiarism->title(),
            'copyright-and-license' => JournalPolicyType::Copyright->title(),
            'article-processing-charges' => JournalPolicyType::Apc->title(),
            'conflict-of-interest' => JournalPolicyType::ConflictOfInterest->title(),
            'corrections-and-retractions' => JournalPolicyType::CorrectionsAndRetractions->title(),
            'complaints-and-appeals' => JournalPolicyType::ComplaintsAndAppeals->title(),
            'reviewer-guidelines' => JournalPolicyType::ReviewerGuidelines->title(),
            'review-process' => JournalPolicyType::ReviewProcess->title(),
            'submission-checklist' => JournalPolicyType::SubmissionChecklist->title(),
            'manuscript-preparation' => JournalPolicyType::ManuscriptPreparation->title(),
        ];

        foreach ($pages as $route => $heading) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($heading, false)
                ->assertSee('<meta name="description"', false);
        }
    }

    public function test_editorial_board_hides_unpublished_members(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();

        EditorialBoardMember::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'Hidden Board Member',
            'is_public' => false,
            'is_active' => true,
        ]);

        EditorialBoardMember::factory()->public()->create([
            'journal_id' => $journal->id,
            'name' => 'Public Board Member',
            'role_title' => 'Associate Editor',
        ]);

        $this->get(route('editorial-board'))
            ->assertOk()
            ->assertSee('Public Board Member', false)
            ->assertDontSee('Hidden Board Member', false);
    }

    public function test_unpublished_articles_are_not_publicly_reachable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $article = Article::factory()->create([
            'journal_id' => $journal->id,
            'status' => ArticleStatus::UnderReview,
            'slug' => 'secret-under-review-manuscript',
            'title' => 'Secret Under Review Manuscript',
        ]);

        $this->get(route('articles.show', $article))
            ->assertNotFound();
        $this->get(route('articles.show', 'secret-under-review-manuscript'))
            ->assertNotFound();
    }

    public function test_published_articles_and_issues_are_visible(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Sample article: keeping unpublished manuscripts private', false);

        $this->get(route('archive'))
            ->assertOk()
            ->assertSee('Previous Issues', false);

        $this->get(route('issues.show', ['volume' => 2, 'issue' => 1]))
            ->assertOk();

        $this->get(route('articles.show', 'sample-article-keeping-unpublished-manuscripts-private'))
            ->assertOk()
            ->assertSee('Abstract', false)
            ->assertSee('How to cite this article', false)
            ->assertSee('Sample Author', false)
            ->assertSee('Development sample account', false)
            ->assertSee('sample-article-keeping-unpublished-manuscripts-private', false)
            ->assertDontSee('Scopus', false)
            ->assertDontSee('ISSN', false)
            ->assertDontSee('doi.org', false);
    }

    public function test_each_published_article_has_a_unique_guest_accessible_url(): void
    {
        $this->seed(DatabaseSeeder::class);

        $first = Article::query()
            ->where('slug', 'sample-article-transparency-in-editorial-workflow')
            ->firstOrFail();
        $second = Article::query()
            ->where('slug', 'sample-article-keeping-unpublished-manuscripts-private')
            ->firstOrFail();
        $third = Article::query()
            ->where('slug', 'sample-article-peer-review-records-and-author-revisions')
            ->firstOrFail();

        $this->assertNotSame($first->publicUrl(), $second->publicUrl());
        $this->assertNotSame($second->publicUrl(), $third->publicUrl());
        $this->assertNotSame($first->slug, $second->slug);

        $this->get($first->publicUrl())
            ->assertOk()
            ->assertSee($first->title, false)
            ->assertSee($first->abstract, false)
            ->assertSee($first->publicUrl(), false)
            ->assertSee('<link rel="canonical" href="'.$first->publicUrl().'"', false)
            ->assertSee('>'.$first->title.'</h1>', false)
            ->assertDontSee('>'.$second->title.'</h1>', false);

        $this->get($second->publicUrl())
            ->assertOk()
            ->assertSee($second->title, false)
            ->assertSee($second->publicUrl(), false)
            ->assertSee('>'.$second->title.'</h1>', false)
            ->assertDontSee('>'.$first->title.'</h1>', false);

        $this->get(route('issues.show', ['volume' => 1, 'issue' => 1]))
            ->assertOk()
            ->assertSee('Contents', false)
            ->assertSee($first->publicUrl(), false)
            ->assertSee($first->title, false);

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Contents', false)
            ->assertSee($second->publicUrl(), false)
            ->assertSee($third->publicUrl(), false)
            ->assertSee($second->title, false)
            ->assertSee($third->title, false);
    }

    public function test_article_search_finds_published_work_by_keyword_and_author(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('articles.index', ['q' => 'editorial-workflow']))
            ->assertOk()
            ->assertSee('Sample article: transparency in editorial workflow', false);

        $this->get(route('archive', ['q' => 'Sample Author']))
            ->assertOk()
            ->assertSee('Sample article: keeping unpublished manuscripts private', false);

        $this->get(route('articles.index', ['q' => 'unpublished-secret-term']))
            ->assertOk()
            ->assertSee('No published articles matched that search', false);
    }

    public function test_unpublished_issues_are_not_listed_in_the_archive(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 99, 'year' => 2026]);
        Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 8,
            'title' => 'Hidden draft issue',
            'status' => IssueStatus::Draft,
        ]);

        $this->get(route('archive'))
            ->assertOk()
            ->assertDontSee('Hidden draft issue', false);

        $this->get(route('issues.show', ['volume' => 99, 'issue' => 8]))
            ->assertNotFound();
    }

    public function test_published_article_pdf_is_streamed_and_unpublished_files_stay_private(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('manuscripts');

        $article = Article::query()
            ->where('slug', 'sample-article-keeping-unpublished-manuscripts-private')
            ->firstOrFail();

        $this->get(route('articles.pdf', $article))->assertNotFound();

        $path = $article->id.'/version-of-record.pdf';
        Storage::disk('manuscripts')->put($path, '%PDF-1.4 sample');

        ArticleFile::factory()->create([
            'article_id' => $article->id,
            'uploaded_by' => $article->corresponding_author_id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => 'version-of-record.pdf',
            'disk' => 'manuscripts',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'is_public' => true,
        ]);

        $this->get(route('articles.pdf', $article))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $secret = Article::factory()->create([
            'journal_id' => $article->journal_id,
            'status' => ArticleStatus::Accepted,
            'slug' => 'secret-accepted-manuscript',
            'title' => 'Secret accepted manuscript',
        ]);

        $secretPath = $secret->id.'/secret.pdf';
        Storage::disk('manuscripts')->put($secretPath, '%PDF-1.4 secret');
        ArticleFile::factory()->create([
            'article_id' => $secret->id,
            'uploaded_by' => $secret->corresponding_author_id,
            'type' => ArticleFileType::Manuscript,
            'original_filename' => 'secret.pdf',
            'disk' => 'manuscripts',
            'path' => $secretPath,
            'mime_type' => 'application/pdf',
            'is_public' => true,
        ]);

        $this->get(route('articles.show', 'secret-accepted-manuscript'))->assertNotFound();
        $this->get(route('articles.pdf', $secret))->assertNotFound();
    }

    public function test_archive_paginates_published_issues(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 10]);

        foreach (range(1, 12) as $number) {
            Issue::factory()->published()->create([
                'journal_id' => $journal->id,
                'volume_id' => $volume->id,
                'number' => $number,
            ]);
        }

        $this->get(route('archive'))
            ->assertOk()
            ->assertSee('page=2', false);
    }

    public function test_contact_form_stores_a_message(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('contact.store'), [
            'name' => 'Reader Name',
            'email' => 'reader@example.com',
            'subject' => 'Question about the archive',
            'message' => 'Please tell me when the next issue will be published.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'reader@example.com',
            'subject' => 'Question about the archive',
        ]);
        $this->assertSame(1, ContactMessage::query()->count());
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->from(route('contact'))
            ->post(route('contact.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
    }

    public function test_seeded_sample_articles_are_marked_as_demo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(
            Article::query()->where('title', 'like', 'Sample article%')->exists()
        );
        $this->assertSame(
            0,
            Article::query()->where('title', 'like', 'Sample article%')->where('is_demo', false)->count()
        );
        $this->assertGreaterThanOrEqual(
            2,
            Article::query()->where('is_demo', true)->count()
        );
    }

    public function test_homepage_shows_call_for_papers_when_the_current_issue_has_fewer_than_five_non_demo_articles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $issue = $journal->currentIssue()->firstOrFail();

        // Exactly 3 published non-demo articles (fewer than 5)
        foreach (range(1, 3) as $number) {
            $article = Article::factory()->published()->create([
                'journal_id' => $journal->id,
                'is_demo' => false,
                'title' => "Inaugural research article {$number}",
            ]);

            $issue->articles()->attach($article->id, [
                'sort_order' => $number + 10,
                'article_number' => (string) $number,
            ]);
        }

        $this->assertSame(3, $issue->publishedNonDemoArticleCount());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertSee('currently accepting submissions for its inaugural issue', false)
            ->assertSee('Submit Your Article', false)
            ->assertSee(route('author-guidelines'), false);
    }

    public function test_production_hides_demo_articles_from_the_homepage_and_current_issue(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->app['env'] = 'production';

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertDontSee('Sample article:', false);

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertDontSee('Sample article: keeping unpublished manuscripts private', false);

        $this->get(route('articles.show', 'sample-article-keeping-unpublished-manuscripts-private'))
            ->assertNotFound();
    }

    public function test_local_environment_still_lists_demo_articles_on_the_current_issue_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->app['env'] = 'local';

        $this->get(route('issues.current'))
            ->assertOk()
            ->assertSee('Sample article: keeping unpublished manuscripts private', false)
            ->assertSee('Sample article: peer review records and author revisions', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertDontSee('Sample article:', false);
    }

    public function test_homepage_lists_the_current_issue_after_five_non_demo_articles_are_published(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $issue = $journal->currentIssue()->firstOrFail();

        foreach (range(1, 5) as $number) {
            $article = Article::factory()->published()->create([
                'journal_id' => $journal->id,
                'is_demo' => false,
                'title' => "Inaugural research article {$number}",
            ]);

            $issue->articles()->attach($article->id, [
                'sort_order' => $number + 10,
                'article_number' => (string) $number,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Vol. 1, No. 1 — Forthcoming (June 2026)', false)
            ->assertSee('Inaugural research article 1', false);
    }

    public function test_author_guidelines_page_lists_submission_requirements(): void
    {
        $this->get(route('author-guidelines'))
            ->assertOk()
            ->assertSee('Author Guidelines', false)
            ->assertSee('3,000–6,000 words', false)
            ->assertSee('15%', false)
            ->assertSee('journal@srtc.ac.in', false)
            ->assertSee('double-blind peer review', false)
            ->assertSee('APA 7th edition', false)
            ->assertSee(route('article-processing-charges'), false);
    }

    public function test_article_processing_charges_policy_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('article-processing-charges'))
            ->assertOk()
            ->assertSee('Article Processing Charges (APC)', false)
            ->assertSee('SRT Journal of Multidisciplinary Research does not charge any article submission, processing, or publication fees. All articles are published under Diamond (Platinum) Open Access at no cost to authors or their institutions.', false)
            ->assertSee('<meta name="description"', false);

        $this->get(route('author-guidelines'))
            ->assertOk()
            ->assertSee(route('article-processing-charges'), false)
            ->assertSee('Article Processing Charges (APC)', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('article-processing-charges'), false);
    }

    public function test_publication_frequency_is_declared_on_about_and_in_footer(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Publication Frequency', false)
            ->assertSee('Biannual — published every June and December.', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Publication Frequency', false)
            ->assertSee('Biannual — published every June and December.', false);
    }

    public function test_publisher_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('publisher'))
            ->assertOk()
            ->assertSee('Sri Raghunandan Tiwari College (S.R.T. College)', false)
            ->assertSee('Dhamni, Block – Meherma, District – Godda, Jharkhand – 814140, India', false)
            ->assertSee('Sido Kanhu Murmu University, Dumka', false)
            ->assertSee('https://srtc.ac.in', false)
            ->assertSee('journal@srtc.ac.in', false);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee(route('publisher'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('publisher'), false);
    }

    public function test_conflict_of_interest_policy_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('conflict-of-interest'))
            ->assertOk()
            ->assertSee('Conflict of Interest Policy', false)
            ->assertSee('All authors, reviewers, and editors of SRT Journal of Multidisciplinary Research are required to disclose any financial, professional, or personal relationships', false)
            ->assertSee('<meta name="description"', false);

        $this->get(route('publication-ethics'))
            ->assertOk()
            ->assertSee(route('conflict-of-interest'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('conflict-of-interest'), false);
    }

    public function test_corrections_and_retractions_policy_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('corrections-and-retractions'))
            ->assertOk()
            ->assertSee('Corrections and Retractions', false)
            ->assertSee('formal erratum', false)
            ->assertSee('retraction notice permanently linked', false);

        $this->get(route('plagiarism-policy'))
            ->assertOk()
            ->assertSee(route('corrections-and-retractions'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('corrections-and-retractions'), false);
    }

    public function test_complaints_and_appeals_policy_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('complaints-and-appeals'))
            ->assertOk()
            ->assertSee('Complaints and Appeals', false)
            ->assertSee('journal@srtc.ac.in', false)
            ->assertSee('independent member of the Editorial Board', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('complaints-and-appeals'), false);
    }

    public function test_reviewer_guidelines_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);
        $reviewer = User::factory()->reviewer()->create();

        $this->get(route('reviewer-guidelines'))
            ->assertOk()
            ->assertSee('Reviewer Guidelines', false)
            ->assertSee('treat manuscripts as confidential', false);

        $this->get(route('peer-review-policy'))
            ->assertOk()
            ->assertSee(route('reviewer-guidelines'), false);

        $this->actingAs($reviewer)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee(route('reviewer-guidelines'), false);
    }

    public function test_review_process_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('review-process'))
            ->assertOk()
            ->assertSee('Review Process', false)
            ->assertSee('double-blind peer review by at least two independent reviewers', false);

        $this->get(route('peer-review-policy'))
            ->assertOk()
            ->assertSee(route('review-process'), false);

        $this->get(route('author-guidelines'))
            ->assertOk()
            ->assertSee(route('review-process'), false);
    }

    public function test_submission_checklist_page_content_and_links(): void
    {
        $this->seed(DatabaseSeeder::class);
        $author = $this->createUserWithRole(RoleSlug::Author);

        $this->get(route('submission-checklist'))
            ->assertOk()
            ->assertSee('Submission Checklist', false)
            ->assertSee('Manuscript is original and not under review elsewhere', false)
            ->assertSee('Word count is within 3,000–6,000 words', false)
            ->assertSee(route('downloads.manuscript-template'), false);

        $this->get(route('author-guidelines'))
            ->assertOk()
            ->assertSee(route('submission-checklist'), false);

        $this->actingAs($author)
            ->get(route('submissions.create'))
            ->assertOk()
            ->assertSee(route('submission-checklist'), false);
    }

    public function test_manuscript_preparation_page_and_template_download(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');
        Storage::disk('public')->put(JournalCopy::MANUSCRIPT_TEMPLATE_PATH, 'fake-docx');

        $this->get(route('manuscript-preparation'))
            ->assertOk()
            ->assertSee('Manuscript Preparation', false)
            ->assertSee('A4 page size', false)
            ->assertSee('Times New Roman 12pt', false)
            ->assertSee('Author names must not appear anywhere in the manuscript body', false)
            ->assertSee(route('downloads.manuscript-template'), false);

        $this->get(route('author-guidelines'))
            ->assertOk()
            ->assertSee(route('downloads.manuscript-template'), false);

        $this->get(route('submission-checklist'))
            ->assertOk()
            ->assertSee(route('downloads.manuscript-template'), false);

        $this->get(route('downloads.manuscript-template'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=SRT-Journal-Manuscript-Template.docx');
    }

    public function test_manuscript_template_download_is_not_found_when_the_file_is_missing(): void
    {
        Storage::fake('public');

        $this->get(route('downloads.manuscript-template'))->assertNotFound();
    }

    public function test_special_issues_show_a_badge_and_theme_on_the_archive(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();
        $volume = Volume::factory()->create(['journal_id' => $journal->id, 'number' => 20]);

        Issue::factory()->published()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 1,
            'is_special_issue' => true,
            'special_issue_theme' => 'Climate and Society in Santhal Pargana',
        ]);

        $this->get(route('issues.index'))
            ->assertOk()
            ->assertSee('Special Issue', false)
            ->assertSee('Climate and Society in Santhal Pargana', false)
            ->assertSee('Vol. 20 No. 1', false);
    }

    public function test_indexing_status_is_shown_on_about(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Indexing Status', false)
            ->assertSee('is not yet indexed in any external abstracting or indexing database', false);
    }
}
