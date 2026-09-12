<x-layouts.admin title="Editorial board">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-slate-600">Only members marked public appear on the website. Do not add placeholder production editors.</p>
        @can('create', App\Models\EditorialBoardMember::class)
            <a href="{{ route('admin.editorial-board.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Add member
            </a>
        @endcan
    </div>

    @if ($pendingNameCount > 0)
        <div class="mt-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950" role="status">
            {{ $pendingNameCount }} of {{ $boardMemberCount }} editorial board members still need real names before ISSN submission.
        </div>
    @endif

    <form method="GET" action="{{ route('admin.editorial-board.index') }}" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Name, title, or affiliation" />
        <x-form.select
            name="visibility"
            label="Visibility"
            :options="['public' => 'Public', 'private' => 'Private']"
            :selected="$filters['visibility'] ?? ''"
            placeholder="All members"
        />
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Name</th>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Affiliation</th>
                    <th class="px-4 py-3 font-medium">Public</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $member->name }}</td>
                        <td class="px-4 py-3">{{ $member->role_title }}</td>
                        <td class="px-4 py-3">{{ $member->affiliation ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $member->is_public ? 'Public' : 'Private' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @can('update', $member)
                                    <a href="{{ route('admin.editorial-board.edit', $member) }}" class="font-medium text-brand-800 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $member)
                                    <x-form.delete :action="route('admin.editorial-board.destroy', $member)" title="Remove this board member?">
                                        Delete
                                    </x-form.delete>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-600">No editorial board members match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $members->links() }}</div>
</x-layouts.admin>
