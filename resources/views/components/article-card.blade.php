@props([
    'article',
])

<article class="card-hover-lift corner-accent group flex flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-7 sm:p-8 shadow-sm">
    <div>
        <div class="flex flex-wrap items-center gap-2.5">
            @if ($article->article_type)
                <span class="inline-flex items-center rounded-lg bg-gradient-to-r from-brand-50 to-brand-100 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 shadow-xs">
                    {{ $article->article_type->label() }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                <x-icon name="calendar" class="h-3.5 w-3.5 text-slate-400" />
                Published
                @if ($article->published_at)
                    · {{ $article->published_at->toFormattedDateString() }}
                @endif
            </span>
            @if (filled($article->doi))
                <span class="inline-flex items-center rounded-md bg-slate-50 px-2.5 py-0.5 font-mono text-[11px] text-slate-600 ring-1 ring-slate-200/80">
                    DOI: {{ $article->doi }}
                </span>
            @endif
        </div>

        <h2 class="mt-4 font-serif text-xl sm:text-2xl font-semibold text-brand-950 transition-colors group-hover:text-brand-700 leading-snug">
            <a href="{{ $article->publicUrl() }}">{{ $article->title }}</a>
        </h2>

        @if ($article->authors->isNotEmpty())
            <p class="mt-3 flex items-center gap-2 text-sm font-medium text-slate-600">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-50 ring-1 ring-brand-200/50">
                    <x-icon name="user" class="h-3 w-3 text-brand-600" />
                </span>
                <span>{{ $article->authors->pluck('name')->join(', ') }}</span>
            </p>
        @endif

        @if ($article->abstract)
            <p class="mt-4 text-sm leading-relaxed text-slate-600">
                {{ \Illuminate\Support\Str::limit($article->abstract, 220) }}
            </p>
        @endif
    </div>

    <div class="mt-7 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p class="break-all font-mono text-xs text-slate-400">{{ $article->publicUrl() }}</p>
        <a href="{{ $article->publicUrl() }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 transition-all hover:text-accent-600 group-hover:translate-x-0.5 shrink-0">
            View article
            <x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
        </a>
    </div>
</article>
