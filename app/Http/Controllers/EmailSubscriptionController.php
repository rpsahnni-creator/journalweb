<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmIssueAlertSubscription;
use App\Models\EmailSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $subscription = EmailSubscription::start($validated['email']);

        if (! $subscription->isConfirmed()) {
            Mail::to($subscription->email)->send(new ConfirmIssueAlertSubscription($subscription));
        }

        return back()->with('status', $subscription->isConfirmed()
            ? 'This address is already subscribed to new-issue alerts.'
            : 'Check your email to confirm the issue alert subscription.');
    }

    public function confirm(string $token): View
    {
        $subscription = EmailSubscription::query()->where('confirm_token', $token)->firstOrFail();
        $subscription->forceFill([
            'confirmed_at' => $subscription->confirmed_at ?? now(),
            'unsubscribed_at' => null,
        ])->save();

        return view('pages.subscription-status', [
            'title' => 'Subscription confirmed',
            'message' => 'You will receive an email when a new issue is published. Unpublished manuscripts are never included.',
        ]);
    }

    public function unsubscribe(string $token): View
    {
        $subscription = EmailSubscription::query()->where('confirm_token', $token)->firstOrFail();
        $subscription->forceFill(['unsubscribed_at' => now()])->save();

        return view('pages.subscription-status', [
            'title' => 'Unsubscribed',
            'message' => 'You will no longer receive table-of-contents alerts.',
        ]);
    }
}
