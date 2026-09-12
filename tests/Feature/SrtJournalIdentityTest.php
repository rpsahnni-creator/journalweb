<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use App\Support\JournalCopy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SrtJournalIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_settings_appear_on_home_footer_and_plagiarism_pages(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('SRT Journal of Multidisciplinary Research', false)
            ->assertSee('peer-reviewed, refereed academic journal', false)
            ->assertSee('SRT-JMR aims to promote quality academic research', false)
            ->assertSee(JournalCopy::PUBLISHER_NAME, false)
            ->assertSee(JournalCopy::PUBLISHER_ADDRESS, false)
            ->assertSee(JournalCopy::PUBLISHER_EMAIL, false)
            ->assertSee('Plagiarism Policy', false)
            ->assertDontSee('journal.srtc.ac.in', false);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Santhal Pargana', false);

        $this->get(route('aims-and-scope'))
            ->assertOk()
            ->assertSee('young researchers affiliated with colleges of the Santhal Pargana region', false);

        $this->get(route('plagiarism-policy'))
            ->assertOk()
            ->assertSee('Plagiarism Policy', false)
            ->assertSee('strict zero-tolerance policy on plagiarism', false)
            ->assertSee('the article will be retracted', false);

        $this->get(route('editorial-board'))
            ->assertOk()
            ->assertSee('Dr. Shambhu Kumar Singh', false)
            ->assertSee('Editor-in-Chief', false)
            ->assertSee('S.R.T. College, Dhamni', false)
            ->assertSee('Mr. A.K. Nath', false)
            ->assertSee('Mr. A.K.', false)
            ->assertSee('Mr. B.K.', false)
            ->assertSee('Mr. C.K.', false)
            ->assertSee('Department of Sociology', false)
            ->assertSee('Department of Political Science', false)
            ->assertDontSee(JournalCopy::PLACEHOLDER_BOARD_NAME, false)
            ->assertDontSee('Name Pending', false);
    }

    public function test_admin_editorial_board_reports_no_pending_names_after_real_roster(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($admin)
            ->get(route('admin.editorial-board.index'))
            ->assertOk()
            ->assertSee('Dr. Shambhu Kumar Singh', false)
            ->assertSee('Mr. A.K. Nath', false)
            ->assertSee('Mr. A.K.', false)
            ->assertSee('Mr. B.K.', false)
            ->assertSee('Mr. C.K.', false)
            ->assertDontSee(JournalCopy::PLACEHOLDER_BOARD_NAME, false)
            ->assertDontSee('still need real names before ISSN submission.', false);
    }
}
