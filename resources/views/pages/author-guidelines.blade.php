<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" eyebrow="Journal policy" />
    </x-slot:header>

        <article class="max-w-3xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <ol class="list-decimal space-y-4 pl-5 text-base leading-7 text-slate-700 marker:font-semibold marker:text-brand-800">
                <li>Manuscripts must be original, unpublished work not under consideration elsewhere.</li>
                {{-- TODO: confirm the manuscript word limit (3,000–6,000 words) before publishing live. --}}
                <li>Word limit: {{ $wordLimit }}, including references.</li>
                <li>Manuscripts must be submitted in MS Word (.docx) format, following APA 7th edition citation style.</li>
                {{-- TODO: confirm the similarity-index threshold (15%) before publishing live. --}}
                <li>All submissions undergo a plagiarism/similarity check; manuscripts exceeding a {{ $similarityThreshold }} similarity index will not be considered.</li>
                <li>All submissions go through double-blind peer review before acceptance.</li>
                <li>Authors must submit a short abstract (150–250 words) and 4–6 keywords along with the full manuscript.</li>
                {{-- TODO: confirm the submissions email (journal@srtc.ac.in) before publishing live. --}}
                <li>
                    Submit manuscripts to
                    <a href="mailto:{{ $submissionsEmail }}" class="font-semibold text-brand-800 hover:text-brand-700 hover:underline">{{ $submissionsEmail }}</a>
                    or through the
                    <a href="{{ auth()->check() ? route('submissions.create') : route('register') }}" class="font-semibold text-brand-800 hover:text-brand-700 hover:underline">submission portal</a>
                    after registering on the site.
                </li>
            </ol>

            <div class="mt-8 rounded-xl border border-emerald-200/80 bg-emerald-50/70 p-5 text-sm text-emerald-950">
                <div class="flex items-center gap-2">
                    <x-icon name="shield-check" class="h-5 w-5 text-emerald-700" />
                    <h3 class="font-semibold text-emerald-900">Article Processing Charges (APC)</h3>
                </div>
                <p class="mt-2 leading-relaxed text-emerald-800">
                    SRT Journal of Multidisciplinary Research does not charge any article submission, processing, or publication fees. All articles are published under Diamond (Platinum) Open Access at no cost to authors or their institutions.
                </p>
                <div class="mt-3">
                    <a href="{{ route('article-processing-charges') }}" class="inline-flex items-center gap-1 font-semibold text-brand-900 underline hover:text-brand-700">
                        Read the full Article Processing Charges (APC) policy &rarr;
                    </a>
                </div>
            </div>

            <div class="mt-8 space-y-3 text-sm">
                <p>
                    <a href="{{ route('review-process') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">How the review process works &rarr;</a>
                </p>
                <p>
                    <a href="{{ route('submission-checklist') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">Submission checklist &rarr;</a>
                </p>
                <p>
                    <a href="{{ route('manuscript-preparation') }}" class="font-semibold text-brand-800 underline hover:text-brand-700">Manuscript preparation &rarr;</a>
                </p>
                <x-manuscript-template-download />
            </div>
        </article>
</x-layouts.public>
