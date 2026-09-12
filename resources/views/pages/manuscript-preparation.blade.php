<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" :eyebrow="$eyebrow ?? 'For authors'" />
    </x-slot:header>

        <article class="max-w-3xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="font-serif text-xl font-semibold text-brand-950">Formatting requirements</h2>
            <ul class="mt-4 list-disc space-y-3 pl-5 text-base leading-7 text-slate-700 marker:text-brand-800">
                @foreach ($items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>

            <div class="mt-8">
                <x-manuscript-template-download />
            </div>
        </article>
</x-layouts.public>
