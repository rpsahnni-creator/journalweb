@props([
    'type' => 'submit',
    'full' => true,
    'variant' => 'primary',
])

@php
    $variants = [
        'primary' => 'bg-brand-900 text-white hover:bg-brand-800 shadow-xs hover:shadow-sm focus-visible:outline-brand-900',
        'secondary' => 'border border-slate-300/80 bg-white text-slate-700 hover:bg-slate-50 shadow-xs focus-visible:outline-slate-400',
        'danger' => 'bg-rose-700 text-white hover:bg-rose-800 shadow-xs hover:shadow-sm focus-visible:outline-rose-700',
        'accent' => 'bg-accent-500 text-brand-950 font-semibold hover:bg-accent-600 hover:text-white shadow-xs focus-visible:outline-accent-500',
    ];
    $width = $full ? 'w-full' : 'inline-flex';
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $width.' inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-150 active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-offset-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed '.$variants[$variant]]) }}
>
    {{ $slot }}
</button>

