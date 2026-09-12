<x-layouts.admin title="Users">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Create accounts, assign roles, and manage access status. Passwords are encrypted with Argon2/Bcrypt.</p>
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-1.5 justify-center rounded-lg bg-brand-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors">
                <x-icon name="user-plus" class="h-4 w-4" />
                <span>Add user</span>
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 grid gap-4 rounded-xl border border-slate-200/80 bg-white p-4.5 shadow-xs md:grid-cols-4">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Name or email" />
        <x-form.select name="role" label="Role" :options="$roles->pluck('name', 'slug')" :selected="$filters['role'] ?? ''" placeholder="All roles" />
        <x-form.select
            name="status"
            label="Status"
            :options="['active' => 'Active', 'inactive' => 'Inactive']"
            :selected="$filters['status'] ?? ''"
            placeholder="All statuses"
        />
        <div class="flex items-end gap-2">
            <x-form.button :full="true">Filter</x-form.button>
            @if(!empty($filters['q']) || !empty($filters['role']) || !empty($filters['status']))
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Roles</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-medium text-slate-900">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-100 text-brand-900 font-bold text-[10px]">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <span class="font-semibold">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 font-mono">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium bg-brand-50 text-brand-800 ring-1 ring-brand-200/60">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @can('update', $user)
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1 font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                                            <x-icon name="edit-2" class="h-3 w-3" />
                                            <span>Edit</span>
                                        </a>
                                    @endcan
                                    @can('delete', $user)
                                        <x-form.delete :action="route('admin.users.destroy', $user)" title="Delete this user?" text="The account will be removed. This cannot be undone.">
                                            Delete
                                        </x-form.delete>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                <x-icon name="users" class="mx-auto h-8 w-8 text-slate-300" />
                                <p class="mt-2">No users match the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
