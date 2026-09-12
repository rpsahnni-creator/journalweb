<x-layouts.author title="Dashboard">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-600">Your manuscript pipeline. Drafts stay private until you submit them to the editorial office.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($revisionCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    {{ $revisionCount }} {{ \Illuminate\Support\Str::plural('revision', $revisionCount) }} requested
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    No revisions pending
                </span>
            @endif
            @can('create', App\Models\Article::class)
                <a href="{{ route('author.manuscripts.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-900 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-brand-800">
                    <x-icon name="plus-circle" class="h-4 w-4" />
                    <span>Start a manuscript</span>
                </a>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manuscripts</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <x-icon name="files" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $totalCount }}</p>
            <p class="mt-2 text-xs text-slate-500">All records in your name</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Drafts</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                    <x-icon name="file-pen" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $draftCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Private working copies</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">In review</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <x-icon name="search" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $inReviewCount }}</p>
            <p class="mt-2 text-xs text-slate-500">With the editorial office</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Revisions</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <x-icon name="rotate-ccw" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $revisionCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Action needed from you</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Accepted</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-600">
                    <x-icon name="badge-check" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $acceptedCount }}</p>
            <p class="mt-2 text-xs text-slate-500">In production</p>
        </div>

        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs hover:shadow-md transition-shadow duration-150">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Published</p>
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <x-icon name="book-open" class="h-4 w-4" />
                </div>
            </div>
            <p class="mt-2 font-serif text-3xl font-bold text-slate-900">{{ $publishedCount }}</p>
            <p class="mt-2 text-xs text-slate-500">Live in the public record</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                        <x-icon name="user" class="h-3.5 w-3.5" />
                    </div>
                    <h2 class="font-semibold text-slate-900 text-sm">Corresponding author</h2>
                </div>
                <a href="{{ route('author.profile.edit') }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                    <span>Edit profile</span>
                    <x-icon name="chevron-right" class="h-3 w-3" />
                </a>
            </div>
            <div class="mt-4 flex items-start gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-700/10">
                    <span class="text-sm font-bold">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-900 truncate">{{ $user->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                </div>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-4">
                @if (filled($user->affiliation))
                    <p class="flex items-start gap-2 text-sm text-slate-700">
                        <x-icon name="building-2" class="mt-0.5 h-4 w-4 text-slate-400 shrink-0" />
                        <span>{{ $user->affiliation }}</span>
                    </p>
                @else
                    <p class="flex items-start gap-2 rounded-lg border border-amber-200/70 bg-amber-50/80 p-3 text-sm text-amber-800">
                        <x-icon name="alert-triangle" class="mt-0.5 h-4 w-4 text-amber-600 shrink-0" />
                        <span>Add your affiliation before you submit a manuscript.</span>
                    </p>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                        <x-icon name="pie-chart" class="h-3.5 w-3.5" />
                    </div>
                    <h2 class="font-semibold text-slate-900 text-sm">Manuscripts by status</h2>
                </div>
                <span class="text-xs text-slate-400 font-mono">{{ $totalCount }} total</span>
            </div>
            <div class="mt-4">
                @if ($statusChart->isEmpty())
                    <div class="px-2 py-10 text-center text-sm text-slate-500">
                        <x-icon name="file-text" class="mx-auto h-8 w-8 text-slate-300" />
                        <p class="mt-2">No manuscripts to chart yet.</p>
                    </div>
                @else
                    <canvas
                        x-data
                        x-init="
                            new Chart($el, {
                                type: 'bar',
                                data: {
                                    labels: @json($statusChart->pluck('label')),
                                    datasets: [{
                                        label: 'Manuscripts',
                                        data: @json($statusChart->pluck('count')),
                                        backgroundColor: '#1b3654',
                                        borderRadius: 6
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: { display: false }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { precision: 0 },
                                            grid: { color: '#f1f5f9' }
                                        },
                                        x: {
                                            grid: { display: false }
                                        }
                                    }
                                }
                            })
                        "
                    ></canvas>
                @endif
            </div>
        </section>
    </div>

    <form method="GET" action="{{ route('author.dashboard') }}" class="mt-8 grid gap-4 rounded-xl border border-slate-200/80 bg-white p-4.5 shadow-xs md:grid-cols-3">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Title or manuscript number" />
        <x-form.select
            name="status"
            label="Status"
            :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])"
            :selected="$filters['status'] ?? ''"
            placeholder="All statuses"
        />
        <div class="flex items-end gap-2">
            <x-form.button :full="true">Filter</x-form.button>
            @if (! empty($filters['q']) || ! empty($filters['status']))
                <a href="{{ route('author.dashboard') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-xs">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div class="flex items-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-700">
                    <x-icon name="scroll-text" class="h-3.5 w-3.5" />
                </div>
                <h2 class="font-semibold text-slate-900 text-sm">Your manuscripts</h2>
            </div>
            <span class="text-xs text-slate-400 font-mono">{{ $manuscripts->total() }} shown</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-50/75 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Manuscript</th>
                        <th class="px-5 py-3">Number</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Files</th>
                        <th class="px-5 py-3 text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($manuscripts as $manuscript)
                        @php
                            $statusClass = match($manuscript->status) {
                                \App\Enums\ArticleStatus::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                \App\Enums\ArticleStatus::Accepted, \App\Enums\ArticleStatus::Copyediting, \App\Enums\ArticleStatus::Scheduled => 'bg-teal-50 text-teal-700 ring-teal-600/20',
                                \App\Enums\ArticleStatus::UnderReview, \App\Enums\ArticleStatus::Submitted, \App\Enums\ArticleStatus::Resubmitted => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                \App\Enums\ArticleStatus::RevisionRequired => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                \App\Enums\ArticleStatus::InitialScreening => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                \App\Enums\ArticleStatus::Rejected, \App\Enums\ArticleStatus::Withdrawn => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                default => 'bg-slate-100 text-slate-700 ring-slate-400/20',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-3 font-serif text-sm font-semibold text-slate-900 max-w-md">
                                <a href="{{ route('author.manuscripts.show', $manuscript) }}" class="hover:text-brand-700">
                                    {{ $manuscript->title }}
                                </a>
                            </td>
                            <td class="px-5 py-3 font-mono text-slate-500">
                                <span class="rounded bg-slate-100 px-2 py-0.5">
                                    {{ $manuscript->submission_number }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusClass }}">
                                    {{ $manuscript->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                <span class="inline-flex items-center gap-1 text-slate-500">
                                    <x-icon name="paperclip" class="h-3.5 w-3.5" />
                                    {{ $manuscript->files_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('author.manuscripts.show', $manuscript) }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                                        View
                                    </a>
                                    @can('updateAsAuthor', $manuscript)
                                        <a href="{{ route('author.manuscripts.edit', $manuscript) }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                <x-icon name="file-text" class="mx-auto h-8 w-8 text-slate-300" />
                                <p class="mt-2 font-medium text-slate-700">No manuscripts yet</p>
                                <p class="text-xs text-slate-500 mt-1">Start a draft to begin submission.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $manuscripts->links() }}</div>
</x-layouts.author>
