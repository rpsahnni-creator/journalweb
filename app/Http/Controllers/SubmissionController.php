<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionReviewStatus;
use App\Enums\SubmissionStatus;
use App\Http\Requests\StoreRevisionRequest;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use App\Notifications\RevisionResubmitted;
use App\Notifications\SubmissionReceived;
use App\Support\SubmissionFileStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $submissions = Submission::query()
            ->ownedBy($user)
            ->with(['reviews' => function ($query): void {
                $query->select('id', 'submission_id', 'status', 'comments_to_author', 'submitted_at')
                    ->where('status', SubmissionReviewStatus::Submitted)
                    ->orderBy('submitted_at')
                    ->orderBy('id');
            }])
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(15);

        $authorComments = [];

        foreach ($submissions as $submission) {
            if ($submission->status === SubmissionStatus::RevisionRequested) {
                $authorComments[$submission->id] = $submission->commentsToAuthor();
            }
        }

        return view('submissions.index', [
            'submissions' => $submissions,
            'authorComments' => $authorComments,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Submission::class);

        return view('submissions.create', [
            'minWords' => (int) config('submissions.manuscript.abstract_min_words'),
            'maxWords' => (int) config('submissions.manuscript.abstract_max_words'),
            'minKeywords' => (int) config('submissions.manuscript.keywords_min'),
            'maxKeywords' => (int) config('submissions.manuscript.keywords_max'),
            'maxKilobytes' => (int) config('submissions.manuscript.max_kilobytes'),
        ]);
    }

    public function store(StoreSubmissionRequest $request, SubmissionFileStore $files): RedirectResponse
    {
        $user = $request->user();
        $upload = $request->file('manuscript');
        $path = $files->store($upload, $user->id);

        $submission = Submission::query()->create([
            'user_id' => $user->id,
            'title' => $request->string('title')->toString(),
            'abstract' => $request->string('abstract')->toString(),
            'keywords' => $request->string('keywords')->toString(),
            'co_authors' => $request->input('co_authors'),
            'manuscript_path' => $path,
            'original_filename' => basename(str_replace('\\', '/', (string) $upload->getClientOriginalName())),
            'status' => SubmissionStatus::Submitted,
            'review_round' => 1,
            'submitted_at' => now(),
        ]);

        $submission->recordVersion($path);

        $user->notify(new SubmissionReceived($submission, forAuthor: true));
        Notification::send(
            User::query()->editors()->whereKeyNot($user->id)->get(),
            new SubmissionReceived($submission, forAuthor: false)
        );

        return redirect()
            ->route('submissions.index')
            ->with('status', 'Your manuscript has been submitted. You can follow its status on this page.');
    }

    public function show(Request $request, Submission $submission): View
    {
        abort_unless($submission->isOwnedBy($request->user()), 403);

        $submission->load('versions');

        if ($submission->status === SubmissionStatus::RevisionRequested) {
            $submission->load(['reviews' => function ($query): void {
                $query->select('id', 'submission_id', 'status', 'comments_to_author', 'submitted_at')
                    ->where('status', SubmissionReviewStatus::Submitted)
                    ->orderBy('submitted_at')
                    ->orderBy('id');
            }]);
        }

        $comments = $submission->status === SubmissionStatus::RevisionRequested
            ? $submission->commentsToAuthor()
            : collect();

        return view('submissions.show', [
            'submission' => $submission,
            'authorComments' => $comments,
        ]);
    }

    public function download(Submission $submission, SubmissionFileStore $files): StreamedResponse
    {
        $this->authorize('download', $submission);

        return $files->download($submission);
    }

    public function storeRevision(StoreRevisionRequest $request, Submission $submission, SubmissionFileStore $files): RedirectResponse
    {
        $upload = $request->file('manuscript');
        $path = $files->store($upload, $request->user()->id);
        $original = basename(str_replace('\\', '/', (string) $upload->getClientOriginalName()));

        $version = $submission->recordVersion($path);

        $submission->update([
            'original_filename' => $original !== '' ? $original : $submission->original_filename,
            'status' => SubmissionStatus::Submitted,
            'review_round' => ($submission->review_round ?: 1) + 1,
        ]);

        Notification::send(
            User::query()->editors()->get(),
            new RevisionResubmitted($submission->fresh() ?? $submission, $version)
        );

        return redirect()
            ->route('submissions.show', $submission)
            ->with('status', 'Revised manuscript uploaded. The editorial office has been notified.');
    }

    public function downloadVersion(Submission $submission, SubmissionVersion $version, SubmissionFileStore $files): StreamedResponse
    {
        $this->authorize('downloadVersion', $submission);

        return $files->downloadVersion($submission, $version);
    }
}
