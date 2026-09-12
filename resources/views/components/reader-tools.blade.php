@props([
    'compact' => false,
])

<div {{ $attributes->class(['flex items-center gap-1.5', 'text-xs' => $compact]) }}>
    <nav aria-label="{{ __('ui.language') }}" class="inline-flex items-center gap-1 rounded-md border border-slate-300 px-1.5 py-1">
        <a
            href="{{ route('locale', ['locale' => 'en']) }}"
            @class([
                'rounded px-1.5 py-0.5 text-xs font-semibold',
                'bg-brand-900 text-white' => app()->isLocale('en'),
                'text-slate-600 hover:text-brand-800' => ! app()->isLocale('en'),
            ])
        >{{ __('ui.english') }}</a>
        <a
            href="{{ route('locale', ['locale' => 'hi']) }}"
            @class([
                'rounded px-1.5 py-0.5 text-xs font-semibold',
                'bg-brand-900 text-white' => app()->isLocale('hi'),
                'text-slate-600 hover:text-brand-800' => ! app()->isLocale('hi'),
            ])
        >{{ __('ui.hindi') }}</a>
    </nav>
    <button
        type="button"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-brand-900"
        @click="toggleTheme()"
        :aria-label="dark ? @js(__('ui.light_mode')) : @js(__('ui.dark_mode'))"
        :title="dark ? @js(__('ui.light_mode')) : @js(__('ui.dark_mode'))"
    >
        <span x-show="!dark"><x-icon name="moon" class="h-4 w-4" /></span>
        <span x-show="dark" x-cloak><x-icon name="sun" class="h-4 w-4" /></span>
    </button>
</div>
