<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_away_from_protected_portals(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('editorial.dashboard'))->assertRedirect(route('login'));
        $this->get(route('reviewer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('author.dashboard'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_unverified_users_cannot_access_portals(): void
    {
        $user = $this->createUserWithRole(RoleSlug::Author, ['email_verified_at' => null]);

        $this->actingAs($user)
            ->get(route('author.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_authors_cannot_access_admin_or_reviewer_routes(): void
    {
        $author = $this->createUserWithRole(RoleSlug::Author);

        $this->actingAs($author)->get(route('author.dashboard'))->assertOk();
        $this->actingAs($author)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($author)->get(route('reviewer.dashboard'))->assertForbidden();
        $this->actingAs($author)->get(route('editorial.dashboard'))->assertForbidden();
    }

    public function test_reviewers_cannot_access_admin_or_author_routes(): void
    {
        $reviewer = $this->createUserWithRole(RoleSlug::Reviewer);

        $this->actingAs($reviewer)->get(route('reviewer.dashboard'))->assertOk();
        $this->actingAs($reviewer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('author.dashboard'))->assertForbidden();
    }

    public function test_readers_cannot_access_staff_portals(): void
    {
        $reader = $this->createUserWithRole(RoleSlug::Reader);

        $this->actingAs($reader)->get(route('dashboard'))->assertOk();
        $this->actingAs($reader)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($reader)->get(route('editorial.dashboard'))->assertForbidden();
        $this->actingAs($reader)->get(route('reviewer.dashboard'))->assertForbidden();
        $this->actingAs($reader)->get(route('author.dashboard'))->assertForbidden();
    }

    public function test_journal_managers_and_editors_can_access_editorial_but_not_admin(): void
    {
        $manager = $this->createUserWithRole(RoleSlug::JournalManager);
        $editorInChief = $this->createUserWithRole(RoleSlug::EditorInChief);
        $sectionEditor = $this->createUserWithRole(RoleSlug::SectionEditor);

        $this->actingAs($manager)->get(route('editorial.dashboard'))->assertOk();
        $this->actingAs($manager)->get(route('admin.dashboard'))->assertForbidden();

        $this->actingAs($editorInChief)->get(route('editorial.dashboard'))->assertOk();
        $this->actingAs($sectionEditor)->get(route('editorial.dashboard'))->assertOk();
    }

    public function test_admins_can_access_protected_portals(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('editorial.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('reviewer.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('author.dashboard'))->assertOk();
    }
}
