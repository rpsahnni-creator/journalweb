<?php

namespace App\Http\Controllers\Editorial;

use App\Enums\ArticleStatus;
use App\Enums\EditorialDecisionType;
use App\Enums\ReviewerAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editorial\AssignReviewerRequest;
use App\Http\Requests\Editorial\ScreenManuscriptRequest;
use App\Http\Requests\Editorial\StoreEditorialDecisionRequest;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ReviewerInvitationNotification;
use App\Support\Auditor;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ManuscriptController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('access-editorial');

        $manuscripts = Article::query()
            ->with(['correspondingAuthor', 'currentRevision'])
            ->withCount('reviewerAssignments')
            ->when(
                $request->string('status')->toString() !== '',
                fn ($query) => $query->where('status', $request->string('status')->toString()),
                fn ($query) => $query->whereIn('status', [
                    ArticleStatus::Submitted,
                    ArticleStatus::InitialScreening,
                    ArticleStatus::UnderReview,
                    ArticleStatus::Resubmitted,
                ])
            )
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('submission_number', 'like', '%'.$search.'%');
                });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('editorial.manuscripts.index', [
            'manuscripts' => $manuscripts,
            'filters' => $request->only(['q', 'status']),
            'statuses' => collect(ArticleStatus::cases())
                ->filter(fn (ArticleStatus $status) => $status !== ArticleStatus::Draft),
        ]);
    }

    public function show(Request $request, Article $manuscript): View
    {
        $this->authorize('viewEditorial', $manuscript);

        $manuscript->load([
            'authors',
            'files',
            'currentRevision',
            'revisions.files',
            'revisions.submitter',
            'statusEvents.user',
            'editorialDecisions.editor',
            'reviewerAssignments.reviewer',
            'reviewerAssignments.review',
            'reviews.reviewer',
        ]);

        $excludeIds = $manuscript->authors->pluck('user_id')->filter()->all();
        $excludeIds[] = $manuscript->corresponding_author_id;

        $assignedIds = $manuscript->reviewerAssignments
            ->reject(fn ($assignment) => $assignment->status === ReviewerAssignmentStatus::Declined)
            ->pluck('reviewer_id')
            ->all();

        $reviewers = collect();

        if ($request->filled('q') && $request->user()?->can('assignReviewers', $manuscript)) {
            $reviewers = User::query()
                ->reviewers()
                ->whereNotIn('id', array_filter([...$excludeIds, ...$assignedIds]))
                ->where(function ($query) use ($request): void {
                    $search = $request->string('q')->toString();
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('affiliation', 'like', '%'.$search.'%');
                })
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        return view('editorial.manuscripts.show', [
            'manuscript' => $manuscript,
            'reviewers' => $reviewers,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function screen(ScreenManuscriptRequest $request, Article $manuscript): RedirectResponse
    {
        $outcome = $request->string('outcome')->toString();

        if ($outcome === 'reject' && $request->user()?->cannot('deskReject', $manuscript)) {
            abort(403);
        }

        $from = $manuscript->status;
        $to = match ($outcome) {
            'send_to_review' => ArticleStatus::UnderReview,
            'reject' => ArticleStatus::Rejected,
            default => ArticleStatus::InitialScreening,
        };

        if ($to === ArticleStatus::Rejected && blank($request->input('comments_to_author'))) {
            return back()->with('error', 'Comments to the author are required for a desk rejection.');
        }

        if (! $from->canTransitionTo($to)) {
            return back()->with('error', 'That screening outcome is not allowed from the current manuscript status.');
        }

        $decision = null;

        DB::transaction(function () use ($request, $manuscript, $to, $outcome, &$decision): void {
            $decisionType = match ($outcome) {
                'send_to_review' => EditorialDecisionType::SendToReview,
                'reject' => EditorialDecisionType::Reject,
                default => null,
            };

            if ($decisionType !== null) {
                $decision = $manuscript->editorialDecisions()->create([
                    'revision_id' => $manuscript->currentRevision?->id,
                    'editor_id' => $request->user()->id,
                    'decision' => $decisionType,
                    'comments_to_author' => $request->input('comments_to_author'),
                    'internal_notes' => $request->input('internal_notes'),
                    'decided_at' => now(),
                ]);
            }

            $manuscript->moveTo($to, $request->user(), $request->input('internal_notes'));
        });

        Auditor::log('screened', $manuscript->fresh(), ['status' => $from->value], ['status' => $to->value, 'outcome' => $outcome], $manuscript->journal_id);

        if ($decision !== null && $manuscript->correspondingAuthor) {
            $notification = $decision->authorNotification(
                $manuscript->fresh(['correspondingAuthor']),
                route('author.manuscripts.show', $manuscript)
            );

            if ($notification !== null) {
                Notifier::notify($manuscript->correspondingAuthor, $notification);
            }
        }

        return redirect()
            ->route('editorial.manuscripts.show', $manuscript)
            ->with('status', 'Screening updated: '.$to->label().'.');
    }

    public function decide(StoreEditorialDecisionRequest $request, Article $manuscript): RedirectResponse
    {
        $decisionType = EditorialDecisionType::from($request->string('decision')->toString());
        $to = $decisionType->resultingStatus();
        $from = $manuscript->status;
        $dueAt = $decisionType->requiresRevisionDeadline() ? $request->date('revision_due_at') : null;

        if ($to === null || ! $from->canTransitionTo($to)) {
            return back()->with('error', 'That editorial decision is not allowed from the current manuscript status.');
        }

        $decision = DB::transaction(function () use ($request, $manuscript, $decisionType, $to, $dueAt) {
            $record = $manuscript->editorialDecisions()->create([
                'revision_id' => $manuscript->currentRevision?->id,
                'editor_id' => $request->user()->id,
                'decision' => $decisionType,
                'comments_to_author' => $request->string('comments_to_author')->toString(),
                'internal_notes' => $request->input('internal_notes'),
                'decided_at' => now(),
                'revision_due_at' => $dueAt,
            ]);

            $manuscript->moveTo($to, $request->user(), $request->input('internal_notes'), [
                'revision_due_at' => $dueAt,
            ]);

            return $record;
        });

        Auditor::log('decided', $decision, ['status' => $from->value], [
            'status' => $to->value,
            'decision' => $decisionType->value,
            'revision_due_at' => $dueAt?->toDateString(),
        ], $manuscript->journal_id);

        if ($manuscript->correspondingAuthor) {
            $notification = $decision->authorNotification(
                $manuscript->fresh(['correspondingAuthor']),
                route('author.manuscripts.show', $manuscript)
            );

            if ($notification !== null) {
                Notifier::notify($manuscript->correspondingAuthor, $notification);
            }
        }

        return redirect()
            ->route('editorial.manuscripts.show', $manuscript)
            ->with('status', 'Decision recorded: '.$decisionType->label().'.');
    }

    public function assign(AssignReviewerRequest $request, Article $manuscript): RedirectResponse
    {
        $reviewer = User::query()->findOrFail($request->integer('reviewer_id'));
        $revision = $manuscript->currentRevision;

        if ($revision === null) {
            return back()->with('error', 'This manuscript has no submitted revision to assign.');
        }

        if (! $reviewer->isReviewer() || ! $reviewer->is_active) {
            return back()->with('error', 'Select an active reviewer account.');
        }

        if ($manuscript->isOwnedBy($reviewer)) {
            return back()->with('error', 'A manuscript author cannot be assigned as a reviewer.');
        }

        $existing = $manuscript->reviewerAssignments()
            ->where('reviewer_id', $reviewer->id)
            ->where('revision_id', $revision->id)
            ->first();

        if ($existing && $existing->status !== ReviewerAssignmentStatus::Declined) {
            return back()->with('error', 'That reviewer is already assigned to this revision.');
        }

        $assignment = DB::transaction(function () use ($request, $manuscript, $reviewer, $revision, $existing) {
            if ($existing) {
                $existing->update([
                    'assigned_by' => $request->user()->id,
                    'status' => ReviewerAssignmentStatus::Invited,
                    'invited_at' => now(),
                    'responded_at' => null,
                    'due_at' => $request->date('due_at'),
                    'completed_at' => null,
                    'response_note' => null,
                ]);

                return $existing->fresh();
            }

            return $manuscript->reviewerAssignments()->create([
                'revision_id' => $revision->id,
                'reviewer_id' => $reviewer->id,
                'assigned_by' => $request->user()->id,
                'status' => ReviewerAssignmentStatus::Invited,
                'invited_at' => now(),
                'due_at' => $request->date('due_at'),
            ]);
        });

        Notifier::notify(
            $reviewer,
            new ReviewerInvitationNotification(
                $assignment,
                route('reviewer.assignments.show', $assignment)
            )
        );

        Auditor::log('assigned', $assignment, null, [
            'reviewer_id' => $reviewer->id,
            'due_at' => $assignment->due_at?->toDateString(),
        ], $manuscript->journal_id);

        return back()->with('status', 'Reviewer invitation sent to '.$reviewer->name.'.');
    }
}
