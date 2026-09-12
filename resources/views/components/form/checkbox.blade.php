@props([
    'for' => null,
    'label',
    'name',
    'value' => '1',
    'checked' => false,
    'hint' => null,
])

@php
    $fieldId = $for ?? $name;
    $isChecked = (bool) old($name, $checked);
@endphp

<div>
    <label for="{{ $fieldId }}" class="inline-flex items-start gap-2 text-sm text-slate-700">
        <input
            id="{{ $fieldId }}"
            name="{{ $name }}"
            type="checkbox"
            value="{{ $value }}"
            @checked($isChecked)
            {{ $attributes->merge(['class' => 'mt-0.5 rounded border-slate-300 text-brand-800 focus:ring-brand-700']) }}
        >
        <span>
            <span class="font-medium">{{ $label }}</span>
            @if ($hint)
                <span class="mt-0.5 block text-slate-500">{{ $hint }}</span>
            @endif
        </span>
    </label>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
