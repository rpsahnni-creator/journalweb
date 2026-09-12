<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Support\CurrentJournal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function current(): View
    {
        $journal = CurrentJournal::get();
        $issue = $journal?->currentIssue()
            ?->with(['volume', 'articles' => fn ($query) => $query->publiclyListed()->with('authors')])
            ->first();

        $showCallForPapers = $issue === null || $issue->publishedNonDemoArticleCount() < 5;

        return view('issues.show', [
            'journal' => $journal,
            'issue' => $issue,
            'isCurrent' => true,
            'showCallForPapers' => $showCallForPapers,
            'title' => $issue ? 'Current Issue: '.$issue->displayLabel() : 'Current Issue',
            'metaDescription' => 'The most recently published issue. Unpublished manuscripts are not listed.',
        ]);
    }

    public function index(Request $request): View
    {
        $journal = CurrentJournal::get();
        $search = trim((string) $request->query('q', ''));

        $issues = $journal
            ? $journal->issues()
                ->archived()
                ->with('volume')
                ->withCount(['articles as article_count' => fn ($query) => $query->published()->where('articles.is_demo', false)])
                ->orderByDesc('is_current')
                ->orderByDesc('volume_number')
                ->orderByDesc('number')
                ->paginate(12)
                ->withQueryString()
            : null;

        $articles = null;
        if ($journal && $search !== '') {
            $articles = $journal->publishedArticles()
                ->with(['authors', 'issues.volume'])
                ->search($search)
                ->paginate(10)
                ->withQueryString();
        }

        return view('issues.index', [
            'journal' => $journal,
            'issues' => $issues,
            'articles' => $articles,
            'search' => $search,
            'title' => $search !== '' ? 'Search archive' : 'Previous Issues',
            'metaDescription' => 'Browse published volumes and issues. Search covers published articles only.',
        ]);
    }

    public function show(int $volume, int $issue): View
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $record = Issue::query()
            ->published()
            ->where('journal_id', $journal->id)
            ->where('number', $issue)
            ->whereHas('volume', fn ($query) => $query->where('number', $volume))
            ->with(['volume', 'articles' => fn ($query) => $query->publiclyListed()->with('authors')])
            ->firstOrFail();

        return view('issues.show', [
            'journal' => $journal,
            'issue' => $record,
            'isCurrent' => false,
            'showCallForPapers' => false,
            'title' => $record->displayLabel(),
            'metaDescription' => 'Published articles in '.$record->displayLabel().'.',
        ]);
    }
}
