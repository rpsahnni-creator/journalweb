<x-layouts.public :title="$title" :meta-description="$metaDescription" :canonical="$article->publicUrl()" og-type="article">
    <x-slot:head>
        <x-scholar-meta :article="$article" :issue="$issue" :journal="$journal" :pdf-file="$pdf" />
    </x-slot:head>

    <script type="application/ld+json">{!! $article->schemaOrgJson() !!}</script>

    <x-slot:header>
        <x-page-header :title="$article->title" :eyebrow="$article->article_type?->label() ?: 'Article'" />
    </x-slot:header>

        <!-- Reading Progress Bar -->
        <div
            class="fixed top-0 left-0 z-50 h-0.5 bg-gradient-to-r from-accent-500 to-brand-800 transition-all duration-100 ease-out"
            x-data="{ progress: 0 }"
            x-init="window.addEventListener('scroll', () => {
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                progress = docHeight > 0 ? Math.min((window.scrollY / docHeight) * 100, 100) : 0;
            })"
            :style="'width: ' + progress + '%'"
        ></div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="Breadcrumb" class="mb-6 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-brand-800 transition-colors">{{ __('ui.home') }}</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('articles.index') }}" class="hover:text-brand-800 transition-colors">{{ __('ui.articles') }}</a>
            @if ($issue)
                <span class="text-slate-300">/</span>
                <a href="{{ route('issues.show', ['volume' => $issue->volume->number, 'issue' => $issue->number]) }}" class="hover:text-brand-800 transition-colors">
                    {{ $issue->displayLabel() }}
                </a>
            @endif
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-medium truncate max-w-sm">{{ $article->title }}</span>
        </nav>

        <!-- Publication Meta Pill Strip -->
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600 bg-slate-100/80 rounded-lg p-3 border border-slate-200/80">
            @if ($issue)
                <a href="{{ $issue->publicUrl() }}" class="font-semibold text-brand-800 hover:text-brand-900 underline">
                    {{ $issue->catalogLabel() }}
                </a>
            @endif
            @if ($article->published_at)
                <span class="text-slate-400">·</span>
                <span>{{ __('ui.published') }} {{ $article->published_at->toFormattedDateString() }}</span>
            @endif
            @if ($article->articleNumber())
                <span class="text-slate-400">·</span>
                <span>{{ __('ui.article_word') }} {{ $article->articleNumber() }}</span>
            @endif
            @if ($article->pageRange())
                <span class="text-slate-400">·</span>
                <span>pp. {{ $article->pageRange() }}</span>
            @endif
            <span class="text-slate-400">·</span>
            <span class="inline-flex items-center gap-1 font-semibold text-emerald-700">
                <x-icon name="unlock" class="h-3 w-3" />
                {{ __('ui.open_access') }}
            </span>
        </div>

        @if (filled($journal?->issn) || filled($journal?->eissn))
            <p class="mt-2 text-xs text-slate-500">
                @if (filled($journal->issn))
                    Print ISSN {{ $journal->issn }}
                @endif
                @if (filled($journal->issn) && filled($journal->eissn))
                    ·
                @endif
                @if (filled($journal->eissn))
                    Online ISSN {{ $journal->eissn }}
                @endif
            </p>
        @endif

        <!-- Article Metrics Strip -->
        <div class="mt-4 flex flex-wrap items-center gap-4 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 font-medium text-blue-700 ring-1 ring-blue-200/60">
                <x-icon name="eye" class="h-3.5 w-3.5" />
                {{ number_format($article->view_count) }} {{ __('ui.views') }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 font-medium text-emerald-700 ring-1 ring-emerald-200/60">
                <x-icon name="download" class="h-3.5 w-3.5" />
                {{ number_format($article->download_count) }} {{ __('ui.downloads') }}
            </span>
            @if ($article->published_at)
                @php
                    $wordCount = str_word_count($article->abstract ?? '');
                    $readingTime = max(1, (int) ceil($wordCount / 200));
                @endphp
                <span class="inline-flex items-center gap-1.5">
                    <x-icon name="clock" class="h-3.5 w-3.5" />
                    {{ $readingTime }} {{ __('ui.min_read') }}
                </span>
            @endif
        </div>

        <!-- Authors Grid -->
        <section class="mt-6 rounded-xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('ui.authors_affiliations') }}</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @foreach ($article->authors as $author)
                    <li class="py-3 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-brand-950 text-base">{{ $author->name }}</span>
                            @if ($author->is_corresponding)
                                <span class="inline-flex items-center rounded-full bg-accent-50 px-2.5 py-0.5 text-xs font-medium text-accent-700 ring-1 ring-accent-600/20">
                                    {{ __('ui.corresponding_author') }}
                                </span>
                            @endif
                        </div>
                        @if ($author->affiliation)
                            <div class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
                                <x-icon name="building-2" class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                <span>{{ $author->affiliation }}</span>
                            </div>
                        @endif
                        @if ($author->orcid)
                            <div class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                <span class="font-semibold text-emerald-700">iD</span>
                                <a href="https://orcid.org/{{ $author->orcid }}" target="_blank" rel="noopener" class="text-brand-800 hover:underline">
                                    ORCID {{ $author->orcid }}
                                </a>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <!-- Action Bar (PDF, Citation, Share, Export) -->
        <div class="no-print mt-6 flex flex-wrap items-center gap-3">
            @if ($article->hasDownloadablePdf())
                <a href="{{ route('articles.pdf', $article) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-brand-800 hover:shadow-sm">
                    <x-icon name="file-down" class="h-4 w-4" />
                    {{ __('ui.download_pdf') }}
                </a>
            @endif
            <a href="#citation" class="inline-flex items-center gap-2 rounded-lg border border-slate-300/90 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-xs transition-all hover:bg-slate-50 hover:text-brand-900">
                <x-icon name="quote" class="h-4 w-4 text-slate-500" />
                {{ __('ui.citation') }}
            </a>

            <!-- Citation Export Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false" type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300/90 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-xs transition-all hover:bg-slate-50 hover:text-brand-900 cursor-pointer">
                    <x-icon name="file-output" class="h-4 w-4 text-slate-500" />
                    {{ __('ui.export') }}
                    <x-icon name="chevron-down" class="h-3 w-3 text-slate-400" />
                </button>
                <div x-show="open" x-cloak x-transition
                    class="absolute left-0 z-10 mt-1 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                    <a href="{{ route('articles.export.bibtex', $article) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">
                        <x-icon name="file-text" class="h-4 w-4 text-slate-400" />
                        BibTeX (.bib)
                    </a>
                    <a href="{{ route('articles.export.ris', $article) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">
                        <x-icon name="file-text" class="h-4 w-4 text-slate-400" />
                        RIS (.ris)
                    </a>
                    <a href="{{ route('articles.export.apa', $article) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">APA</a>
                    <a href="{{ route('articles.export.mla', $article) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">MLA</a>
                    <a href="{{ route('articles.export.chicago', $article) }}"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">Chicago</a>
                </div>
            </div>

            <!-- Social Share Buttons -->
            <div class="ml-auto flex items-center gap-2">
                <span class="text-xs font-medium text-slate-400 mr-1">{{ __('ui.share') }}:</span>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($article->title) }}&url={{ urlencode($article->publicUrl()) }}"
                    target="_blank" rel="noopener" title="Share on X (Twitter)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-sky-100 hover:text-sky-600">
                    <x-icon name="share-2" class="h-3.5 w-3.5" />
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($article->publicUrl()) }}"
                    target="_blank" rel="noopener" title="Share on Facebook"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-blue-100 hover:text-blue-600">
                    <x-icon name="share-2" class="h-3.5 w-3.5" />
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($article->publicUrl()) }}"
                    target="_blank" rel="noopener" title="Share on LinkedIn"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-blue-100 hover:text-blue-600">
                    <x-icon name="linkedin" class="h-3.5 w-3.5" />
                </a>
                <a href="https://wa.me/?text={{ urlencode($article->title.' '.$article->publicUrl()) }}"
                    target="_blank" rel="noopener" title="Share on WhatsApp"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-emerald-100 hover:text-emerald-600">
                    <x-icon name="message-circle" class="h-3.5 w-3.5" />
                </a>
                <a href="mailto:?subject={{ urlencode($article->title) }}&body={{ urlencode('Read this article: '.$article->publicUrl()) }}"
                    title="Share via email"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-amber-100 hover:text-amber-600">
                    <x-icon name="mail" class="h-3.5 w-3.5" />
                </a>
                <button type="button" title="Copy link"
                    x-data
                    @click="
                        navigator.clipboard.writeText('{{ $article->publicUrl() }}');
                        Swal.fire({ title: 'Link copied!', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
                    "
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition-colors hover:bg-brand-100 hover:text-brand-700 cursor-pointer">
                    <x-icon name="link" class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <!-- DOI Badge -->
        @if (filled($article->doi))
            <div class="mt-4 flex flex-wrap items-center gap-4">
                <div class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm ring-1 ring-amber-200/60">
                    <span class="font-semibold text-amber-800">DOI</span>
                    <a href="https://doi.org/{{ $article->doi }}" target="_blank" rel="noopener"
                        class="font-mono text-amber-700 hover:text-amber-900 hover:underline">
                        {{ $article->doi }}
                    </a>
                </div>
                @if ($article->hasResolvableDoi())
                    <div
                        class="altmetric-embed"
                        data-badge-type="donut"
                        data-doi="{{ $article->doi }}"
                        data-hide-no-mentions="true"
                    ></div>
                @endif
            </div>
        @endif

        <!-- Abstract Card -->
        <section class="mt-8 rounded-xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs border-l-4 border-l-brand-800">
            <h2 class="font-serif text-2xl font-semibold text-brand-950">{{ __('ui.abstract') }}</h2>
            <p class="mt-4 text-base leading-relaxed text-slate-700">{{ $article->abstract ?: __('ui.no_abstract') }}</p>
        </section>

        <!-- Keywords -->
        @if ($article->keywords)
            <div class="mt-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ __('ui.keywords') }}</h3>
                <ul class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($article->keywords as $keyword)
                        <li>
                            <a href="{{ route('articles.index', ['q' => $keyword]) }}" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3.5 py-1 text-xs font-medium text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-800">
                                <x-icon name="tag" class="h-3 w-3 text-slate-400" />
                                <span>{{ $keyword }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- How to cite section -->
        <section id="citation" class="mt-10 rounded-xl border border-slate-200/90 bg-white p-6 sm:p-7 shadow-xs">
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-serif text-2xl font-semibold text-brand-950">{{ __('ui.cite') }}</h2>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer"
                    x-data
                    @click="
                        navigator.clipboard.writeText(@js($article->citation()));
                        Swal.fire({
                            title: 'Citation copied!',
                            text: 'Formatted citation has been copied to your clipboard.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    "
                >
                    <x-icon name="copy" class="h-3.5 w-3.5" />
                    <span>{{ __('ui.copy_citation') }}</span>
                </button>
            </div>
            
            <p class="mt-4 rounded-lg bg-slate-50 p-4 font-mono text-xs leading-relaxed text-slate-800 border border-slate-200/70">
                {{ $article->citation() }}
            </p>
            
            <p class="mt-4 break-all text-xs text-slate-500">
                {{ __('ui.article_url') }}
                <a href="{{ $article->publicUrl() }}" class="font-mono text-brand-800 hover:underline">{{ $article->publicUrl() }}</a>
            </p>
            @if (filled($article->doi))
                <p class="mt-2 text-xs text-slate-500">https://doi.org/{{ $article->doi }}</p>
            @endif
        </section>

        <!-- Related Articles -->
        @if ($relatedArticles->isNotEmpty())
            <section class="mt-10">
                <h2 class="font-serif text-2xl font-semibold text-brand-950">{{ __('ui.related') }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ __('ui.related_hint') }}</p>
                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    @foreach ($relatedArticles as $related)
                        <a href="{{ $related->publicUrl() }}" class="group flex flex-col rounded-xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:shadow-sm hover:border-brand-200">
                            <h3 class="font-serif text-base font-semibold leading-snug text-brand-950 group-hover:text-brand-800">
                                {{ $related->title }}
                            </h3>
                            @if ($related->authors->isNotEmpty())
                                <p class="mt-1.5 text-xs text-slate-500">
                                    {{ $related->authors->pluck('name')->join(', ') }}
                                </p>
                            @endif
                            @if (filled($related->abstract))
                                <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ Str::limit($related->abstract, 120) }}</p>
                            @endif
                            <div class="mt-auto pt-3 flex items-center gap-3 text-xs text-slate-400">
                                @if ($related->published_at)
                                    <span>{{ $related->published_at->toFormattedDateString() }}</span>
                                @endif
                                <span>{{ number_format($related->view_count) }} {{ __('ui.views') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Back to Top Button -->
        @if ($article->hasResolvableDoi())
            <script src="https://d1bxh8uas1mnw7.cloudfront.net/assets/embed.js" async></script>
        @endif

        <div
            class="fixed bottom-6 right-6 z-40"
            x-data="{ show: false }"
            x-init="window.addEventListener('scroll', () => show = window.scrollY > 500)"
        >
            <button
                x-show="show"
                x-cloak
                x-transition
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-900 text-white shadow-lg transition-all hover:bg-brand-800 cursor-pointer"
                title="Back to top"
            >
                <x-icon name="arrow-up" class="h-4 w-4" />
            </button>
        </div>
</x-layouts.public>
