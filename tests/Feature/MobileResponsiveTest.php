<?php

namespace Tests\Feature;

use App\Enums\RoleSlug;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileResponsiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_use_a_mobile_viewport_and_menu(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            route('home'),
            route('about'),
            route('articles.index'),
            route('issues.index'),
            route('contact'),
            route('login'),
            route('register'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('width=device-width, initial-scale=1, viewport-fit=cover', false)
                ->assertSee('aria-controls="primary-navigation"', false)
                ->assertSee('lg:hidden', false);
        }
    }

    public function test_workspace_layouts_use_the_mobile_viewport(): void
    {
        $this->seed(DatabaseSeeder::class);

        $author = $this->createUserWithRole(RoleSlug::Author);
        $admin = $this->createUserWithRole(RoleSlug::Admin);

        $this->actingAs($author)
            ->get(route('author.dashboard'))
            ->assertOk()
            ->assertSee('width=device-width, initial-scale=1, viewport-fit=cover', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('width=device-width, initial-scale=1, viewport-fit=cover', false);
    }
}
