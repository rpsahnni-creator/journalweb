<?php

namespace App\Policies;

use App\Models\EditorialBoardMember;
use App\Models\User;

class EditorialBoardMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('editorial_board.manage');
    }

    public function view(User $user, EditorialBoardMember $member): bool
    {
        return $user->hasPermission('editorial_board.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('editorial_board.manage');
    }

    public function update(User $user, EditorialBoardMember $member): bool
    {
        return $user->hasPermission('editorial_board.manage');
    }

    public function delete(User $user, EditorialBoardMember $member): bool
    {
        return $user->hasPermission('editorial_board.manage');
    }
}
