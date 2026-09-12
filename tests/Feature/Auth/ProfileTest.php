<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'academic_title' => 'Dr',
            'affiliation' => 'Updated University',
            'orcid' => null,
            'biography' => 'A short biography.',
        ])->assertRedirect();

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('Updated University', $user->affiliation);
    }

    public function test_password_can_be_updated_and_is_hashed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'password',
            'password' => 'updated-password',
            'password_confirmation' => 'updated-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('updated-password', $user->fresh()->password));
        $this->assertNotSame('updated-password', $user->fresh()->password);
    }

    public function test_correct_current_password_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'updated-password',
            'password_confirmation' => 'updated-password',
        ])->assertSessionHasErrors('current_password');
    }

    public function test_profile_update_cannot_mass_assign_privileged_fields(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'last_login_at' => null,
        ]);
        $originalPassword = $user->password;

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'academic_title' => 'Dr',
            'affiliation' => 'Updated University',
            'orcid' => null,
            'biography' => 'A short biography.',
            'is_active' => '0',
            'password' => 'hijacked-password',
            'last_login_at' => '2020-01-01 00:00:00',
        ])->assertRedirect();

        $user->refresh();

        $this->assertTrue($user->is_active);
        $this->assertNull($user->last_login_at);
        $this->assertSame($originalPassword, $user->password);
        $this->assertSame('Updated Name', $user->name);
    }
}
