<x-layouts.app title="Editorial notifications">
    <x-editorial-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Editor notifications</h1>
    <p class="mt-2 text-slate-600">Invitations, reviews, submissions, and mail deliveries are recorded here as notification logs.</p>

    @if ($mailTestingEnabled)
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-sm text-slate-600">Development mail test uses the <span class="font-medium">{{ $mailer }}</span> mailer from <code>.env</code>. SMTP passwords are not stored in code.</p>
            <form method="POST" action="{{ route('editorial.notifications.test-mail') }}" class="mt-3">
                @csrf
                <x-form.button :full="false">Send me a test email</x-form.button>
            </form>
        </div>
    @endif

    <div class="mt-8 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white">
        @forelse ($notifications as $notification)
            <article class="px-4 py-4">
                <p class="font-medium text-brand-950">{{ $notification->subject }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                <p class="mt-2 text-xs text-slate-500">
                    {{ $notification->type }}
                    · {{ $notification->channel->value }}
                    · {{ $notification->sent_at?->format('Y-m-d H:i') ?: $notification->created_at?->format('Y-m-d H:i') }}
                </p>
            </article>
        @empty
            <p class="px-4 py-10 text-center text-sm text-slate-600">No notifications yet.</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts.app>
