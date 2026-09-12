<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleSlug;
use App\Models\Journal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_journal_settings_without_issn(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->from(route('admin.journal.edit'))
            ->followingRedirects()
            ->put(route('admin.journal.update'), [
                'name' => 'Campus Research Journal',
                'slug' => 'campus-research-journal',
                'abbreviation' => 'CRJ',
                'description' => 'A campus journal for published research.',
                'publisher' => 'Campus Press',
                'website_url' => 'https://example.com/journal',
                'issn' => null,
                'eissn' => null,
                'is_active' => '1',
                'contact_email' => 'journal@example.com',
                'contact_address' => 'Campus Mail Stop 1',
                'seo_description' => 'Campus journal public description',
            ])
            ->assertOk()
            ->assertSee('Journal created.', false);

        $journal = Journal::query()->where('slug', 'campus-research-journal')->firstOrFail();

        $this->assertNull($journal->issn);
        $this->assertNull($journal->eissn);
        $this->assertNull($journal->setting('issn'));
        $this->assertSame('journal@example.com', $journal->setting('contact_email'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => Journal::class,
            'auditable_id' => $journal->id,
        ]);
    }

    public function test_admin_can_update_existing_journal_settings(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::factory()->create([
            'name' => 'Original Journal',
            'slug' => 'original-journal',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.journal.update'), [
                'name' => 'Updated Journal Name',
                'slug' => 'original-journal',
                'abbreviation' => $journal->abbreviation,
                'description' => 'Updated description',
                'publisher' => 'Updated Press',
                'is_active' => '1',
                'contact_email' => 'office@example.com',
            ])
            ->assertRedirect(route('admin.journal.edit'))
            ->assertSessionHas('status', 'Journal settings saved.');

        $this->assertSame('Updated Journal Name', $journal->fresh()->name);
        $this->assertSame('office@example.com', $journal->fresh()->setting('contact_email'));
        $this->assertNull($journal->fresh()->setting('issn'));
    }

    public function test_admin_can_save_issn_to_journal_settings(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::factory()->create([
            'name' => 'Campus Research Journal',
            'slug' => 'campus-research-journal',
            'issn' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.journal.update'), [
                'name' => $journal->name,
                'slug' => $journal->slug,
                'issn' => '1234-5678',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.journal.edit'));

        $journal->refresh();

        $this->assertSame('1234-5678', $journal->issn);
        $this->assertSame('1234-5678', $journal->setting('issn'));
        $this->assertSame('1234-5678', $journal->citationIssn());
    }

    public function test_updating_publication_frequency_in_admin_updates_the_public_about_page(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $journal = Journal::query()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.journal.update'), [
                'name' => $journal->name,
                'slug' => $journal->slug,
                'abbreviation' => $journal->abbreviation,
                'description' => $journal->description,
                'publisher' => $journal->publisher,
                'is_active' => '1',
                'publication_frequency' => 'Quarterly — March, June, September, and December.',
                'publisher_website' => 'https://college.example.test',
                'indexing_status' => 'Indexed in a campus discovery service for verification only.',
            ])
            ->assertRedirect(route('admin.journal.edit'));

        $this->assertSame(
            'Quarterly — March, June, September, and December.',
            $journal->fresh()->setting('publication_frequency')
        );
        $this->assertSame('https://college.example.test', $journal->fresh()->setting('publisher_website'));
        $this->assertSame(
            'Indexed in a campus discovery service for verification only.',
            $journal->fresh()->setting('indexing_status')
        );

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Quarterly — March, June, September, and December.', false)
            ->assertDontSee('Biannual — published every June and December.', false)
            ->assertSee('Indexed in a campus discovery service for verification only.', false)
            ->assertDontSee('is not yet indexed in any external abstracting or indexing database', false);

        $this->get(route('publisher'))
            ->assertOk()
            ->assertSee('https://college.example.test', false);
    }

    public function test_journal_settings_require_a_name_and_slug(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->from(route('admin.journal.edit'))
            ->followingRedirects()
            ->put(route('admin.journal.update'), [
                'name' => '',
                'slug' => '',
            ])
            ->assertOk()
            ->assertSee('Please correct the highlighted fields.', false)
            ->assertSee('The name field is required.', false);
    }
}
