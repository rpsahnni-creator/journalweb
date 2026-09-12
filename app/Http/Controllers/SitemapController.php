<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Issue;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = $this->staticPages()
            ->concat($this->issuePages())
            ->concat($this->articlePages());

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function staticPages(): Collection
    {
        return collect([
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('about'), 'lastmod' => null],
            ['loc' => route('aims-and-scope'), 'lastmod' => null],
            ['loc' => route('editorial-board'), 'lastmod' => null],
            ['loc' => route('author-guidelines'), 'lastmod' => null],
            ['loc' => route('plagiarism-policy'), 'lastmod' => null],
            ['loc' => route('conflict-of-interest'), 'lastmod' => null],
            ['loc' => route('corrections-and-retractions'), 'lastmod' => null],
            ['loc' => route('complaints-and-appeals'), 'lastmod' => null],
            ['loc' => route('reviewer-guidelines'), 'lastmod' => null],
            ['loc' => route('review-process'), 'lastmod' => null],
            ['loc' => route('submission-checklist'), 'lastmod' => null],
            ['loc' => route('manuscript-preparation'), 'lastmod' => null],
            ['loc' => route('issues.index'), 'lastmod' => null],
        ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function issuePages(): Collection
    {
        return Issue::query()
            ->published()
            ->with('volume')
            ->orderByDesc('published_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Issue $issue): array => [
                'loc' => $issue->publicUrl(),
                'lastmod' => $issue->published_at?->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function articlePages(): Collection
    {
        return Article::query()
            ->published()
            ->where('is_demo', false)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Article $article): array => [
                'loc' => $article->publicUrl(),
                'lastmod' => $article->published_at?->toDateString(),
            ]);
    }
}
