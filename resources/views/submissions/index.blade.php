<x-layouts.public title="My submissions">
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">My submissions</h1>
            <p class="mt-1 text-sm text-slate-500">Follow the status of each manuscript here. If a revision is requested, you can upload a revised file from this page.</p>
        </div>
        <a href="{{ route('submissions.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs transition-all hover:bg-brand-800 hover:shadow-sm">
            <x-icon name="plus-circle" class="h-4 w-4" />
            <span>Submit manuscript</span>
        </a>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-xs">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50/90 border-b border-slate-200/80 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Title</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Submitted</th>
                        <th class="px-5 py-3.5 text-right"><span class="sr-only">File</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        @php
                            $comments = $authorComments[$submission->id] ?? collect();
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4 font-serif font-semibold text-brand-950 max-w-md">
                                <a href="{{ route('submissions.show', $submission) }}" class="hover:underline">{{ $submission->title }}</a>
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1',
                                    $submission->status->badgeClasses(),
                                ])>
                                    {{ $submission->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $submission->submitted_at?->timezone(config('app.timezone'))->format('d M Y') ?: '—' }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('submissions.download', $submission) }}" class="inline-flex items-center gap-1 font-medium text-brand-800 hover:underline">
                                    <x-icon name="download" class="h-3.5 w-3.5" />
                                    <span>Download</span>
                                </a>
                            </td>
                        </tr>
                        @if ($submission->canUploadRevision())
                            <tr class="bg-slate-50/70">
                                <td colspan="4" class="px-5 py-4">
                                    @if ($comments->isNotEmpty())
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reviewer comments</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Reviewer identities are not disclosed.</p>
                                        <ol class="mt-3 space-y-3">
                                            @foreach ($comments as $comment)
                                                <li class="rounded-lg border border-slate-200 bg-white p-3">
                                                    <p class="text-xs font-semibold text-slate-500">Comment {{ $loop->iteration }}</p>
                                                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $comment }}</p>
                                                </li>
                                            @endforeach
                                        </ol>
                                    @endif
                                    <div @class(['mt-4 border-t border-slate-200 pt-4' => $comments->isNotEmpty()])>
                                        <p class="text-sm font-semibold text-slate-900">Upload a revised manuscript</p>
                                        <p class="mt-0.5 text-xs text-slate-500">The new file is stored privately. The editorial office is notified when you submit.</p>
                                        @include('submissions._revision-form', ['submission' => $submission])
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-slate-500">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <x-icon name="file-text" class="h-6 w-6" />
                                </div>
                                <p class="font-medium text-slate-700">No submissions yet</p>
                                <p class="mt-1 text-xs text-slate-500">Register and submit a manuscript to see its status here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $submissions->links() }}</div>
</x-layouts.public>
