<?php

namespace App\Support;

use App\Enums\JournalPolicyType;

class JournalCopy
{
    public const ABOUT_TEXT = 'SRT Journal of Multidisciplinary Research (SRT-JMR) is a peer-reviewed, refereed academic journal published by Sri Raghunandan Tiwari College (S.R.T. College), Dhamni, Block Meherma, District Godda, Jharkhand, a constituent unit of Sido Kanhu Murmu University, Dumka. The journal serves as a platform for teachers, scholars and researchers from the Santhal Pargana region and beyond, publishing original research across Humanities, Social Sciences and Natural Sciences.';

    public const AIMS_SCOPE_TEXT = 'SRT-JMR aims to promote quality academic research across Humanities, Social Sciences, Natural Sciences, Education and Learning, Society and Culture, and Interdisciplinary Research. The journal particularly encourages original contributions from faculty members and young researchers affiliated with colleges of the Santhal Pargana region, while welcoming submissions from all disciplines and institutions.';

    public const PUBLISHER_NAME = 'Sri Raghunandan Tiwari College (S.R.T. College)';

    public const PUBLISHER_ADDRESS = 'Dhamni, Block – Meherma, District – Godda, Jharkhand – 814140, India. A Constituent Unit of Sido Kanhu Murmu University, Dumka.';

    public const PUBLISHER_EMAIL = 'journal@srtc.ac.in';

    public const PUBLISHER_WEBSITE = 'https://srtc.ac.in';

    public const PUBLICATION_FREQUENCY = 'Biannual — published every June and December.';

    public const APC_POLICY = 'SRT Journal of Multidisciplinary Research does not charge any article submission, processing, or publication fees. All articles are published under Diamond (Platinum) Open Access at no cost to authors or their institutions.';

    public const PLAGIARISM_POLICY = 'SRT Journal of Multidisciplinary Research maintains a strict zero-tolerance policy on plagiarism. All submitted manuscripts are checked for textual similarity before being sent for peer review. Manuscripts found to contain plagiarized content, duplicate publication, or improperly reproduced material from other journals or conference proceedings will be rejected outright. If plagiarism is discovered after publication, the article will be retracted and the authors\' institution will be notified.';

    public const CONFLICT_OF_INTEREST_POLICY = 'All authors, reviewers, and editors of SRT Journal of Multidisciplinary Research are required to disclose any financial, professional, or personal relationships that could be perceived as influencing the objectivity of a submitted manuscript or its review. Authors must declare any conflicts of interest at the time of submission. Reviewers and editors must recuse themselves from evaluating a manuscript when a conflict of interest exists.';

    // TODO: confirm the designated contact or committee for evaluating correction and retraction requests before publishing live.
    public const CORRECTIONS_AND_RETRACTIONS_POLICY = 'SRT Journal of Multidisciplinary Research is committed to maintaining the accuracy of the scholarly record. Minor errors that do not affect the overall interpretation of a published article may be corrected through a formal erratum, published alongside the original article with a note explaining the correction. Articles found to contain plagiarized content, fabricated data, or serious ethical violations after publication will be retracted, with a retraction notice permanently linked to the original article.';

    // TODO: confirm the exact escalation contact and response timeframe for complaints and appeals before publishing live.
    public const COMPLAINTS_AND_APPEALS_POLICY = 'Authors who wish to raise a concern about the editorial process, or appeal a decision on their manuscript, may submit a written complaint to the Editor-in-Chief at journal@srtc.ac.in. Complaints will be acknowledged and reviewed by the editorial team. Where a complaint concerns the Editor-in-Chief directly, it will be escalated to an independent member of the Editorial Board.';

    // TODO: confirm the exact review turnaround period with the editorial office before publishing live.
    public const REVIEWER_GUIDELINES = 'Reviewers are asked to evaluate manuscripts fairly, constructively, and within the requested timeframe. Reviews should assess originality, methodological soundness, clarity, and contribution to the field. Reviewers must treat manuscripts as confidential and must not share, cite, or use unpublished data or ideas from a manuscript under review. Reviewers should declare any conflict of interest and decline the assignment where appropriate. Recommendations should be supported by specific, constructive comments addressed to both the editor and the author.';

