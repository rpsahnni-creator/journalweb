<?php

namespace App\Http\Controllers\Reviewer;

use App\Enums\ReviewRecommendation;
use App\Enums\SubmissionReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reviewer\StoreSubmissionReviewRequest;
use App\Models\SubmissionReview;
use App\Models\User;
use App\Notifications\ReviewSubmitted;
use App\Support\SubmissionFileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionReviewController extends Controller
{
    public function index(Request $request): View
    {
        $reviews = SubmissionReview::query()
            ->where('reviewer_id', $request->user()->id)
            ->with(['submission' => fn ($query) => $query->select([
                'id',
                'title',
                'abstract',
                'keywords',
                'status',
                'manuscript_path',
                'original_filename',
            ])])
            ->latest('assigned_at')
            ->paginate(15);

        return view('reviews.index', [
            'reviews' => $reviews,
        ]);
    }

    public function show(Request $request, SubmissionReview $submissionReview): View
    {
        $this->assertAssigned($request, $submissionReview);

        $submissionReview->markInProgress();
        $submissionReview->refresh();
        $submissionReview->load([
            'submission' => fn ($query) => $query->select([
                'id',
                'title',
                'abstract',
                'keywords',
                'status',
                'manuscript_path',
                'original_filename',
            ]),
        ]);

        return view('reviews.show', [
            'review' => $submissionReview,
            'recommendations' => ReviewRecommendation::options(),
        ]);
    }

    public function update(StoreSubmissionReviewRequest $request, SubmissionReview $submissionReview): RedirectResponse
    {
        $this->assertAssigned($request, $submissionReview);

        $submissionReview->update([
            'recommendation' => $request->enum('recommendation', ReviewRecommendation::class),
            'comments_to_editor' => $request->input('comments_to_editor'),
            'comments_to_author' => $request->input('comments_to_author'),
            'status' => SubmissionReviewStatus::Submitted,
            'submitted_at' => now(),
        ]);

        Notification::send(
            User::query()->editors()->get(),
            new ReviewSubmitted($submissionReview->fresh() ?? $submissionReview)
        );

        return redirect()
            ->route('reviews.show', $submissionReview)
            ->with('status', 'Your review has been submitted.');
    }

    public function download(Request $request, SubmissionReview $submissionReview, SubmissionFileStore $files): StreamedResponse
    {
        $this->assertAssigned($request, $submissionReview);

        $submission = $submissionReview->submission;

        abort_unless($submission && $submission->manuscriptExists(), 404);

        $response = $files->download($submission);
        $extension = strtolower((string) pathinfo($submission->downloadFilename(), PATHINFO_EXTENSION));
        $blindName = $extension !== '' ? 'manuscript.'.$extension : 'manuscript';

        $response->headers->set('Content-Disposition', 'attachment; filename="'.$blindName.'"');

        return $response;
    }

    private function assertAssigned(Request $request, SubmissionReview $submissionReview): void
    {
        abort_unless(
            $request->user() !== null
            && $request->user()->is_reviewer === true
            && $submissionReview->isAssignedTo($request->user()),
            403
        );
    }
}
