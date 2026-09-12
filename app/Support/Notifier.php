<?php

namespace App\Support;

use App\Enums\NotificationChannel;
use App\Models\JournalNotification;
use App\Models\User;
use App\Notifications\JournalMailNotification;
use Illuminate\Support\Facades\Notification;

class Notifier
{
    public static function notify(User $user, JournalMailNotification $notification): JournalNotification
    {
        $record = self::record($user, $notification, NotificationChannel::Database);

        if (filled($user->email)) {
            $user->notify($notification);
            self::record($user, $notification, NotificationChannel::Mail);
        }

        return $record;
    }

    /**
     * @return list<JournalNotification>
     */
    public static function editors(JournalMailNotification $notification, ?int $exceptUserId = null): array
    {
        $editors = User::query()
            ->active()
            ->whereHas('roles.permissions', function ($query): void {
                $query->whereIn('slug', ['reviews.assign', 'articles.decide']);
            })
            ->when($exceptUserId, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->get()
            ->unique('id');

        $notifications = [];

        foreach ($editors as $editor) {
            $notifications[] = self::notify($editor, clone $notification);
        }

        return $notifications;
    }

    public static function mail(string $email, JournalMailNotification $notification): void
    {
        Notification::route('mail', $email)->notify($notification);
    }

    public static function record(
        User $user,
        JournalMailNotification $notification,
        NotificationChannel $channel,
    ): JournalNotification {
        $related = $notification->related();

        return JournalNotification::query()->create([
            'user_id' => $user->id,
            'type' => $notification->notificationType()->value,
            'channel' => $channel,
            'subject' => $notification->logSubject(),
            'body' => $notification->logBody(),
            'data' => $notification->logData(),
            'related_type' => $related === null ? null : $related::class,
            'related_id' => $related?->getKey(),
            'sent_at' => now(),
        ]);
    }
}
