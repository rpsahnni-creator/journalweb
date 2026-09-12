<x-layouts.admin title="Add policy">
    <p class="text-slate-600">Published content is shown on the matching public page. Keep drafts unpublished until the text is ready.</p>

    <form method="POST" action="{{ route('admin.policies.store') }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-form.select
            name="type"
            label="Type"
            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->title()])"
            :selected="old('type')"
            required
        />
        <x-form.input name="title" label="Title" value="{{ old('title') }}" required />
        <x-form.input name="slug" label="Slug" value="{{ old('slug') }}" required />
        <x-form.textarea name="body" label="Body" rows="12" required>{{ old('body') }}</x-form.textarea>
        <x-form.checkbox name="is_published" label="Publish on the public site" :checked="old('is_published', false)" />

        <div class="flex gap-3">
            <x-form.button :full="false">Create policy</x-form.button>
            <a href="{{ route('admin.policies.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
