<x-layouts.public title="Home" meta-description="Published issues, editorial policy, and author information. Unpublished manuscripts are not displayed." :full-bleed="true">
    @if ($showCallForPapers)
        <div class="border-b border-amber-200 bg-amber-50">
            <x-public-container class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm text-amber-950">
                <p class="font-medium">{{ __('ui.cfp_open') }}</p>
                <a href="{{ route('author-guidelines') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">{{ __('ui.author_guidelines_short') }}</a>
            </x-public-container>
        </div>
    @endif

    <section class="bg-brand-950 text-white">
        <x-public-container class="py-10 text-center sm:py-16 lg:py-24">
            <p class="text-xs font-semibold uppercase tracking-widest text-accent-500 sm:text-sm sm:tracking-[0.2em]">{{ __('ui.scholarly_publishing') }}</p>
            <h1 class="mx-auto mt-4 max-w-3xl font-serif text-3xl font-semibold leading-tight sm:text-4xl lg:text-5xl">
                {{ $journal?->name ?? config('app.name') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-7 text-slate-200 sm:text-lg sm:leading-8">
                {{ $journal?->description ?: 'A working platform for academic journal operations. Published content appears here after editorial acceptance. Unpublished manuscripts remain private.' }}
            </p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('issues.current') }}" class="inline-flex items-center gap-2 rounded-md bg-accent-500 px-5 py-2.5 text-sm font-semibold text-brand-950 hover:bg-accent-600 hover:text-white">
                    <x-icon name="newspaper" class="h-4 w-4" />
                    {{ __('ui.current_issue_cta') }}
                </a>
                <a href="{{ route('author-guidelines') }}" class="inline-flex items-center gap-2 rounded-md border border-white/20 px-5 py-2.5 text-sm font-semibold text-white hover:bg-white/10">
                    {{ __('ui.for_authors_cta') }}
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </x-public-container>
    </section>

    <section class="bg-white">
        <x-public-container class="grid gap-6 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-5 py-4 text-center">
                <p class="font-serif text-3xl font-semibold text-brand-950">{{ number_format($stats['articles']) }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('ui.published_articles') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-5 py-4 text-center">
                <p class="font-serif text-3xl font-semibold text-brand-950">{{ number_format($stats['issues']) }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('ui.published_issues') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-5 py-4 text-center">
                <p class="font-serif text-3xl font-semibold text-brand-950">{{ number_format($stats['downloads']) }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('ui.pdf_downloads') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-5 py-4 text-center">
                <p class="font-serif text-3xl font-semibold text-brand-950">{{ number_format($stats['board']) }}</p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('ui.editorial_board') }}</p>
            </div>
        </x-public-container>
    </section>

    @if ($featuredArticle)
        <section class="bg-slate-50">
            <x-public-container class="py-12">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-600">{{ __('ui.featured') }}</p>
                <h2 class="mt-2 font-serif text-2xl font-semibold text-brand-950">{{ $featuredArticle->title }}</h2>
                @if ($featuredArticle->authors->isNotEmpty())
                    <p class="mt-2 text-sm text-slate-600">{{ $featuredArticle->authors->pluck('name')->join(', ') }}</p>
                @endif
                <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($featuredArticle->abstract, 280) }}</p>
                <a href="{{ $featuredArticle->publicUrl() }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-800 hover:underline">
                    {{ __('ui.read_article') }}
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </x-public-container>
        </section>
    @endif

    <section class="bg-slate-50">
        <x-public-container class="py-16">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <x-feature-card icon="info" :title="__('ui.about_journal')" :href="route('about')">
                    {{ $aboutCardText }}
                </x-feature-card>
                <x-feature-card icon="target" :title="__('ui.aims')" :href="route('aims-and-scope')">
                    {{ $aimsCardText }}
                </x-feature-card>
                <x-feature-card icon="users" :title="__('ui.editorial_board')" :href="route('editorial-board')">
                    {{ $boardCount > 0 ? $boardCount.' public board listing'.($boardCount === 1 ? '' : 's') : 'Public members will appear after they are published.' }}
                </x-feature-card>
            </div>
        </x-public-container>
    </section>

    <x-areas-of-focus background="bg-white" />

    <section class="bg-slate-50">
        <x-public-container class="py-16">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between sm:gap-4">
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-brand-950 sm:text-3xl">{{ __('ui.current_issue') }}</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        {{ $showCallForPapers ? __('ui.current_issue_open') : __('ui.only_published') }}
                    </p>
                </div>
                <a href="{{ route('issues.index') }}" class="shrink-0 text-sm font-semibold text-brand-800 hover:text-brand-700">{{ __('ui.previous_issues') }}</a>
            </div>

            @if ($showCallForPapers)
                <x-call-for-papers class="mt-8" />
            @elseif ($currentIssue)
                <div class="mt-8 grid items-stretch gap-6 lg:grid-cols-2">
                    <article class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                        <div class="relative overflow-hidden bg-brand-950 px-5 py-8 text-white sm:px-7 sm:py-10">
                            <div class="absolute -right-8 -top-10 h-40 w-40 rounded-full bg-accent-500/15 blur-2xl"></div>
                            <div class="relative">
                                <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-accent-300 ring-1 ring-white/15">
                                    {{ __('ui.current_issue') }}
                                </span>
                                <p class="mt-5 font-serif text-3xl font-semibold tracking-tight">{{ $currentIssue->displayLabel() }}</p>
                                @if ($currentIssue->publication_month_year)
                                    <p class="mt-2 text-sm text-slate-300">{{ $currentIssue->publication_month_year }}</p>
                                @elseif ($currentIssue->volume)
                                    <p class="mt-2 text-sm text-slate-300">{{ $currentIssue->volume->year }} · Volume {{ $currentIssue->volume->number }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-6 sm:p-7">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <x-icon name="book-open" class="h-5 w-5" />
                            </div>
                            <h3 class="mt-5 font-serif text-xl font-semibold text-brand-950">
                                {{ $currentIssue->title ?: __('ui.latest_published_issue') }}
                            </h3>
                            @if ($currentIssue->description)
                                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ \Illuminate\Support\Str::limit($currentIssue->description, 160) }}</p>
                            @else
                                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('ui.published_from_issue') }}</p>
                            @endif

                            <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium text-slate-500">
                                @if ($currentIssue->publication_month_year)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200/80">
                                        <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                                        {{ $currentIssue->publication_month_year }}
                                    </span>
                                @elseif ($currentIssue->published_at)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200/80">
                                        <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                                        {{ $currentIssue->published_at->toFormattedDateString() }}
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 ring-1 ring-slate-200/80">
                                    <x-icon name="file-text" class="h-3.5 w-3.5 text-slate-400" />
                                    {{ $currentArticles->count() }} {{ \Illuminate\Support\Str::plural('article', $currentArticles->count()) }}
                                </span>
                            </div>

                            <a href="{{ $currentIssue->publicUrl() }}" class="mt-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-800">
                                {{ __('ui.view_current_issue') }}
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </article>

                    <article class="flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-7">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="font-serif text-xl font-semibold text-brand-950">{{ __('ui.in_this_issue') }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ __('ui.only_published') }}</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <x-icon name="list" class="h-5 w-5" />
                            </div>
                        </div>

                        <div class="mt-6 flex-1">
                            @forelse ($currentArticles->take(5) as $article)
                                <a href="{{ $article->publicUrl() }}" class="block rounded-lg border-t border-slate-100 py-3.5 first:border-t-0 first:pt-0 last:pb-0 hover:text-brand-800">
                                    <p class="font-serif text-base font-semibold leading-snug text-brand-950">{{ $article->title }}</p>
                                    @if ($article->authors->isNotEmpty())
                                        <p class="mt-1 text-xs text-slate-500">{{ $article->authors->pluck('name')->join(', ') }}</p>
                                    @endif
                                </a>
                            @empty
                                <p class="text-sm leading-relaxed text-slate-500">{{ __('ui.no_articles_in_issue') }}</p>
                            @endforelse
                        </div>

                        @if ($currentArticles->isNotEmpty())
                            <a href="{{ $currentIssue->publicUrl() }}" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-800 hover:text-brand-700">
                                {{ $currentArticles->count() > 5 ? __('ui.view_all_articles') : __('ui.open_issue_page') }}
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endif
                    </article>
                </div>
            @else
                <x-empty-state
                    class="mt-8"
                    :title="__('ui.no_issue_yet')"
                    :description="__('ui.no_issue_yet_desc')"
                    icon="newspaper"
                />
            @endif
        </x-public-container>
    </section>

    <section class="bg-white">
        <x-public-container class="py-16">
            <h2 class="font-serif text-2xl font-semibold text-brand-950 sm:text-3xl">{{ __('ui.recent_articles') }}</h2>
            <div class="mt-8 grid gap-4 lg:grid-cols-2">
                @forelse ($recentArticles as $article)
                    <x-article-card :article="$article" />
                @empty
                    <x-empty-state
                        class="lg:col-span-2"
                        :title="__('ui.no_articles_yet')"
                        :description="__('ui.no_articles_yet_desc')"
                        icon="file-text"
                    />
                @endforelse
            </div>
        </x-public-container>
    </section>
</x-layouts.public>
