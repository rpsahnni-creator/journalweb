@props([
    'icon',
    'title',
    'href' => null,
])

@php
    $classes = 'flex h-full flex-col rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-7';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes.' card-hover-lift transition-colors hover:border-brand-200') }}>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
        <h3 class="mt-5 font-serif text-lg font-semibold text-brand-950">{{ $title }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $slot }}</p>
    </a>
@else
    <div {{ $attributes->class($classes) }}>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
        <h3 class="mt-5 font-serif text-lg font-semibold text-brand-950">{{ $title }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $slot }}</p>
    </div>
@endif
