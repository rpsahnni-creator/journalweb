<?php

namespace App\Console\Commands;

use App\Enums\RoleSlug;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create or update an administrator from ADMIN_EMAIL and ADMIN_PASSWORD (or interactive prompts).';

    public function handle(): int
    {
        $email = $this->resolveEmail();

        if ($email === null) {
            $this->error('A valid admin email is required.');

            return self::FAILURE;
        }

        $password = $this->resolvePassword();

        if ($password === null) {
            $this->error('An admin password is required.');

            return self::FAILURE;
        }

        if (strlen($password) < 12) {
            $this->error('The admin password must be at least 12 characters. The account was not created or updated.');

            return self::FAILURE;
        }

        $role = Role::query()->where('slug', RoleSlug::Admin->value)->first();

        if ($role === null) {
            $this->error('The admin role is missing. Run `php artisan db:seed --class=RoleSeeder` first.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => User::query()->where('email', $email)->value('name') ?: 'Administrator',
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
                'is_editor' => true,
            ]
        );

        $user->assignRole($role);

        $this->info($user->wasRecentlyCreated
            ? 'Administrator created for '.$user->email.'.'
            : 'Administrator updated for '.$user->email.'.');
        $this->comment('Remove ADMIN_PASSWORD from .env if you set it there.');

        return self::SUCCESS;
    }

    private function resolveEmail(): ?string
    {
        $email = $this->environmentValue('ADMIN_EMAIL') ?? $this->ask('Admin email');
        $email = is_string($email) ? strtolower(trim($email)) : '';

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function resolvePassword(): ?string
    {
        $password = $this->environmentValue('ADMIN_PASSWORD');

        if ($password === null) {
            $password = $this->secret('Admin password');
        }

        return is_string($password) && $password !== '' ? $password : null;
    }

    private function environmentValue(string $key): ?string
    {
        $value = env($key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
