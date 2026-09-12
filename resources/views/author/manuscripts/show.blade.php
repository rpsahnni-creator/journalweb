<x-layouts.author :title="$manuscript->title">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="font-mono text-xs text-slate-500">{{ $manuscript->submission_number }}</p>
            <p class="mt-2 text-sm text-slate-600">
                {{ $manuscript->article_type?->label() ?? 'No article type' }}
                · {{ $manuscript->status->label() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            @can('updateAsAuthor', $manuscript)
                <a href="{{ route('author.manuscripts.edit', $manuscript) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    {{ $manuscript->status === \App\Enums\ArticleStatus::RevisionRequired ? 'Edit revision' : 'Edit draft' }}
                </a>
            @endcan
            @can('submit', $manuscript)
                @if ($manuscript->status !== \App\Enums\ArticleStatus::RevisionRequired)
                    <form method="POST" action="{{ route('author.manuscripts.submit', $manuscript) }}">
                        @csrf
                        <x-form.button :full="false">Submit manuscript</x-form.button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    @if ($manuscript->isAuthorEditable())
        @if ($manuscript->isReadyToSubmit())
            <x-alert class="mt-6">This manuscript is complete and can be submitted. The manuscript number will remain {{ $manuscript->submission_number }}.</x-alert>
        @else
            <x-alert type="warning" class="mt-6">
                Before submitting:
                {{ implode(' ', $manuscript->submissionBlockers()) }}
            </x-alert>
        @endif
    @else
        <x-alert type="warning" class="mt-6">
            This manuscript cannot be edited unless the editorial office asks for a revision. Current status: {{ $manuscript->status->label() }}.
        </x-alert>
    @endif

    @if ($decision)
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Decision letter</h2>
            <p class="mt-2 text-sm text-slate-600">
                {{ $decision->decision->label() }}
                · {{ $decision->decided_at?->format('Y-m-d') }}
                @if ($manuscript->revision_due_at && $manuscript->status === \App\Enums\ArticleStatus::RevisionRequired)
                    · revision due {{ $manuscript->revision_due_at->toDateString() }}
                    @if ($manuscript->isRevisionOverdue())
                        · overdue
                    @endif
                @endif
            </p>
            <p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $decision->comments_to_author }}</p>
        </section>
    @endif

    @if ($manuscript->authorFacingReviews()->isNotEmpty())
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Reviewer comments</h2>
            <p class="mt-1 text-sm text-slate-600">Confidential comments to the editor are not shown here. Reviewer identities are withheld.</p>
            @foreach ($manuscript->authorFacingReviews() as $review)
                <article class="mt-4 rounded-md border border-slate-100 p-4 text-sm">
                    <p class="font-medium text-brand-950">Reviewer · {{ $review->recommendation->label() }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-slate-700">{{ $review->comments_to_author }}</p>
                </article>
            @endforeach
        </section>
    @endif

    @if ($manuscript->status === \App\Enums\ArticleStatus::RevisionRequired)
        <form method="POST" action="{{ route('author.manuscripts.submit', $manuscript) }}" class="mt-8 space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-semibold text-brand-950">Author response</h2>
            <p class="text-sm text-slate-600">Respond to the reviewers, upload a new manuscript file from the edit page, then submit this revision. Previous files stay on record.</p>
            <x-form.textarea name="author_response" label="Response to reviewers" rows="8" required>{{ old('author_response', $manuscript->author_response) }}</x-form.textarea>
            <x-form.button :full="false">Submit revision</x-form.button>
        </form>
    @elseif ($manuscript->revisions->contains(fn ($revision) => filled($revision->author_response)))
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Your responses</h2>
            @foreach ($manuscript->revisions as $revision)
                @if (filled($revision->author_response))
                    <article class="mt-4 text-sm">
                        <p class="font-medium text-brand-950">Version {{ $revision->version }}</p>
                        <p class="mt-2 whitespace-pre-wrap text-slate-700">{{ $revision->author_response }}</p>
                    </article>
                @endif
            @endforeach
        </section>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-slate-200 bg-white p-6 lg:col-span-2">
            <h2 class="font-semibold text-brand-950">Abstract</h2>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $manuscript->abstract ?: 'No abstract yet.' }}</p>

            @if ($manuscript->keywords)
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($manuscript->keywords as $keyword)
                        <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">{{ $keyword }}</li>
                    @endforeach
                </ul>
            @endif

            <h2 class="mt-8 font-semibold text-brand-950">Cover letter</h2>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $manuscript->cover_letter ?: 'No cover letter yet.' }}</p>

            <h2 class="mt-8 font-semibold text-brand-950">Declarations</h2>
            <ul class="mt-3 space-y-2 text-sm text-slate-700">
                <li>Originality: {{ $manuscript->originality_confirmed ? 'Confirmed' : 'Not confirmed' }}</li>
                <li>Conflict of interest: {{ $manuscript->conflict_of_interest_declared ? 'Declared' : 'Not declared' }}</li>
                @if ($manuscript->conflict_of_interest_statement)
                    <li>{{ $manuscript->conflict_of_interest_statement }}</li>
                @endif
            </ul>
        </section>

        <section class="space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Authors</h2>
                <ol class="mt-3 space-y-3 text-sm">
                    @foreach ($manuscript->authors as $author)
                        <li>
                            <p class="font-medium text-brand-950">{{ $author->name }}</p>
                            <p class="text-slate-600">{{ $author->affiliation }}</p>
                            @if ($author->is_corresponding)
                                <p class="text-xs text-slate-500">Corresponding author</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Private files</h2>
                <p class="mt-1 text-xs text-slate-500">Downloads require a signed-in author session. Submitted versions are never overwritten.</p>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($manuscript->files as $file)
                        <li class="flex items-center justify-between gap-3">
                            <span>
                                {{ $file->original_filename }}
                                <span class="text-xs text-slate-500">
                                    ({{ $file->type->label() }}
                                    @if ($file->revision)
                                        · version {{ $file->revision->version }}
                                    @else
                                        · working copy
                                    @endif)
                                </span>
                            </span>
                            @can('download', $file)
                                <a href="{{ route('author.manuscripts.files.download', [$manuscript, $file]) }}" class="font-medium text-brand-800 hover:underline">Download</a>
                            @endcan
                        </li>
                    @empty
                        <li class="text-slate-600">No files uploaded.</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>

    <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-semibold text-brand-950">Status history</h2>
        <ol class="mt-4 space-y-3 text-sm">
            @forelse ($manuscript->statusEvents as $event)
                <li class="border-l-2 border-brand-800 pl-4">
                    <p class="font-medium text-brand-950">{{ $event->to_status->label() }}</p>
                    <p class="text-slate-500">
                        {{ $event->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                        @if ($event->user && (int) $event->user->id === (int) $manuscript->corresponding_author_id)
                            · {{ $event->user->name }}
                        @elseif ($event->user)
                            · Editorial office
                        @endif
                    </p>
                    @if ($event->note)
                        <p class="mt-1 text-slate-600">{{ $event->note }}</p>
                    @endif
                </li>
            @empty
                <li class="text-slate-600">No status changes recorded yet.</li>
            @endforelse
        </ol>
    </section>
</x-layouts.author>
