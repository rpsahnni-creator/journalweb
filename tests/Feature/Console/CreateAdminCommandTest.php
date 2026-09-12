<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->clearAdminEnvironment();

        parent::tearDown();
    }

    public function test_it_creates_an_editor_admin_from_environment_variables(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setAdminEnvironment('editor@srtc.ac.in', 'deploy-password-12');

        $this->artisan('admin:create')
            ->assertSuccessful()
            ->expectsOutputToContain('Administrator created');

        $admin = User::query()->where('email', 'editor@srtc.ac.in')->firstOrFail();

        $this->assertTrue($admin->is_editor);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('deploy-password-12', $admin->password));
        $this->assertNotSame('deploy-password-12', $admin->password);
    }

    public function test_it_updates_an_existing_account_without_creating_a_duplicate(): void
    {
        $this->seed(RoleSeeder::class);
        $existing = User::factory()->create([
            'email' => 'editor@srtc.ac.in',
            'name' => 'Existing Editor',
            'is_editor' => false,
            'password' => 'old-password-12',
        ]);

        $this->setAdminEnvironment('editor@srtc.ac.in', 'rotated-password-12');

        $this->artisan('admin:create')
            ->assertSuccessful()
            ->expectsOutputToContain('Administrator updated');

        $this->assertSame(1, User::query()->where('email', 'editor@srtc.ac.in')->count());

        $existing->refresh();
        $this->assertTrue($existing->is_editor);
        $this->assertSame('Existing Editor', $existing->name);
        $this->assertTrue(Hash::check('rotated-password-12', $existing->password));
    }

    public function test_it_refuses_a_password_shorter_than_twelve_characters(): void
    {
        $this->seed(RoleSeeder::class);
        $this->setAdminEnvironment('editor@srtc.ac.in', 'tooshort');

        $this->artisan('admin:create')
            ->assertFailed()
            ->expectsOutputToContain('at least 12 characters');

        $this->assertDatabaseMissing('users', ['email' => 'editor@srtc.ac.in']);
    }

    public function test_it_prompts_when_environment_variables_are_missing(): void
    {
        $this->seed(RoleSeeder::class);
        $this->clearAdminEnvironment();

        $this->artisan('admin:create')
            ->expectsQuestion('Admin email', 'prompted@srtc.ac.in')
            ->expectsQuestion('Admin password', 'prompted-pass-12')
            ->assertSuccessful();

        $this->assertTrue(User::query()->where('email', 'prompted@srtc.ac.in')->exists());
    }

    private function setAdminEnvironment(string $email, string $password): void
    {
        Env::getRepository()->set('ADMIN_EMAIL', $email);
        Env::getRepository()->set('ADMIN_PASSWORD', $password);
    }

    private function clearAdminEnvironment(): void
    {
        Env::getRepository()->clear('ADMIN_EMAIL');
        Env::getRepository()->clear('ADMIN_PASSWORD');
    }
}
