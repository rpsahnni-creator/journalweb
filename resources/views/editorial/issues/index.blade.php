<x-layouts.app title="Issues">
    <x-editorial-nav />
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">Issues</h1>
            <p class="mt-2 text-slate-600">Assign accepted articles, set the publication date, and publish when the issue is complete. Draft issues are not public.</p>
        </div>
        @can('create', App\Models\Issue::class)
            <a href="{{ route('editorial.issues.create') }}" class="inline-flex items-center justify-center rounded-md bg-brand-900 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Create issue
            </a>
        @endcan
    </div>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Issue</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Cover date</th>
                    <th class="px-4 py-3 font-medium">Articles</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($issues as $issue)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">
                            <a href="{{ route('editorial.issues.show', $issue) }}" class="hover:underline">{{ $issue->displayLabel() }}</a>
                            @if ($issue->title)
                                <p class="font-normal text-slate-500">{{ $issue->title }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $issue->status->label() }}</td>
                        <td class="px-4 py-3">{{ $issue->published_at?->toDateString() ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $issue->issue_articles_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('editorial.issues.show', $issue) }}" class="font-medium text-brand-800 hover:underline">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-slate-600">No issues have been created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $issues->links() }}</div>
</x-layouts.app>
