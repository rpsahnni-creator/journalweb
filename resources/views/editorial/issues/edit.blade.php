<x-layouts.app title="Edit issue">
    <x-editorial-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Edit {{ $issue->displayLabel() }}</h1>
    <p class="mt-2 text-slate-600">Update the cover date and description. Publishing and article order are managed on the issue page.</p>

    <form method="POST" action="{{ route('editorial.issues.update', $issue) }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.select
            name="volume_id"
            label="Volume"
            :options="$volumes->mapWithKeys(fn ($volume) => [$volume->id => $volume->displayLabel()])"
            :selected="old('volume_id', $issue->volume_id)"
            required
        />
        <x-form.input name="number" type="number" label="Issue number" value="{{ old('number', $issue->number) }}" min="1" required />
        <x-form.input name="title" label="Title (optional)" value="{{ old('title', $issue->title) }}" />
        <x-form.textarea name="description" label="Description (optional)" rows="4">{{ old('description', $issue->description) }}</x-form.textarea>
        <x-form.input name="published_at" type="date" label="Publication date" value="{{ old('published_at', $issue->published_at?->toDateString()) }}" />

        <div class="flex gap-3">
            <x-form.button :full="false">Save issue</x-form.button>
            <a href="{{ route('editorial.issues.show', $issue) }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.app>
