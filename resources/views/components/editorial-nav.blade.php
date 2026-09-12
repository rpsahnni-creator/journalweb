@php
    $links = [
        ['route' => 'editorial.dashboard', 'label' => 'Overview', 'match' => 'editorial.dashboard', 'icon' => 'layout-dashboard'],
        ['route' => 'editorial.manuscripts.index', 'label' => 'Manuscripts', 'match' => 'editorial.manuscripts.*', 'icon' => 'file-text'],
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

    @can('viewAny', App\Models\Volume::class)
        @php
            $volActive = request()->routeIs('editorial.volumes.*');
        @endphp
        <a
            href="{{ route('editorial.volumes.index') }}"
            @class([
                'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-150',
                'bg-brand-900 text-white shadow-xs' => $volActive,
                'text-slate-600 hover:bg-slate-100/80 hover:text-brand-900' => ! $volActive,
            ])
        >
            <x-icon name="library" class="h-4 w-4" />
            <span>Volumes</span>
        </a>
    @endcan

    @can('viewAny', App\Models\Issue::class)
        @php
            $issueActive = request()->routeIs('editorial.issues.*');
        @endphp
        <a
            href="{{ route('editorial.issues.index') }}"
            @class([
                'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-150',
                'bg-brand-900 text-white shadow-xs' => $issueActive,
                'text-slate-600 hover:bg-slate-100/80 hover:text-brand-900' => ! $issueActive,
            ])
        >
            <x-icon name="book-open" class="h-4 w-4" />
            <span>Issues</span>
        </a>
    @endcan

    @php
        $notifActive = request()->routeIs('editorial.notifications.*');
    @endphp
    <a
        href="{{ route('editorial.notifications.index') }}"
        @class([
            'inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-all duration-150',
            'bg-brand-900 text-white shadow-xs' => $notifActive,
            'text-slate-600 hover:bg-slate-100/80 hover:text-brand-900' => ! $notifActive,
        ])
    >
        <x-icon name="bell" class="h-4 w-4" />
        <span>Notifications</span>
    </a>
</nav>

