@props([
    'for' => null,
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
])

@php
    $fieldId = $for ?? $name;
    $current = old($name, $selected);
@endphp

<div>
    @if ($label)
        <label for="{{ $fieldId }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <select
        id="{{ $fieldId }}"
        name="{{ $name }}"
        {{ $attributes->merge(['class' => 'mt-1.5 block w-full rounded-lg border border-slate-300/90 bg-white px-3.5 py-2 text-sm text-slate-900 shadow-xs transition-colors focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20']) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if ($slot->isEmpty())
            @foreach ($options as $value => $optionLabel)
                <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $optionLabel }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>
    @error($name)
        <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
            <x-icon name="alert-circle" class="h-3.5 w-3.5" />
            {{ $message }}
        </p>
    @enderror
</div>

