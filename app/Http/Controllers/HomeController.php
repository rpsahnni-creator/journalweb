<?php

namespace App\Http\Controllers;

use App\Enums\JournalPolicyType;
use App\Support\CurrentJournal;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $journal = CurrentJournal::get();

        $currentIssue = $journal?->currentIssue()
            ?->with('volume')
            ->first();

        $currentArticles = $currentIssue
            ? $currentIssue->listedArticlesQuery()->with(['authors', 'issue.volume', 'issues.volume'])->get()
            : collect();

        $showCallForPapers = $currentIssue === null || $currentArticles->count() < 5;
        $publishedArticles = $journal?->publishedArticles();

        return view('home', [
            'journal' => $journal,
            'about' => $journal?->publishedPolicy(JournalPolicyType::About),
            'aboutCardText' => $this->cardText($journal?->setting('about_text'), 'Purpose, audience, and how this site presents published work.'),
            'aimsCardText' => $this->cardText($journal?->setting('aims_scope_text'), 'Subject coverage and the kinds of submissions considered.'),
            'currentIssue' => $currentIssue,
            'currentArticles' => $currentArticles,
            'recentArticles' => $currentArticles->take(6),
            'featuredArticle' => $journal
                ? $journal->publishedArticles()
                    ->publishedNonDemo()
                    ->with('authors')
                    ->orderByDesc('view_count')
                    ->orderByDesc('published_at')
                    ->first()
                : null,
            'showCallForPapers' => $showCallForPapers,
            'boardCount' => $journal?->editorialBoardMembers()->public()->count() ?? 0,
            'stats' => [
                'articles' => $publishedArticles?->count() ?? 0,
                'issues' => $journal?->publishedIssues()->count() ?? 0,
                'downloads' => (int) ($publishedArticles?->sum('download_count') ?? 0),
                'board' => $journal?->editorialBoardMembers()->public()->count() ?? 0,
            ],
        ]);
    }

    private function cardText(mixed $value, string $fallback): string
    {
        $text = is_string($value) ? trim($value) : '';

        return $text !== '' ? Str::limit($text, 280) : $fallback;
    }
}
