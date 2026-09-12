<?php

namespace App\Http\Controllers\Reviewer;

use App\Enums\ArticleFileType;
use App\Enums\ReviewerAssignmentStatus;
use App\Enums\ReviewRecommendation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviewer\DeclineAssignmentRequest;
use App\Http\Requests\Reviewer\StoreReviewRequest;
use App\Models\ReviewerAssignment;
use App\Notifications\ReviewerAcceptedNotification;
use App\Notifications\ReviewerDeclinedNotification;
use App\Notifications\ReviewSubmittedNotification;
use App\Support\Auditor;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function show(ReviewerAssignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load(['article.files', 'article.currentRevision', 'review']);

        $files = $assignment->canAccessManuscript()
            ? $assignment->article->files->filter(fn ($file) => in_array($file->type, [
                ArticleFileType::Manuscript,
                ArticleFileType::Supplementary,
            ], true))
            : collect();

        return view('reviewer.assignments.show', [
            'assignment' => $assignment,
            'manuscript' => $assignment->article,
            'files' => $files,
            'recommendations' => ReviewRecommendation::cases(),
        ]);
    }

    public function accept(ReviewerAssignment $assignment): RedirectResponse
    {
        $this->authorize('respond', $assignment);

        $assignment->load(['article', 'reviewer']);

        $assignment->update([
            'status' => ReviewerAssignmentStatus::Accepted,
            'responded_at' => now(),
        ]);

        Auditor::log('accepted', $assignment, ['status' => ReviewerAssignmentStatus::Invited->value], ['status' => ReviewerAssignmentStatus::Accepted->value], $assignment->article->journal_id);

        Notifier::editors(
            new ReviewerAcceptedNotification(
                $assignment,
                route('editorial.manuscripts.show', $assignment->article)
            ),
            $assignment->reviewer_id
        );

        return redirect()
            ->route('reviewer.assignments.show', $assignment)
            ->with('status', 'Invitation accepted. You can now download the manuscript and submit a review.');
    }

    public function decline(DeclineAssignmentRequest $request, ReviewerAssignment $assignment): RedirectResponse
    {
        $assignment->load(['article', 'reviewer']);

        $assignment->update([
            'status' => ReviewerAssignmentStatus::Declined,
            'responded_at' => now(),
            'response_note' => $request->input('response_note'),
        ]);

        Auditor::log('declined', $assignment, ['status' => ReviewerAssignmentStatus::Invited->value], ['status' => ReviewerAssignmentStatus::Declined->value], $assignment->article->journal_id);

        Notifier::editors(
            new ReviewerDeclinedNotification(
                $assignment,
                route('editorial.manuscripts.show', $assignment->article)
            ),
            $assignment->reviewer_id
        );

        return redirect()
            ->route('reviewer.dashboard')
            ->with('status', 'Invitation declined.');
    }

    public function storeReview(StoreReviewRequest $request, ReviewerAssignment $assignment): RedirectResponse
    {
        DB::transaction(function () use ($request, $assignment): void {
            $assignment->review()->create([
                'article_id' => $assignment->article_id,
                'revision_id' => $assignment->revision_id,
                'reviewer_id' => $assignment->reviewer_id,
                'recommendation' => $request->string('recommendation')->toString(),
                'comments_to_author' => $request->string('comments_to_author')->toString(),
                'comments_to_editor' => $request->input('comments_to_editor'),
                'submitted_at' => now(),
            ]);

            $assignment->update([
                'status' => ReviewerAssignmentStatus::Completed,
                'completed_at' => now(),
            ]);
        });

        $assignment->load(['article', 'reviewer', 'review']);

        Auditor::log('reviewed', $assignment->review, null, [
            'recommendation' => $assignment->review->recommendation->value,
        ], $assignment->article->journal_id);

        Notifier::editors(
            new ReviewSubmittedNotification(
                $assignment,
                $assignment->review,
                route('editorial.manuscripts.show', $assignment->article)
            ),
            $assignment->reviewer_id
        );

        return redirect()
            ->route('reviewer.assignments.show', $assignment)
            ->with('status', 'Review submitted.');
    }
}
