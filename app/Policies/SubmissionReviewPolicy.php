<?php

namespace App\Policies;

use App\Models\SubmissionReview;
use App\Models\User;

class SubmissionReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_reviewer === true;
    }

    public function view(User $user, SubmissionReview $submissionReview): bool
    {
        return $user->isEditor() || $submissionReview->isAssignedTo($user);
    }

    public function submit(User $user, SubmissionReview $submissionReview): bool
    {
        return $user->is_reviewer === true
            && $submissionReview->isAssignedTo($user)
            && ! $submissionReview->isSubmitted();
    }

    public function download(User $user, SubmissionReview $submissionReview): bool
    {
        return $submissionReview->isAssignedTo($user) || $user->isEditor();
    }
}
