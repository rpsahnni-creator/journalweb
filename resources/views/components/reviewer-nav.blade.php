@php
    $links = [
        ['route' => 'reviewer.dashboard', 'label' => 'Assignments', 'match' => 'reviewer.dashboard', 'icon' => 'clipboard-check'],
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
    @if (request()->routeIs('reviewer.assignments.*'))
        <span class="inline-flex items-center gap-2 rounded-lg bg-brand-900 px-3.5 py-2 text-sm font-medium text-white shadow-xs">
            <x-icon name="file-text" class="h-4 w-4" />
            <span>Review Assignment</span>
        </span>
    @endif
</nav>

