<x-layouts.admin title="Issues">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Create volumes and issues, then mark one as the current issue shown on the homepage. Add Article Directly publishes a vetted manuscript without using the submissions queue. Accepted portal submissions still use Publish to Issue on the submission page.</p>
        <div class="flex flex-wrap items-center gap-2">
            @if (auth()->user()?->isEditor())
                <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center gap-1.5 justify-center rounded-lg border border-brand-800 bg-white px-4 py-2 text-xs font-semibold text-brand-900 shadow-xs hover:bg-brand-50 transition-colors">
                    <x-icon name="file-plus" class="h-4 w-4" />
                    <span>Add Article Directly</span>
                </a>
            @endif
            @can('create', App\Models\Issue::class)
                <a href="{{ route('admin.issues.create') }}" class="inline-flex items-center gap-1.5 justify-center rounded-lg bg-brand-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-brand-800 transition-colors">
                    <x-icon name="plus-circle" class="h-4 w-4" />
                    <span>Add issue</span>
                </a>
            @endcan
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Issue</th>
                        <th class="px-5 py-3">Title</th>
                        <th class="px-5 py-3">Publication</th>
                        <th class="px-5 py-3">Articles</th>
                        <th class="px-5 py-3">Current</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($issues as $issue)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span>{{ $issue->displayLabel() }}</span>
                                    @if ($issue->is_special_issue)
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800 ring-1 ring-amber-600/20">Special Issue</span>
                                    @endif
                                </div>
                                @if ($issue->is_special_issue && filled($issue->special_issue_theme))
                                    <p class="mt-1 font-normal text-slate-500">{{ $issue->special_issue_theme }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $issue->title ?: '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $issue->publication_month_year ?: ($issue->published_at?->format('F Y') ?: '—') }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $issue->issue_articles_count }}</td>
                            <td class="px-5 py-3">
                                @if ($issue->is_current)
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20">Current</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @can('update', $issue)
                                        <a href="{{ route('admin.issues.edit', $issue) }}" class="font-semibold text-brand-800 hover:underline">Edit</a>
                                        @unless ($issue->is_current)
                                            <form method="POST" action="{{ route('admin.issues.current', $issue) }}">
                                                @csrf
                                                <button type="submit" class="font-semibold text-brand-800 hover:underline">Set as Current Issue</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">No issues have been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $issues->links() }}</div>
</x-layouts.admin>
