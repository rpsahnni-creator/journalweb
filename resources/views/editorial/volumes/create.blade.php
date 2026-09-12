<x-layouts.app title="Create volume">
    <x-editorial-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Create volume</h1>
    <p class="mt-2 text-slate-600">Volume numbers must be unique for this journal.</p>

    <form method="POST" action="{{ route('editorial.volumes.store') }}" class="mt-6 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-form.input name="number" type="number" label="Volume number" value="{{ old('number') }}" min="1" required />
        <x-form.input name="year" type="number" label="Year" value="{{ old('year', now()->year) }}" min="1800" required />
        <x-form.input name="title" label="Title (optional)" value="{{ old('title') }}" />

        <div class="flex gap-3">
            <x-form.button :full="false">Create volume</x-form.button>
            <a href="{{ route('editorial.volumes.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.app>
