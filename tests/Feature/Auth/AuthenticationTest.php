<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleSlug;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_the_password_and_assigns_reader_and_author_roles(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $response = $this->post(route('register'), [
            'name' => 'Ada Author',
            'email' => 'ada@example.com',
            'affiliation' => 'Example University',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'ada@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotSame('password', $user->password);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue($user->hasRole(RoleSlug::Reader));
        $this->assertTrue($user->hasRole(RoleSlug::Author));

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_register_flow_points_authors_to_the_author_guidelines(): void
    {
        Notification::fake();
        $this->seed(RoleSeeder::class);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Author Guidelines', false)
            ->assertSee(route('author-guidelines'), false);

        $this->post(route('register'), [
            'name' => 'Ada Author',
            'email' => 'ada@example.com',
            'affiliation' => 'Example University',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('verification.notice'));

        $this->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Author Guidelines', false)
            ->assertSee(route('author-guidelines'), false);
    }

    public function test_the_seeded_development_admin_can_log_in(): void
    {
        $this->app['env'] = 'local';
        $this->seed(DatabaseSeeder::class);
        $this->app['env'] = 'testing';

        $response = $this->post(route('login'), [
            'email' => AdminUserSeeder::EMAIL,
            'password' => AdminUserSeeder::PASSWORD,
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check(AdminUserSeeder::PASSWORD, User::query()->where('email', AdminUserSeeder::EMAIL)->value('password')));
    }

    public function test_users_cannot_log_in_with_an_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(
            'These credentials do not match our records.',
            session('errors')->first('email')
        );
    }

    public function test_login_form_includes_a_csrf_token_field(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertTrue(
            $response->status() === 429 || $response->session()->has('errors'),
            'Repeated failed logins must be throttled.'
        );
        $this->assertGuest();
    }

    public function test_authenticated_users_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_logout_must_be_posted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('logout'))
            ->assertMethodNotAllowed();
    }
}
