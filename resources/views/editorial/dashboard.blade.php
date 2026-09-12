<x-layouts.app title="Editorial office">
    <x-editorial-nav />
    <x-flash />

    <div class="flex flex-col gap-1">
        <h1 class="font-serif text-3xl font-semibold text-brand-950">Editorial office</h1>
        <p class="text-sm text-slate-500">Submitted manuscripts stay private. Peer review files are only available through authorized downloads.</p>
    </div>

    <!-- 5 Metrics Cards -->
    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">New submissions</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <x-icon name="inbox" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-3 font-serif text-3xl font-bold text-brand-950">{{ $submittedCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Awaiting editor triage</p>
        </div>

        <div class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Initial screening</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700">
                    <x-icon name="search" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-3 font-serif text-3xl font-bold text-brand-950">{{ $screeningCount }}</p>
            <p class="mt-1 text-xs text-slate-500">In pre-review checks</p>
        </div>

        <div class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Under review</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <x-icon name="clock" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-3 font-serif text-3xl font-bold text-brand-950">{{ $underReviewCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Active peer review</p>
        </div>

        <div class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Awaiting revision</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-700">
                    <x-icon name="edit" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-3 font-serif text-3xl font-bold text-brand-950">{{ $revisionRequiredCount }}</p>
            <p class="mt-1 text-xs text-slate-500">With corresponding author</p>
        </div>

        <div class="rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Pending invitations</span>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-700">
                    <x-icon name="mail" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-3 font-serif text-3xl font-bold text-brand-950">{{ $pendingInvitations }}</p>
            <p class="mt-1 text-xs text-slate-500">Awaiting reviewer replies</p>
        </div>
    </div>

    <!-- Editorial Queue & Notifications -->
    <div class="mt-8 grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <div class="flex items-center gap-2">
                    <x-icon name="list-checks" class="h-4 w-4 text-brand-800" />
                    <h2 class="font-serif font-semibold text-brand-950">Editorial queue</h2>
                </div>
                <a href="{{ route('editorial.manuscripts.index') }}" class="text-xs font-semibold text-brand-800 hover:text-brand-900 hover:underline">View all</a>
            </div>
            @if ($recentQueue->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">No manuscripts are waiting for editorial action.</p>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($recentQueue as $manuscript)
                        <li class="px-5 py-3.5 hover:bg-slate-50/80 transition-colors">
                            <a href="{{ route('editorial.manuscripts.show', $manuscript) }}" class="font-medium text-brand-950 hover:text-brand-700 block truncate">{{ $manuscript->title }}</a>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                <span class="font-mono">{{ $manuscript->submission_number }}</span>
                                <span>·</span>
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700">{{ $manuscript->status->label() }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 px-5 py-4">
                <div class="flex items-center gap-2">
                    <x-icon name="bell" class="h-4 w-4 text-brand-800" />
                    <h2 class="font-serif font-semibold text-brand-950">Notifications</h2>
                </div>
                <a href="{{ route('editorial.notifications.index') }}" class="text-xs font-semibold text-brand-800 hover:text-brand-900 hover:underline">View all</a>
            </div>
            @if ($notifications->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">No editorial notifications yet.</p>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($notifications as $notification)
                        <li class="px-5 py-3.5 hover:bg-slate-50/80 transition-colors">
                            <p class="font-medium text-brand-950">{{ $notification->subject }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500 line-clamp-2">{{ $notification->body }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>

