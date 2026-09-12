<x-layouts.app title="Editorial manuscripts">
    <x-editorial-nav />
    <x-flash />

    <h1 class="font-serif text-3xl font-semibold text-brand-950">Submitted manuscripts</h1>
    <p class="mt-2 text-slate-600">Screen submissions and assign reviewers. Unpublished files are not listed on the public site.</p>

    <form method="GET" action="{{ route('editorial.manuscripts.index') }}" class="mt-8 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-3">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Title or manuscript number" />
        <x-form.select
            name="status"
            label="Status"
            :options="$statuses->mapWithKeys(fn ($status) => [$status->value => $status->label()])"
            :selected="$filters['status'] ?? ''"
            placeholder="Active editorial queue"
        />
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="px-4 py-3 font-medium">Manuscript</th>
                    <th class="px-4 py-3 font-medium">Number</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Submitted</th>
                    <th class="px-4 py-3 font-medium">Reviewers</th>
                    <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($manuscripts as $manuscript)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-medium text-brand-950">{{ $manuscript->title }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $manuscript->submission_number }}</td>
                        <td class="px-4 py-3">{{ $manuscript->status->label() }}</td>
                        <td class="px-4 py-3">{{ $manuscript->submitted_at?->toDateString() ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $manuscript->reviewer_assignments_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('editorial.manuscripts.show', $manuscript) }}" class="font-medium text-brand-800 hover:underline">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-600">No manuscripts match the current filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $manuscripts->links() }}</div>
</x-layouts.app>
