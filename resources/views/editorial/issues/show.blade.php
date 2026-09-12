<x-layouts.app :title="$issue->displayLabel()">
    <x-editorial-nav />
    <x-flash />

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500">{{ $issue->status->label() }}</p>
            <h1 class="font-serif text-3xl font-semibold text-brand-950">{{ $issue->displayLabel() }}</h1>
            @if ($issue->title)
                <p class="mt-1 text-lg text-slate-700">{{ $issue->title }}</p>
            @endif
            <p class="mt-2 text-slate-600">
                Cover date: {{ $issue->published_at?->toFormattedDateString() ?: 'Not set' }}
            </p>
            @if ($issue->description)
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $issue->description }}</p>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @can('update', $issue)
                <a href="{{ route('editorial.issues.edit', $issue) }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Edit details
                </a>
            @endcan
            @can('publish', $issue)
                @if ($issue->isPublished())
                    <form
                        method="POST"
                        action="{{ route('editorial.issues.unpublish', $issue) }}"
                        @submit.prevent="Swal.fire({
                            title: 'Unpublish this issue?',
                            text: 'Articles will leave the public site until the issue is published again.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#b91c1c',
                            confirmButtonText: 'Unpublish'
                        }).then((result) => { if (result.isConfirmed) $el.submit() })"
                    >
                        @csrf
                        <x-form.button variant="danger" :full="false">Unpublish</x-form.button>
                    </form>
                @else
                    <form method="POST" action="{{ route('editorial.issues.publish', $issue) }}">
                        @csrf
                        <x-form.button :full="false">Publish issue</x-form.button>
                    </form>
                @endif
            @endcan
            @can('delete', $issue)
                <x-form.delete :action="route('editorial.issues.destroy', $issue)" title="Delete this draft issue?">
                    Delete issue
                </x-form.delete>
            @endcan
        </div>
    </div>

    @if ($issue->isPublished())
        <p class="mt-4 text-sm text-slate-600">
            Public issue page:
            <a href="{{ route('issues.show', ['volume' => $issue->volume->number, 'issue' => $issue->number]) }}" class="font-medium text-brand-800 hover:underline">
                {{ route('issues.show', ['volume' => $issue->volume->number, 'issue' => $issue->number]) }}
            </a>
        </p>
    @endif

    <section class="mt-10 rounded-lg border border-slate-200 bg-white p-6">
        <h2 class="font-serif text-2xl font-semibold text-brand-950">Articles in this issue</h2>
        <p class="mt-1 text-sm text-slate-600">Set the reading order, an article number or page range, then publish. Each published article receives its own public URL.</p>

        @if ($issue->issueArticles->isNotEmpty())
            @can('assignArticles', $issue)
                <form method="POST" action="{{ route('editorial.issues.articles.reorder', $issue) }}" class="mt-6">
                    @csrf
                    @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Order</th>
                                    <th class="px-3 py-2 font-medium">Article</th>
                                    <th class="px-3 py-2 font-medium">Number</th>
                                    <th class="px-3 py-2 font-medium">Pages</th>
                                    <th class="px-3 py-2 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($issue->issueArticles as $placement)
                                    @php $article = $placement->article; @endphp
                                    <tr class="border-t border-slate-100">
                                        <td class="px-3 py-3">
                                            <input
                                                type="number"
                                                name="order[{{ $article->id }}]"
                                                value="{{ old('order.'.$article->id, $placement->sort_order) }}"
                                                min="1"
                                                class="w-20 rounded-md border border-slate-300 px-2 py-1 text-sm"
                                            >
                                        </td>
                                        <td class="px-3 py-3">
                                            <p class="font-medium text-brand-950">{{ $article->title }}</p>
                                            <p class="text-xs text-slate-500">{{ $article->submission_number }}</p>
                                            @if ($article->isPubliclyVisible())
                                                <a href="{{ route('articles.show', $article) }}" class="text-xs font-medium text-brand-800 hover:underline">Public page</a>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-slate-600">{{ $placement->article_number ?: '—' }}</td>
                                        <td class="px-3 py-3 text-slate-600">{{ $article->pageRange() ?: '—' }}</td>
                                        <td class="px-3 py-3">{{ $article->status->label() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <x-form.button :full="false" variant="secondary">Save order</x-form.button>
                    </div>
                </form>
            @else
                <ul class="mt-6 divide-y divide-slate-100 text-sm">
                    @foreach ($issue->issueArticles as $placement)
                        <li class="py-3">
                            <p class="font-medium text-brand-950">{{ $placement->article->title }}</p>
                            <p class="text-slate-500">{{ $placement->article->status->label() }} · {{ $placement->article_number ?: 'No article number' }} · {{ $placement->article->pageRange() ?: 'No page range' }}</p>
                        </li>
                    @endforeach
                </ul>
            @endcan

            @can('assignArticles', $issue)
                <div class="mt-8 space-y-4">
                    <h3 class="font-semibold text-brand-950">Article numbers and page ranges</h3>
                    @foreach ($issue->issueArticles as $placement)
                        @php $article = $placement->article; @endphp
                        <form method="POST" action="{{ route('editorial.issues.articles.update', [$issue, $placement]) }}" class="rounded-md border border-slate-200 bg-slate-50 p-4">
                            @csrf
                            @method('PUT')
                            <p class="font-medium text-brand-950">{{ $article->title }}</p>
                            <div class="mt-3 grid gap-3 md:grid-cols-4">
                                <x-form.input name="sort_order" type="number" label="Order" value="{{ $placement->sort_order }}" min="1" />
                                <x-form.input name="article_number" label="Article number" value="{{ $placement->article_number }}" />
                                <x-form.input name="page_start" type="number" label="First page" value="{{ $article->page_start }}" min="1" />
                                <x-form.input name="page_end" type="number" label="Last page" value="{{ $article->page_end }}" min="1" />
                            </div>
                            <div class="mt-3 flex items-center gap-4">
                                <x-form.button :full="false">Save placement</x-form.button>
                                @unless ($issue->isPublished())
                                    <x-form.delete
                                        :action="route('editorial.issues.articles.destroy', [$issue, $placement])"
                                        title="Remove this article from the issue?"
                                        text="The article returns to the accepted list."
                                        confirm="Remove"
                                    >
                                        Remove
                                    </x-form.delete>
                                @endunless
                            </div>
                        </form>
                    @endforeach
                </div>
            @endcan
        @else
            <p class="mt-6 text-sm text-slate-600">No articles have been assigned yet.</p>
        @endif
    </section>

    @can('assignArticles', $issue)
        <section class="mt-8 rounded-lg border border-slate-200 bg-white p-6">
            <h2 class="font-serif text-2xl font-semibold text-brand-950">Assign an accepted article</h2>
            @if ($assignable->isEmpty())
                <p class="mt-3 text-sm text-slate-600">There are no accepted articles waiting for an issue. Articles already placed in another issue cannot be moved here until they are removed.</p>
            @else
                <form method="POST" action="{{ route('editorial.issues.articles.store', $issue) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                    @csrf
                    <div class="md:col-span-2">
                        <x-form.select
                            name="article_id"
                            label="Article"
                            :options="$assignable->mapWithKeys(fn ($article) => [$article->id => $article->title.' ('.$article->submission_number.')'])"
                            :selected="old('article_id')"
                            placeholder="Select an accepted article"
                            required
                        />
                    </div>
                    <x-form.input name="article_number" label="Article number (optional)" value="{{ old('article_number') }}" />
                    <x-form.input name="sort_order" type="number" label="Order" value="{{ old('sort_order') }}" min="1" />
                    <x-form.input name="page_start" type="number" label="First page" value="{{ old('page_start') }}" min="1" />
                    <x-form.input name="page_end" type="number" label="Last page" value="{{ old('page_end') }}" min="1" />
                    <div class="md:col-span-2">
                        <x-form.button :full="false">Assign article</x-form.button>
                    </div>
                </form>
            @endif
        </section>
    @endcan
</x-layouts.app>
