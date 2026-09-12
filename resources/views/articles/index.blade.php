<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header
            :title="$title"
            description="Browse published articles. Search, filter by year, and sort by relevance, date, or popularity."
            :eyebrow="__('ui.articles')"
        />
    </x-slot:header>

        <form method="GET" action="{{ route('articles.index') }}" class="mb-8 space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row">
                <label class="sr-only" for="article-search">Search published articles</label>
                <div class="relative flex-1">
                    <x-icon name="search" class="absolute left-3.5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <input
                        id="article-search"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search by title, abstract, keyword, or author..."
                        class="w-full rounded-xl border border-slate-300/90 bg-white pl-11 pr-4 py-3 text-sm text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                    >
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-6 py-3 text-sm font-semibold text-white shadow-xs transition-all hover:bg-brand-800 hover:shadow-sm cursor-pointer shrink-0">
                    <x-icon name="search" class="h-4 w-4" />
                    {{ __('ui.search') }}
                </button>
            </div>

            <!-- Filters Row -->
            <div class="flex flex-wrap items-center gap-3">
                @if (($availableVolumes ?? collect())->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <label for="volume-filter" class="text-xs font-medium text-slate-500">{{ __('ui.volume') }}:</label>
                        <select id="volume-filter" name="volume" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300/90 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 cursor-pointer">
                            <option value="">{{ __('ui.all_volumes') }}</option>
                            @foreach ($availableVolumes as $volumeNumber)
                                <option value="{{ $volumeNumber }}" {{ (string) ($selectedVolume ?? '') === (string) $volumeNumber ? 'selected' : '' }}>{{ $volumeNumber }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <label for="type-filter" class="text-xs font-medium text-slate-500">{{ __('ui.type') }}:</label>
                    <select id="type-filter" name="type" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300/90 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 cursor-pointer">
                        <option value="">{{ __('ui.all_types') }}</option>
                        @foreach ($articleTypes ?? [] as $articleType)
                            <option value="{{ $articleType->value }}" {{ ($selectedType ?? '') === $articleType->value ? 'selected' : '' }}>{{ $articleType->label() }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($availableYears->isNotEmpty())
                    <div class="flex items-center gap-2">
                        <label for="year-filter" class="text-xs font-medium text-slate-500">{{ __('ui.year') }}:</label>
                        <select id="year-filter" name="year" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300/90 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 cursor-pointer">
                            <option value="">{{ __('ui.all_years') }}</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <label for="sort-filter" class="text-xs font-medium text-slate-500">{{ __('ui.sort') }}:</label>
                    <select id="sort-filter" name="sort" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300/90 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-xs focus:border-brand-700 focus:ring-2 focus:ring-brand-700/20 cursor-pointer">
                        <option value="newest" {{ $sortBy === 'newest' ? 'selected' : '' }}>{{ __('ui.newest_first') }}</option>
                        <option value="oldest" {{ $sortBy === 'oldest' ? 'selected' : '' }}>{{ __('ui.oldest_first') }}</option>
                        <option value="most_viewed" {{ $sortBy === 'most_viewed' ? 'selected' : '' }}>{{ __('ui.most_viewed') }}</option>
                        <option value="most_downloaded" {{ $sortBy === 'most_downloaded' ? 'selected' : '' }}>{{ __('ui.most_downloaded') }}</option>
                    </select>
                </div>

                @if ($search || $selectedYear || ($selectedType ?? null) || ($selectedVolume ?? null) || $sortBy !== 'newest')
                    <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300/80 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors">
                        <x-icon name="x" class="h-3 w-3" />
                        {{ __('ui.clear_all') }}
                    </a>
                @endif

                {{-- RSS feed link --}}
                <a href="{{ route('feed.articles') }}" class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-orange-600 hover:text-orange-700" title="Subscribe to article feed">
                    <x-icon name="rss" class="h-3.5 w-3.5" />
                    {{ __('ui.rss_feed') }}
                </a>
            </div>
        </form>

        @if ($search)
            <div class="mb-6 flex items-center justify-between">
                <p class="text-sm text-slate-600">
                    Showing results for <span class="font-semibold text-brand-950">"{{ $search }}"</span>
                    @if ($articles)
                        <span class="text-slate-400">· {{ $articles->total() }} {{ Str::plural('result', $articles->total()) }}</span>
                    @endif
                </p>
                <a href="{{ route('articles.index') }}" class="text-xs text-brand-800 hover:underline">Reset search</a>
            </div>
        @endif

        <div class="grid gap-5">
            @forelse ($articles ?? [] as $article)
                <x-article-card :article="$article" />
            @empty
                <x-empty-state
                    title="{{ $search !== '' ? 'No published articles matched that search' : 'No published articles yet' }}"
                    description="Accepted work appears here after it is published in an issue."
                    icon="file-text"
                />
            @endforelse
        </div>

        @if ($articles)
            <div class="mt-8">{{ $articles->links() }}</div>
        @endif
</x-layouts.public>
