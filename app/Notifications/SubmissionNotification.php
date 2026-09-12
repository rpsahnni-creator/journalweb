<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class SubmissionNotification extends Notification
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function actionUrl(): string;

    abstract public function actionLabel(): string;

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->actionUrl(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.$this->notifiableName($notifiable).',')
            ->line($this->message())
            ->action($this->actionLabel(), $this->actionUrl())
            ->line('This message was sent by '.config('app.name').'. Unpublished manuscripts remain private.');
    }

    protected function notifiableName(object $notifiable): string
    {
        $name = $notifiable->name ?? null;

        return filled($name) ? (string) $name : 'there';
    }
}
