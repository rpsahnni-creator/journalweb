<?php

namespace App\Http\Controllers;

use App\Models\DatabaseNotification;
use App\Support\SafeRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationInboxController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function show(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        if ($record instanceof DatabaseNotification) {
            $record->markAsRead();
        }

        $target = is_array($record->data) ? ($record->data['url'] ?? null) : null;

        return redirect()->to(SafeRedirect::target(
            is_string($target) ? $target : null,
            route('notifications.index')
        ));
    }
}
