<?php

namespace App\Policies;

use App\Enums\ArticleFileType;
use App\Enums\ReviewerAssignmentStatus;
use App\Models\ArticleFile;
use App\Models\User;

class ArticleFilePolicy
{
    public function view(User $user, ArticleFile $file): bool
    {
        return $this->download($user, $file);
    }

    public function download(User $user, ArticleFile $file): bool
    {
        $article = $file->article;

        if ($article === null) {
            return false;
        }

        if ($file->isVisibleToPublic()) {
            return true;
        }

        if ($user->hasPermission('files.view_unpublished')) {
            return true;
        }

        if ($article->isOwnedBy($user)) {
            return true;
        }

        if ($user->hasPermission('articles.review') === false) {
            return false;
        }

        if (! in_array($file->type, [ArticleFileType::Manuscript, ArticleFileType::Supplementary], true)) {
            return false;
        }

        return $article->reviewerAssignments()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', [
                ReviewerAssignmentStatus::Accepted,
                ReviewerAssignmentStatus::Completed,
            ])
            ->exists();
    }

    public function delete(User $user, ArticleFile $file): bool
    {
        $article = $file->article;

        if ($article === null) {
            return false;
        }

        return $file->isWorkingCopy()
            && $user->can('updateAsAuthor', $article);
    }
}
