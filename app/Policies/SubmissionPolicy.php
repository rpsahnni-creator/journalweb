<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor();
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->isEditor() || $submission->isOwnedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->isEditor();
    }

    public function download(User $user, Submission $submission): bool
    {
        return $user->isEditor() || $submission->isOwnedBy($user);
    }

    public function uploadRevision(User $user, Submission $submission): bool
    {
        return $submission->isOwnedBy($user) && $submission->canUploadRevision();
    }

    public function downloadVersion(User $user, Submission $submission): bool
    {
        return $this->download($user, $submission);
    }

    public function convert(User $user, Submission $submission): bool
    {
        return $user->isEditor() && $submission->canConvertToArticle();
    }

    public function publish(User $user, Submission $submission): bool
    {
        return $user->isEditor() && $submission->canPublishToIssue();
    }
}
