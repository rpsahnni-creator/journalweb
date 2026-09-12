@php
    $links = [
        ['route' => 'author.dashboard', 'label' => 'Manuscripts', 'match' => 'author.dashboard', 'icon' => 'file-text'],
        ['route' => 'submissions.create', 'label' => 'Submit manuscript', 'match' => 'submissions.create', 'icon' => 'upload'],
        ['route' => 'submissions.index', 'label' => 'My submissions', 'match' => 'submissions.index', 'icon' => 'inbox'],
        ['route' => 'author.manuscripts.create', 'label' => 'New manuscript', 'match' => 'author.manuscripts.create', 'icon' => 'plus-circle'],
        ['route' => 'author.profile.edit', 'label' => 'Author profile', 'match' => 'author.profile.*', 'icon' => 'user'],
    ];
@endphp

<nav class="mb-8 flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-4 text-sm font-medium">
    @foreach ($links as $link)
        @php
            $active = request()->routeIs($link['match']);
        @endphp
        <a
            href="{{ route($link['route']) }}"
            @class([
                'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-150',
                'bg-brand-900 text-white shadow-xs' => $active,
                'text-slate-600 hover:bg-slate-100/80 hover:text-brand-900' => ! $active,
            ])
        >
            <x-icon :name="$link['icon']" class="h-4 w-4" />
            <span>{{ $link['label'] }}</span>
        </a>
    @endforeach
</nav>

