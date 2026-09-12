<x-layouts.admin title="Edit user">
    <p class="text-slate-600">Leave the password blank to keep the current hash. You cannot deactivate or delete your own account.</p>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Full name" value="{{ old('name', $user->name) }}" required />
        <x-form.input name="email" type="email" label="Email" value="{{ old('email', $user->email) }}" required />
        <x-form.input name="affiliation" label="Affiliation" value="{{ old('affiliation', $user->affiliation) }}" />
        <x-form.input name="password" type="password" label="New password" autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" />

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-slate-700">Roles</legend>
            @foreach ($roles as $role)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="role_ids[]"
                        value="{{ $role->id }}"
                        @checked(in_array($role->id, old('role_ids', $user->roles->pluck('id')->all()), false))
                        class="rounded border-slate-300 text-brand-800 focus:ring-brand-700"
                    >
                    {{ $role->name }}
                </label>
            @endforeach
            @error('role_ids')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <x-form.checkbox name="is_active" label="Active account" :checked="old('is_active', $user->is_active)" />
        <x-form.checkbox name="is_editor" label="Editor access" hint="Can review manuscript submissions in the admin submissions queue." :checked="old('is_editor', $user->is_editor)" />
        <x-form.checkbox name="is_reviewer" label="Reviewer access" hint="Can be assigned double-blind reviews of manuscript submissions." :checked="old('is_reviewer', $user->is_reviewer)" />

        <div class="flex gap-3">
            <x-form.button :full="false">Save user</x-form.button>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
