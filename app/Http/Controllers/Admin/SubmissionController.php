<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublishSubmissionRequest;
use App\Http\Requests\Admin\UpdateSubmissionRequest;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Notifications\SubmissionDecisionMade;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use App\Support\PublicationFileStore;
use App\Support\SubmissionFileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Submission::class);

        $status = $request->string('status')->toString();

        $submissions = Submission::query()
            ->with('author')
            ->when($status !== '', function ($query) use ($status): void {
                $query->where('status', $status);
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.submissions.index', [
            'submissions' => $submissions,
            'statuses' => SubmissionStatus::options(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Submission $submission): View
    {
        $this->authorize('view', $submission);

        $submission->load(['author', 'article.issue', 'reviews.reviewer', 'versions']);

        $assignedIds = $submission->reviews
            ->where('round', $submission->review_round)
            ->pluck('reviewer_id');

        $availableReviewers = User::query()
            ->reviewerAccounts()
            ->where('id', '!=', $submission->user_id)
            ->when($assignedIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $assignedIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'affiliation']);

        $journal = CurrentJournal::managed();

        $issues = Issue::query()
            ->with('volume')
            ->when($journal, fn ($query) => $query->where('journal_id', $journal->id))
            ->orderByDesc('is_current')
            ->orderByDesc('volume_number')
            ->orderByDesc('number')
            ->get();

        return view('admin.submissions.show', [
            'submission' => $submission,
            'statuses' => SubmissionStatus::options(),
            'availableReviewers' => $availableReviewers,
            'reviewsComplete' => $submission->assignedReviewsAreComplete(),
            'issues' => $issues,
        ]);
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $old = [
            'status' => $submission->status->value,
            'editor_notes' => $submission->editor_notes,
        ];

        $previous = $submission->status;
        $decision = $request->enum('status', SubmissionStatus::class);

        $submission->update([
            'status' => $decision,
            'editor_notes' => $request->input('editor_notes'),
        ]);

        Auditor::log('updated', $submission, $old, [
            'status' => $submission->status->value,
            'editor_notes' => $submission->editor_notes,
        ]);

        if (
            $previous !== $decision
            && in_array($decision, [
                SubmissionStatus::RevisionRequested,
                SubmissionStatus::Accepted,
                SubmissionStatus::Rejected,
            ], true)
            && $submission->author
        ) {
            $submission->author->notify(new SubmissionDecisionMade(
                $submission,
                $decision,
                $submission->compiledCommentsToAuthor()
            ));
        }

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'Submission updated.');
    }

    public function download(Submission $submission, SubmissionFileStore $files): StreamedResponse
    {
        $this->authorize('download', $submission);

        return $files->download($submission);
    }

    public function downloadVersion(Submission $submission, SubmissionVersion $version, SubmissionFileStore $files): StreamedResponse
    {
        $this->authorize('downloadVersion', $submission);

        return $files->downloadVersion($submission, $version);
    }

    public function convert(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorize('convert', $submission);

        if (! $submission->canConvertToArticle()) {
            return back()->with('error', 'Only accepted submissions that have not already been converted can become articles.');
        }

        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return back()->with('error', 'A journal record must exist before a submission can be converted to an article.');
        }

        $actor = $request->user();

        $article = DB::transaction(function () use ($submission, $journal, $actor): Article {
            $author = $submission->author;
            $keywords = Article::parseKeywords($submission->keywords);

            $article = Article::query()->create([
                'journal_id' => $journal->id,
                'corresponding_author_id' => $submission->user_id,
                'title' => $submission->title,
                'abstract' => $submission->abstract,
                'keywords' => $keywords,
                'article_type' => ArticleType::ResearchArticle,
                'language' => 'en',
                'status' => ArticleStatus::Accepted,
                'is_demo' => false,
                'submitted_at' => $submission->submitted_at ?? now(),
            ]);

            $article->authors()->create([
                'user_id' => $author?->id,
                'name' => $author?->name ?: 'Corresponding author',
                'email' => $author?->email,
                'affiliation' => $author?->affiliation,
                'orcid' => $author?->orcid,
                'sequence' => 1,
                'is_corresponding' => true,
            ]);

            $article->recordStatusChange(null, ArticleStatus::Accepted, $actor, 'Converted from portal submission. Attach the formatted file before publishing.');

            $submission->update(['article_id' => $article->id]);

            return $article;
        });

        Auditor::log('converted', $submission, null, [
            'article_id' => $article->id,
            'title' => $article->title,
        ]);

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'Article record created. Attach the final formatted file and assign it to an issue before it appears in the Current Issue.');
    }

    public function publish(
        PublishSubmissionRequest $request,
        Submission $submission,
        PublicationFileStore $files,
    ): RedirectResponse {
        $this->authorize('publish', $submission);

        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return back()->with('error', 'A journal record must exist before a submission can be published.');
        }

        $issue = $request->issue();
        $actor = $request->user();

        $article = DB::transaction(function () use ($submission, $journal, $issue, $request, $files, $actor): Article {
            $author = $submission->author;
            $keywords = Article::parseKeywords($submission->keywords);
            $article = $submission->article;

            $payload = [
                'journal_id' => $journal->id,
                'issue_id' => $issue->id,
                'corresponding_author_id' => $submission->user_id,
                'title' => $submission->title,
                'abstract' => $submission->abstract,
                'keywords' => $keywords,
                'article_type' => ArticleType::ResearchArticle,
                'language' => 'en',
                'status' => ArticleStatus::Published,
                'is_demo' => false,
                'page_start' => $request->input('page_start'),
                'page_end' => $request->input('page_end'),
                'submitted_at' => $submission->submitted_at ?? now(),
                'published_at' => now(),
            ];

            if ($article === null) {
                $article = Article::query()->create($payload);
                $article->recordStatusChange(null, ArticleStatus::Published, $actor, 'Published from portal submission.');
            } else {
                $from = $article->status;
                $article->update($payload);
                $article->recordStatusChange($from, ArticleStatus::Published, $actor, 'Published from portal submission.');
            }

            $article->authors()->delete();
            $article->authors()->create([
                'user_id' => $author?->id,
                'name' => $author?->name ?: 'Corresponding author',
                'email' => $author?->email,
                'affiliation' => $author?->affiliation,
                'orcid' => $author?->orcid,
                'sequence' => 1,
                'is_corresponding' => true,
            ]);

            $this->syncCoAuthors($article, $submission->co_authors);

            $nextOrder = (int) $issue->issueArticles()->max('sort_order') + 1;
            $issue->articles()->syncWithoutDetaching([
                $article->id => [
                    'sort_order' => $nextOrder,
                    'article_number' => (string) $nextOrder,
                ],
            ]);

            $upload = $request->file('pdf');

            if ($upload !== null) {
                $path = $files->store($article, $upload);
                $article->update([
                    'pdf_path' => $path,
                    'pdf_original_filename' => basename(str_replace('\\', '/', (string) $upload->getClientOriginalName())),
                ]);
            }

            $submission->update([
                'article_id' => $article->id,
                'status' => SubmissionStatus::Published,
            ]);

            $article = $article->fresh() ?? $article;
            $article->mintDoi();

            return $article;
        });

        Auditor::log('published', $submission, null, [
            'article_id' => $article->id,
            'issue_id' => $issue->id,
            'title' => $article->title,
        ]);

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'The article is now published in the selected issue.');
    }

    private function syncCoAuthors(Article $article, ?string $coAuthors): void
    {
        if ($coAuthors === null || trim($coAuthors) === '') {
            return;
        }

        $lines = preg_split('/\r\n|\r|\n/', $coAuthors) ?: [];
        $sequence = 2;

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $name = $line;
            $affiliation = null;

            if (str_contains($line, ',')) {
                [$name, $affiliation] = array_map(trim(...), explode(',', $line, 2));
            }

            if ($name === '') {
                continue;
            }

            $article->authors()->create([
                'name' => $name,
                'affiliation' => $affiliation,
                'sequence' => $sequence,
                'is_corresponding' => false,
            ]);

            $sequence++;
        }
    }
}
