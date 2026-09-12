<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" eyebrow="Journal policy" />
    </x-slot:header>

        <article class="prose-journal max-w-3xl text-base leading-7 text-slate-700">
            {!! nl2br(e($body)) !!}
        </article>
        <aside class="mt-8 max-w-3xl rounded-xl border border-slate-200/80 bg-slate-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Related</p>
            <p class="mt-3 text-sm leading-6 text-slate-700">
                For post-publication corrections and retractions, see the
                <a href="{{ route('corrections-and-retractions') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">Corrections and Retractions</a>
                policy.
            </p>
        </aside>
</x-layouts.public>
