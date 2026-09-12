<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDirectArticleRequest;
use App\Models\Article;
use App\Models\Issue;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use App\Support\PublicationFileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function create(): View
    {
        $this->authorize('publishDirectly', Article::class);

        $journal = CurrentJournal::managed();

        $issues = Issue::query()
            ->with('volume')
            ->when($journal, fn ($query) => $query->where('journal_id', $journal->id))
            ->orderByDesc('is_current')
            ->orderByDesc('volume_number')
            ->orderByDesc('number')
            ->get();

        $currentIssueId = $issues->firstWhere('is_current', true)?->id
            ?? $issues->first()?->id;

        return view('admin.articles.create', [
            'issues' => $issues,
            'currentIssueId' => $currentIssueId,
        ]);
    }

    public function store(StoreDirectArticleRequest $request, PublicationFileStore $files): RedirectResponse
    {
        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return back()->with('error', 'A journal record must exist before an article can be published.');
        }

        $issue = Issue::query()
            ->where('journal_id', $journal->id)
            ->findOrFail($request->integer('issue_id'));

        $editor = $request->user();

        $article = DB::transaction(function () use ($request, $files, $journal, $issue, $editor): Article {
            $article = Article::query()->create([
                'journal_id' => $journal->id,
                'issue_id' => $issue->id,
                'corresponding_author_id' => $editor->id,
                'editor_id' => $editor->id,
                'title' => $request->string('title')->toString(),
                'abstract' => $request->string('abstract')->toString(),
                'keywords' => $request->keywordList(),
                'article_type' => ArticleType::ResearchArticle,
                'language' => 'en',
                'status' => ArticleStatus::Published,
                'is_demo' => false,
                'page_start' => $request->input('page_start'),
                'page_end' => $request->input('page_end'),
                'submitted_at' => now(),
                'published_at' => now(),
                'confirmed_at' => now(),
            ]);

            foreach ($request->authorNames() as $index => $name) {
                $article->authors()->create([
                    'name' => $name,
                    'sequence' => $index + 1,
                    'is_corresponding' => $index === 0,
                ]);
            }

            $nextOrder = (int) $issue->issueArticles()->max('sort_order') + 1;
            $issue->articles()->syncWithoutDetaching([
                $article->id => [
                    'sort_order' => $nextOrder,
                    'article_number' => (string) $nextOrder,
                ],
            ]);

            $upload = $request->file('pdf');
            $path = $files->store($article, $upload);

            $article->update([
                'pdf_path' => $path,
                'pdf_original_filename' => basename(str_replace('\\', '/', (string) $upload->getClientOriginalName())),
            ]);

            $article->recordStatusChange(null, ArticleStatus::Published, $editor, 'Published directly to '.$issue->displayLabel().' by the editorial office.');

            $article = $article->fresh() ?? $article;
            $article->mintDoi();

            return $article;
        });

        Auditor::log('published-directly', $article, null, [
            'issue_id' => $issue->id,
            'title' => $article->title,
            'editor_id' => $editor->id,
            'confirmed_at' => $article->confirmed_at?->toIso8601String(),
        ], $journal->id);

        return redirect()
            ->route('admin.issues.index')
            ->with('status', 'The article has been published in '.$issue->displayLabel().'.');
    }
}
