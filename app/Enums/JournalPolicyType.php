<?php

namespace App\Enums;

enum JournalPolicyType: string
{
    case About = 'about';
    case AimsAndScope = 'aims_and_scope';
    case AuthorGuidelines = 'author_guidelines';
    case PeerReview = 'peer_review';
    case PublicationEthics = 'publication_ethics';
    case Plagiarism = 'plagiarism';
    case Copyright = 'copyright';
    case Apc = 'apc';
    case ConflictOfInterest = 'conflict_of_interest';
    case CorrectionsAndRetractions = 'corrections_and_retractions';
    case ComplaintsAndAppeals = 'complaints_and_appeals';
    case ReviewerGuidelines = 'reviewer_guidelines';
    case ReviewProcess = 'review_process';
    case SubmissionChecklist = 'submission_checklist';
    case ManuscriptPreparation = 'manuscript_preparation';

    public function title(): string
    {
        return match ($this) {
            self::About => 'About the Journal',
            self::AimsAndScope => 'Aims and Scope',
            self::AuthorGuidelines => 'Author Guidelines',
            self::PeerReview => 'Peer Review Policy',
            self::PublicationEthics => 'Publication Ethics',
            self::Plagiarism => 'Plagiarism Policy',
            self::Copyright => 'Copyright and License',
            self::Apc => 'Article Processing Charges (APC)',
            self::ConflictOfInterest => 'Conflict of Interest Policy',
            self::CorrectionsAndRetractions => 'Corrections and Retractions',
            self::ComplaintsAndAppeals => 'Complaints and Appeals',
            self::ReviewerGuidelines => 'Reviewer Guidelines',
            self::ReviewProcess => 'Review Process',
            self::SubmissionChecklist => 'Submission Checklist',
            self::ManuscriptPreparation => 'Manuscript Preparation',
        };
    }

    public function metaDescription(): string
    {
        return match ($this) {
            self::About => 'Learn about the journal, its purpose, and how published content is presented.',
            self::AimsAndScope => 'Read the journal aims, subject coverage, and the kinds of work considered for publication.',
            self::AuthorGuidelines => 'Manuscript preparation, submission, and revision requirements for authors.',
            self::PeerReview => 'How peer review is conducted, including confidentiality of unpublished manuscripts.',
            self::PublicationEthics => 'Ethical standards for authors, reviewers, and editors.',
            self::Plagiarism => 'How similarity, overlapping publication, and citation issues are handled.',
            self::Copyright => 'Copyright, licensing, and reuse terms for published articles.',
            self::Apc => 'Article processing charges, submission fees, and Diamond Open Access policy.',
            self::ConflictOfInterest => 'Disclosure requirements for authors, reviewers, and editors.',
            self::CorrectionsAndRetractions => 'How errata and retractions are handled after publication.',
            self::ComplaintsAndAppeals => 'How authors may raise concerns or appeal an editorial decision.',
            self::ReviewerGuidelines => 'Expectations for fair, confidential, and constructive peer review.',
            self::ReviewProcess => 'How submissions are screened, reviewed, and decided.',
            self::SubmissionChecklist => 'Items authors should confirm before submitting a manuscript.',
            self::ManuscriptPreparation => 'Formatting requirements and the downloadable manuscript template.',
        };
    }

    public function routeName(): string
    {
        return match ($this) {
            self::About => 'about',
            self::AimsAndScope => 'aims-and-scope',
            self::AuthorGuidelines => 'author-guidelines',
            self::PeerReview => 'peer-review-policy',
            self::PublicationEthics => 'publication-ethics',
            self::Plagiarism => 'plagiarism-policy',
            self::Copyright => 'copyright-and-license',
            self::Apc => 'article-processing-charges',
            self::ConflictOfInterest => 'conflict-of-interest',
            self::CorrectionsAndRetractions => 'corrections-and-retractions',
            self::ComplaintsAndAppeals => 'complaints-and-appeals',
            self::ReviewerGuidelines => 'reviewer-guidelines',
            self::ReviewProcess => 'review-process',
            self::SubmissionChecklist => 'submission-checklist',
            self::ManuscriptPreparation => 'manuscript-preparation',
        };
    }
}
