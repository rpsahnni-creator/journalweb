<x-layouts.admin title="Edit policy">
    <p class="text-slate-600">Saving a published policy updates the public page immediately.</p>

    <form method="POST" action="{{ route('admin.policies.update', $policy) }}" class="mt-6 max-w-3xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.select
            name="type"
            label="Type"
            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->title()])"
            :selected="old('type', $policy->type)"
            required
        />
        <x-form.input name="title" label="Title" value="{{ old('title', $policy->title) }}" required />
        <x-form.input name="slug" label="Slug" value="{{ old('slug', $policy->slug) }}" required />
        <x-form.textarea name="body" label="Body" rows="12" required>{{ old('body', $policy->body) }}</x-form.textarea>
        <x-form.checkbox name="is_published" label="Publish on the public site" :checked="old('is_published', $policy->is_published)" />

        <div class="flex gap-3">
            <x-form.button :full="false">Save policy</x-form.button>
            <a href="{{ route('admin.policies.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
