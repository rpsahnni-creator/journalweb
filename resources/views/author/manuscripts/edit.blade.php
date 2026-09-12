@php
    $authorRows = old('authors', $manuscript->authors->map(fn ($author) => [
        'name' => $author->name,
        'email' => $author->email,
        'affiliation' => $author->affiliation,
        'orcid' => $author->orcid,
    ])->values()->all());
    $matched = $manuscript->authors->values()->search(fn ($author) => $author->is_corresponding);
    $correspondingIndex = (int) old('corresponding_index', $matched === false ? 0 : $matched);
    $manuscriptConfig = config('manuscripts.manuscript');
    $supplementaryConfig = config('manuscripts.supplementary');
@endphp

<x-layouts.app title="Edit manuscript">
    <x-author-nav />
    <x-flash />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $manuscript->submission_number }}</p>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">{{ $manuscript->status === \App\Enums\ArticleStatus::RevisionRequired ? 'Revise manuscript' : 'Edit draft' }}</h1>
            <p class="mt-2 text-slate-600">Status: {{ $manuscript->status->label() }}. Submitted files stay on record; a new upload becomes the working copy for the next version.</p>
        </div>
        <a href="{{ route('author.manuscripts.show', $manuscript) }}" class="text-sm font-medium text-brand-800 hover:underline">View submission</a>
    </div>

    <form method="POST" action="{{ route('author.manuscripts.update', $manuscript) }}" class="mt-8 space-y-8">
        @csrf
        @method('PUT')

        <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Manuscript details</h2>
            <x-form.input name="title" label="Title" value="{{ old('title', $manuscript->title) }}" required />
            <x-form.select
                name="article_type"
                label="Article type"
                :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])"
                :selected="old('article_type', $manuscript->article_type?->value)"
                required
            />
            <x-form.textarea name="abstract" label="Abstract" rows="8">{{ old('abstract', $manuscript->abstract) }}</x-form.textarea>
            <x-form.input name="keywords" label="Keywords" value="{{ old('keywords', $manuscript->keywordsList()) }}" placeholder="Comma-separated, at least three for submission" />
        </section>

        <section
            class="space-y-4 rounded-lg border border-slate-200 bg-white p-6"
            x-data='{
                authors: @json($authorRows),
                corresponding: {{ $correspondingIndex }},
                addAuthor() {
                    this.authors.push({ name: "", email: "", affiliation: "", orcid: "" })
                },
                removeAuthor(index) {
                    if (this.authors.length === 1) return
                    this.authors.splice(index, 1)
                    if (this.corresponding >= this.authors.length) this.corresponding = 0
                }
            }'
        >
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-semibold text-brand-950">Authors</h2>
                <button type="button" class="text-sm font-medium text-brand-800 hover:underline" @click="addAuthor()">Add author</button>
            </div>
            <p class="text-sm text-slate-600">Add every contributor and select one corresponding author.</p>
            @error('authors')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('corresponding_index')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <template x-for="(author, index) in authors" :key="index">
                <div class="grid gap-3 rounded-md border border-slate-100 p-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700">Name</label>
                        <input x-model="author.name" :name="'authors[' + index + '][name]'" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email</label>
                        <input type="email" x-model="author.email" :name="'authors[' + index + '][email]'" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Affiliation</label>
                        <input x-model="author.affiliation" :name="'authors[' + index + '][affiliation]'" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">ORCID</label>
                        <input x-model="author.orcid" :name="'authors[' + index + '][orcid]'" class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-slate-900 shadow-sm focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700">
                    </div>
                    <div class="flex items-end justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" name="corresponding_index" :value="index" x-model.number="corresponding">
                            Corresponding author
                        </label>
                        <button type="button" class="text-sm text-red-700 hover:underline" @click="removeAuthor(index)" x-show="authors.length > 1">Remove</button>
                    </div>
                </div>
            </template>
        </section>

        <section class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-brand-950">Cover letter and declarations</h2>
            <x-form.textarea name="cover_letter" label="Cover letter" rows="6">{{ old('cover_letter', $manuscript->cover_letter) }}</x-form.textarea>
            <x-form.checkbox
                name="originality_confirmed"
                label="Originality declaration"
                hint="I confirm that this work is original, has not been published elsewhere, and is not under consideration by another journal."
                :checked="old('originality_confirmed', $manuscript->originality_confirmed)"
            />
            <x-form.checkbox
                name="conflict_of_interest_declared"
                label="Conflict of interest declaration completed"
                hint="Check this after you have described any conflicts, or stated that there are none."
                :checked="old('conflict_of_interest_declared', $manuscript->conflict_of_interest_declared)"
            />
            <x-form.textarea name="conflict_of_interest_statement" label="Conflict of interest statement" rows="4">{{ old('conflict_of_interest_statement', $manuscript->conflict_of_interest_statement) }}</x-form.textarea>
            @if ($manuscript->status === \App\Enums\ArticleStatus::RevisionRequired)
                <x-form.textarea name="author_response" label="Response to reviewers" rows="8">{{ old('author_response', $manuscript->author_response) }}</x-form.textarea>
            @endif
        </section>

        <x-form.button :full="false">Save draft</x-form.button>
    </form>

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('author.manuscripts.files.store', $manuscript) }}" enctype="multipart/form-data" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-semibold text-brand-950">Manuscript file</h2>
            <p class="text-sm text-slate-600">PDF or Word, maximum {{ number_format($manuscriptConfig['max_kilobytes'] / 1024, 0) }} MB. A new upload replaces only the current working copy. Previous submitted versions are kept.</p>
            <input type="hidden" name="type" value="manuscript">
            <x-form.file name="file" label="Upload manuscript" accept=".pdf,.doc,.docx,application/pdf" required />
            <x-form.button :full="false">Upload manuscript</x-form.button>
        </form>

        <form method="POST" action="{{ route('author.manuscripts.files.store', $manuscript) }}" enctype="multipart/form-data" class="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
            @csrf
            <h2 class="font-semibold text-brand-950">Supplementary files</h2>
            <p class="text-sm text-slate-600">PDF, Office, CSV, ZIP, or images, maximum {{ number_format($supplementaryConfig['max_kilobytes'] / 1024, 0) }} MB each.</p>
            <input type="hidden" name="type" value="supplementary">
            <x-form.file name="file" label="Upload supplementary file" required />
            <x-form.button :full="false">Upload supplementary file</x-form.button>
        </form>
    </section>

    @if ($manuscript->files->isNotEmpty())
        <section class="mt-8 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">File</th>
                        <th class="px-4 py-3 font-medium">Type</th>
                        <th class="px-4 py-3 font-medium">Size</th>
                        <th class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($manuscript->files as $file)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                {{ $file->original_filename }}
                                @if ($file->isImmutable())
                                    <span class="ml-2 text-xs text-slate-500">submitted version</span>
                                @else
                                    <span class="ml-2 text-xs text-slate-500">working copy</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $file->type->label() }}</td>
                            <td class="px-4 py-3">{{ number_format($file->size_bytes / 1024, 1) }} KB</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('author.manuscripts.files.download', [$manuscript, $file]) }}" class="font-medium text-brand-800 hover:underline">Download</a>
                                    @can('delete', $file)
                                        <x-form.delete :action="route('author.manuscripts.files.destroy', [$manuscript, $file])" title="Remove this file?">
                                            Remove
                                        </x-form.delete>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-layouts.app>
