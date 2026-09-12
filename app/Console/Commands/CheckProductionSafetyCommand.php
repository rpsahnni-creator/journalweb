<?php

namespace App\Console\Commands;

use App\Support\ProductionSafety;
use Illuminate\Console\Command;

class CheckProductionSafetyCommand extends Command
{
    protected $signature = 'journal:check-production-safety';

    protected $description = 'Warn if production still has the default seeded admin password or APP_DEBUG enabled.';

    public function handle(): int
    {
        if (! app()->environment('production')) {
            $this->info('Production safety checks run only when APP_ENV=production.');

            return self::SUCCESS;
        }

        $warned = ProductionSafety::warnIfDebugEnabled()
            || ProductionSafety::warnIfDefaultAdminExists();

        if ($warned) {
            $this->warn('Production safety warnings were written to the application log. Credentials were not changed.');
        } else {
            $this->info('No default-admin or debug-mode production warnings.');
        }

        return self::SUCCESS;
    }
}
