<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

abstract class JournalMailNotification extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    abstract public function notificationType(): NotificationType;

    abstract public function logSubject(): string;

    abstract public function logBody(): string;

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
}
