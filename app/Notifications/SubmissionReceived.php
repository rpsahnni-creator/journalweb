<?php

namespace App\Notifications;

use App\Models\Submission;

class SubmissionReceived extends SubmissionNotification
{
    public function __construct(
        public Submission $submission,
        public bool $forAuthor = false,
    ) {}

    public function title(): string
    {
        return $this->forAuthor
            ? 'We received your manuscript'
            : 'New manuscript submitted';
    }

    public function message(): string
    {
        $title = $this->submission->title;

        return $this->forAuthor
            ? 'Your manuscript “'.$title.'” has been received. You can follow its status from My Submissions.'
            : 'A new manuscript has been submitted: “'.$title.'”.';
    }

    public function actionUrl(): string
    {
        return $this->forAuthor
            ? route('submissions.index')
            : route('admin.submissions.show', $this->submission);
    }

    public function actionLabel(): string
    {
        return $this->forAuthor ? 'View my submissions' : 'Open submission';
    }
}
