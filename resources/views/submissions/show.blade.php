<x-layouts.public title="Submission">
    <x-flash />

    <div class="mb-6">
        <a href="{{ route('submissions.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            My submissions
        </a>
    </div>

    <section class="rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manuscript</p>
                <h1 class="mt-1 font-serif text-2xl font-semibold text-brand-950">{{ $submission->title }}</h1>
            </div>
            <span @class([
                'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1',
                $submission->status->badgeClasses(),
            ])>
                {{ $submission->status->label() }}
            </span>
        </div>

        <dl class="mt-5 grid gap-4 sm:grid-cols-2 text-sm">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Submitted</dt>
                <dd class="mt-1 text-slate-800">{{ $submission->submitted_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Keywords</dt>
                <dd class="mt-1 text-slate-800">{{ $submission->keywords }}</dd>
            </div>
        </dl>

        <div class="mt-6 border-t border-slate-100 pt-5">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Abstract</h2>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $submission->abstract }}</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('submissions.download', $submission) }}" class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-4 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-brand-800">
                <x-icon name="download" class="h-4 w-4" />
                Download current manuscript
            </a>
        </div>
    </section>

    @if ($submission->versions->isNotEmpty())
        <section class="mt-6 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
            <h2 class="font-semibold text-slate-900">Manuscript versions</h2>
            <p class="mt-1 text-sm text-slate-500">Download previously uploaded manuscript files and revisions.</p>
            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($submission->versions as $version)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-slate-900">Version {{ $version->version_number }}</span>
                                @if ($loop->first)
                                    <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-600/20">Current</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500">
                                Uploaded {{ $version->uploaded_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?: '—' }}
                            </p>
                        </div>
                        <a href="{{ route('submissions.versions.download', [$submission, $version]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
                            <x-icon name="download" class="h-3.5 w-3.5" />
                            Download v{{ $version->version_number }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($submission->canUploadRevision())
        <section class="mt-6 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
            <h2 class="font-semibold text-slate-900">Revision requested</h2>
            <p class="mt-1 text-sm text-slate-500">Upload a revised PDF or DOCX. Reviewer identities are not disclosed.</p>
            @if ($authorComments->isNotEmpty())
                <ol class="mt-4 space-y-4">
                    @foreach ($authorComments as $comment)
                        <li class="rounded-lg border border-slate-100 bg-slate-50/80 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Comment {{ $loop->iteration }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $comment }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
            @include('submissions._revision-form', ['submission' => $submission])
        </section>
    @endif
</x-layouts.public>
