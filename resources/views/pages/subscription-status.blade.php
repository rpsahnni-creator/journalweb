<x-layouts.public :title="$title">
    <x-slot:header>
        <x-page-header :title="$title" eyebrow="Alerts" />
    </x-slot:header>

    <div class="rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
        <p class="text-base leading-7 text-slate-700">{{ $message }}</p>
        <p class="mt-4">
            <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-800 hover:underline">Return to the journal</a>
        </p>
    </div>
</x-layouts.public>
