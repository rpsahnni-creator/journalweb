<x-layouts.public title="Submit manuscript">
    <x-flash />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">Submit manuscript</h1>
            <p class="mt-1 text-sm text-slate-500">Upload a PDF or DOCX file. Manuscripts are stored privately and are only available to editors through an authorized download.</p>
        </div>
        <a href="{{ route('submissions.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-800 hover:underline">
            <span>My submissions</span>
            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('submissions.store') }}"
        enctype="multipart/form-data"
        class="mt-8 max-w-3xl space-y-5 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs"
        x-data="{
            abstract: {{ \Illuminate\Support\Js::from(old('abstract', '')) }},
            words() {
                const text = (this.abstract || '').trim();
                return text === '' ? 0 : text.split(/\s+/).filter(Boolean).length;
            }
        }"
    >
        @csrf

        <x-form.input name="title" label="Title" value="{{ old('title') }}" required maxlength="255" />

        <div>
            <label for="abstract" class="block text-sm font-medium text-slate-700">Abstract</label>
            <p class="mt-0.5 text-xs text-slate-500">{{ $minWords }}–{{ $maxWords }} words. A live count is shown as you type.</p>
            <textarea
                id="abstract"
                name="abstract"
                rows="8"
                required
                x-model="abstract"
                class="mt-1.5 block w-full rounded-lg border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm leading-relaxed text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20"
                placeholder="Summarize the manuscript in {{ $minWords }} to {{ $maxWords }} words."
            ></textarea>
            <p class="mt-1.5 text-xs font-medium" :class="words() >= {{ $minWords }} && words() <= {{ $maxWords }} ? 'text-emerald-700' : 'text-slate-500'">
                <span x-text="words()"></span> / {{ $minWords }}–{{ $maxWords }} words
            </p>
            @error('abstract')
                <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                    <x-icon name="alert-circle" class="h-3.5 w-3.5" />
                    {{ $message }}
                </p>
            @enderror
        </div>

        <x-form.input
            name="keywords"
            label="Keywords"
            value="{{ old('keywords') }}"
            required
            placeholder="Four to six keywords, separated by commas"
        />
        <p class="-mt-3 text-xs text-slate-500">Provide {{ $minKeywords }}–{{ $maxKeywords }} keywords, separated by commas.</p>

        <x-form.textarea name="co_authors" label="Co-authors (optional)" rows="3" placeholder="Names and affiliations, one per line">{{ old('co_authors') }}</x-form.textarea>

        <x-form.file
            name="manuscript"
            label="Manuscript file"
            hint="PDF or DOCX only, maximum {{ number_format($maxKilobytes / 1024, 0) }} MB."
            accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            required
        />

        <p class="text-sm text-slate-600">
            Before you submit, review the
            <a href="{{ route('submission-checklist') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">submission checklist</a>.
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <x-form.button :full="false">Submit manuscript</x-form.button>
            <a href="{{ route('submissions.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</a>
        </div>
    </form>
</x-layouts.public>
