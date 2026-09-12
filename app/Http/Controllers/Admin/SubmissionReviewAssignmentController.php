<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignSubmissionReviewersRequest;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Notifications\ReviewerAssigned;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;

class SubmissionReviewAssignmentController extends Controller
{
    public function store(AssignSubmissionReviewersRequest $request, Submission $submission): RedirectResponse
    {
        $reviewerIds = collect($request->input('reviewer_ids', []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $alreadyAssigned = $submission->reviews()
            ->where('round', $submission->review_round)
            ->pluck('reviewer_id');
        $newIds = $reviewerIds->diff($alreadyAssigned);

        $created = 0;

        foreach ($newIds as $reviewerId) {
            $review = SubmissionReview::query()->create([
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewerId,
                'status' => 'assigned',
                'round' => $submission->review_round ?: 1,
                'assigned_at' => now(),
            ]);

            $reviewer = User::query()->find($reviewerId);

            if ($reviewer !== null) {
                $reviewer->notify(new ReviewerAssigned($review));
            }

            $created++;
        }

        if ($created > 0 && $submission->status === SubmissionStatus::Submitted) {
            $submission->update(['status' => SubmissionStatus::UnderReview]);
        }

        if ($created > 0) {
            Auditor::log('assigned-reviewers', $submission, null, [
                'reviewer_ids' => $newIds->all(),
            ]);
        }

        $skipped = $reviewerIds->intersect($alreadyAssigned)->count();
        $message = $created === 0
            ? 'No new reviewers were assigned.'
            : $created.' reviewer'.($created === 1 ? '' : 's').' assigned.';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' already had an assignment.';
        }

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', $message);
    }

    public function destroy(Submission $submission, SubmissionReview $submissionReview): RedirectResponse
    {
        $this->authorize('update', $submission);

        abort_unless($submissionReview->submission_id === $submission->id, 404);

        if ($submissionReview->isSubmitted()) {
            return back()->with('error', 'A submitted review cannot be removed.');
        }

        $submissionReview->delete();

        Auditor::log('unassigned-reviewer', $submission, [
            'reviewer_id' => $submissionReview->reviewer_id,
        ], null);

        return redirect()
            ->route('admin.submissions.show', $submission)
            ->with('status', 'Reviewer assignment removed.');
    }
}
