<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_a_custom_role(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $this->seed(PermissionSeeder::class);
        $permission = Permission::query()->where('slug', 'audit.view')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.roles.create'))
            ->followingRedirects()
            ->post(route('admin.roles.store'), [
                'name' => 'Guest editor',
                'slug' => 'guest-editor',
                'description' => 'Temporary editorial access',
                'permission_ids' => [$permission->id],
            ])
            ->assertOk()
            ->assertSee('Role created.', false)
            ->assertSee('Guest editor', false);

        $role = Role::query()->where('slug', 'guest-editor')->firstOrFail();
        $this->assertTrue($role->permissions->contains('slug', 'audit.view'));

        $this->actingAs($admin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Guest editor updated',
                'slug' => 'guest-editor',
                'description' => 'Updated description',
                'permission_ids' => [],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status', 'Role updated.');

        $this->assertSame('Guest editor updated', $role->fresh()->name);
        $this->assertCount(0, $role->fresh()->permissions);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('status', 'Role deleted.');

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => Role::class,
            'auditable_id' => $role->id,
        ]);
    }

    public function test_system_roles_and_assigned_roles_cannot_be_deleted(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $adminRole = Role::query()->where('slug', RoleSlug::Admin->value)->firstOrFail();
        $custom = Role::factory()->create(['name' => 'Assigned Role', 'slug' => 'assigned-role']);
        $admin->assignRole($custom);

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $adminRole))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'System roles cannot be deleted.');

        $this->actingAs($admin)
            ->from(route('admin.roles.index'))
            ->delete(route('admin.roles.destroy', $custom))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'A role assigned to users cannot be deleted.');

        $this->assertDatabaseHas('roles', ['id' => $adminRole->id]);
        $this->assertDatabaseHas('roles', ['id' => $custom->id]);
    }

    public function test_role_index_can_be_searched(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        Role::factory()->create(['name' => 'Special Search Role', 'slug' => 'special-search-role']);

        $this->actingAs($admin)
            ->get(route('admin.roles.index', ['q' => 'Special Search']))
            ->assertOk()
            ->assertSee('Special Search Role', false)
            ->assertDontSee('Journal Manager', false)
            ->assertDontSee('editor_in_chief', false);
    }
}
