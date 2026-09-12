<x-layouts.admin title="Edit board member">
    <p class="text-slate-600">Changing visibility updates the public editorial board immediately.</p>

    <form method="POST" action="{{ route('admin.editorial-board.update', $member) }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Name" value="{{ old('name', $member->name) }}" required />
        <x-form.input name="role_title" label="Role title" value="{{ old('role_title', $member->role_title) }}" required />
        <x-form.input name="department" label="Department" value="{{ old('department', $member->department) }}" />
        <x-form.input name="affiliation" label="Institution" value="{{ old('affiliation', $member->affiliation) }}" />
        <x-form.textarea name="official_address" label="Official address" rows="3">{{ old('official_address', $member->official_address) }}</x-form.textarea>
        <x-form.input name="country" label="Country" value="{{ old('country', $member->country) }}" />
        <x-form.input name="email" label="Institutional email" value="{{ old('email', $member->email) }}" />
        <x-form.textarea name="bio" label="Biography" rows="4">{{ old('bio', $member->bio) }}</x-form.textarea>
        <x-form.select
            name="user_id"
            label="Linked user account"
            :options="$users->mapWithKeys(fn ($user) => [$user->id => $user->name.' ('.$user->email.')'])"
            :selected="old('user_id', $member->user_id)"
            placeholder="Not linked"
        />
        <x-form.input name="sort_order" type="number" label="Sort order" value="{{ old('sort_order', $member->sort_order) }}" min="0" />
        <x-form.checkbox name="is_public" label="Show on the public editorial board page" :checked="old('is_public', $member->is_public)" />
        <x-form.checkbox name="is_active" label="Active listing" :checked="old('is_active', $member->is_active)" />

        <div class="flex gap-3">
            <x-form.button :full="false">Save member</x-form.button>
            <a href="{{ route('admin.editorial-board.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
