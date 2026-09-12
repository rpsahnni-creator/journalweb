<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Models\JournalNotification;
use App\Notifications\TestMailNotification;
use App\Support\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('access-editorial');

        $notifications = JournalNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('editorial.notifications.index', [
            'notifications' => $notifications,
            'mailTestingEnabled' => (bool) config('mail.testing.enabled'),
            'mailer' => config('mail.default'),
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        abort_unless((bool) config('mail.testing.enabled'), 404);
        $this->authorize('access-editorial');

        Notifier::notify($request->user(), new TestMailNotification);

        return back()->with('status', 'Test message sent to '.$request->user()->email.' using the '.config('mail.default').' mailer. SMTP settings come from .env.');
    }
}
