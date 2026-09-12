<?php

namespace App\Policies;

use App\Models\JournalPolicy as ContentPolicy;
use App\Models\User;

class JournalContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('policies.manage');
    }

    public function view(User $user, ContentPolicy $policy): bool
    {
        return $user->hasPermission('policies.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('policies.manage');
    }

    public function update(User $user, ContentPolicy $policy): bool
    {
        return $user->hasPermission('policies.manage');
    }

    public function delete(User $user, ContentPolicy $policy): bool
    {
        return $user->hasPermission('policies.manage');
    }
}
