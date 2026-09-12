<x-layouts.app title="Create issue">
    <x-editorial-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Create issue</h1>
    <p class="mt-2 text-slate-600">Set the cover date and description now if you know them. The issue stays private until you publish it.</p>

    @if ($volumes->isEmpty())
        <x-alert type="warning">Create a volume before adding an issue.</x-alert>
    @endif

    <form method="POST" action="{{ route('editorial.issues.store') }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-form.select
            name="volume_id"
            label="Volume"
            :options="$volumes->mapWithKeys(fn ($volume) => [$volume->id => $volume->displayLabel()])"
            :selected="old('volume_id')"
            placeholder="Select a volume"
            required
        />
        <x-form.input name="number" type="number" label="Issue number" value="{{ old('number') }}" min="1" required />
        <x-form.input name="title" label="Title (optional)" value="{{ old('title') }}" />
        <x-form.textarea name="description" label="Description (optional)" rows="4">{{ old('description') }}</x-form.textarea>
        <x-form.input name="published_at" type="date" label="Publication date" value="{{ old('published_at') }}" />
        <p class="text-sm text-slate-500">The publication date is stored as the cover date. Public pages use it only after the issue is published.</p>

        <div class="flex gap-3">
            <x-form.button :full="false">Create issue</x-form.button>
            <a href="{{ route('editorial.issues.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.app>
