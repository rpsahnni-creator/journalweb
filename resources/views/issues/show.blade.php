<x-layouts.public
    :title="$title"
    :meta-description="$metaDescription"
    :canonical="$issue?->publicUrl() ?: route('issues.current')"
>
    <x-slot:header>
        <x-page-header
            :title="$title"
            :description="$issue ? $issue->displayLabel() : 'The latest published issue appears here after release.'"
            :eyebrow="$isCurrent ? 'Current issue' : 'Archive'"
        />
    </x-slot:header>

        {{-- CFP when non-demo count is under 5 ($showCallForPapers). Local demo articles stay visible via $issue->articles (publiclyListed). --}}
        @if ($isCurrent && ($showCallForPapers ?? false) && (! $issue || $issue->articles->isEmpty()))
            <x-call-for-papers />
        @elseif (! $issue)
            <x-empty-state
                title="No issue has been published yet"
                description="When a volume and issue are released, published articles will be listed here."
                icon="newspaper"
            />
        @else
            <!-- Breadcrumbs -->
            <nav aria-label="Breadcrumb" class="mb-6 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <a href="{{ route('home') }}" class="hover:text-brand-800 transition-colors">Home</a>
                <span class="text-slate-300">/</span>
                <a href="{{ route('issues.index') }}" class="hover:text-brand-800 transition-colors">Previous Issues</a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-medium">{{ $issue->displayLabel() }}</span>
            </nav>

            <div class="mb-10 relative overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <div class="absolute inset-y-0 left-0 w-2 bg-gradient-to-b from-brand-800 to-brand-950"></div>
                <div class="pl-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-md bg-accent-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-accent-700 ring-1 ring-accent-600/20">
                            {{ $issue->displayLabel() }}
                        </span>
                        @if ($issue->volume)
                            <span class="text-xs text-slate-500 font-medium">Volume {{ $issue->volume->number }} · Year {{ $issue->volume->year }}</span>
                        @endif
                        @if ($issue->published_at)
                            <span class="text-xs text-slate-400">·</span>
                            <span class="text-xs text-slate-500 font-medium">Published {{ $issue->published_at->toFormattedDateString() }}</span>
                        @endif
                    </div>

                    @if ($issue->title)
                        <h2 class="mt-4 font-serif text-2xl sm:text-3xl font-semibold text-brand-950">{{ $issue->title }}</h2>
                    @endif

                    @if ($issue->description)
                        <p class="mt-3 text-sm leading-relaxed text-slate-600 max-w-3xl">{{ $issue->description }}</p>
                    @endif

                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <p class="break-all font-mono text-xs text-slate-400">
                            Issue URL:
                            <a href="{{ $issue->publicUrl() }}" class="text-brand-800 hover:underline">{{ $issue->publicUrl() }}</a>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-brand-950">Contents</h2>
                    <p class="mt-1 text-sm text-slate-500">Each published article has its own public webpage. Abstracts below are available without a login.</p>
                </div>
                <span class="text-xs font-medium text-slate-500">
                    {{ $issue->articles->count() }} {{ \Illuminate\Support\Str::plural('article', $issue->articles->count()) }}
                </span>
            </div>

            <div class="grid gap-5">
                @forelse ($issue->articles as $article)
                    <x-article-card :article="$article" />
                @empty
                    <x-empty-state title="No published articles in this issue" icon="file-text" />
                @endforelse
            </div>
        @endif
</x-layouts.public>

