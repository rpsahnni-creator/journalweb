<?php

namespace App\Http\Controllers;

use App\Support\CurrentJournal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    /**
     * Atom feed of the latest published articles.
     */
    public function articles(): Response
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $articles = $journal->publishedArticles()
            ->with(['authors', 'issues.volume'])
            ->latest('published_at')
            ->limit(25)
            ->get();

        $lastUpdated = $articles->first()?->published_at ?? now();

        return response()
            ->view('feed.articles', [
                'journal' => $journal,
                'articles' => $articles,
                'lastUpdated' => $lastUpdated,
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Atom feed of published issues.
     */
    public function issues(): Response
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $issues = $journal->issues()
            ->published()
            ->with('volume')
            ->latest('published_at')
            ->limit(20)
            ->get();

        $lastUpdated = $issues->first()?->published_at ?? now();

        return response()
            ->view('feed.issues', [
                'journal' => $journal,
                'issues' => $issues,
                'lastUpdated' => $lastUpdated,
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function issue(int $volume, int $issue): Response
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $record = $journal->issues()
            ->published()
            ->where('number', $issue)
            ->whereHas('volume', fn ($query) => $query->where('number', $volume))
            ->with(['volume', 'articles' => fn ($query) => $query->publiclyListed()->with('authors')])
            ->firstOrFail();

        return response()
            ->view('feed.issue', [
                'journal' => $journal,
                'issue' => $record,
                'articles' => $record->articles,
                'lastUpdated' => $record->published_at ?? now(),
            ])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
