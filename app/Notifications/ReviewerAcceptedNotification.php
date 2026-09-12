<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\ReviewerAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewerAcceptedNotification extends JournalMailNotification
{
    public function __construct(
        public ReviewerAssignment $assignment,
        public string $actionUrl,
    ) {
        $this->assignment->loadMissing(['article', 'reviewer']);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ReviewerAccepted;
    }

    public function logSubject(): string
    {
        return 'Review invitation accepted for '.$this->assignment->article->submission_number;
    }

    public function logBody(): string
    {
        return $this->assignment->reviewer->name.' accepted the invitation to review “'.$this->assignment->article->title.'”.';
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
        return ['assignment_id' => $this->assignment->id];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.reviewer-accepted', [
                'assignment' => $this->assignment,
                'article' => $this->assignment->article,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
