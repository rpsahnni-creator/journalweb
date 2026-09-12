<x-layouts.app :title="$manuscript->submission_number">
    <x-reviewer-nav />
    <x-flash />

    <p class="font-mono text-sm text-slate-500">{{ $manuscript->submission_number }}</p>
    <h1 class="mt-1 font-serif text-3xl font-semibold text-brand-950">{{ $manuscript->title }}</h1>
    <p class="mt-2 text-slate-600">
        Invitation: {{ $assignment->displayStatus()->label() }}
        @if ($assignment->due_at)
            · deadline {{ $assignment->due_at->toDateString() }}
        @endif
    </p>

    <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-semibold text-brand-950">Manuscript details</h2>
        <p class="mt-2 text-sm text-slate-600">{{ $manuscript->article_type?->label() }}</p>
        <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $manuscript->abstract }}</p>
        @if ($manuscript->keywords)
            <ul class="mt-4 flex flex-wrap gap-2">
                @foreach ($manuscript->keywords as $keyword)
                    <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">{{ $keyword }}</li>
                @endforeach
            </ul>
        @endif
        <p class="mt-4 text-xs text-slate-500">Author identities, the cover letter, and conflict statements are withheld from the reviewer workspace.</p>
    </section>

    @can('respond', $assignment)
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('reviewer.assignments.accept', $assignment) }}">
                @csrf
                <x-form.button :full="false">Accept invitation</x-form.button>
            </form>
            <form method="POST" action="{{ route('reviewer.assignments.decline', $assignment) }}" class="flex flex-wrap items-end gap-3">
                @csrf
                <x-form.input name="response_note" label="Optional reason" value="{{ old('response_note') }}" />
                <x-form.button :full="false" variant="secondary">Decline</x-form.button>
            </form>
        </div>
    @endcan

    @if ($assignment->canAccessManuscript())
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Authorized files</h2>
            <p class="mt-1 text-xs text-slate-500">These downloads require your accepted assignment. They are not published at a public URL.</p>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($files as $file)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $file->original_filename }} <span class="text-xs text-slate-500">({{ $file->type->label() }})</span></span>
                        <a href="{{ route('reviewer.assignments.files.download', [$assignment, $file]) }}" class="font-medium text-brand-800 hover:underline">Download</a>
                    </li>
                @empty
                    <li class="text-slate-600">No manuscript files are attached to this revision.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @can('submitReview', $assignment)
        <form method="POST" action="{{ route('reviewer.assignments.review.store', $assignment) }}" class="mt-8 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-semibold text-brand-950">Review form</h2>
            <x-form.select
                name="recommendation"
                label="Recommendation"
                :options="collect($recommendations)->mapWithKeys(fn ($recommendation) => [$recommendation->value => $recommendation->label()])"
                :selected="old('recommendation')"
                placeholder="Select a recommendation"
                required
            />
            <x-form.textarea name="comments_to_author" label="Comments to the author" rows="8" required>{{ old('comments_to_author') }}</x-form.textarea>
            <x-form.textarea name="comments_to_editor" label="Confidential comments to the editor" rows="6">{{ old('comments_to_editor') }}</x-form.textarea>
            <x-form.button :full="false">Submit review</x-form.button>
        </form>
    @endcan

    @if ($assignment->review)
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Submitted review</h2>
            <p class="mt-2 text-sm text-slate-600">Recommendation: {{ $assignment->review->recommendation->label() }}</p>
            <p class="mt-4 text-sm font-medium text-slate-500">Comments to author</p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $assignment->review->comments_to_author }}</p>
            <p class="mt-4 text-sm font-medium text-slate-500">Confidential comments to editor</p>
            <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ $assignment->review->comments_to_editor ?: 'None' }}</p>
        </section>
    @endif
</x-layouts.app>
