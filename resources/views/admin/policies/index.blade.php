<x-layouts.admin title="Policies">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-slate-600">Published pages appear on the public site. Drafts remain private.</p>
        @can('create', App\Models\JournalPolicy::class)
            <a href="{{ route('admin.policies.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Add policy
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('admin.policies.index') }}" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-4">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Title or slug" />
        <x-form.select
            name="type"
            label="Type"
            :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->title()])"
            :selected="$filters['type'] ?? ''"
            placeholder="All types"
        />
        <x-form.select
            name="status"
            label="Status"
            :options="['published' => 'Published', 'draft' => 'Draft']"
            :selected="$filters['status'] ?? ''"
            placeholder="All statuses"
        />
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Type</th>
                    <th class="px-4 py-3 font-medium">Slug</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($policies as $policy)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $policy->title }}</td>
                        <td class="px-4 py-3">{{ \App\Enums\JournalPolicyType::tryFrom($policy->type)?->title() ?? $policy->type }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $policy->slug }}</td>
                        <td class="px-4 py-3">{{ $policy->is_published ? 'Published' : 'Draft' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @can('update', $policy)
                                    <a href="{{ route('admin.policies.edit', $policy) }}" class="font-medium text-brand-800 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $policy)
                                    <x-form.delete :action="route('admin.policies.destroy', $policy)" title="Delete this policy page?">
                                        Delete
                                    </x-form.delete>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-600">No policies match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $policies->links() }}</div>
</x-layouts.admin>
