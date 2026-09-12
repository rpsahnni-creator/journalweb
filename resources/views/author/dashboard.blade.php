<x-layouts.app title="Author portal">
    <x-author-nav />
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">Your manuscripts</h1>
            <p class="mt-1 text-sm text-slate-500">Drafts stay strictly private. Submitted files are only accessed through authorized editorial workflows.</p>
        </div>
        @can('create', App\Models\Article::class)
            <a href="{{ route('author.manuscripts.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-brand-800 hover:shadow-sm">
                <x-icon name="plus-circle" class="h-4 w-4" />
                <span>Start a manuscript</span>
            </a>
        @endcan
    </div>

    <!-- Author Profile Summary Card -->
    <section class="mt-6 rounded-xl border border-slate-200/90 bg-white p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-700/10">
                    <x-icon name="user" class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-brand-950">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                </div>
            </div>
            <a href="{{ route('author.profile.edit') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
                <span>Edit profile</span>
                <x-icon name="arrow-right" class="h-3.5 w-3.5" />
            </a>
        </div>
        <div class="mt-4 border-t border-slate-100 pt-3">
            @if (filled($user->affiliation))
                <p class="flex items-center gap-2 text-sm text-slate-700">
                    <x-icon name="building-2" class="h-4 w-4 text-slate-400 shrink-0" />
                    <span>Affiliation: <strong class="font-medium text-slate-900">{{ $user->affiliation }}</strong></span>
                </p>
            @else
                <p class="flex items-center gap-2 text-sm text-amber-800 bg-amber-50/80 p-3 rounded-lg border border-amber-200/70">
                    <x-icon name="alert-triangle" class="h-4 w-4 text-amber-600 shrink-0" />
                    <span>Add your affiliation before you submit a manuscript.</span>
                </p>
            @endif
        </div>
    </section>

    <!-- Filters Form -->
    <form method="GET" action="{{ route('author.dashboard') }}" class="mt-6 grid gap-4 rounded-xl border border-slate-200/90 bg-white p-4 shadow-xs md:grid-cols-3">
        <x-form.input name="q" label="Search" value="{{ $filters['q'] ?? '' }}" placeholder="Title or manuscript number" />
        <x-form.select
            name="status"
            label="Status"
            :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])"
            :selected="$filters['status'] ?? ''"
            placeholder="All statuses"
        />
        <div class="flex items-end">
            <x-form.button :full="true">Filter</x-form.button>
        </div>
    </form>

    <!-- Manuscripts Table -->
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50/90 border-b border-slate-200/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Manuscript</th>
                        <th class="px-5 py-3.5">Number</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Files</th>
                        <th class="px-5 py-3.5 text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($manuscripts as $manuscript)
                        @php
                            $statusClass = match($manuscript->status) {
                                \App\Enums\ArticleStatus::Published => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                \App\Enums\ArticleStatus::Accepted => 'bg-teal-50 text-teal-700 ring-teal-600/20',
                                \App\Enums\ArticleStatus::UnderReview => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                \App\Enums\ArticleStatus::RevisionRequired => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                \App\Enums\ArticleStatus::InitialScreening => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                                \App\Enums\ArticleStatus::Rejected => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                default => 'bg-slate-100 text-slate-700 ring-slate-400/20',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4 font-serif font-semibold text-brand-950 max-w-md">
                                <a href="{{ route('author.manuscripts.show', $manuscript) }}" class="hover:text-brand-700">
                                    {{ $manuscript->title }}
                                </a>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-500">
                                <span class="rounded bg-slate-100 px-2 py-0.5">
                                    {{ $manuscript->submission_number }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                    {{ $manuscript->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                <span class="inline-flex items-center gap-1 text-xs text-slate-500">
                                    <x-icon name="paperclip" class="h-3.5 w-3.5" />
                                    {{ $manuscript->files_count }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('author.manuscripts.show', $manuscript) }}" class="inline-flex items-center gap-1 font-medium text-brand-800 hover:text-brand-900 hover:underline">
                                        <span>View</span>
                                    </a>
                                    @can('updateAsAuthor', $manuscript)
                                        <a href="{{ route('author.manuscripts.edit', $manuscript) }}" class="inline-flex items-center gap-1 font-medium text-brand-800 hover:text-brand-900 hover:underline">
                                            <span>Edit</span>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-3">
                                    <x-icon name="file-text" class="h-6 w-6" />
                                </div>
                                <p class="font-medium text-slate-700">No manuscripts yet</p>
                                <p class="text-xs text-slate-500 mt-1">Start a draft to begin submission.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $manuscripts->links() }}</div>
</x-layouts.app>