    public const REVIEW_PROCESS = 'Every submission undergoes an initial editorial screening for scope and basic quality, followed by double-blind peer review by at least two independent reviewers. Reviewers evaluate the manuscript without knowledge of the author\'s identity, and authors do not know their reviewers\' identities. Based on reviewer recommendations, the editor decides to accept, request revisions, or reject the manuscript. Authors may be asked to revise and resubmit before a final decision is reached. Accepted manuscripts proceed to copyediting and are scheduled for an upcoming issue.';

    public const INDEXING_STATUS = 'SRT Journal of Multidisciplinary Research is a newly established journal and is not yet indexed in any external abstracting or indexing database. The journal is in the process of applying for an ISSN and will pursue indexing with recognized academic databases as it establishes a publication record.';

    public const MANUSCRIPT_TEMPLATE_PATH = 'templates/SRT-Journal-Manuscript-Template.docx';

    public const MANUSCRIPT_TEMPLATE_FILENAME = 'SRT-Journal-Manuscript-Template.docx';

    // TODO: confirm the manuscript word limit with the editorial office before publishing live.
    public const AUTHOR_GUIDELINES_WORD_LIMIT = '3,000–6,000 words';

    // TODO: confirm the similarity-index threshold with the editorial office before publishing live.
    public const AUTHOR_GUIDELINES_SIMILARITY_THRESHOLD = '15%';

    // TODO: confirm the submissions email with the editorial office before publishing live.
    public const AUTHOR_GUIDELINES_EMAIL = 'journal@srtc.ac.in';

    public const PLACEHOLDER_BOARD_NAME = '[Editorial Board Member — Name Pending]';

    public const PLACEHOLDER_BOARD_DESIGNATION = '[Designation Pending]';

    public const PLACEHOLDER_BOARD_INSTITUTION = '[Institution Pending]';

    public const PLACEHOLDER_BOARD_EMAIL = '[pending@srtc.ac.in]';

