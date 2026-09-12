<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->is($model) || $user->hasPermission('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->is($model) || $user->hasPermission('users.manage');
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $user->hasPermission('users.manage');
    }

    public function assignRoles(User $user): bool
    {
        return $user->hasPermission('roles.manage') || $user->hasPermission('users.manage');
    }

    public function deactivate(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        return $this->update($user, $model);
    }
}
