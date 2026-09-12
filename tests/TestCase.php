<?php

namespace Tests;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createUserWithRole(string|RoleSlug $role, array $attributes = []): User
    {
        if (Role::query()->doesntExist()) {
            $this->seed(RoleSeeder::class);
        }

        if (Permission::query()->doesntExist()) {
            $this->seed(PermissionSeeder::class);
            $this->seed(RolePermissionSeeder::class);
        }

        $slug = $role instanceof RoleSlug ? $role->value : $role;
        $user = User::factory()->create($attributes);
        $user->assignRole($slug);

        return $user->fresh(['roles']);
    }
}
