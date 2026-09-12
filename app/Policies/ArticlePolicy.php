<?php

namespace App\Policies;

use App\Enums\ReviewerAssignmentStatus;
use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(?User $user, Article $article): bool
    {
        if ($article->isPubliclyVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($article->isOwnedBy($user)) {
            return true;
        }

        if ($user->hasPermission('articles.view_all') || $user->hasPermission('files.view_unpublished')) {
            return true;
        }

        return $article->reviewerAssignments()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', [
                ReviewerAssignmentStatus::Invited,
                ReviewerAssignmentStatus::Accepted,
                ReviewerAssignmentStatus::Completed,
            ])
            ->exists();
    }

    public function viewAsAuthor(User $user, Article $article): bool
    {
        return $article->isOwnedBy($user);
    }

    public function viewEditorial(User $user, Article $article): bool
    {
        return $user->hasPermission('articles.view_all')
            || $user->hasPermission('files.view_unpublished');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('articles.submit');
    }

    public function publishDirectly(User $user): bool
    {
        return $user->isEditor();
    }

    public function update(User $user, Article $article): bool
    {
        if ($user->hasPermission('articles.decide')) {
            return true;
        }

        return $this->updateAsAuthor($user, $article);
    }

    public function updateAsAuthor(User $user, Article $article): bool
    {
        if (! $article->isOwnedBy($user) || ! $user->hasPermission('articles.edit_own')) {
            return false;
        }

        return $article->isAuthorEditable();
    }

    public function submit(User $user, Article $article): bool
    {
        if (! $article->isOwnedBy($user) || ! $user->hasPermission('articles.submit')) {
            return false;
        }

        return $article->isAuthorEditable();
    }

    public function screen(User $user, Article $article): bool
    {
        if (! $article->status->canBeScreened()) {
            return false;
        }

        return $user->hasPermission('articles.decide')
            || $user->hasPermission('reviews.assign');
    }

    public function deskReject(User $user, Article $article): bool
    {
        return $article->status->canBeScreened()
            && $user->hasPermission('articles.decide');
    }

    public function assignReviewers(User $user, Article $article): bool
    {
        return $article->status->canReceiveReviewers()
            && $user->hasPermission('reviews.assign');
    }

    public function review(User $user, Article $article): bool
    {
        if ($user->hasPermission('articles.review') === false) {
            return false;
        }

        return $article->reviewerAssignments()
            ->where('reviewer_id', $user->id)
            ->where('status', ReviewerAssignmentStatus::Accepted)
            ->exists();
    }

    public function decide(User $user, Article $article): bool
    {
        return $user->hasPermission('articles.decide');
    }

    public function issueDecision(User $user, Article $article): bool
    {
        return $article->status->canReceiveEditorialDecision()
            && $user->hasPermission('articles.decide');
    }
}
