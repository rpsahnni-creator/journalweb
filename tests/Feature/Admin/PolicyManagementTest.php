<?php

namespace Tests\Feature\Admin;

use App\Enums\JournalPolicyType;
use App\Enums\RoleSlug;
use App\Models\Journal;
use App\Models\JournalPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_policy_updates_to_the_public_page(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::factory()->create();
        $policy = JournalPolicy::factory()->published()->create([
            'journal_id' => $journal->id,
            'type' => JournalPolicyType::About->value,
            'title' => 'About the Journal',
            'slug' => 'about',
            'body' => 'Original about text.',
        ]);

        $this->get(route('about'))->assertSee('Original about text.', false);

        $this->actingAs($admin)
            ->from(route('admin.policies.edit', $policy))
            ->followingRedirects()
            ->put(route('admin.policies.update', $policy), [
                'type' => JournalPolicyType::About->value,
                'title' => 'About the Journal',
                'slug' => 'about',
                'body' => 'Updated about text for the public site.',
                'is_published' => '1',
            ])
            ->assertOk()
            ->assertSee('Policy updated.', false);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Updated about text for the public site.', false)
            ->assertDontSee('Original about text.', false);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'updated',
            'auditable_type' => JournalPolicy::class,
            'auditable_id' => $policy->id,
        ]);
    }

    public function test_admin_can_create_and_unpublish_a_policy(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        Journal::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.policies.store'), [
                'type' => JournalPolicyType::Plagiarism->value,
                'title' => 'Plagiarism Policy',
                'slug' => 'plagiarism-policy',
                'body' => 'Similarity screening description.',
                'is_published' => '1',
            ])
            ->assertRedirect(route('admin.policies.index'))
            ->assertSessionHas('status', 'Policy created.');

        $policy = JournalPolicy::query()->where('slug', 'plagiarism-policy')->firstOrFail();

        $this->get(route('plagiarism-policy'))->assertSee('Similarity screening description.', false);

        $this->actingAs($admin)
            ->put(route('admin.policies.update', $policy), [
                'type' => JournalPolicyType::Plagiarism->value,
                'title' => 'Plagiarism Policy',
                'slug' => 'plagiarism-policy',
                'body' => 'Similarity screening description.',
            ])
            ->assertRedirect(route('admin.policies.index'));

        $this->assertFalse($policy->fresh()->is_published);
        $this->get(route('plagiarism-policy'))->assertDontSee('Similarity screening description.', false);

        $this->actingAs($admin)
            ->get(route('admin.policies.index', ['q' => 'Plagiarism', 'type' => JournalPolicyType::Plagiarism->value, 'status' => 'draft']))
            ->assertOk()
            ->assertSee('Plagiarism Policy', false);
    }

    public function test_policy_create_shows_validation_errors(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        Journal::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.policies.create'))
            ->followingRedirects()
            ->post(route('admin.policies.store'), [
                'type' => '',
                'title' => '',
                'slug' => '',
                'body' => '',
            ])
            ->assertOk()
            ->assertSee('Please correct the highlighted fields.', false)
            ->assertSee('The title field is required.', false);
    }
}
