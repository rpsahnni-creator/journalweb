@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

<section class="border-b border-slate-200 bg-brand-950 text-white">
    <x-public-container class="py-12">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-500">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-3 font-serif text-3xl font-semibold sm:text-4xl">{{ $title }}</h1>
        @if ($description)
            <p class="mt-4 max-w-3xl text-base leading-7 text-slate-200">{{ $description }}</p>
        @endif
        {{ $slot }}
    </x-public-container>
</section>
