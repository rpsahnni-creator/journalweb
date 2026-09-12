<x-layouts.public title="Review manuscript">
    <x-flash />

    <div class="mb-6">
        <a href="{{ route('reviews.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            Assigned reviews
        </a>
    </div>

    <div class="space-y-6">
        <section class="rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Blinded manuscript</p>
                    <h1 class="mt-1 font-serif text-2xl font-semibold text-brand-950">{{ $review->submission?->title }}</h1>
                </div>
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

            <p class="mt-2 text-xs text-slate-500">Author names, co-authors, and institutions are withheld for double-blind review.</p>

            <div class="mt-5">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Abstract</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->submission?->abstract }}</p>
            </div>

            @if (filled($review->submission?->keywords))
                <div class="mt-5">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keywords</h2>
                    <p class="mt-1 text-sm text-slate-700">{{ $review->submission->keywords }}</p>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('reviews.manuscript', $review) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800">
                    <x-icon name="download" class="h-4 w-4" />
                    Download manuscript
                </a>
                <p class="self-center text-xs text-slate-500">Authorized private download. Do not share this file.</p>
            </div>
        </section>

        @if ($review->isSubmitted())
            <section class="rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                <h2 class="font-semibold text-slate-900">Submitted review</h2>
                <p class="mt-3 text-sm text-slate-700">
                    <span class="font-medium">Recommendation:</span>
                    {{ $review->recommendation?->label() ?? '—' }}
                </p>
                @if (filled($review->comments_to_author))
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Comments to the author</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->comments_to_author }}</p>
                    </div>
                @endif
                @if (filled($review->comments_to_editor))
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Comments to the editor</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->comments_to_editor }}</p>
                    </div>
                @endif
            </section>
        @else
            <form method="POST" action="{{ route('reviews.update', $review) }}" class="space-y-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                @csrf
                @method('PUT')
                <h2 class="font-semibold text-slate-900">Review form</h2>
                <x-form.select
                    name="recommendation"
                    label="Recommendation"
                    :options="$recommendations"
                    :selected="old('recommendation', $review->recommendation?->value)"
                    placeholder="Select a recommendation"
                    required
                />
                <x-form.textarea name="comments_to_editor" label="Comments to the editor (private)" rows="6">{{ old('comments_to_editor', $review->comments_to_editor) }}</x-form.textarea>
                <p class="text-xs text-slate-500">Visible only to editors. Do not include your name.</p>
                <x-form.textarea name="comments_to_author" label="Comments to the author" rows="8" required>{{ old('comments_to_author', $review->comments_to_author) }}</x-form.textarea>
                <p class="text-xs text-slate-500">Shared with the author if a revision is requested. Do not reveal your identity.</p>
                <x-form.button :full="false">Submit review</x-form.button>
            </form>
        @endif
    </div>
</x-layouts.public>
