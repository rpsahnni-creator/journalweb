<x-layouts.app title="New manuscript">
    <x-author-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Create draft manuscript</h1>
    <p class="mt-2 text-slate-600">A unique manuscript number is assigned immediately. The draft remains private until you submit it.</p>

    <form method="POST" action="{{ route('author.manuscripts.store') }}" class="mt-8 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-form.input name="title" label="Title" value="{{ old('title') }}" required />
        <x-form.select
            name="article_type"
            label="Article type"
            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
            :selected="old('article_type')"
            placeholder="Select a type"
            required
        />
        <x-form.textarea name="abstract" label="Abstract" rows="6">{{ old('abstract') }}</x-form.textarea>
        <x-form.input name="keywords" label="Keywords" value="{{ old('keywords') }}" placeholder="Comma-separated keywords" />
        <x-form.button :full="false">Create draft</x-form.button>
    </form>
</x-layouts.app>
