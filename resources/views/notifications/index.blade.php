<x-layouts.public title="Notifications">
    <x-flash />

    <div>
        <h1 class="font-serif text-3xl font-semibold text-brand-950">Notifications</h1>
        <p class="mt-1 text-sm text-slate-500">Updates about submissions, review assignments, and editorial decisions. Opening a notification marks it as read.</p>
    </div>

    <div class="mt-6 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xs">
        @forelse ($notifications as $notification)
            @php
                $data = is_array($notification->data) ? $notification->data : [];
                $title = $data['title'] ?? 'Notification';
                $message = $data['message'] ?? '';
            @endphp
            <a
                href="{{ route('notifications.show', $notification) }}"
                @class([
                    'block px-5 py-4 transition-colors hover:bg-slate-50/80',
                    'bg-brand-50/40' => $notification->read_at === null,
                ])
            >
                <div class="flex items-start justify-between gap-3">
                    <p class="font-medium text-brand-950">{{ $title }}</p>
                    @if ($notification->read_at === null)
                        <span class="mt-0.5 inline-flex shrink-0 rounded-full bg-brand-800 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">Unread</span>
                    @endif
                </div>
                @if (filled($message))
                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ \Illuminate\Support\Str::limit($message, 220) }}</p>
                @endif
                <p class="mt-2 text-xs text-slate-500">{{ $notification->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</p>
            </a>
        @empty
            <p class="px-5 py-12 text-center text-sm text-slate-500">You have no notifications yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $notifications->links() }}</div>
</x-layouts.public>
