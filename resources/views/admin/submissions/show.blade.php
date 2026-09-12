<x-layouts.admin title="Submission">
    <div class="mb-6">
        <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            All submissions
        </a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manuscript</p>
                        <h2 class="mt-1 font-serif text-2xl font-semibold text-brand-950">{{ $submission->title }}</h2>
                    </div>
                    <span @class([
                        'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1',
                        $submission->status->badgeClasses(),
                    ])>
                        {{ $submission->status->label() }}
                    </span>
                </div>

                <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Corresponding author</dt>
                        <dd class="mt-1 font-medium text-slate-900">{{ $submission->author?->name ?? '—' }}</dd>
                        <dd class="text-slate-500">{{ $submission->author?->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-slate-800">{{ $submission->submitted_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?: '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keywords</dt>
                        <dd class="mt-1 text-slate-800">{{ $submission->keywords }}</dd>
                    </div>
                    @if (filled($submission->co_authors))
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Co-authors</dt>
                            <dd class="mt-1 whitespace-pre-line text-slate-800">{{ $submission->co_authors }}</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Abstract</h3>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $submission->abstract }}</p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('admin.submissions.download', $submission) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800">
                        <x-icon name="download" class="h-4 w-4" />
                        Download latest manuscript
                    </a>
                    <p class="self-center text-xs text-slate-500">Private file. Not published on the website.</p>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <h3 class="font-semibold text-slate-900">Version history</h3>
                <p class="mt-1 text-sm text-slate-500">Each upload is stored privately. Version {{ $submission->latestVersion?->version_number ?? 1 }} is the current manuscript.</p>
                @if ($submission->versions->isNotEmpty())
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($submission->versions as $version)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">Version {{ $version->version_number }}</p>
                                    <p class="text-xs text-slate-500">
                                        Uploaded {{ $version->uploaded_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?: '—' }}
                                    </p>
                                </div>
                                <a href="{{ route('admin.submissions.versions.download', [$submission, $version]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
                                    <x-icon name="download" class="h-3.5 w-3.5" />
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-500">No version history is recorded for this submission yet. The latest file is still available from the download button above.</p>
                @endif
            </section>

            <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">Assign reviewers</h3>
                        <p class="mt-1 text-sm text-slate-500">Double-blind review. Reviewers never see author names or affiliations. Current review round: {{ $submission->review_round ?: 1 }}.</p>
                    </div>
                    @if ($reviewsComplete)
                        <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20">
                            Reviews complete
                        </span>
                    @endif
                </div>

                @if ($submission->reviews->isNotEmpty())
                    @foreach ($submission->reviews->groupBy(fn ($review) => (int) ($review->round ?: 1)) as $round => $roundReviews)
                        <div @class(['mt-5' => ! $loop->first, 'mt-4' => $loop->first])>
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Review round {{ $round }}</h4>
                            <ul class="mt-2 divide-y divide-slate-100">
                                @foreach ($roundReviews as $review)
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900">{{ $review->reviewer?->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $review->reviewer?->email }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $review->dueStatusLabel() }}</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span @class([
                                                    'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1',
                                                    $review->status->badgeClasses(),
                                                ])>
                                                    {{ $review->status->label() }}
                                                </span>
                                                @if ($review->isSubmitted() && $review->recommendation)
                                                    <span class="inline-flex items-center rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-700/15">
                                                        {{ $review->recommendation->label() }}
                                                    </span>
                                                @endif
                                                @unless ($review->isSubmitted())
                                                    <form method="POST" action="{{ route('admin.submissions.reviews.destroy', [$submission, $review]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-medium text-rose-700 hover:underline">Remove</button>
                                                    </form>
                                                @endunless
                                            </div>
                                        </div>
                                        @if ($review->isSubmitted() && filled($review->comments_to_editor))
                                            <div class="mt-2 rounded-lg bg-slate-50 p-3 text-sm text-slate-700">
                                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Comments to editor</p>
                                                <p class="mt-1 whitespace-pre-line">{{ $review->comments_to_editor }}</p>
                                            </div>
                                        @endif
                                        @if ($review->isSubmitted() && filled($review->comments_to_author))
                                            <div class="mt-2 rounded-lg border border-slate-100 p-3 text-sm text-slate-700">
                                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Comments to author</p>
                                                <p class="mt-1 whitespace-pre-line">{{ $review->comments_to_author }}</p>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @else
                    <p class="mt-4 text-sm text-slate-500">No reviewers assigned yet.</p>
                @endif

                @if ($availableReviewers->isNotEmpty())
                    <form method="POST" action="{{ route('admin.submissions.reviews.store', $submission) }}" class="mt-5 border-t border-slate-100 pt-5">
                        @csrf
                        <fieldset>
                            <legend class="text-sm font-medium text-slate-700">Select reviewers</legend>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                @foreach ($availableReviewers as $reviewer)
                                    <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            name="reviewer_ids[]"
                                            value="{{ $reviewer->id }}"
                                            class="mt-0.5 rounded border-slate-300 text-brand-800 focus:ring-brand-700"
                                        >
                                        <span>
                                            <span class="font-medium">{{ $reviewer->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $reviewer->email }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error('reviewer_ids')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                            @error('reviewer_ids.*')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </fieldset>
                        <div class="mt-4">
                            <x-form.button :full="false">Assign selected reviewers</x-form.button>
                        </div>
                    </form>
                @endif
            </section>

            @if ($submission->canPublishToIssue())
                <section class="rounded-xl border border-teal-200 bg-teal-50/70 p-6 shadow-xs">
                    <h3 class="font-semibold text-teal-950">Publish to issue</h3>
                    <p class="mt-1 text-sm text-teal-900/80">Copies the title, abstract, keywords, and authors onto a public article page. The formatted PDF stays on a private disk and is only downloaded through an authorized route.</p>
                    <form method="POST" action="{{ route('admin.submissions.publish', $submission) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <x-form.select
                            name="issue_id"
                            label="Issue"
                            :options="$issues->mapWithKeys(fn ($issue) => [$issue->id => $issue->catalogLabel().($issue->title ? ' — '.$issue->title : '')])"
                            :selected="old('issue_id')"
                            placeholder="Select an issue"
                            required
                        />
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.input name="page_start" type="number" label="First page (optional)" value="{{ old('page_start') }}" min="1" />
                            <x-form.input name="page_end" type="number" label="Last page (optional)" value="{{ old('page_end') }}" min="1" />
                        </div>
                        <x-form.file name="pdf" label="Formatted PDF (optional)" hint="PDF only. Stored privately; readers download it through the article page." accept=".pdf,application/pdf" />
                        <x-form.button :full="false">Publish to Issue</x-form.button>
                    </form>
                    @if ($issues->isEmpty())
                        <p class="mt-3 text-sm text-teal-900/80">
                            No issues yet.
                            <a href="{{ route('admin.issues.create') }}" class="font-semibold underline">Create an issue</a>
                            first.
                        </p>
                    @endif
                </section>
            @endif

            @if ($submission->canConvertToArticle())
                <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <h3 class="font-semibold text-slate-900">Convert to article</h3>
                    <p class="mt-1 text-sm text-slate-600">Optional. Creates an unpublished article record without assigning it to an issue.</p>
                    <form method="POST" action="{{ route('admin.submissions.convert', $submission) }}" class="mt-4">
                        @csrf
                        <x-form.button :full="false" variant="secondary">Convert to Article</x-form.button>
                    </form>
                </section>
            @elseif ($submission->article)
                <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                    <h3 class="font-semibold text-slate-900">Linked article</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $submission->status->value === 'published' ? 'This submission has been published.' : 'An article record exists and can be published to an issue when the status is accepted.' }}
                    </p>
                    <p class="mt-3 text-sm font-medium text-brand-950">{{ $submission->article->title }}</p>
                    <p class="mt-1 text-xs font-mono text-slate-500">{{ $submission->article->submission_number }} · {{ $submission->article->status->label() }}</p>
                    @if ($submission->article->isPubliclyVisible())
                        <a href="{{ $submission->article->publicUrl() }}" class="mt-3 inline-flex text-sm font-medium text-brand-800 hover:underline">Open public article page</a>
                    @endif
                </section>
            @endif
        </div>

        <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs h-fit">
            <h3 class="font-semibold text-slate-900">Editorial workflow</h3>
            <p class="mt-1 text-xs text-slate-500">Notes are visible to editors only.</p>
            @if ($reviewsComplete)
                <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900">Reviews complete. You can move this submission to revision requested, accepted, or rejected.</p>
            @endif

            <form method="POST" action="{{ route('admin.submissions.update', $submission) }}" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <x-form.select
                    name="status"
                    label="Status"
                    :options="$statuses"
                    :selected="old('status', $submission->status->value)"
                />
                <x-form.textarea name="editor_notes" label="Editor notes" rows="8">{{ old('editor_notes', $submission->editor_notes) }}</x-form.textarea>
                <x-form.button :full="true">Save changes</x-form.button>
            </form>
        </section>
    </div>
</x-layouts.admin>
