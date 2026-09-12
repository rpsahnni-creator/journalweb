<?php

namespace App\Mail;

use App\Models\EmailSubscription;
use App\Models\Issue;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TableOfContentsAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Issue $issue,
        public EmailSubscription $subscription,
    ) {
        $this->issue->loadMissing(['volume', 'articles' => fn ($query) => $query->publiclyListed()->with('authors')]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New issue: '.$this->issue->displayLabel().' — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.toc-alert',
            with: [
                'issue' => $this->issue,
                'articles' => $this->issue->articles,
                'unsubscribeUrl' => route('unsubscribe', $this->subscription->confirm_token),
            ],
        );
    }
}
