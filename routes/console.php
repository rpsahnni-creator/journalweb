<?php

use App\Console\Commands\SendReviewRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SendReviewRemindersCommand::class)->daily();
Schedule::command('journal:check-production-safety')->daily();
