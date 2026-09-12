<x-layouts.admin title="Volumes">
    <x-alert type="warning">
        Volume numbering is managed in the editorial office. This list is read-only.
    </x-alert>

    <p class="mt-4">
        <a href="{{ route('editorial.volumes.index') }}" class="font-medium text-brand-800 hover:underline">Open editorial volumes</a>
    </p>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Volume</th>
                    <th class="px-4 py-3 font-medium">Year</th>
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Issues</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($volumes as $volume)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $volume->number }}</td>
                        <td class="px-4 py-3">{{ $volume->year }}</td>
                        <td class="px-4 py-3">{{ $volume->title ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $volume->issues_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-600">No volumes have been recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $volumes->links() }}</div>
</x-layouts.admin>
