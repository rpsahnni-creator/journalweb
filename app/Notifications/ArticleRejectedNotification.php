<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use App\Models\EditorialDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ArticleRejectedNotification extends JournalMailNotification
{
    public function __construct(
        public Article $article,
        public EditorialDecision $decision,
        public string $actionUrl,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::ArticleRejected;
    }

    public function logSubject(): string
    {
        return 'Decision on manuscript '.$this->article->submission_number;
    }

    public function logBody(): string
    {
        return 'The editorial office declined “'.$this->article->title.'”.';
    }

    public function related(): ?Model
    {
        return $this->decision;
    }

    /**
     * @return array<string, mixed>
     */
    public function logData(): array
    {
        return ['decision' => $this->decision->decision->value];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.article-rejected', [
                'article' => $this->article,
                'decision' => $this->decision,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
