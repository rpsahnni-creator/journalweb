<?php

namespace App\Policies;

use App\Models\Journal;
use App\Models\User;

class JournalPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Journal $journal): bool
    {
        return $journal->is_active || ($user?->hasPermission('journals.manage') ?? false);
    }

    public function update(User $user, Journal $journal): bool
    {
        return $user->hasPermission('journals.manage')
            || $user->hasPermission('journals.settings.update');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('journals.manage');
    }

    public function viewUnpublished(User $user): bool
    {
        return $user->hasPermission('files.view_unpublished');
    }
}
