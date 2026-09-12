<?php

namespace Tests\Feature\Admin;

use App\Enums\IssueStatus;
use App\Enums\RoleSlug;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogAndPlaceholdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_lists_and_filters_recorded_changes(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $user = User::factory()->create(['name' => 'Audited User']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Audited User Updated',
                'email' => $user->email,
                'is_active' => '1',
                'role_ids' => [],
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('updated', false)
            ->assertSee($admin->name, false)
            ->assertSee('User #'.$user->id, false);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['action' => 'deleted']))
            ->assertOk()
            ->assertDontSee('User #'.$user->id, false);

        $this->assertGreaterThan(0, AuditLog::query()->where('action', 'updated')->count());
    }

    public function test_volume_and_issue_placeholders_list_existing_records(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::factory()->create();
        $volume = Volume::factory()->create([
            'journal_id' => $journal->id,
            'number' => 3,
            'year' => 2026,
            'title' => 'Development volume',
        ]);
        Issue::factory()->create([
            'journal_id' => $journal->id,
            'volume_id' => $volume->id,
            'number' => 2,
            'status' => IssueStatus::Draft,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.volumes.index'))
            ->assertOk()
            ->assertSee('Development volume', false)
            ->assertSee('read-only', false)
            ->assertDontSee('Create volume', false);

        $this->actingAs($admin)
            ->get(route('admin.issues.index'))
            ->assertOk()
            ->assertSee('Vol. 3 No. 2', false)
            ->assertSee('Add issue', false);
    }
}
