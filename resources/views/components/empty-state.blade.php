@props([
    'title',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-200 bg-white/60 px-6 py-16 text-center backdrop-blur-sm']) }}>
    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-50 to-brand-100 text-brand-600 ring-8 ring-brand-50/50 shadow-sm">
        <x-icon :name="$icon" class="h-7 w-7" />
    </div>
    <h2 class="mt-6 font-serif text-xl font-semibold text-brand-950">{{ $title }}</h2>
    @if ($description)
        <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-slate-500">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-7">
            {{ $slot }}
        </div>
    @endif
</div>
