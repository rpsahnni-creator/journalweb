@props([
    'href',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 transition-colors hover:bg-brand-50 hover:text-brand-900']) }}>
    {{ $slot }}
</a>

