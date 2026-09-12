<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public const EMAIL = 'admin@example.com';

    public const PASSWORD = 'password';

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Development Admin',
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'is_active' => true,
                'is_editor' => true,
            ]
        );

        $admin->assignRole(Role::query()->where('slug', 'admin')->firstOrFail());
    }
}
