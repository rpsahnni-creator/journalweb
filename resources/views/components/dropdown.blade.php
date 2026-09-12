@props([
    'label',
    'align' => 'left',
])

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition-all hover:bg-brand-50/80 hover:text-brand-900 cursor-pointer"
        @click="open = !open"
        @click.outside="open = false"
        :aria-expanded="open.toString()"
    >
        <span>{{ $label }}</span>
        <x-icon name="chevron-down" class="h-3.5 w-3.5 text-slate-400 transition-transform duration-200" ::class="open ? 'rotate-180 text-brand-700' : ''" />
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-95"
        @class([
            'absolute z-30 mt-2.5 w-60 rounded-xl border border-slate-200/80 bg-white/95 backdrop-blur-xl p-1.5 shadow-2xl ring-1 ring-black/5',
            'right-0 origin-top-right' => $align === 'right',
            'left-0 origin-top-left' => $align !== 'right',
        ])
    >
        {{ $slot }}
    </div>
</div>
