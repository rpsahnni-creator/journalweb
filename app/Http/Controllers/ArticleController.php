<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleView;
use App\Support\CurrentJournal;
use App\Support\ManuscriptFileStore;
use App\Support\PublicationFileStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $journal = CurrentJournal::get();
        $search = trim((string) $request->query('q', ''));
        $year = $request->query('year');
        $type = $request->query('type');
        $volume = $request->query('volume');
        $sortBy = $request->query('sort', 'newest');

        $articles = $journal
            ? $journal->publishedArticles()
                ->with(['authors', 'issues.volume'])
                ->when($search !== '', fn ($query) => $query->search($search))
                ->when($year, fn ($query) => $query->whereYear('published_at', (int) $year))
                ->when($type, fn ($query) => $query->where('article_type', $type))
                ->when($volume, fn ($query) => $query->whereHas('issues.volume', fn ($volumes) => $volumes->where('number', (int) $volume)))
                ->when($sortBy === 'oldest', fn ($query) => $query->reorder('published_at', 'asc'))
                ->when($sortBy === 'most_viewed', fn ($query) => $query->reorder('view_count', 'desc'))
                ->when($sortBy === 'most_downloaded', fn ($query) => $query->reorder('download_count', 'desc'))
                ->paginate(10)
                ->withQueryString()
            : null;

        $availableYears = $journal
            ? $journal->publishedArticles()
                ->whereNotNull('published_at')
                ->get(['published_at'])
                ->map(fn (Article $article): ?int => $article->published_at?->year)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
            : collect();

        $availableVolumes = $journal
            ? $journal->publishedIssues()->with('volume')->get()
                ->map(fn ($issue) => $issue->volume?->number)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
            : collect();

        return view('articles.index', [
            'journal' => $journal,
            'articles' => $articles,
            'search' => $search,
            'selectedYear' => $year,
            'selectedType' => $type,
            'selectedVolume' => $volume,
            'sortBy' => $sortBy,
            'availableYears' => $availableYears,
            'availableVolumes' => $availableVolumes,
            'articleTypes' => \App\Enums\ArticleType::cases(),
            'title' => $search !== '' ? 'Search articles' : 'Articles',
            'metaDescription' => 'Search published articles by title, abstract, keywords, or author. Unpublished manuscripts are not listed.',
        ]);
    }

    public function show(Request $request, string $article): View
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $record = Article::query()
            ->publiclyListed()
            ->where('journal_id', $journal->id)
            ->where('slug', $article)
            ->with(['authors', 'issue.volume', 'issues.volume', 'files' => fn ($query) => $query->public()])
            ->firstOrFail();

        $this->authorize('view', $record);

        // Track the page view.
        ArticleView::record(
            $record,
            'page_view',
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
        );

        // Find related articles based on shared keywords.
        $relatedArticles = collect();
        if (($record->keywords ?? []) !== []) {
            $relatedArticles = Article::query()
                ->publiclyListed()
                ->where('journal_id', $journal->id)
                ->where('id', '!=', $record->id)
                ->where(function ($query) use ($record): void {
                    foreach ($record->keywords as $keyword) {
                        $query->orWhereJsonContains('keywords', $keyword);
                    }
                })
                ->with('authors')
                ->limit(4)
                ->get();
        }

        return view('articles.show', [
            'journal' => $journal,
            'article' => $record,
            'issue' => $record->publishedIssue(),
            'pdf' => $record->publicPdfFile(),
            'relatedArticles' => $relatedArticles,
            'title' => $record->title,
            'metaDescription' => str($record->abstract ?: $record->title)->limit(160)->toString(),
        ]);
    }

    public function pdf(Request $request, string $article, ManuscriptFileStore $files, PublicationFileStore $publications): StreamedResponse
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        $record = Article::query()
            ->publiclyListed()
            ->where('journal_id', $journal->id)
            ->where('slug', $article)
            ->with('files')
            ->firstOrFail();

        // Track the PDF download.
        ArticleView::record(
            $record,
            'pdf_download',
            $request->ip(),
            $request->userAgent(),
            $request->header('referer'),
        );

        if (filled($record->pdf_path)) {
            return $publications->download($record);
        }

        $file = $record->publicPdfFile();

        abort_if($file === null, 404);

        return $files->stream($file);
    }
}

