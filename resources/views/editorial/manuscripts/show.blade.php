<x-layouts.app :title="$manuscript->submission_number">
    <x-editorial-nav />
    <x-flash />

    <p class="font-mono text-sm text-slate-500">{{ $manuscript->submission_number }}</p>
    <h1 class="mt-1 font-serif text-3xl font-semibold text-brand-950">{{ $manuscript->title }}</h1>
    <p class="mt-2 text-slate-600">{{ $manuscript->article_type?->label() }} · {{ $manuscript->status->label() }}</p>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Abstract</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $manuscript->abstract }}</p>
                @if ($manuscript->keywords)
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($manuscript->keywords as $keyword)
                            <li class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">{{ $keyword }}</li>
                        @endforeach
                    </ul>
                @endif

                <h2 class="mt-8 font-semibold text-brand-950">Cover letter</h2>
                <p class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ $manuscript->cover_letter ?: 'None' }}</p>

                <h2 class="mt-8 font-semibold text-brand-950">Declarations</h2>
                <ul class="mt-3 space-y-1 text-sm text-slate-700">
                    <li>Originality: {{ $manuscript->originality_confirmed ? 'Confirmed' : 'Not confirmed' }}</li>
                    <li>Conflict of interest: {{ $manuscript->conflict_of_interest_statement ?: 'Not provided' }}</li>
                </ul>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Authors</h2>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach ($manuscript->authors as $author)
                        <li>
                            <span class="font-medium text-brand-950">{{ $author->name }}</span>
                            @if ($author->is_corresponding)
                                <span class="text-xs text-slate-500">corresponding</span>
                            @endif
                            <p class="text-slate-600">{{ $author->affiliation }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Private files</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($manuscript->files as $file)
                        <li class="flex items-center justify-between gap-3">
                            <span>{{ $file->original_filename }} <span class="text-xs text-slate-500">({{ $file->type->label() }})</span></span>
                            @can('download', $file)
                                <a href="{{ route('editorial.manuscripts.files.download', [$manuscript, $file]) }}" class="font-medium text-brand-800 hover:underline">Download</a>
                            @endcan
                        </li>
                    @empty
                        <li class="text-slate-600">No files uploaded.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Revision history</h2>
                @forelse ($manuscript->revisions as $revision)
                    <article class="mt-4 rounded-md border border-slate-100 p-4 text-sm">
                        <p class="font-medium text-brand-950">Version {{ $revision->version }}</p>
                        <p class="text-slate-500">
                            Submitted {{ $revision->submitted_at?->toDateString() ?: '—' }}
                            @if ($revision->submitter)
                                · {{ $revision->submitter->name }}
                            @endif
                        </p>
                        @if (filled($revision->author_response))
                            <p class="mt-2 text-slate-500">Author response</p>
                            <p class="whitespace-pre-wrap text-slate-700">{{ $revision->author_response }}</p>
                        @endif
                        <ul class="mt-3 space-y-1">
                            @forelse ($revision->files as $file)
                                <li class="flex items-center justify-between gap-3">
                                    <span>{{ $file->original_filename }} <span class="text-xs text-slate-500">({{ $file->type->label() }})</span></span>
                                    @can('download', $file)
                                        <a href="{{ route('editorial.manuscripts.files.download', [$manuscript, $file]) }}" class="font-medium text-brand-800 hover:underline">Download</a>
                                    @endcan
                                </li>
                            @empty
                                <li class="text-slate-600">No files attached to this version.</li>
                            @endforelse
                        </ul>
                    </article>
                @empty
                    <p class="mt-3 text-sm text-slate-600">No submitted revisions yet.</p>
                @endforelse
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Decision letters</h2>
                @forelse ($manuscript->editorialDecisions as $decision)
                    <article class="mt-4 rounded-md border border-slate-100 p-4 text-sm">
                        <p class="font-medium text-brand-950">{{ $decision->decision->label() }}</p>
                        <p class="text-slate-500">
                            {{ $decision->decided_at?->format('Y-m-d H:i') }}
                            @if ($decision->editor)
                                · {{ $decision->editor->name }}
                            @endif
                            @if ($decision->revision_due_at)
                                · revision due {{ $decision->revision_due_at->toDateString() }}
                            @endif
                        </p>
                        @if (filled($decision->comments_to_author))
                            <p class="mt-2 text-slate-500">Letter to author</p>
                            <p class="whitespace-pre-wrap text-slate-700">{{ $decision->comments_to_author }}</p>
                        @endif
                        @if (filled($decision->internal_notes))
                            <p class="mt-2 text-slate-500">Internal notes</p>
                            <p class="whitespace-pre-wrap text-slate-700">{{ $decision->internal_notes }}</p>
                        @endif
                    </article>
                @empty
                    <p class="mt-3 text-sm text-slate-600">No editorial decisions recorded yet.</p>
                @endforelse
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Reviews</h2>
                @forelse ($manuscript->reviews as $review)
                    <article class="mt-4 rounded-md border border-slate-100 p-4 text-sm">
                        <p class="font-medium text-brand-950">{{ $review->reviewer?->name }} · {{ $review->recommendation->label() }}</p>
                        <p class="mt-2 text-slate-500">Comments to author</p>
                        <p class="whitespace-pre-wrap text-slate-700">{{ $review->comments_to_author }}</p>
                        <p class="mt-2 text-slate-500">Confidential comments to editor</p>
                        <p class="whitespace-pre-wrap text-slate-700">{{ $review->comments_to_editor ?: 'None' }}</p>
                    </article>
                @empty
                    <p class="mt-3 text-sm text-slate-600">No reviews have been submitted yet.</p>
                @endforelse
            </section>
        </div>

        <div class="space-y-6">
            @can('screen', $manuscript)
                <section class="rounded-lg border border-slate-200 bg-white p-6">
                    <h2 class="font-semibold text-brand-950">Initial screening</h2>
                    <form method="POST" action="{{ route('editorial.manuscripts.screen', $manuscript) }}" class="mt-4 space-y-4">
                        @csrf
                        <x-form.select
                            name="outcome"
                            label="Outcome"
                            :options="array_filter([
                                'in_progress' => 'Keep in screening',
                                'send_to_review' => 'Send to peer review',
                                'reject' => auth()->user()?->can('deskReject', $manuscript) ? 'Desk reject' : null,
                            ])"
                            :selected="old('outcome', 'in_progress')"
                            required
                        />
                        <x-form.textarea name="internal_notes" label="Internal notes" rows="4">{{ old('internal_notes') }}</x-form.textarea>
                        <x-form.textarea name="comments_to_author" label="Comments to author" rows="4">{{ old('comments_to_author') }}</x-form.textarea>
                        <x-form.button :full="false">Save screening</x-form.button>
                    </form>
                </section>
            @endcan

            @can('issueDecision', $manuscript)
                <section
                    class="rounded-lg border border-slate-200 bg-white p-6"
                    x-data="{ decision: '{{ old('decision', '') }}' }"
                >
                    <h2 class="font-semibold text-brand-950">Editorial decision</h2>
                    <p class="mt-1 text-sm text-slate-600">The decision letter is sent to the corresponding author. Confidential reviewer comments stay on this page.</p>
                    <form method="POST" action="{{ route('editorial.manuscripts.decision.store', $manuscript) }}" class="mt-4 space-y-4">
                        @csrf
                        <x-form.select
                            name="decision"
                            label="Decision"
                            :options="collect(\App\Enums\EditorialDecisionType::postReviewCases())->mapWithKeys(fn ($decision) => [$decision->value => $decision->label()])"
                            :selected="old('decision')"
                            placeholder="Select a decision"
                            required
                            x-model="decision"
                        />
                        <div x-show="decision === 'minor_revision' || decision === 'major_revision'" x-cloak>
                            <x-form.input
                                name="revision_due_at"
                                type="date"
                                label="Revision deadline"
                                value="{{ old('revision_due_at', now()->addDays(30)->toDateString()) }}"
                            />
                        </div>
                        <x-form.textarea name="comments_to_author" label="Decision letter to author" rows="8" required>{{ old('comments_to_author') }}</x-form.textarea>
                        <x-form.textarea name="internal_notes" label="Internal notes" rows="4">{{ old('internal_notes') }}</x-form.textarea>
                        <x-form.button :full="false">Record decision</x-form.button>
                    </form>
                </section>
            @endcan

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Reviewer invitations</h2>
                <ul class="mt-3 space-y-3 text-sm">
                    @forelse ($manuscript->reviewerAssignments as $assignment)
                        <li>
                            <p class="font-medium text-brand-950">{{ $assignment->reviewer?->name }}</p>
                            <p class="text-slate-600">
                                {{ $assignment->displayStatus()->label() }}
                                @if ($assignment->due_at)
                                    · due {{ $assignment->due_at->toDateString() }}
                                @endif
                            </p>
                            @if ($assignment->response_note)
                                <p class="text-slate-500">{{ $assignment->response_note }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="text-slate-600">No reviewers assigned yet.</li>
                    @endforelse
                </ul>

                @can('assignReviewers', $manuscript)
                    <form method="GET" action="{{ route('editorial.manuscripts.show', $manuscript) }}" class="mt-6 space-y-3">
                        <x-form.input name="q" label="Search reviewers" value="{{ $search }}" placeholder="Name, email, or affiliation" />
                        <x-form.button :full="false">Search</x-form.button>
                    </form>

                    @if ($search !== '')
                        <div class="mt-4 space-y-3">
                            @forelse ($reviewers as $reviewer)
                                <form method="POST" action="{{ route('editorial.manuscripts.reviewers.store', $manuscript) }}" class="rounded-md border border-slate-100 p-3">
                                    @csrf
                                    <p class="font-medium text-brand-950">{{ $reviewer->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $reviewer->email }}</p>
                                    <p class="text-xs text-slate-500">{{ $reviewer->affiliation ?: 'No affiliation listed' }}</p>
                                    <input type="hidden" name="reviewer_id" value="{{ $reviewer->id }}">
                                    <div class="mt-3">
                                        <x-form.input name="due_at" type="date" label="Deadline" value="{{ old('due_at', now()->addDays(21)->toDateString()) }}" required />
                                    </div>
                                    <div class="mt-3">
                                        <x-form.button :full="false">Invite</x-form.button>
                                    </div>
                                </form>
                            @empty
                                <p class="text-sm text-slate-600">No matching reviewers. Try another search.</p>
                            @endforelse
                        </div>
                    @endif
                @else
                    @if (! $manuscript->status->canReceiveReviewers())
                        <p class="mt-3 text-sm text-slate-500">Send the manuscript to peer review before assigning reviewers.</p>
                    @endif
                @endcan
            </section>

            <section class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="font-semibold text-brand-950">Status history</h2>
                <ol class="mt-3 space-y-3 text-sm">
                    @foreach ($manuscript->statusEvents as $event)
                        <li class="border-l-2 border-brand-800 pl-3">
                            <p class="font-medium text-brand-950">{{ $event->to_status->label() }}</p>
                            <p class="text-slate-500">{{ $event->created_at?->format('Y-m-d H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-layouts.app>
