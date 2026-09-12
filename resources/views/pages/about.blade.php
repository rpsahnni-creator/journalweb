<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" eyebrow="About" />
    </x-slot:header>

    <section>
            @if ($journal?->description || $policy)
                <div class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
                    @if ($journal?->description)
                        <p class="text-lg leading-8 text-slate-700">{{ $journal->description }}</p>
                    @endif

                    @if ($policy)
                        <article @class(['prose-journal text-base leading-7 text-slate-700', 'mt-6' => (bool) $journal?->description])>
                            {!! nl2br(e($policy->body)) !!}
                        </article>
                    @endif

                    @if (($publicationFrequency ?? null) || ($indexingStatus ?? null))
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            @if ($publicationFrequency ?? null)
                                <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Publication Frequency</span>
                                    <p class="mt-0.5 text-base font-semibold text-brand-950">{{ $publicationFrequency }}</p>
                                </div>
                            @endif
                            @if ($indexingStatus ?? null)
                                <div class="rounded-xl border border-slate-200/80 bg-slate-50 p-4">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Indexing Status</span>
                                    <p class="mt-0.5 text-sm leading-6 text-slate-700">{{ $indexingStatus }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @else
                <x-empty-state
                    :title="$title.' is not published yet'"
                    description="This page is reserved for verified journal information. It will appear here after the editorial office publishes it."
                    icon="file-text"
                />
            @endif

            <div class="mt-12 text-center">
                <h2 class="font-serif text-3xl font-bold tracking-tight text-brand-950 sm:text-4xl">Journal information</h2>
                <p class="mt-3 text-sm text-slate-500 sm:text-base">
                    Editorial pages, policies, and author guidance for this journal.
                </p>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <x-feature-card icon="target" title="Aims and Scope" :href="route('aims-and-scope')">
                    Subject coverage and the kinds of submissions considered.
                </x-feature-card>
                <x-feature-card icon="users" title="Editorial Board" :href="route('editorial-board')">
                    Public members of the editorial board.
                </x-feature-card>
                <x-feature-card icon="building" title="Publisher Information" :href="route('publisher')">
                    {{ $publisherName ?? 'Publisher details for this journal.' }}
                </x-feature-card>
                <x-feature-card icon="file-text" title="Author Guidelines" :href="route('author-guidelines')">
                    Manuscript preparation and submission requirements.
                </x-feature-card>
                <x-feature-card icon="shield-check" title="Article Processing Charges" :href="route('article-processing-charges')">
                    Diamond Open Access policy with zero fees for authors.
                </x-feature-card>
                <x-feature-card icon="check-square" title="Peer Review Policy" :href="route('peer-review-policy')">
                    How peer review is conducted for submitted manuscripts.
                </x-feature-card>
                <x-feature-card icon="shield-check" title="Publication Ethics" :href="route('publication-ethics')">
                    Ethical standards for authors, reviewers, and editors.
                </x-feature-card>
                <x-feature-card icon="mail" title="Contact" :href="route('contact')">
                    Editorial office details and a form for correspondence.
                </x-feature-card>
            </div>
    </section>

    <x-slot:after>
        <x-areas-of-focus background="bg-white" />
    </x-slot:after>
</x-layouts.public>
