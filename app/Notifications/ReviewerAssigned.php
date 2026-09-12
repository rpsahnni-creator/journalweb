<?php

namespace App\Notifications;

use App\Models\SubmissionReview;

class ReviewerAssigned extends SubmissionNotification
{
    public function __construct(
        public SubmissionReview $review,
    ) {
        $this->review->loadMissing('submission');
    }

    public function title(): string
    {
        return 'You have been assigned a manuscript to review';
    }

    public function message(): string
    {
        $title = $this->review->submission?->title ?: 'a manuscript';

        return 'Please review “'.$title.'”. Author identities are hidden in the reviewer workspace.';
    }

    public function actionUrl(): string
    {
        return route('reviews.show', $this->review);
    }

    public function actionLabel(): string
    {
        return 'Open review';
    }
}
