<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function adminGetRoutes(): array
    {
        return [
            'admin.dashboard',
            'admin.users.index',
            'admin.users.create',
            'admin.roles.index',
            'admin.roles.create',
            'admin.journal.edit',
            'admin.editorial-board.index',
            'admin.editorial-board.create',
            'admin.policies.index',
            'admin.policies.create',
            'admin.volumes.index',
            'admin.issues.index',
            'admin.audit-logs.index',
        ];
    }

    public function test_guests_are_redirected_from_admin_pages(): void
    {
        foreach ($this->adminGetRoutes() as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_authors_cannot_access_admin_pages_or_actions(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author);

        foreach ($this->adminGetRoutes() as $route) {
            $this->actingAs($author)->get(route($route))->assertForbidden();
        }

        $this->actingAs($author)->post(route('admin.users.store'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    public function test_journal_managers_cannot_access_the_admin_panel(): void
    {
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);

        $this->actingAs($manager)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($manager)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($manager)->put(route('admin.journal.update'), [
            'name' => 'Changed',
            'slug' => 'changed',
        ])->assertForbidden();
    }

    public function test_admins_can_open_the_dashboard_and_sidebar_sections(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard', false)
            ->assertSee('Users', false)
            ->assertSee('Roles', false)
            ->assertSee('Journal settings', false)
            ->assertSee('Editorial board', false)
            ->assertSee('Policies', false)
            ->assertSee('Volumes', false)
            ->assertSee('Issues', false)
            ->assertSee('Audit log', false);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.journal.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.editorial-board.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.policies.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.volumes.index'))->assertOk()->assertSee('read-only', false);
        $this->actingAs($admin)->get(route('admin.issues.index'))->assertOk()->assertSee('Add issue', false);
        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_unverified_admins_cannot_use_the_admin_panel(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin, ['email_verified_at' => null]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_inactive_admins_cannot_use_the_admin_panel(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin, ['is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