    /**
     * Editor-supplied public board listings (Editor-in-Chief plus associate members).
     *
     * @return list<array{
     *     name: string,
     *     role_title: string,
     *     department: ?string,
     *     affiliation: string,
     *     official_address: ?string,
     *     email: string,
     *     sort_order: int,
     *     is_public: bool
     * }>
     */
    public static function editorialBoardRoster(): array
    {
        return [
            [
                'name' => 'Dr. Shambhu Kumar Singh',
                'role_title' => 'Editor-in-Chief',
                'department' => null,
                'affiliation' => 'S.R.T. College, Dhamni',
                'official_address' => null,
                'email' => 'editor@srtc.ac.in',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'name' => 'Mr. A.K. Nath',
                'role_title' => 'Assistant Professor',
                'department' => 'Department of Sociology',
                'affiliation' => 'S.R.T. College, Dhamni',
                'official_address' => 'S.R.T. College, Dhamni',
                'email' => 'info@srtc.ac.in',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'name' => 'Mr. A.K.',
                'role_title' => 'Assistant Professor',
                'department' => 'Department of Political Science',
                'affiliation' => 'S.R.T. College, Dhamni',
                'official_address' => 'S.R.T. College, Dhamni',
                'email' => 'info@srtc.ac.in',
                'sort_order' => 3,
                'is_public' => true,
            ],
            [
                'name' => 'Mr. B.K.',
                'role_title' => 'Assistant Professor',
                'department' => 'Department of Sociology',
                'affiliation' => 'S.R.T. College, Dhamni',
                'official_address' => 'S.R.T. College, Dhamni',
                'email' => 'info@srtc.ac.in',
                'sort_order' => 4,
                'is_public' => true,
            ],
            [
                'name' => 'Mr. C.K.',
                'role_title' => 'Assistant Professor',
                'department' => 'Department of Political Science',
                'affiliation' => 'S.R.T. College, Dhamni',
                'official_address' => 'S.R.T. College, Dhamni',
                'email' => 'info@srtc.ac.in',
                'sort_order' => 5,
                'is_public' => true,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function authorGuidelinesItems(): array
    {
        return [
            'Manuscripts must be original, unpublished work not under consideration elsewhere.',
            'Word limit: '.self::AUTHOR_GUIDELINES_WORD_LIMIT.', including references.',
            'Manuscripts must be submitted in MS Word (.docx) format, following APA 7th edition citation style.',
            'All submissions undergo a plagiarism/similarity check; manuscripts exceeding a '.self::AUTHOR_GUIDELINES_SIMILARITY_THRESHOLD.' similarity index will not be considered.',
            'All submissions go through double-blind peer review before acceptance.',
            'Authors must submit a short abstract (150–250 words) and 4–6 keywords along with the full manuscript.',
            'Submit manuscripts to '.self::AUTHOR_GUIDELINES_EMAIL.' or through the submission portal after registering on the site.',
        ];
    }

    public static function authorGuidelinesBody(): string
    {
        return collect(self::authorGuidelinesItems())
            ->values()
            ->map(fn (string $item, int $index): string => ($index + 1).'. '.$item)
            ->implode("\n\n");
    }

    /**
     * @return list<string>
     */
    public static function submissionChecklistItems(): array
    {
        return [
            'Manuscript is original and not under review elsewhere',
            'Word count is within 3,000–6,000 words',
            'Manuscript follows APA 7th edition formatting',
            'Abstract (150–250 words) and 4–6 keywords included',
            'All author details and affiliations are correct',
            'Conflict of interest statement included',
            'Manuscript has been checked for plagiarism/similarity',
        ];
    }

    public static function submissionChecklistBody(): string
    {
        return implode("\n", self::submissionChecklistItems());
    }

    /**
     * @return list<string>
     */
    public static function manuscriptPreparationItems(): array
    {
        return [
            'A4 page size',
            '1-inch margins',
            'Times New Roman 12pt',
            '1.5 line spacing',
            'APA 7th edition citation style',
            'Sequential numbering for tables and figures, each with a caption',
            'Author names must not appear anywhere in the manuscript body (double-blind review)',
        ];
    }

    public static function manuscriptPreparationBody(): string
    {
        return implode("\n", self::manuscriptPreparationItems());
    }

    /**
     * @return list<string>
     */
    public static function listItems(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text) ?: [])
            ->map(fn (mixed $line): string => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    public static function policyFallback(JournalPolicyType $type): ?string
    {
        return match ($type) {
            JournalPolicyType::About => self::ABOUT_TEXT,
            JournalPolicyType::AimsAndScope => self::AIMS_SCOPE_TEXT,
            JournalPolicyType::AuthorGuidelines => self::authorGuidelinesBody(),
            JournalPolicyType::Plagiarism => self::PLAGIARISM_POLICY,
            JournalPolicyType::Apc => self::APC_POLICY,
            JournalPolicyType::ConflictOfInterest => self::CONFLICT_OF_INTEREST_POLICY,
            JournalPolicyType::CorrectionsAndRetractions => self::CORRECTIONS_AND_RETRACTIONS_POLICY,
            JournalPolicyType::ComplaintsAndAppeals => self::COMPLAINTS_AND_APPEALS_POLICY,
            JournalPolicyType::ReviewerGuidelines => self::REVIEWER_GUIDELINES,
            JournalPolicyType::ReviewProcess => self::REVIEW_PROCESS,
            JournalPolicyType::SubmissionChecklist => self::submissionChecklistBody(),
            JournalPolicyType::ManuscriptPreparation => self::manuscriptPreparationBody(),
            default => null,
        };
    }
}
