<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $authorRole = Role::query()->where('slug', RoleSlug::Author->value)->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->followingRedirects()
            ->post(route('admin.users.store'), [
                'name' => 'New Author',
                'email' => 'new-author@example.com',
                'affiliation' => 'Example University',
                'password' => 'secret-pass',
                'password_confirmation' => 'secret-pass',
                'is_active' => '1',
                'role_ids' => [$authorRole->id],
            ])
            ->assertOk()
            ->assertSee('User created.', false)
            ->assertSee('New Author', false);

        $user = User::query()->where('email', 'new-author@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secret-pass', $user->password));
        $this->assertNotSame('secret-pass', $user->password);
        $this->assertTrue($user->hasRole(RoleSlug::Author));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_user_create_form_shows_validation_errors(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->from(route('admin.users.create'))
            ->followingRedirects()
            ->post(route('admin.users.store'), [])
            ->assertOk()
            ->assertSee('Please correct the highlighted fields.', false)
            ->assertSee('The name field is required.', false)
            ->assertSee('The email field is required.', false)
            ->assertSee('The password field is required.', false);
    }

    public function test_admin_can_update_a_user_and_filter_the_index(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $readerRole = Role::query()->where('slug', RoleSlug::Reader->value)->firstOrFail();
        $user = User::factory()->create(['name' => 'Filterable Person', 'email' => 'filterable@example.com']);
        $user->assignRole(RoleSlug::Reader);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated Person',
                'email' => 'updated-person@example.com',
                'affiliation' => 'Updated Lab',
                'is_active' => '1',
                'role_ids' => [$readerRole->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'User updated.');

        $this->assertSame('Updated Person', $user->fresh()->name);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Updated Person', 'role' => RoleSlug::Reader->value, 'status' => 'active']))
            ->assertOk()
            ->assertSee('Updated Person', false)
            ->assertDontSee($admin->email, false);
    }

    public function test_user_index_paginates_results(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        User::factory()->count(16)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('page=2', false);
    }

    public function test_admin_cannot_delete_or_deactivate_their_own_account(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $adminRoleId = Role::query()->where('slug', RoleSlug::Admin->value)->valueOrFail('id');

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role_ids' => [$adminRoleId],
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHas('error', 'You cannot deactivate your own account.');

        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_last_administrator_cannot_be_removed(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $authorRoleId = Role::query()->where('slug', RoleSlug::Author->value)->valueOrFail('id');

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => '1',
                'role_ids' => [$authorRoleId],
            ])
            ->assertSessionHas('error', 'The last administrator role cannot be removed.');

        $this->assertTrue($admin->fresh()->hasRole(RoleSlug::Admin));

        $other = $this->createUserWithRole(RoleSlug::Author);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => User::class,
            'auditable_id' => $other->id,
        ]);
    }

    public function test_another_admin_cannot_delete_the_last_remaining_admin(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $second = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $second))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'User deleted.');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $second->id]);
    }

    public function test_the_last_active_administrator_cannot_be_deactivated(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $second = $this->createUserWithRole(RoleSlug::Admin);
        $authorRoleId = Role::query()->where('slug', RoleSlug::Author->value)->valueOrFail('id');
        $adminRoleId = Role::query()->where('slug', RoleSlug::Admin->value)->valueOrFail('id');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $second), [
                'name' => $second->name,
                'email' => $second->email,
                'role_ids' => [$adminRoleId],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($second->fresh()->is_active);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role_ids' => [$adminRoleId],
            ])
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
        $this->assertTrue($admin->fresh()->hasRole(RoleSlug::Admin));

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'is_active' => '1',
                'role_ids' => [$authorRoleId],
            ])
            ->assertSessionHas('error', 'The last administrator role cannot be removed.');

        $this->assertTrue($admin->fresh()->hasRole(RoleSlug::Admin));
    }
}
