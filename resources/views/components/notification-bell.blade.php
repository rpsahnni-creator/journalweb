@props([
    'compact' => false,
])

@auth
    @php
        $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
    @endphp

    <a
        href="{{ route('notifications.index') }}"
        title="Notifications"
        {{ $attributes->class('relative inline-flex items-center gap-1.5 rounded-lg transition-colors') }}
    >
        <span class="relative inline-flex">
            <x-icon name="bell" class="h-4 w-4" />
            @if ($unreadNotificationCount > 0)
                <span class="absolute -right-1.5 -top-1.5 inline-flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-4 text-white">
                    {{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}
                </span>
            @endif
        </span>
        <span @class(['sr-only' => $compact, 'text-sm font-medium' => ! $compact])>Notifications</span>
        @if ($unreadNotificationCount > 0)
            <span class="sr-only">{{ $unreadNotificationCount }} unread</span>
        @endif
    </a>
@endauth
