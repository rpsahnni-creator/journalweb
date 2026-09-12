<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class ArticlePublishedNotification extends JournalMailNotification
{
    public function __construct(
        public Article $article,
        public string $actionUrl,
    ) {
        $this->article->loadMissing(['issues.volume']);
    }

    public function notificationType(): NotificationType
    {
        return NotificationType::ArticlePublished;
    }

    public function logSubject(): string
    {
        return 'Your article has been published: '.$this->article->submission_number;
    }

    public function logBody(): string
    {
        return '“'.$this->article->title.'” is now available on the public site.';
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
            'slug' => $this->article->slug,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.article-published', [
                'article' => $this->article,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
