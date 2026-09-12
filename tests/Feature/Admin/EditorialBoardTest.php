<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Models\EditorialBoardMember;
use App\Models\Journal;
use App\Support\JournalCopy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_editorial_board_members_and_control_public_visibility(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.editorial-board.create'))
            ->followingRedirects()
            ->post(route('admin.editorial-board.store'), [
                'name' => 'Public Board Scholar',
                'role_title' => 'Associate Editor',
                'affiliation' => 'Example Department',
                'country' => 'India',
                'sort_order' => 1,
                'is_public' => '1',
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertSee('Editorial board member added.', false)
            ->assertSee('Public Board Scholar', false);

        $public = EditorialBoardMember::query()->where('name', 'Public Board Scholar')->firstOrFail();

        $this->get(route('editorial-board'))
            ->assertOk()
            ->assertSee('Public Board Scholar', false);

        $this->actingAs($admin)
            ->put(route('admin.editorial-board.update', $public), [
                'name' => 'Public Board Scholar',
                'role_title' => 'Associate Editor',
                'affiliation' => 'Example Department',
                'country' => 'India',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.editorial-board.index'));

        $this->assertFalse($public->fresh()->is_public);
        $this->get(route('editorial-board'))->assertDontSee('Public Board Scholar', false);

        $private = EditorialBoardMember::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'Private Board Scholar',
            'is_public' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.editorial-board.index', ['visibility' => 'private', 'q' => 'Private Board']))
            ->assertOk()
            ->assertSee('Private Board Scholar', false)
            ->assertDontSee('Public Board Scholar', false);

        $this->actingAs($admin)
            ->delete(route('admin.editorial-board.destroy', $private))
            ->assertRedirect(route('admin.editorial-board.index'))
            ->assertSessionHas('status', 'Editorial board member removed.');

        $this->assertDatabaseMissing('editorial_board_members', ['id' => $private->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'auditable_type' => EditorialBoardMember::class,
            'auditable_id' => $private->id,
        ]);
    }

    public function test_admin_warning_appears_only_when_a_placeholder_name_remains(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.editorial-board.index'))
            ->assertOk()
            ->assertDontSee('still need real names before ISSN submission.', false);

        EditorialBoardMember::factory()->create([
            'journal_id' => $journal->id,
            'name' => JournalCopy::PLACEHOLDER_BOARD_NAME,
            'is_public' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.editorial-board.index'))
            ->assertOk()
            ->assertSee('still need real names before ISSN submission.', false)
            ->assertSee(JournalCopy::PLACEHOLDER_BOARD_NAME, false);
    }

    public function test_board_members_cannot_be_added_before_a_journal_exists(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->post(route('admin.editorial-board.store'), [
                'name' => 'Early Member',
                'role_title' => 'Editor',
            ])
            ->assertRedirect(route('admin.journal.edit'))
            ->assertSessionHas('error', 'Create the journal before adding board members.');
    }
}
