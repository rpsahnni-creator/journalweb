<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header :title="$title" :description="$metaDescription" :eyebrow="$eyebrow ?? 'Publisher'" />
    </x-slot:header>

        <article class="max-w-3xl rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-900">
                    <x-icon name="building" class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="font-serif text-2xl font-semibold text-brand-950">{{ $publisherName }}</h2>
                    <p class="text-sm font-medium text-accent-700">Constituent Unit of Sido Kanhu Murmu University, Dumka</p>
                </div>
            </div>

            <div class="mt-6 space-y-6 text-base leading-7 text-slate-700">
                <p>
                    <strong>{{ $publisherName }}</strong>, situated in {{ $publisherAddress }}, is the official institutional publisher of the <em>SRT Journal of Multidisciplinary Research</em>. The college is a constituent unit of Sido Kanhu Murmu University, Dumka, dedicated to promoting quality academic research, education, and regional development across Jharkhand and India.
                </p>

                <div class="rounded-xl border border-slate-200/70 bg-slate-50/80 p-5 space-y-3 text-sm">
                    <h3 class="font-semibold text-slate-900">Institutional &amp; Contact Details</h3>
                    <dl class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-1 text-slate-500 font-medium">Institution:</div>
                        <div class="sm:col-span-2 text-slate-900 font-semibold">{{ $publisherName }}</div>

                        <div class="sm:col-span-1 text-slate-500 font-medium">Affiliation:</div>
                        <div class="sm:col-span-2 text-slate-800">A constituent unit of Sido Kanhu Murmu University, Dumka</div>

                        <div class="sm:col-span-1 text-slate-500 font-medium">Postal Address:</div>
                        <div class="sm:col-span-2 text-slate-800">{{ $publisherAddress }}</div>

                        @if ($publisherEmail)
                            <div class="sm:col-span-1 text-slate-500 font-medium">Email:</div>
                            <div class="sm:col-span-2">
                                <a href="mailto:{{ $publisherEmail }}" class="font-semibold text-brand-800 hover:text-brand-700 hover:underline">{{ $publisherEmail }}</a>
                            </div>
                        @endif

                        <div class="sm:col-span-1 text-slate-500 font-medium">Official Website:</div>
                        <div class="sm:col-span-2">
                            <a href="{{ $publisherWebsite }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 font-semibold text-brand-800 hover:text-brand-700 hover:underline">
                                {{ $publisherWebsite }}
                                <x-icon name="external-link" class="h-3.5 w-3.5 text-slate-400" />
                            </a>
                        </div>
                    </dl>
                </div>

                <p class="text-sm text-slate-600">
                    For institutional inquiries, editorial oversight correspondence, or print record requests, please contact the college administration or email <a href="mailto:{{ $publisherEmail }}" class="text-brand-800 hover:underline">{{ $publisherEmail }}</a>.
                </p>
            </div>
        </article>
</x-layouts.public>
