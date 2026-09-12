<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header
            :title="$title"
            description="Browse published volumes and issues. Search looks through published article titles, abstracts, keywords, and authors."
            eyebrow="Archive"
        />
    </x-slot:header>

        <form method="GET" action="{{ route('issues.index') }}" class="mb-10 flex flex-col gap-3 sm:flex-row">
            <label class="sr-only" for="archive-search">Search published articles</label>
            <div class="relative flex-1">
                <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                <input
                    id="archive-search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search published articles by title, abstract, keyword, or author..."
                    class="w-full rounded-xl border border-slate-300/90 bg-white pl-11 pr-4 py-3 text-sm text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                >
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-6 py-3 text-sm font-semibold text-white shadow-xs transition-all hover:bg-brand-800 hover:shadow-sm cursor-pointer shrink-0">
                <x-icon name="search" class="h-4 w-4" />
                Search
            </button>
            @if ($search)
                <a href="{{ route('issues.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300/80 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Clear
                </a>
            @endif
        </form>

        @if ($articles)
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-serif text-2xl font-semibold text-brand-950">Search results</h2>
                <p class="text-xs text-slate-500">Showing matches for "{{ $search }}"</p>
            </div>
            <div class="grid gap-5">
                @forelse ($articles as $article)
                    <x-article-card :article="$article" />
                @empty
                    <x-empty-state title="No published articles matched that search" icon="search" />
                @endforelse
            </div>
            <div class="mt-8">{{ $articles->links() }}</div>
        @endif

        <div class="{{ $articles ? 'mt-14 pt-10 border-t border-slate-200/80' : '' }}">
            <h2 class="font-serif text-2xl font-semibold text-brand-950">Published issues</h2>
            <p class="mt-1 text-sm text-slate-500">Volumes and numbered issues released for open access reading.</p>
            
            @if ($issues && $issues->count())
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    @foreach ($issues as $issue)
                        <x-issue-card :issue="$issue" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $issues->links() }}</div>
            @else
                <x-empty-state
                    class="mt-6"
                    title="No previous issues"
                    description="Published volumes will be listed here after the first issue is released."
                    icon="library"
                />
            @endif
        </div>
</x-layouts.public>

