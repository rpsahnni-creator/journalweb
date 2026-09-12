<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends JournalMailNotification
{
    public function __construct(
        #[\SensitiveParameter]
        public string $token,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::PasswordReset;
    }

    public function logSubject(): string
    {
        return 'Password reset link';
    }

    public function logBody(): string
    {
        return 'A password reset link was sent. The token is not stored in this log.';
    }

    public function related(): ?Model
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function logData(): array
    {
        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.password-reset', [
                'actionUrl' => url(route('password.reset', [
                    'token' => $this->token,
                    'email' => $email,
                ], false)),
                'expireMinutes' => $minutes,
            ]);
    }
}
