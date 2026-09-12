<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_development_admin_only_in_the_local_environment(): void
    {
        $this->app['env'] = 'local';
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_editor);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check(AdminUserSeeder::PASSWORD, $admin->password));
    }

    public function test_it_does_not_create_the_development_admin_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => AdminUserSeeder::EMAIL]);
    }
}
