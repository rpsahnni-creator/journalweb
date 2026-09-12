<x-layouts.app title="Reviewer portal">
    <x-reviewer-nav />
    <x-flash />

    <div class="flex flex-col gap-1">
        <h1 class="font-serif text-3xl font-semibold text-brand-950">Reviewer portal</h1>
        <p class="text-sm text-slate-500">Invitations and assigned manuscripts stay private. Files are available only after you accept, through an authorized download.</p>
    </div>

    <!-- Invitations Section -->
    <section class="mt-8 rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <x-icon name="mail" class="h-4 w-4 text-accent-600" />
            <h2 class="font-serif font-semibold text-brand-950">Invitations</h2>
        </div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($invitations as $assignment)
                <li class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50/80 transition-colors">
                    <div>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="font-serif font-semibold text-brand-950 hover:text-brand-700 block">
                            {{ $assignment->article->title }}
                        </a>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $assignment->article->submission_number }}</span>
                            <span>·</span>
                            <span>due {{ $assignment->due_at?->toDateString() ?: 'not set' }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-600/20">
                            {{ $assignment->status->label() }}
                        </span>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="inline-flex items-center gap-1 rounded-lg bg-brand-900 px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-800 transition-colors">
                            <span>Respond</span>
                            <x-icon name="arrow-right" class="h-3 w-3" />
                        </a>
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-slate-500 text-sm">No pending invitations.</li>
            @endforelse
        </ul>
    </section>

    <!-- Reviews in Progress Section -->
    <section class="mt-6 rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <x-icon name="clock" class="h-4 w-4 text-blue-600" />
            <h2 class="font-serif font-semibold text-brand-950">Reviews in progress</h2>
        </div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($inProgress as $assignment)
                <li class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50/80 transition-colors">
                    <div>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="font-serif font-semibold text-brand-950 hover:text-brand-700 block">
                            {{ $assignment->article->title }}
                        </a>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <span class="font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $assignment->article->submission_number }}</span>
                            <span>·</span>
                            <span>due {{ $assignment->due_at?->toDateString() ?: 'not set' }}</span>
                            @if ($assignment->isOverdue())
                                <span>·</span>
                                <span class="rounded bg-rose-50 px-2 py-0.5 font-semibold text-rose-700 ring-1 ring-rose-600/20">overdue</span>
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-600/20">
                            {{ $assignment->displayStatus()->label() }}
                        </span>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300/90 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-brand-900 transition-colors">
                            <span>Open evaluation</span>
                            <x-icon name="arrow-right" class="h-3 w-3" />
                        </a>
                    </div>
                </li>
            @empty
                <li class="p-8 text-center text-slate-500 text-sm">No accepted reviews in progress.</li>
            @endforelse
        </ul>
    </section>

    <!-- Completed Reviews Section -->
    <section class="mt-6 rounded-xl border border-slate-200/90 bg-white shadow-xs overflow-hidden">
        <div class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/50 px-5 py-4">
            <x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />
            <h2 class="font-serif font-semibold text-brand-950">Completed reviews</h2>
        </div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($completed as $assignment)
                <li class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between hover:bg-slate-50/80 transition-colors">
                    <div>
                        <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="font-serif font-semibold text-brand-950 hover:text-brand-700 block">
                            {{ $assignment->article->title }}
                        </a>
                        <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">{{ $assignment->review?->recommendation?->label() }}</span>
                            <span>·</span>
                            <span>completed {{ $assignment->completed_at?->toDateString() }}</span>
                        </p>
                    </div>
                    <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="text-xs font-semibold text-brand-800 hover:underline">
                        View submitted report
                    </a>
                </li>
            @empty
                <li class="p-8 text-center text-slate-500 text-sm">No completed reviews yet.</li>
            @endforelse
        </ul>
    </section>
</x-layouts.app>

