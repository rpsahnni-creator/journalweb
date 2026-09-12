<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\ReviewerAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewerInvitationNotification extends JournalMailNotification
{
    public function __construct(
        public ReviewerAssignment $assignment,
        public string $actionUrl,
    ) {
        $this->assignment->loadMissing(['article']);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ReviewerInvitation;
    }

    public function logSubject(): string
    {
        return 'Review invitation for '.$this->assignment->article->submission_number;
    }

    public function logBody(): string
    {
        return 'You have been invited to review “'.$this->assignment->article->title.'”. Please accept or decline the invitation.';
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
            'article_id' => $this->assignment->article_id,
            'due_at' => $this->assignment->due_at?->toDateString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.reviewer-invitation', [
                'assignment' => $this->assignment,
                'article' => $this->assignment->article,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
