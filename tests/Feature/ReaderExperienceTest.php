<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Journal;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_renders_hindi_chrome_without_translating_policy_bodies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->from(route('home'))
            ->get(route('locale', ['locale' => 'hi']))
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('lang="hi"', false)
            ->assertSee('मुखपृष्ठ', false)
            ->assertSee('पत्रिका', false)
            ->assertSee('वर्तमान अंक के लिए शोध-पत्र आमंत्रित हैं।', false);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('मुखपृष्ठ', false)
            ->assertSee('About the Journal', false);
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }

    public function test_public_pages_expose_pwa_and_theme_controls(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('toggleTheme()', false)
            ->assertSee(__('ui.dark_mode'), false);

        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')
            ->assertSee('SRT Journal', false)
            ->assertSee('/images/pwa-192.png', false);

        $this->get(route('pwa.service-worker'))
            ->assertOk()
            ->assertSee('srtjmr-shell-v1', false)
            ->assertSee('admin|editorial|reviewer|author|submissions', false);

        $this->assertFileExists(public_path('images/pwa-192.png'));
        $this->assertFileExists(public_path('images/pwa-512.png'));
    }

    public function test_altmetric_badge_appears_only_for_a_real_doi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $journal = Journal::query()->firstOrFail();

        $withoutDoi = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Article without a DOI',
            'is_demo' => false,
            'doi' => null,
        ]);

        $placeholder = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Article with placeholder DOI',
            'is_demo' => false,
            'doi' => '10.XXXX/srtjmr.demo',
        ]);

        $real = Article::factory()->published()->create([
            'journal_id' => $journal->id,
            'title' => 'Article with a registered DOI',
            'is_demo' => false,
            'doi' => '10.12345/srtjmr.2026.1.1.1',
        ]);

        $this->get(route('articles.show', $withoutDoi))
            ->assertOk()
            ->assertDontSee('altmetric-embed', false)
            ->assertDontSee('badge.altmetric.com', false)
            ->assertDontSee('d1bxh8uas1mnw7.cloudfront.net', false);

        $this->get(route('articles.show', $placeholder))
            ->assertOk()
            ->assertSee('10.XXXX/srtjmr.demo', false)
            ->assertDontSee('altmetric-embed', false)
            ->assertDontSee('d1bxh8uas1mnw7.cloudfront.net', false);

        $this->get(route('articles.show', $real))
            ->assertOk()
            ->assertSee('class="altmetric-embed"', false)
            ->assertSee('data-doi="10.12345/srtjmr.2026.1.1.1"', false)
            ->assertSee('d1bxh8uas1mnw7.cloudfront.net/assets/embed.js', false);
    }
}
