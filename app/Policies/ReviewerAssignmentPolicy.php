<?php

namespace App\Policies;

use App\Models\ReviewerAssignment;
use App\Models\User;

class ReviewerAssignmentPolicy
{
    public function view(User $user, ReviewerAssignment $assignment): bool
    {
        if ($user->id === $assignment->reviewer_id) {
            return true;
        }

        return $user->hasPermission('articles.view_all')
            || $user->hasPermission('reviews.assign');
    }

    public function respond(User $user, ReviewerAssignment $assignment): bool
    {
        return $user->id === $assignment->reviewer_id
            && $user->hasPermission('articles.review')
            && $assignment->canBeRespondedTo();
    }

    public function download(User $user, ReviewerAssignment $assignment): bool
    {
        if ($user->hasPermission('files.view_unpublished')) {
            return true;
        }

        return $user->id === $assignment->reviewer_id
            && $user->hasPermission('articles.review')
            && $assignment->canAccessManuscript();
    }

    public function submitReview(User $user, ReviewerAssignment $assignment): bool
    {
        return $user->id === $assignment->reviewer_id
            && $user->hasPermission('reviews.submit')
            && $assignment->canSubmitReview();
    }
}
