<?php

namespace App\Http\Controllers;

use App\Enums\JournalPolicyType;
use App\Models\Journal;
use App\Models\JournalPolicy;
use App\Support\CurrentJournal;
use App\Support\JournalCopy;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyPageController extends Controller
{
    public function about(): View
    {
        $journal = CurrentJournal::get();
        $policy = $journal?->publishedPolicy(JournalPolicyType::About);
        $type = JournalPolicyType::About;

        return view('pages.about', [
            'journal' => $journal,
            'type' => $type,
            'policy' => $policy,
            'title' => $policy?->title ?? $type->title(),
            'metaDescription' => $type->metaDescription(),
            'eyebrow' => 'About',
            'publicationFrequency' => $this->setting($journal, 'publication_frequency', JournalCopy::PUBLICATION_FREQUENCY),
            'indexingStatus' => $this->setting($journal, 'indexing_status', JournalCopy::INDEXING_STATUS),
            'publisherName' => $this->setting($journal, 'publisher_name', $journal?->publisher ?: JournalCopy::PUBLISHER_NAME),
        ]);
    }

    public function aimsAndScope(): View
    {
        return $this->page(JournalPolicyType::AimsAndScope);
    }

    public function authorGuidelines(): View
    {
        $journal = CurrentJournal::get();
        $policy = $journal?->publishedPolicy(JournalPolicyType::AuthorGuidelines);

        return view('pages.author-guidelines', [
            'journal' => $journal,
            'title' => $policy?->title ?? JournalPolicyType::AuthorGuidelines->title(),
            'metaDescription' => JournalPolicyType::AuthorGuidelines->metaDescription(),
            'wordLimit' => JournalCopy::AUTHOR_GUIDELINES_WORD_LIMIT,
            'similarityThreshold' => JournalCopy::AUTHOR_GUIDELINES_SIMILARITY_THRESHOLD,
            'submissionsEmail' => JournalCopy::AUTHOR_GUIDELINES_EMAIL,
        ]);
    }

    public function peerReview(): View
    {
        return $this->page(JournalPolicyType::PeerReview);
    }

    public function publicationEthics(): View
    {
        return $this->page(JournalPolicyType::PublicationEthics);
    }

    public function plagiarism(): View
    {
        $journal = CurrentJournal::get();
        $policy = $journal?->publishedPolicy(JournalPolicyType::Plagiarism);

        return view('pages.plagiarism-policy', [
            'journal' => $journal,
            'policy' => $policy,
            'body' => $policy?->body ?: JournalCopy::PLAGIARISM_POLICY,
            'title' => $policy?->title ?? JournalPolicyType::Plagiarism->title(),
            'metaDescription' => JournalPolicyType::Plagiarism->metaDescription(),
        ]);
    }

    public function copyright(): View
    {
        return $this->page(JournalPolicyType::Copyright);
    }

    public function apc(): View
    {
        return $this->page(JournalPolicyType::Apc);
    }

    public function conflictOfInterest(): View
    {
        return $this->page(JournalPolicyType::ConflictOfInterest);
    }

    public function correctionsAndRetractions(): View
    {
        return $this->page(JournalPolicyType::CorrectionsAndRetractions);
    }

    public function complaintsAndAppeals(): View
    {
        return $this->page(JournalPolicyType::ComplaintsAndAppeals);
    }

    public function reviewerGuidelines(): View
    {
        return $this->page(JournalPolicyType::ReviewerGuidelines);
    }

    public function reviewProcess(): View
    {
        return $this->page(JournalPolicyType::ReviewProcess);
    }

    public function submissionChecklist(): View
    {
        $type = JournalPolicyType::SubmissionChecklist;
        $journal = CurrentJournal::get();
        $policy = $this->resolvedPolicy($journal, $type);
        $items = JournalCopy::listItems($policy?->body ?: JournalCopy::submissionChecklistBody());

        return view('pages.submission-checklist', [
            'journal' => $journal,
            'type' => $type,
            'policy' => $policy,
            'items' => $items,
            'title' => $policy?->title ?? $type->title(),
            'metaDescription' => $type->metaDescription(),
            'eyebrow' => 'For authors',
        ]);
    }

    public function manuscriptPreparation(): View
    {
        $type = JournalPolicyType::ManuscriptPreparation;
        $journal = CurrentJournal::get();
        $policy = $this->resolvedPolicy($journal, $type);
        $items = JournalCopy::listItems($policy?->body ?: JournalCopy::manuscriptPreparationBody());

        return view('pages.manuscript-preparation', [
            'journal' => $journal,
            'type' => $type,
            'policy' => $policy,
            'items' => $items,
            'title' => $policy?->title ?? $type->title(),
            'metaDescription' => $type->metaDescription(),
            'eyebrow' => 'For authors',
        ]);
    }

    public function downloadManuscriptTemplate(): StreamedResponse
    {
        $disk = Storage::disk('public');
        $path = JournalCopy::MANUSCRIPT_TEMPLATE_PATH;

        abort_unless($disk->exists($path), 404);

        return $disk->download($path, JournalCopy::MANUSCRIPT_TEMPLATE_FILENAME);
    }

    public function publisher(): View
    {
        $journal = CurrentJournal::get();

        return view('pages.publisher', [
            'journal' => $journal,
            'title' => 'Publisher Information',
            'metaDescription' => 'Publisher information for '.$this->setting($journal, 'publisher_name', JournalCopy::PUBLISHER_NAME).'.',
            'publisherName' => $this->setting($journal, 'publisher_name', $journal?->publisher ?: JournalCopy::PUBLISHER_NAME),
            'publisherAddress' => $this->setting($journal, 'publisher_address', JournalCopy::PUBLISHER_ADDRESS),
            'publisherEmail' => $this->setting($journal, 'publisher_email', JournalCopy::PUBLISHER_EMAIL),
            'publisherWebsite' => $this->setting($journal, 'publisher_website', JournalCopy::PUBLISHER_WEBSITE),
            'eyebrow' => 'Publisher',
        ]);
    }

    private function page(JournalPolicyType $type): View
    {
        $journal = CurrentJournal::get();
        $policy = $this->resolvedPolicy($journal, $type);

        $view = $type === JournalPolicyType::About ? 'pages.about' : 'pages.policy';

        return view($view, [
            'journal' => $journal,
            'type' => $type,
            'policy' => $policy,
            'title' => $policy?->title ?? $type->title(),
            'metaDescription' => $type->metaDescription(),
            'eyebrow' => $type === JournalPolicyType::About ? 'About' : 'Journal policy',
            'relatedLinks' => $this->relatedLinks($type),
        ]);
    }

    private function resolvedPolicy(?Journal $journal, JournalPolicyType $type): ?JournalPolicy
    {
        $policy = $journal?->publishedPolicy($type);
        $fallback = JournalCopy::policyFallback($type);

        if ($policy || $fallback === null) {
            return $policy;
        }

        return new JournalPolicy([
            'title' => $type->title(),
            'body' => $fallback,
            'is_published' => true,
        ]);
    }

    /**
     * @return list<array{href: string, label: string}>
     */
    private function relatedLinks(JournalPolicyType $type): array
    {
        return match ($type) {
            JournalPolicyType::PublicationEthics => [
                ['href' => route('conflict-of-interest'), 'label' => 'Conflict of Interest Policy'],
            ],
            JournalPolicyType::PeerReview => [
                ['href' => route('review-process'), 'label' => 'Review Process'],
                ['href' => route('reviewer-guidelines'), 'label' => 'Reviewer Guidelines'],
            ],
            default => [],
        };
    }

    private function setting(?Journal $journal, string $key, ?string $fallback = null): ?string
    {
        $value = $journal?->setting($key);

        return filled($value) ? (string) $value : $fallback;
    }
}
