<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestMailNotification;
use App\Support\Notifier;
use Illuminate\Console\Command;

class SendTestMailCommand extends Command
{
    protected $signature = 'mail:test {email? : Address that should receive the test message}';

    protected $description = 'Send a development test email using MAIL_* settings from the environment.';

    public function handle(): int
    {
        if (! config('mail.testing.enabled')) {
            $this->error('Mail testing is disabled. Set MAIL_TEST_ENABLED=true in .env on a development machine.');

            return self::FAILURE;
        }

        $email = $this->argument('email') ?: config('mail.testing.address') ?: config('mail.from.address');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid email address: php artisan mail:test you@example.com');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            Notifier::notify($user, new TestMailNotification);
        } else {
            Notifier::mail($email, new TestMailNotification);
            $this->warn('No user account uses that address, so a notification log row was not stored.');
        }

        $this->info('Test message queued through the "'.config('mail.default').'" mailer to '.$email.'.');
        $this->comment('SMTP host, username, and password come from .env. They are not hardcoded.');

        return self::SUCCESS;
    }
}
