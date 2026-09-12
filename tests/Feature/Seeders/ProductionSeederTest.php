<?php

namespace Tests\Feature\Seeders;

use App\Models\Article;
use App\Models\Journal;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_bootstrap_creates_roles_and_journal_without_demo_users_or_articles(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => ProductionSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertTrue(Role::query()->where('slug', 'admin')->exists());
        $this->assertTrue(Journal::query()->where('slug', 'academic-journal')->exists());
        $this->assertDatabaseMissing('users', ['email' => AdminUserSeeder::EMAIL]);
        $this->assertSame(0, Article::query()->where('is_demo', true)->count());
        $this->assertSame(0, User::query()->count());
    }

    public function test_full_database_seeder_skips_demo_content_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => AdminUserSeeder::EMAIL]);
        $this->assertSame(0, Article::query()->where('is_demo', true)->count());
        $this->assertTrue(Journal::query()->where('slug', 'academic-journal')->exists());
    }
}
