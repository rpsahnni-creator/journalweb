<?php

namespace App\Support;

use App\Mail\TableOfContentsAlert;
use App\Models\EmailSubscription;
use App\Models\Issue;
use Illuminate\Support\Facades\Mail;

class TableOfContentsMailer
{
    public function sendForIssue(Issue $issue): int
    {
        $issue->loadMissing(['volume', 'articles' => fn ($query) => $query->publiclyListed()->with('authors')]);

        if ($issue->articles->isEmpty()) {
            return 0;
        }

        $sent = 0;

        EmailSubscription::query()->confirmed()->each(function (EmailSubscription $subscription) use ($issue, &$sent): void {
            Mail::to($subscription->email)->send(new TableOfContentsAlert($issue, $subscription));
            $sent++;
        });

        return $sent;
    }
}
