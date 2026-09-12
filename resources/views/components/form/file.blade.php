@props([
    'for' => null,
    'label' => null,
    'name',
    'hint' => null,
])

@php
    $fieldId = $for ?? $name;
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    @if ($hint)
        <p class="mt-0.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="file"
        {{ $attributes->merge(['class' => 'mt-1.5 block w-full text-sm text-slate-700 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-brand-900 file:px-3.5 file:py-2 file:text-xs file:font-semibold file:text-white file:transition-colors hover:file:bg-brand-800']) }}
    >
    @error($name)
        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
            <x-icon name="alert-circle" class="h-3.5 w-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

