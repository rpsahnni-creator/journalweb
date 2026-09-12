<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewSubmittedNotification extends JournalMailNotification
{
    public function __construct(
        public ReviewerAssignment $assignment,
        public Review $review,
        public string $actionUrl,
    ) {
        $this->assignment->loadMissing(['article', 'reviewer']);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ReviewSubmitted;
    }

    public function logSubject(): string
    {
        return 'Review submitted for '.$this->assignment->article->submission_number;
    }

    public function logBody(): string
    {
        return $this->assignment->reviewer->name.' submitted a review with recommendation: '.$this->review->recommendation->label().'.';
    }

    public function related(): ?Model
    {
        return $this->review;
    }

    /**
     * @return array<string, mixed>
     */
    public function logData(): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'recommendation' => $this->review->recommendation->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.review-submitted', [
                'assignment' => $this->assignment,
                'article' => $this->assignment->article,
                'review' => $this->review,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
