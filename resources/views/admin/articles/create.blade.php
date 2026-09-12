<x-layouts.admin title="Add article directly">
    <div class="mb-6">
        <a href="{{ route('admin.issues.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" />
            Issues
        </a>
    </div>

    <div class="max-w-3xl">
        <h1 class="font-serif text-2xl font-semibold text-brand-950">Add Article Directly</h1>
        <p class="mt-2 text-sm text-slate-600">
            Use this path only for manuscripts already reviewed by the editorial team.
            Accepted portal submissions should still use <span class="font-semibold">Publish to Issue</span> on the submission page.
        </p>

        <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-xs">
            @csrf
            <x-form.input name="title" label="Title" value="{{ old('title') }}" required maxlength="255" />
            <x-form.textarea name="abstract" label="Abstract" rows="8" required>{{ old('abstract') }}</x-form.textarea>
            <x-form.textarea name="authors" label="Author name(s)" rows="3" required>{{ old('authors') }}</x-form.textarea>
            <p class="text-xs text-slate-500">Separate multiple authors with commas or new lines. The first name is treated as the corresponding author on the public page.</p>
            <x-form.input name="keywords" label="Keywords" value="{{ old('keywords') }}" required />
            <p class="text-xs text-slate-500">4–6 keywords, comma-separated.</p>
            <x-form.select
                name="issue_id"
                label="Issue"
                :options="$issues->mapWithKeys(fn ($issue) => [$issue->id => $issue->catalogLabel().($issue->title ? ' — '.$issue->title : '').($issue->is_current ? ' (current)' : '')])"
                :selected="old('issue_id', $currentIssueId)"
                placeholder="Select an issue"
                required
            />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="page_start" type="number" label="First page" value="{{ old('page_start') }}" min="1" />
                <x-form.input name="page_end" type="number" label="Last page" value="{{ old('page_end') }}" min="1" />
            </div>
            <x-form.file name="pdf" label="Formatted PDF" hint="PDF only. Stored privately and downloaded through the article page." accept=".pdf,application/pdf" required />
            <x-form.checkbox
                name="editorial_confirmation"
                label="I confirm this manuscript has been reviewed by the editorial team and is free of plagiarism."
                :checked="old('editorial_confirmation', false)"
            />

            @if ($issues->isEmpty())
                <p class="text-sm text-rose-700">
                    No issues exist yet.
                    <a href="{{ route('admin.issues.create') }}" class="font-semibold underline">Create an issue</a>
                    before publishing.
                </p>
            @endif

            <div class="flex gap-3">
                <x-form.button :full="false">Publish article</x-form.button>
                <a href="{{ route('admin.issues.index') }}" class="inline-flex items-center rounded-md px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
            </div>
        </form>
    </div>
</x-layouts.admin>
