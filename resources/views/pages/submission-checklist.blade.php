<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" :eyebrow="$eyebrow ?? 'For authors'" />
    </x-slot:header>

        <article class="max-w-3xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <ul class="list-disc space-y-3 pl-5 text-base leading-7 text-slate-700 marker:text-brand-800">
                @foreach ($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <x-manuscript-template-download />
                <a href="{{ route('manuscript-preparation') }}" class="text-sm font-semibold text-brand-800 underline hover:text-brand-700">Manuscript preparation &rarr;</a>
            </div>
        </article>
</x-layouts.public>
