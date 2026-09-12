@php
    $journal = \App\Support\CurrentJournal::get();
    $publisherName = $journal?->setting('publisher_name') ?: ($journal?->publisher ?: \App\Support\JournalCopy::PUBLISHER_NAME);
    $publisherAddress = $journal?->setting('publisher_address') ?: \App\Support\JournalCopy::PUBLISHER_ADDRESS;
    $publisherEmail = $journal?->setting('publisher_email') ?: \App\Support\JournalCopy::PUBLISHER_EMAIL;
    $publicationFrequency = $journal?->setting('publication_frequency') ?: \App\Support\JournalCopy::PUBLICATION_FREQUENCY;
@endphp

<footer class="border-t border-slate-200 bg-brand-950 text-slate-200">
    <div class="mx-auto grid max-w-public gap-8 px-4 py-10 sm:px-6 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 lg:px-8">
        <div class="md:col-span-2">
            <p class="flex items-center gap-2 font-serif text-lg font-semibold text-white">
                <x-icon name="book-open" class="h-5 w-5 text-accent-500" />
                {{ $journal?->name ?? config('app.name') }}
            </p>
            <p class="mt-3 max-w-sm text-sm leading-6 text-slate-300">
                {{ __('ui.footer_blurb') }}
            </p>

            @if ($publisherName || $publisherAddress || $publisherEmail || $publicationFrequency)
                <div class="mt-6 max-w-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('ui.publisher') }}</p>
                        <a href="{{ route('publisher') }}" class="text-xs font-medium text-accent-400 hover:text-accent-300 hover:underline">{{ __('ui.publisher_details') }} &rarr;</a>
                    </div>
                    @if ($publisherName)
                        <p class="mt-3 text-sm font-medium text-white">{{ $publisherName }}</p>
                    @endif
                    @if ($publisherAddress)
                        <p class="mt-2 text-sm leading-6 text-slate-300">{{ $publisherAddress }}</p>
                    @endif
                    @if ($publisherEmail)
                        <p class="mt-2 text-sm">
                            <a href="mailto:{{ $publisherEmail }}" class="hover:text-white">{{ $publisherEmail }}</a>
                        </p>
                    @endif
                    @if ($publicationFrequency)
                        <div class="mt-4 pt-3 border-t border-white/10">
                            <p class="text-xs font-semibold uppercase tracking-wide text-accent-400">{{ __('ui.frequency') }}</p>
                            <p class="mt-1 text-sm text-slate-300">{{ $publicationFrequency }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('ui.journal') }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('about') }}" class="hover:text-white">{{ __('ui.about') }}</a></li>
                <li><a href="{{ route('aims-and-scope') }}" class="hover:text-white">{{ __('ui.aims') }}</a></li>
                <li><a href="{{ route('editorial-board') }}" class="hover:text-white">{{ __('ui.board') }}</a></li>
                <li><a href="{{ route('publisher') }}" class="hover:text-white">{{ __('ui.publisher_information') }}</a></li>
                <li><a href="{{ route('contact') }}" class="hover:text-white">{{ __('ui.contact') }}</a></li>
            </ul>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('ui.publishing') }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('issues.current') }}" class="hover:text-white">{{ __('ui.current_issue') }}</a></li>
                <li><a href="{{ route('issues.index') }}" class="hover:text-white">{{ __('ui.previous_issues') }}</a></li>
                <li><a href="{{ route('author-guidelines') }}" class="hover:text-white">{{ __('ui.guidelines') }}</a></li>
                <li><a href="{{ route('article-processing-charges') }}" class="hover:text-white">{{ __('ui.apc') }}</a></li>
                <li><a href="{{ route('peer-review-policy') }}" class="hover:text-white">{{ __('ui.peer_review') }}</a></li>
                <li><a href="{{ route('plagiarism-policy') }}" class="hover:text-white">{{ __('ui.plagiarism') }}</a></li>
                <li><a href="{{ route('feed.articles') }}" class="hover:text-white">{{ __('ui.atom_feed') }}</a></li>
                <li><a href="{{ route('oai', ['verb' => 'Identify']) }}" class="hover:text-white">{{ __('ui.oai') }}</a></li>
            </ul>
            <form method="POST" action="{{ route('subscribe') }}" class="mt-5 space-y-2">
                @csrf
                <label for="toc-email" class="text-xs font-semibold uppercase tracking-wide text-white">{{ __('ui.issue_alerts') }}</label>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input id="toc-email" type="email" name="email" required placeholder="you@institution.edu"
                        class="w-full min-w-0 rounded-md border border-white/15 bg-white/10 px-3 py-2.5 text-sm text-white placeholder:text-slate-400 sm:text-xs">
                    <button type="submit" class="rounded-md bg-accent-500 px-3 py-2.5 text-sm font-semibold text-brand-950 hover:bg-accent-400 sm:text-xs">{{ __('ui.join') }}</button>
                </div>
                <p class="text-[11px] text-slate-400">{{ __('ui.alerts_hint') }}</p>
            </form>
        </div>

        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('ui.ethics') }}</p>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('publication-ethics') }}" class="hover:text-white">{{ __('ui.ethics') }}</a></li>
                <li><a href="{{ route('conflict-of-interest') }}" class="hover:text-white">{{ __('ui.conflict') }}</a></li>
                <li><a href="{{ route('corrections-and-retractions') }}" class="hover:text-white">{{ __('ui.corrections') }}</a></li>
                <li><a href="{{ route('complaints-and-appeals') }}" class="hover:text-white">{{ __('ui.complaints') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <p class="mx-auto max-w-public px-4 py-4 text-xs text-slate-400 sm:px-6 lg:px-8">
            &copy; {{ now()->year }} {{ $journal?->name ?? config('app.name') }}. {{ __('ui.all_rights') }}
        </p>
    </div>
</footer>
