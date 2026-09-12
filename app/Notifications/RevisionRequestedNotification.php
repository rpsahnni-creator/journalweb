<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Article;
use App\Models\EditorialDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

class RevisionRequestedNotification extends JournalMailNotification
{
    public function __construct(
        public Article $article,
        public EditorialDecision $decision,
        public string $actionUrl,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::RevisionRequested;
    }

    public function logSubject(): string
    {
        return 'Revision requested for '.$this->article->submission_number;
    }

    public function logBody(): string
    {
        return 'The editorial office issued a '.$this->decision->decision->label().' decision on “'.$this->article->title.'”.';
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
        return [
            'decision' => $this->decision->decision->value,
            'revision_due_at' => $this->decision->revision_due_at?->toDateString(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->logSubject())
            ->markdown('mail.notifications.revision-requested', [
                'article' => $this->article,
                'decision' => $this->decision,
                'actionUrl' => $this->actionUrl,
            ]);
    }
}
