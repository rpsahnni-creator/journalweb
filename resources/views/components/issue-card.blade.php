@props([
    'issue',
])

<a href="{{ route('issues.show', ['volume' => $issue->volume->number, 'issue' => $issue->number]) }}" class="card-hover-lift corner-accent group relative block overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm">
    <div class="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-brand-600 via-brand-800 to-brand-950"></div>
    <div class="pl-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-lg bg-gradient-to-r from-accent-50 to-accent-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-accent-700 ring-1 ring-accent-200/60 shadow-xs">
                    {{ $issue->displayLabel() }}
                </span>
                @if ($issue->is_special_issue)
                    <span class="inline-flex items-center rounded-lg bg-amber-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-amber-800 ring-1 ring-amber-600/20">Special Issue</span>
                @endif
                @if ($issue->is_special_issue && filled($issue->special_issue_theme))
                    <span class="text-xs font-medium text-brand-800">{{ $issue->special_issue_theme }}</span>
                @endif
            </div>
            @if ($issue->publication_month_year || $issue->volume)
                <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-500">
                    <x-icon name="calendar" class="h-3 w-3" />
                    {{ $issue->publication_month_year ?: $issue->volume?->year }}
                </span>
            @endif
        </div>

        <h2 class="mt-4 font-serif text-xl font-semibold text-brand-950 transition-colors group-hover:text-brand-700">
            {{ $issue->title ?: 'Issue '.$issue->number }}
        </h2>

        <div class="mt-4 flex items-center justify-between gap-2 text-sm text-slate-500">
            <span>
                {{ $issue->article_count ?? $issue->articles_count ?? $issue->publishedNonDemoArticleCount() }}
                {{ \Illuminate\Support\Str::plural('article', $issue->article_count ?? $issue->articles_count ?? $issue->publishedNonDemoArticleCount()) }}
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand-700 group-hover:text-accent-600 transition-colors">
                View issue
                <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform group-hover:translate-x-1" />
            </span>
        </div>
    </div>
</a>
