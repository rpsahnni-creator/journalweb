<x-layouts.app title="Volumes">
    <x-editorial-nav />
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">Volumes</h1>
            <p class="mt-2 text-slate-600">Create numbered volumes before adding issues. Unpublished issues stay off the public site.</p>
        </div>
        @can('create', App\Models\Volume::class)
            <a href="{{ route('editorial.volumes.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Create volume
            </a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Volume</th>
                    <th class="px-4 py-3 font-medium">Year</th>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Issues</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($volumes as $volume)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $volume->number }}</td>
                        <td class="px-4 py-3">{{ $volume->year }}</td>
                        <td class="px-4 py-3">{{ $volume->title ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $volume->issues_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @can('update', $volume)
                                    <a href="{{ route('editorial.volumes.edit', $volume) }}" class="font-medium text-brand-800 hover:underline">Edit</a>
                                @endcan
                                @can('delete', $volume)
                                    <x-form.delete :action="route('editorial.volumes.destroy', $volume)" title="Delete this volume?">
                                        Delete
                                    </x-form.delete>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-600">No volumes have been created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $volumes->links() }}</div>
</x-layouts.app>
