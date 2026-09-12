@props([
    'for' => null,
    'label' => null,
    'type' => 'text',
    'name',
])

@php
    $fieldId = $for ?? $name;
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->merge(['class' => 'mt-1.5 block w-full rounded-lg border border-slate-300/90 bg-white px-3.5 py-2 text-sm text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20']) }}
    >
    @error($name)
        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
            <x-icon name="alert-circle" class="h-3.5 w-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

