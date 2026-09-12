@props([
    'class' => '',
])

<a
    href="{{ route('downloads.manuscript-template') }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800 '.$class]) }}
>
    <x-icon name="download" class="h-4 w-4" />
    Download Manuscript Template (.docx)
</a>
