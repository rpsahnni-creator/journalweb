<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Enums\JournalPolicyType;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\EditorialBoardMember;
use App\Models\Issue;
use App\Models\IssueArticle;
use App\Models\Journal;
use App\Models\JournalPolicy;
use App\Models\JournalSetting;
use App\Models\User;
use App\Models\Volume;
use App\Support\JournalCopy;
use Illuminate\Database\Seeder;

class JournalContentSeeder extends Seeder
{
    public function run(): void
    {
        $journal = Journal::query()->updateOrCreate(
            ['slug' => 'academic-journal'],
            [
                'name' => 'SRT Journal of Multidisciplinary Research',
                'abbreviation' => 'SRT-JMR',
                'description' => 'A peer-reviewed, refereed academic journal published by Sri Raghunandan Tiwari College (S.R.T. College), Dhamni.',
                'issn' => null,
                'eissn' => null,
                'publisher' => JournalCopy::PUBLISHER_NAME,
                'website_url' => null,
                'is_active' => true,
            ]
        );

        $settings = [
            'issn' => null,
            'contact_email' => JournalCopy::PUBLISHER_EMAIL,
            'contact_address' => JournalCopy::PUBLISHER_ADDRESS,
            'seo_description' => 'Published research, issues, and journal policies. Unpublished manuscripts are not displayed.',
            'about_text' => JournalCopy::ABOUT_TEXT,
            'aims_scope_text' => JournalCopy::AIMS_SCOPE_TEXT,
            'publisher_name' => JournalCopy::PUBLISHER_NAME,
            'publisher_address' => JournalCopy::PUBLISHER_ADDRESS,
            'publisher_email' => JournalCopy::PUBLISHER_EMAIL,
            'publisher_website' => JournalCopy::PUBLISHER_WEBSITE,
            'publication_frequency' => JournalCopy::PUBLICATION_FREQUENCY,
            'indexing_status' => JournalCopy::INDEXING_STATUS,
        ];

        foreach ($settings as $key => $value) {
            JournalSetting::query()->updateOrCreate(
                ['journal_id' => $journal->id, 'key' => $key],
                ['value' => $value]
            );
        }

        foreach ($this->policies() as $type => $body) {
            $policyType = JournalPolicyType::from($type);

            JournalPolicy::query()->updateOrCreate(
                ['journal_id' => $journal->id, 'type' => $policyType->value],
                [
                    'title' => $policyType->title(),
                    'slug' => str_replace('_', '-', $policyType->value),
                    'body' => $body,
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }

        $this->seedEditorialBoard($journal);

        $author = User::query()->updateOrCreate(
            ['email' => 'sample.author@example.com'],
            [
                'name' => 'Sample Author',
                'affiliation' => 'Development sample account',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $author->assignRole(RoleSlug::Author);

        $volumeOne = Volume::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'number' => 1],
            ['year' => now()->year, 'title' => 'Volume 1']
        );
        $volumeTwo = Volume::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'number' => 2],
            ['year' => now()->year, 'title' => 'Volume 2']
        );

        $previousIssue = Issue::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'volume_id' => $volumeOne->id, 'number' => 1],
            [
                'volume_number' => 1,
                'title' => 'Sample issue for development',
                'publication_month_year' => now()->subMonths(2)->format('F Y'),
                'description' => 'Development sample of a previous published issue. Identifiers are shown only when they have been assigned.',
                'status' => IssueStatus::Published,
                'published_at' => now()->subMonths(2),
                'is_current' => false,
            ]
        );

        $currentIssue = Issue::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'volume_id' => $volumeTwo->id, 'number' => 1],
            [
                'volume_number' => 2,
                'title' => 'Latest sample issue',
                'publication_month_year' => 'June 2026',
                'description' => 'Development sample of the current issue. Each published article has its own public webpage.',
                'status' => IssueStatus::Published,
                'published_at' => now()->subWeek(),
                'is_current' => true,
            ]
        );

        Issue::query()
            ->where('journal_id', $journal->id)
            ->whereKeyNot($currentIssue->id)
            ->update(['is_current' => false]);

        $this->publishSampleArticle(
            $journal,
            $author,
            $previousIssue,
            'Sample article: transparency in editorial workflow',
            'sample-article-transparency-in-editorial-workflow',
            1,
            now()->subMonths(2)
        );

        $this->publishSampleArticle(
            $journal,
            $author,
            $currentIssue,
            'Sample article: keeping unpublished manuscripts private',
            'sample-article-keeping-unpublished-manuscripts-private',
            1,
            now()->subWeek()
        );

        $this->publishSampleArticle(
            $journal,
            $author,
            $currentIssue,
            'Sample article: peer review records and author revisions',
            'sample-article-peer-review-records-and-author-revisions',
            2,
            now()->subDays(5)
        );
    }

    private function seedEditorialBoard(Journal $journal): void
    {
        EditorialBoardMember::syncRoster($journal);
    }

    private function publishSampleArticle(
        Journal $journal,
        User $author,
        Issue $issue,
        string $title,
        string $slug,
        int $sortOrder,
        mixed $publishedAt
    ): void {
        $article = Article::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'slug' => $slug],
            [
                'issue_id' => $issue->id,
                'corresponding_author_id' => $author->id,
                'title' => $title,
                'abstract' => 'This is a development sample article used to demonstrate published issue pages. It is not a claim of completed research, indexing, or a persistent identifier.',
                'keywords' => ['sample', 'editorial-workflow', 'publishing'],
                'language' => 'en',
                'status' => ArticleStatus::Published,
                'doi' => null,
                'page_start' => $sortOrder,
                'page_end' => $sortOrder,
                'submitted_at' => $publishedAt,
                'published_at' => $publishedAt,
                'is_demo' => true,
            ]
        );

        ArticleAuthor::query()->updateOrCreate(
            ['article_id' => $article->id, 'sequence' => 1],
            [
                'user_id' => $author->id,
                'name' => $author->name,
                'email' => $author->email,
                'affiliation' => $author->affiliation,
                'is_corresponding' => true,
            ]
        );

        IssueArticle::query()->updateOrCreate(
            ['issue_id' => $issue->id, 'article_id' => $article->id],
            ['sort_order' => $sortOrder, 'article_number' => (string) $sortOrder]
        );
    }

    /**
     * @return array<string, string>
     */
    private function policies(): array
    {
        return [
            JournalPolicyType::About->value => JournalCopy::ABOUT_TEXT,
            JournalPolicyType::AimsAndScope->value => JournalCopy::AIMS_SCOPE_TEXT,
            JournalPolicyType::AuthorGuidelines->value => JournalCopy::authorGuidelinesBody(),
            JournalPolicyType::PeerReview->value => <<<'TXT'
Manuscripts that pass initial screening may be sent to independent reviewers. Reviewer reports are used by editors to decide whether to request revision, accept, or reject a submission.

Unpublished manuscripts, review reports, and reviewer identities are confidential. Reviewers must not use unpublished material for their own work. The public website lists only articles that have completed the editorial process and been published in an issue.
TXT,
            JournalPolicyType::PublicationEthics->value => <<<'TXT'
Authors, reviewers, and editors are expected to follow recognised publication-ethics practice: honest reporting, appropriate citation, disclosure of competing interests, and respect for confidentiality.

Allegations of misconduct are handled by the editors. The journal does not claim membership of any ethics organisation on this page unless that membership has been separately established and recorded.
TXT,
            JournalPolicyType::Plagiarism->value => JournalCopy::PLAGIARISM_POLICY,
            JournalPolicyType::Copyright->value => <<<'TXT'
Authors will be asked to confirm the copyright and licence terms that apply at acceptance. Until those terms are published for this journal, readers should treat published HTML records on this site as the journal's display copies.

Reuse of published articles should follow the licence stated on the article page. If no licence is listed, contact the editorial office before redistributing the work.
TXT,
            JournalPolicyType::Apc->value => JournalCopy::APC_POLICY,
            JournalPolicyType::ConflictOfInterest->value => JournalCopy::CONFLICT_OF_INTEREST_POLICY,
            JournalPolicyType::CorrectionsAndRetractions->value => JournalCopy::CORRECTIONS_AND_RETRACTIONS_POLICY,
            JournalPolicyType::ComplaintsAndAppeals->value => JournalCopy::COMPLAINTS_AND_APPEALS_POLICY,
            JournalPolicyType::ReviewerGuidelines->value => JournalCopy::REVIEWER_GUIDELINES,
            JournalPolicyType::ReviewProcess->value => JournalCopy::REVIEW_PROCESS,
            JournalPolicyType::SubmissionChecklist->value => JournalCopy::submissionChecklistBody(),
            JournalPolicyType::ManuscriptPreparation->value => JournalCopy::manuscriptPreparationBody(),
        ];
    }
}
