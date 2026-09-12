<?php

namespace App\Notifications;

use App\Models\SubmissionReview;

class ReviewSubmitted extends SubmissionNotification
{
    public function __construct(
        public SubmissionReview $review,
    ) {
        $this->review->loadMissing(['submission', 'reviewer']);
    }

    public function title(): string
    {
        return 'A review has been submitted';
    }

    public function message(): string
    {
        $title = $this->review->submission?->title ?: 'a manuscript';

        return 'A reviewer submitted a report for “'.$title.'”.';
    }

    public function actionUrl(): string
    {
        $submission = $this->review->submission;

        return $submission
            ? route('admin.submissions.show', $submission)
            : route('admin.submissions.index');
    }

    public function actionLabel(): string
    {
        return 'Open submission';
    }
}
