<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIssueRequest;
use App\Http\Requests\Admin\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Volume;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Issue::class);

        $journal = CurrentJournal::managed();

        $issues = Issue::query()
            ->with('volume')
            ->withCount('issueArticles')
            ->when($journal, fn ($query) => $query->where('journal_id', $journal->id))
            ->orderByDesc('is_current')
            ->orderByDesc('volume_number')
            ->orderByDesc('number')
            ->paginate(15);

        return view('admin.issues.index', [
            'issues' => $issues,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Issue::class);

        return view('admin.issues.create');
    }

    public function store(StoreIssueRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return back()->with('error', 'A journal record must exist before an issue can be created.');
        }

        $issue = DB::transaction(function () use ($request, $journal): Issue {
            $volume = $this->findOrCreateVolume($journal->id, $request->integer('volume_number'));

            $duplicate = Issue::query()
                ->where('volume_id', $volume->id)
                ->where('number', $request->integer('issue_number'))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'issue_number' => 'That volume already has this issue number.',
                ]);
            }

            $isSpecial = $request->boolean('is_special_issue');

            $issue = Issue::query()->create([
                'journal_id' => $journal->id,
                'volume_id' => $volume->id,
                'volume_number' => $volume->number,
                'number' => $request->integer('issue_number'),
                'title' => $request->input('title'),
                'publication_month_year' => $request->string('publication_month_year')->toString(),
                'status' => IssueStatus::Published,
                'published_at' => now(),
                'is_current' => false,
                'is_special_issue' => $isSpecial,
                'special_issue_theme' => $isSpecial ? $request->input('special_issue_theme') : null,
            ]);

            Auditor::log('created', $issue, null, $issue->only([
                'volume_number',
                'number',
                'title',
                'publication_month_year',
            ]), $journal->id);

            return $issue;
        });

        return redirect()
            ->route('admin.issues.index')
            ->with('status', 'Issue created.');
    }

    public function edit(Issue $issue): View
    {
        $this->authorize('update', $issue);

        $issue->load('volume');

        return view('admin.issues.edit', [
            'issue' => $issue,
        ]);
    }

    public function update(UpdateIssueRequest $request, Issue $issue): RedirectResponse
    {
        $old = $issue->only(['volume_number', 'number', 'title', 'publication_month_year', 'is_special_issue', 'special_issue_theme']);
        $volume = $this->findOrCreateVolume($issue->journal_id, $request->integer('volume_number'));
        $isSpecial = $request->boolean('is_special_issue');

        $duplicate = Issue::query()
            ->where('volume_id', $volume->id)
            ->where('number', $request->integer('issue_number'))
            ->whereKeyNot($issue->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'issue_number' => 'That volume already has this issue number.',
            ]);
        }

        $issue->update([
            'volume_id' => $volume->id,
            'volume_number' => $volume->number,
            'number' => $request->integer('issue_number'),
            'title' => $request->input('title'),
            'publication_month_year' => $request->string('publication_month_year')->toString(),
            'is_special_issue' => $isSpecial,
            'special_issue_theme' => $isSpecial ? $request->input('special_issue_theme') : null,
        ]);

        Auditor::log('updated', $issue->fresh(), $old, $issue->only([
            'volume_number',
            'number',
            'title',
            'publication_month_year',
            'is_special_issue',
            'special_issue_theme',
        ]));

        return redirect()
            ->route('admin.issues.index')
            ->with('status', 'Issue updated.');
    }

    public function markCurrent(Issue $issue): RedirectResponse
    {
        $this->authorize('update', $issue);

        $issue->markAsCurrent();

        Auditor::log('updated', $issue, ['is_current' => false], ['is_current' => true]);

        return redirect()
            ->route('admin.issues.index')
            ->with('status', 'This issue is now the current issue.');
    }

    private function findOrCreateVolume(int $journalId, int $number): Volume
    {
        $year = (int) now()->year;

        return Volume::query()->firstOrCreate(
            ['journal_id' => $journalId, 'number' => $number],
            ['year' => $year, 'title' => 'Volume '.$number]
        );
    }
}
