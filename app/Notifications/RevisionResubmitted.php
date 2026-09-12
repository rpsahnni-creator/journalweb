<?php

namespace App\Notifications;

use App\Models\Submission;
use App\Models\SubmissionVersion;

class RevisionResubmitted extends SubmissionNotification
{
    public function __construct(
        public Submission $submission,
        public SubmissionVersion $version,
    ) {}

    public function title(): string
    {
        return 'A revised manuscript was uploaded';
    }

    public function message(): string
    {
        return 'The author uploaded version '.$this->version->version_number.' of “'.$this->submission->title.'”.';
    }

    public function actionUrl(): string
    {
        return route('admin.submissions.show', $this->submission);
    }

    public function actionLabel(): string
    {
        return 'Open submission';
    }
}
