@props([
    'name',
])

<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0']) }}></i>
