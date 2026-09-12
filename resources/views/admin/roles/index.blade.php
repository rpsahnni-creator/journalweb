<x-layouts.admin title="Roles">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-slate-600">System roles cannot be deleted. Custom roles can be created for additional permission sets.</p>
        @can('create', App\Models\Role::class)
            <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Add role
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.roles.index') }}" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
        <div class="md:col-span-2">
            <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Name or slug" />
        </div>
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Slug</th>
                    <th class="px-4 py-3 font-medium">Users</th>
                    <th class="px-4 py-3 font-medium">Permissions</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $role->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $role->slug }}</td>
                        <td class="px-4 py-3">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @can('update', $role)
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="font-medium text-brand-800 hover:underline">Edit</a>
                                @endcan
                                @if (\App\Enums\RoleSlug::tryFrom($role->slug) === null && $role->users_count === 0)
                                    @can('delete', $role)
                                        <x-form.delete :action="route('admin.roles.destroy', $role)" title="Delete this role?">
                                            Delete
                                        </x-form.delete>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-600">No roles match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $roles->links() }}</div>
</x-layouts.admin>
