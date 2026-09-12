@props([
    'type' => 'success',
])

@php
    $styles = match ($type) {
        'error' => ['class' => 'border-rose-200 bg-rose-50/90 text-rose-900', 'icon' => 'alert-circle', 'iconClass' => 'text-rose-600'],
        'warning' => ['class' => 'border-amber-200 bg-amber-50/90 text-amber-900', 'icon' => 'alert-triangle', 'iconClass' => 'text-amber-600'],
        'info' => ['class' => 'border-blue-200 bg-blue-50/90 text-blue-900', 'icon' => 'info', 'iconClass' => 'text-blue-600'],
        default => ['class' => 'border-emerald-200 bg-emerald-50/90 text-emerald-900', 'icon' => 'check-circle', 'iconClass' => 'text-emerald-600'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 flex items-start gap-3 rounded-lg border p-4 text-sm shadow-xs '.$styles['class']]) }}>
    <x-icon :name="$styles['icon']" class="mt-0.5 h-4 w-4 shrink-0 {{ $styles['iconClass'] }}" />
    <div class="flex-1 leading-relaxed">
        {{ $slot }}
    </div>
</div>

