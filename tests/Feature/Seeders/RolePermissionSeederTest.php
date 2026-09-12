<?php

namespace Tests\Feature\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_roles_and_permissions_without_the_local_dev_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(9, Role::query()->count());
        $this->assertGreaterThanOrEqual(18, Permission::query()->count());
        $this->assertDatabaseMissing('users', ['email' => AdminUserSeeder::EMAIL]);

        $author = Role::query()->where('slug', 'author')->firstOrFail();
        $this->assertTrue($author->permissions->contains('slug', 'articles.submit'));
        $this->assertFalse($author->permissions->contains('slug', 'articles.publish'));
    }
}
