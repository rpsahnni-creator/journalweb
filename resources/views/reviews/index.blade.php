<x-layouts.public title="Assigned reviews">
    <x-flash />

    <div>
        <h1 class="font-serif text-3xl font-semibold text-brand-950">Assigned reviews</h1>
        <p class="mt-1 text-sm text-slate-500">Double-blind review. Author names, co-authors, and institutions are withheld.</p>
        <p class="mt-3 text-sm">
            <a href="{{ route('reviewer-guidelines') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">Reviewer Guidelines</a>
        </p>
    </div>

    <div class="mt-6 space-y-4">
        @forelse ($reviews as $review)
            <article class="rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2 class="font-serif text-xl font-semibold text-brand-950">
                        <a href="{{ route('reviews.show', $review) }}" class="hover:underline">{{ $review->submission?->title }}</a>
                    </h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <span @class([
                            'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1',
                            $review->status->badgeClasses(),
                        ])>
                            {{ $review->status->label() }}
                        </span>
                        <span class="text-xs font-medium text-slate-500">{{ $review->dueStatusLabel() }}</span>
                    </div>
                </div>

                <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->submission?->abstract }}</p>

                @if (filled($review->submission?->keywords))
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Keywords</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $review->submission->keywords }}</p>
                @endif

                <div class="mt-4">
                    <a href="{{ route('reviews.show', $review) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
                        {{ $review->isSubmitted() ? 'View review' : 'Open review form' }}
                        <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-slate-200/90 bg-white px-5 py-12 text-center shadow-xs">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <x-icon name="clipboard-check" class="h-6 w-6" />
                </div>
                <p class="font-medium text-slate-700">No assigned reviews</p>
                <p class="mt-1 text-xs text-slate-500">Editors will assign manuscripts to you from the submissions queue.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $reviews->links() }}</div>
</x-layouts.public>
