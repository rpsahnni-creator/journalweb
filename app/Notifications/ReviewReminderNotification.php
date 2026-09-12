<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\ReviewerAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewReminderNotification extends JournalMailNotification
{
    public function __construct(
        public ReviewerAssignment $assignment,
        public string $actionUrl,
    ) {
        $this->assignment->loadMissing(['article']);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ReviewReminder;
    }

    public function logSubject(): string
    {
        return 'Reminder: review due for '.$this->assignment->article->submission_number;
    }

    public function logBody(): string
    {
        $due = $this->assignment->due_at?->toFormattedDateString() ?: 'soon';

        return 'Please complete your review of “'.$this->assignment->article->title.'” (due '.$due.').';
    }

    public function related(): ?Model
    {
        return $this->assignment;
    }

    /**
     * @return array<string, mixed>
     */
    public function logData(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'due_at' => $this->assignment->due_at?->toDateString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.review-reminder', [
                'assignment' => $this->assignment,
                'article' => $this->assignment->article,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
