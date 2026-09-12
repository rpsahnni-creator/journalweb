<?php

namespace App\Http\Controllers\Editorial;

use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editorial\AssignIssueArticleRequest;
use App\Http\Requests\Editorial\ReorderIssueArticlesRequest;
use App\Http\Requests\Editorial\StoreIssueRequest;
use App\Http\Requests\Editorial\UpdateIssueArticleRequest;
use App\Http\Requests\Editorial\UpdateIssueRequest;
use App\Models\Article;
use App\Models\Issue;
use App\Models\IssueArticle;
use App\Models\Journal;
use App\Models\Volume;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use App\Support\IssuePublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

class IssueController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Issue::class);

        $journal = $this->journal();

        $issues = Issue::query()
            ->with('volume')
            ->withCount('issueArticles')
            ->where('journal_id', $journal->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('editorial.issues.index', [
            'issues' => $issues,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Issue::class);

        return view('editorial.issues.create', [
            'volumes' => $this->volumes(),
        ]);
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $journal = $this->journal();
        $issue = Issue::query()->create([
            ...$request->safe()->only(['volume_id', 'number', 'title', 'description', 'published_at']),
            'journal_id' => $journal->id,
            'status' => IssueStatus::Draft,
        ]);

        Auditor::log('created', $issue, null, $issue->only(['volume_id', 'number', 'title', 'published_at']), $journal->id);

        return redirect()
            ->route('editorial.issues.show', $issue)
            ->with('status', 'Issue created. Assign accepted articles, then publish when the contents are ready.');
    }

    public function show(Issue $issue): View
    {
        $this->authorize('view', $issue);

        $issue->load(['volume', 'issueArticles.article.authors']);

        $assignable = Article::query()
            ->where('journal_id', $issue->journal_id)
            ->whereIn('status', [
                ArticleStatus::Accepted,
                ArticleStatus::Copyediting,
                ArticleStatus::Scheduled,
            ])
            ->whereDoesntHave('issues')
            ->orderBy('title')
            ->get();

        return view('editorial.issues.show', [
            'issue' => $issue,
            'assignable' => $assignable,
        ]);
    }

    public function edit(Issue $issue): View
    {
        $this->authorize('update', $issue);

        return view('editorial.issues.edit', [
            'issue' => $issue,
            'volumes' => $this->volumes(),
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $old = $issue->only(['volume_id', 'number', 'title', 'description', 'published_at']);
        $issue->update($request->safe()->only(['volume_id', 'number', 'title', 'description', 'published_at']));

        Auditor::log('updated', $issue->fresh(), $old, $issue->only(['volume_id', 'number', 'title', 'description', 'published_at']), $issue->journal_id);

        return redirect()
            ->route('editorial.issues.show', $issue)
            ->with('status', 'Issue details saved.');
    }

    public function destroy(Issue $issue, IssuePublisher $publisher): RedirectResponse
    {
        $this->authorize('delete', $issue);

        $issue->load(['issueArticles.article', 'volume']);

        foreach ($issue->issueArticles as $placement) {
            if ($placement->article) {
                $publisher->remove($issue, $placement->article, request()->user());
            }
        }

        $old = $issue->only(['volume_id', 'number', 'title']);
        $issue->delete();

        Auditor::log('deleted', $issue, $old, null, $issue->journal_id);

        return redirect()
            ->route('editorial.issues.index')
            ->with('status', 'Issue deleted.');
    }

    public function assign(AssignIssueArticleRequest $request, Issue $issue, IssuePublisher $publisher): RedirectResponse
    {
        $article = Article::query()
            ->where('journal_id', $issue->journal_id)
            ->findOrFail($request->integer('article_id'));

        try {
            $publisher->assign(
                $issue,
                $article,
                $request->user(),
                $request->input('article_number'),
                $request->integer('page_start') ?: null,
                $request->integer('page_end') ?: null,
                $request->integer('sort_order') ?: null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        Auditor::log('updated', $issue, null, [
            'assigned_article_id' => $article->id,
        ], $issue->journal_id);

        return back()->with('status', 'Article placed in this issue.');
    }

    public function updatePlacement(UpdateIssueArticleRequest $request, Issue $issue, IssueArticle $placement, IssuePublisher $publisher): RedirectResponse
    {
        abort_unless($placement->issue_id === $issue->id, 404);

        $article = $placement->article()->firstOrFail();

        try {
            $publisher->assign(
                $issue,
                $article,
                $request->user(),
                $request->input('article_number'),
                $request->filled('page_start') ? $request->integer('page_start') : $article->page_start,
                $request->filled('page_end') ? $request->integer('page_end') : $article->page_end,
                $request->integer('sort_order') ?: $placement->sort_order,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $placement->update([
            'article_number' => $request->input('article_number'),
            'sort_order' => $request->integer('sort_order') ?: $placement->sort_order,
        ]);

        return back()->with('status', 'Article placement saved.');
    }

    public function removePlacement(Issue $issue, IssueArticle $placement, IssuePublisher $publisher): RedirectResponse
    {
        $this->authorize('assignArticles', $issue);
        abort_unless($placement->issue_id === $issue->id, 404);

        $article = $placement->article()->firstOrFail();

        try {
            $publisher->remove($issue, $article, request()->user());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('status', 'Article removed from this issue.');
    }

    public function reorder(ReorderIssueArticlesRequest $request, Issue $issue, IssuePublisher $publisher): RedirectResponse
    {
        $publisher->reorder($issue, $request->input('order', []));

        return back()->with('status', 'Article order saved.');
    }

    public function publish(Issue $issue, IssuePublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $issue);

        try {
            $publisher->publish($issue->load('volume'), request()->user());
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        Auditor::log('published', $issue->fresh(), null, [
            'status' => IssueStatus::Published->value,
            'published_at' => optional($issue->fresh()->published_at)?->toDateTimeString(),
        ], $issue->journal_id);

        return back()->with('status', 'Issue published. Each article now has a public webpage.');
    }

    public function unpublish(Issue $issue, IssuePublisher $publisher): RedirectResponse
    {
        $this->authorize('publish', $issue);

        $publisher->unpublish($issue->load(['volume', 'articles']), request()->user());

        Auditor::log('unpublished', $issue->fresh(), [
            'status' => IssueStatus::Published->value,
        ], [
            'status' => IssueStatus::Draft->value,
        ], $issue->journal_id);

        return back()->with('status', 'Issue unpublished. Articles are no longer listed on the public site.');
    }

    private function journal(): Journal
    {
        $journal = CurrentJournal::managed();

        abort_if($journal === null, 404);

        return $journal;
    }

    /**
     * @return Collection<int, Volume>
     */
    private function volumes(): Collection
    {
        return Volume::query()
            ->where('journal_id', $this->journal()->id)
            ->orderByDesc('year')
            ->orderBy('number')
            ->get();
    }
}
