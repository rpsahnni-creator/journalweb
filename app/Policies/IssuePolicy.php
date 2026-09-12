<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\User;

class IssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isEditor()
            || $user->hasPermission('issues.manage')
            || $user->hasPermission('articles.publish');
    }

    public function view(User $user, Issue $issue): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isEditor() || $user->hasPermission('issues.manage');
    }

    public function update(User $user, Issue $issue): bool
    {
        return $user->isEditor() || $user->hasPermission('issues.manage');
    }

    public function delete(User $user, Issue $issue): bool
    {
        return $user->hasPermission('issues.manage') && ! $issue->isPublished();
    }

    public function assignArticles(User $user, Issue $issue): bool
    {
        return $user->hasPermission('issues.manage');
    }

    public function publish(User $user, Issue $issue): bool
    {
        return $user->hasPermission('articles.publish');
    }
}
