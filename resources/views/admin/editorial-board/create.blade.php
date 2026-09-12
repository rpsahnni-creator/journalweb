<x-layouts.admin title="Add board member">
    <p class="text-slate-600">Use a real name only when you have permission to list the person. Private members stay off the public page.</p>

    <form method="POST" action="{{ route('admin.editorial-board.store') }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        <x-form.input name="name" label="Name" value="{{ old('name') }}" required />
        <x-form.input name="role_title" label="Role title" value="{{ old('role_title') }}" required />
        <x-form.input name="department" label="Department" value="{{ old('department') }}" />
        <x-form.input name="affiliation" label="Institution" value="{{ old('affiliation') }}" />
        <x-form.textarea name="official_address" label="Official address" rows="3">{{ old('official_address') }}</x-form.textarea>
        <x-form.input name="country" label="Country" value="{{ old('country') }}" />
        <x-form.input name="email" label="Institutional email" value="{{ old('email') }}" />
        <x-form.textarea name="bio" label="Biography" rows="4">{{ old('bio') }}</x-form.textarea>
        <x-form.select
            name="user_id"
            label="Linked user account"
            :options="$users->mapWithKeys(fn ($user) => [$user->id => $user->name.' ('.$user->email.')'])"
            :selected="old('user_id')"
            placeholder="Not linked"
        />
        <x-form.input name="sort_order" type="number" label="Sort order" value="{{ old('sort_order', 0) }}" min="0" />
        <x-form.checkbox name="is_public" label="Show on the public editorial board page" :checked="old('is_public', false)" />
        <x-form.checkbox name="is_active" label="Active listing" :checked="old('is_active', true)" />

        <div class="flex gap-3">
            <x-form.button :full="false">Add member</x-form.button>
            <a href="{{ route('admin.editorial-board.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
