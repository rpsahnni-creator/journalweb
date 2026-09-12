<?php

namespace App\Notifications;

use App\Enums\SubmissionStatus;
use App\Models\Submission;

class SubmissionDecisionMade extends SubmissionNotification
{
    public function __construct(
        public Submission $submission,
        public SubmissionStatus $decision,
        public string $commentsToAuthor = '',
    ) {}

    public function title(): string
    {
        return match ($this->decision) {
            SubmissionStatus::RevisionRequested => 'Revision requested',
            SubmissionStatus::Accepted => 'Your manuscript has been accepted',
            SubmissionStatus::Rejected => 'Decision on your manuscript',
            default => 'Update on your manuscript',
        };
    }

    public function message(): string
    {
        $title = $this->submission->title;
        $intro = match ($this->decision) {
            SubmissionStatus::RevisionRequested => 'The editors have requested a revision of “'.$title.'”. Please upload a revised manuscript from My Submissions.',
            SubmissionStatus::Accepted => '“'.$title.'” has been accepted.',
            SubmissionStatus::Rejected => '“'.$title.'” was not accepted for publication.',
            default => 'The status of “'.$title.'” has been updated to '.$this->decision->label().'.',
        };

        if (trim($this->commentsToAuthor) === '') {
            return $intro;
        }

        return $intro.' Comments to the author: '.$this->commentsToAuthor;
    }

    public function actionUrl(): string
    {
        return route('submissions.index');
    }

    public function actionLabel(): string
    {
        return 'View my submissions';
    }
}
