@php
    $protected = \App\Enums\RoleSlug::tryFrom($role->slug) !== null;
@endphp

<x-layouts.admin title="Edit role">
    <p class="text-slate-600">
        @if ($protected)
            This is a system role. The slug cannot be changed and the role cannot be deleted.
        @else
            Changing permissions affects every user assigned to this role.
        @endif
    </p>

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="mt-6 max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Name" value="{{ old('name', $role->name) }}" required />
        <x-form.input name="slug" label="Slug" value="{{ old('slug', $role->slug) }}" required @readonly($protected) />
        <x-form.input name="description" label="Description" value="{{ old('description', $role->description) }}" />

        <fieldset class="space-y-2">
            <legend class="text-sm font-medium text-slate-700">Permissions</legend>
            @forelse ($permissions as $permission)
                <label class="flex items-start gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        name="permission_ids[]"
                        value="{{ $permission->id }}"
                        @checked(in_array($permission->id, old('permission_ids', $role->permissions->pluck('id')->all()), false))
                        class="mt-0.5 rounded border-slate-300 text-brand-800 focus:ring-brand-700"
                    >
                    <span>
                        <span class="font-medium">{{ $permission->name }}</span>
                        <span class="block font-mono text-xs text-slate-500">{{ $permission->slug }}</span>
                    </span>
                </label>
            @empty
                <p class="text-sm text-slate-500">No permissions have been seeded yet.</p>
            @endforelse
            @error('permission_ids')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </fieldset>

        <div class="flex gap-3">
            <x-form.button :full="false">Save role</x-form.button>
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
