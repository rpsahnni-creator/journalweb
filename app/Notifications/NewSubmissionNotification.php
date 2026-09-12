<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class NewSubmissionNotification extends JournalMailNotification
{
    public function __construct(
        public Article $article,
        public string $actionUrl,
        public bool $forAuthor = false,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::SubmissionReceived;
    }

    public function logSubject(): string
    {
        return $this->forAuthor
            ? 'We received manuscript '.$this->article->submission_number
            : 'Manuscript '.$this->article->submission_number.' submitted';
    }

    public function logBody(): string
    {
        return $this->forAuthor
            ? 'Your manuscript “'.$this->article->title.'” has been received and is waiting for editorial screening.'
            : $this->article->title.' is ready for editorial screening.';
    }

    public function related(): ?Model
    {
        return $this->article;
    }

    /**
     * @return array<string, mixed>
     */
    public function logData(): array
    {
        return [
            'submission_number' => $this->article->submission_number,
            'audience' => $this->forAuthor ? 'author' : 'editor',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.new-submission', [
                'article' => $this->article,
                'actionUrl' => $this->actionUrl,
                'forAuthor' => $this->forAuthor,
            ]);
    }
}
