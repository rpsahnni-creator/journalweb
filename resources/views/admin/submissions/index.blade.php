<x-layouts.admin title="Submissions">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Manuscript files stay on a private disk. Download them only from the authorized detail page.</p>
    </div>

    <form method="GET" action="{{ route('admin.submissions.index') }}" class="mt-6 grid gap-4 rounded-xl border border-slate-200/80 bg-white p-4.5 shadow-xs md:grid-cols-3">
        <x-form.select
            name="status"
            label="Status"
            :options="$statuses"
            :selected="$filters['status'] ?? ''"
            placeholder="All statuses"
        />
        <div class="flex items-end gap-2">
            <x-form.button :full="true">Filter</x-form.button>
            @if (! empty($filters['status']))
                <a href="{{ route('admin.submissions.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
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
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Author</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Submitted</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-medium text-slate-900 max-w-md">
                                <a href="{{ route('admin.submissions.show', $submission) }}" class="font-semibold hover:text-brand-800">
                                    {{ $submission->title }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                <div>{{ $submission->author?->name ?? '—' }}</div>
                                <div class="font-mono text-[11px] text-slate-500">{{ $submission->author?->email }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1',
                                    $submission->status->badgeClasses(),
                                ])>
                                    {{ $submission->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-500 whitespace-nowrap">
                                {{ $submission->submitted_at?->timezone(config('app.timezone'))->format('Y-m-d') ?: '—' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.submissions.show', $submission) }}" class="font-semibold text-brand-800 hover:underline">
                                    Review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                No submissions match this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $submissions->links() }}</div>
</x-layouts.admin>
