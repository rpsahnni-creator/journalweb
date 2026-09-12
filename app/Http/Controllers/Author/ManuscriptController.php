<?php

namespace App\Http\Controllers\Author;

use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Author\StoreManuscriptRequest;
use App\Http\Requests\Author\SubmitManuscriptRequest;
use App\Http\Requests\Author\UpdateManuscriptRequest;
use App\Models\Article;
use App\Notifications\NewSubmissionNotification;
use App\Notifications\RevisionSubmittedNotification;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManuscriptController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('author.manuscripts.create', [
            'types' => ArticleType::cases(),
        ]);
    }

    public function store(StoreManuscriptRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return back()->with('error', 'A journal record must exist before manuscripts can be submitted.');
        }

        $user = $request->user();
        $article = Article::query()->create([
            'journal_id' => $journal->id,
            'corresponding_author_id' => $user->id,
            'title' => $request->string('title')->toString(),
            'article_type' => $request->string('article_type')->toString(),
            'abstract' => $request->input('abstract'),
            'keywords' => Article::parseKeywords($request->input('keywords')),
            'language' => $request->input('language') ?: 'en',
            'status' => ArticleStatus::Draft,
        ]);

        $article->authors()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'affiliation' => $user->affiliation,
            'orcid' => $user->orcid,
            'sequence' => 1,
            'is_corresponding' => true,
        ]);

        $article->recordStatusChange(null, ArticleStatus::Draft, $user, 'Draft created.');
        Auditor::log('created', $article, null, $article->only(['title', 'submission_number', 'status']), $journal->id);

        return redirect()
            ->route('author.manuscripts.edit', $article)
            ->with('status', 'Draft manuscript created. Add authors, files, and declarations before submitting.');
    }

    public function show(Article $manuscript): View
    {
        $this->authorize('viewAsAuthor', $manuscript);

        $manuscript->load([
            'authors',
            'files.uploader',
            'files.revision',
            'statusEvents.user',
            'revisions.files',
            'editorialDecisions',
            'reviews',
        ]);

        return view('author.manuscripts.show', [
            'manuscript' => $manuscript,
            'decision' => $manuscript->latestAuthorFacingDecision(),
        ]);
    }

    public function edit(Article $manuscript): View
    {
        $this->authorize('updateAsAuthor', $manuscript);

        $manuscript->load(['authors', 'files']);

        return view('author.manuscripts.edit', [
            'manuscript' => $manuscript,
            'types' => ArticleType::cases(),
        ]);
    }

    public function update(UpdateManuscriptRequest $request, Article $manuscript): RedirectResponse
    {
        $old = $manuscript->only(['title', 'abstract', 'article_type']);

        DB::transaction(function () use ($request, $manuscript): void {
            $manuscript->update([
                'title' => $request->string('title')->toString(),
                'article_type' => $request->string('article_type')->toString(),
                'abstract' => $request->input('abstract'),
                'keywords' => Article::parseKeywords($request->input('keywords')),
                'language' => $request->input('language') ?: 'en',
                'cover_letter' => $request->input('cover_letter'),
                'author_response' => $request->input('author_response', $manuscript->author_response),
                'originality_confirmed' => $request->boolean('originality_confirmed'),
                'conflict_of_interest_declared' => $request->boolean('conflict_of_interest_declared'),
                'conflict_of_interest_statement' => $request->input('conflict_of_interest_statement'),
            ]);

            $this->syncAuthors($manuscript, $request->input('authors', []), (int) $request->input('corresponding_index', 0));
        });

        Auditor::log('updated', $manuscript->fresh(), $old, $manuscript->only(['title', 'abstract', 'article_type']), $manuscript->journal_id);

        return redirect()
            ->route('author.manuscripts.edit', $manuscript)
            ->with('status', 'Draft manuscript saved.');
    }

    public function submit(SubmitManuscriptRequest $request, Article $manuscript): RedirectResponse
    {
        $manuscript->load(['authors', 'files', 'correspondingAuthor']);

        if ($manuscript->status === ArticleStatus::RevisionRequired && $request->filled('author_response')) {
            $manuscript->author_response = $request->string('author_response')->toString();
        }

        $blockers = $manuscript->submissionBlockers();

        if ($blockers !== []) {
            return back()->with('error', implode(' ', $blockers));
        }

        $from = $manuscript->status;
        $to = $from === ArticleStatus::RevisionRequired
            ? ArticleStatus::Resubmitted
            : ArticleStatus::Submitted;

        if (! $from->canTransitionTo($to)) {
            return back()->with('error', 'This manuscript cannot be submitted from its current status.');
        }

        $revision = null;

        DB::transaction(function () use ($request, $manuscript, $to, &$revision): void {
            $version = (int) $manuscript->revisions()->max('version') + 1;
            $revision = $manuscript->revisions()->create([
                'submitted_by' => $request->user()->id,
                'version' => $version,
                'notes_to_editor' => $manuscript->cover_letter,
                'author_response' => $to === ArticleStatus::Resubmitted ? $manuscript->author_response : null,
                'submitted_at' => now(),
            ]);

            $manuscript->files()
                ->whereNull('revision_id')
                ->update(['revision_id' => $revision->id]);

            $note = $to === ArticleStatus::Resubmitted
                ? 'Revision submitted as version '.$version.'.'
                : 'Manuscript submitted as '.$manuscript->submission_number.'.';

            $manuscript->moveTo($to, $request->user(), $note, [
                'submitted_at' => $manuscript->submitted_at ?? now(),
                'author_response' => null,
                'revision_due_at' => $to === ArticleStatus::Resubmitted ? null : $manuscript->revision_due_at,
            ]);
        });

        Auditor::log('submitted', $manuscript->fresh(), ['status' => $from->value], [
            'status' => $to->value,
            'submission_number' => $manuscript->submission_number,
            'version' => $revision?->version,
        ], $manuscript->journal_id);

        if ($to === ArticleStatus::Resubmitted && $revision !== null) {
            Notifier::editors(new RevisionSubmittedNotification(
                $manuscript->fresh(),
                $revision,
                route('editorial.manuscripts.show', $manuscript)
            ), $request->user()?->id);
        } else {
            Notifier::editors(new NewSubmissionNotification(
                $manuscript->fresh(),
                route('editorial.manuscripts.show', $manuscript),
                false
            ), $request->user()?->id);

            if ($manuscript->correspondingAuthor) {
                Notifier::notify(
                    $manuscript->correspondingAuthor,
                    new NewSubmissionNotification(
                        $manuscript->fresh(),
                        route('author.manuscripts.show', $manuscript),
                        true
                    )
                );
            }
        }

        $message = $to === ArticleStatus::Resubmitted
            ? 'Revision '.$manuscript->submission_number.' (version '.$revision?->version.') submitted.'
            : 'Manuscript '.$manuscript->submission_number.' submitted.';

        return redirect()
            ->route('author.manuscripts.show', $manuscript)
            ->with('status', $message);
    }

    /**
     * @param  list<array<string, mixed>>  $authors
     */
    private function syncAuthors(Article $manuscript, array $authors, int $correspondingIndex): void
    {
        $manuscript->authors()->delete();

        foreach (array_values($authors) as $index => $author) {
            $email = strtolower((string) ($author['email'] ?? ''));
            $ownerEmail = strtolower((string) $manuscript->correspondingAuthor?->email);

            $manuscript->authors()->create([
                'user_id' => $email !== '' && $email === $ownerEmail ? $manuscript->corresponding_author_id : null,
                'name' => $author['name'],
                'email' => $author['email'] ?? null,
                'affiliation' => $author['affiliation'] ?? null,
                'orcid' => $author['orcid'] ?? null,
                'sequence' => $index + 1,
                'is_corresponding' => $index === $correspondingIndex,
            ]);
        }
    }
}
