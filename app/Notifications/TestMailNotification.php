<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class TestMailNotification extends JournalMailNotification
{
    public function notificationType(): NotificationType
    {
        return NotificationType::MailTest;
    }

    public function logSubject(): string
    {
        return 'Development mail test from '.config('app.name');
    }

    public function logBody(): string
    {
        return 'This is a development test message. SMTP credentials are read from the environment, not from application code.';
    }

    public function related(): ?Model
    {
        return null;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.mail-test', [
                'appName' => config('app.name'),
                'mailer' => config('mail.default'),
            ]);
    }
}
