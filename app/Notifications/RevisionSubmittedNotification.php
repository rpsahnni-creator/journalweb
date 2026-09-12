<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class RevisionSubmittedNotification extends JournalMailNotification
{
    public function __construct(
        public Article $article,
        public Revision $revision,
        public string $actionUrl,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::RevisionSubmitted;
    }

    public function logSubject(): string
    {
        return 'Revision submitted for '.$this->article->submission_number;
    }

    public function logBody(): string
    {
        return $this->article->title.' was resubmitted as version '.$this->revision->version.'.';
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
            'version' => $this->revision->version,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.revision-submitted', [
                'article' => $this->article,
                'revision' => $this->revision,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
