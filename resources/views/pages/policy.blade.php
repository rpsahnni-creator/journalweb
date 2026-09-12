<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" :eyebrow="$eyebrow ?? 'Journal policy'" />
    </x-slot:header>

        @if ($policy)
            <article class="prose-journal max-w-3xl text-base leading-7 text-slate-700">
                {!! nl2br(e($policy->body)) !!}
            </article>
            @if (! empty($relatedLinks))
                <aside class="mt-8 max-w-3xl rounded-xl border border-slate-200/80 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Related</p>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($relatedLinks as $link)
                            <li>
                                <a href="{{ $link['href'] }}" class="font-semibold text-brand-800 underline hover:text-brand-700">{{ $link['label'] }} &rarr;</a>
                            </li>
                        @endforeach
                    </ul>
                </aside>
            @endif
        @else
            <x-empty-state
                :title="$title.' is not published yet'"
                description="This page is reserved for verified journal policy text. It will appear here after the editorial office publishes it."
                icon="file-text"
            />
        @endif
</x-layouts.public>
