<?php

namespace App\Mail;

use App\Models\EmailSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmIssueAlertSubscription extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public EmailSubscription $subscription) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your issue alerts — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.confirm-subscription',
            with: [
                'confirmUrl' => route('subscribe.confirm', $this->subscription->confirm_token),
            ],
        );
    }
}
