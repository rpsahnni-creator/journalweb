<?php

namespace App\Support;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProductionSafety
{
    public const DEFAULT_ADMIN_WARNING = 'SECURITY: The default seeded admin (admin@example.com) still exists with the default password. Change this credential before serving journal.srtc.ac.in. The account was not deleted automatically.';

    public const DEBUG_WARNING = 'SECURITY: APP_DEBUG is enabled while APP_ENV=production. Debug mode must be false before deploying to journal.srtc.ac.in — stack traces and file paths would leak publicly.';

    /**
     * Log loud warnings for leftover development credentials and debug mode.
     * Does not delete or mutate any accounts.
     */
    public static function check(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        self::warnIfDebugEnabled();
        self::warnIfDefaultAdminExists();
    }

    public static function warnIfDebugEnabled(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        $debugWasRequested = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);

        if (! $debugWasRequested && ! (bool) config('app.debug')) {
            return false;
        }

        Log::warning(self::DEBUG_WARNING);

        return true;
    }

    public static function warnIfDefaultAdminExists(): bool
    {
        if (! app()->environment('production')) {
            return false;
        }

        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            $admin = User::query()->where('email', AdminUserSeeder::EMAIL)->first();

            if ($admin === null || ! Hash::check(AdminUserSeeder::PASSWORD, $admin->password)) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        Log::warning(self::DEFAULT_ADMIN_WARNING);

        return true;
    }
}
