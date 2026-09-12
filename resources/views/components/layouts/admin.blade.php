@props([
    'title' => 'Admin',
])

@php
    $pageTitle = $title.' — Admin — '.config('app.name');
    $journal = \App\Support\CurrentJournal::managed();
    $navUser = auth()->user();
    $navItems = collect([
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'match' => 'admin.dashboard', 'show' => $navUser?->isAdmin()],
        ['label' => 'Submissions', 'route' => 'admin.submissions.index', 'icon' => 'inbox', 'match' => 'admin.submissions.*', 'show' => $navUser?->isEditor()],
        ['label' => 'Users', 'route' => 'admin.users.index', 'icon' => 'users', 'match' => 'admin.users.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Roles', 'route' => 'admin.roles.index', 'icon' => 'shield', 'match' => 'admin.roles.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Journal settings', 'route' => 'admin.journal.edit', 'icon' => 'settings', 'match' => 'admin.journal.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Editorial board', 'route' => 'admin.editorial-board.index', 'icon' => 'user-cog', 'match' => 'admin.editorial-board.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Policies', 'route' => 'admin.policies.index', 'icon' => 'file-text', 'match' => 'admin.policies.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Volumes', 'route' => 'admin.volumes.index', 'icon' => 'library', 'match' => 'admin.volumes.*', 'show' => $navUser?->isAdmin()],
        ['label' => 'Issues', 'route' => 'admin.issues.index', 'icon' => 'book-open', 'match' => 'admin.issues.*', 'show' => $navUser?->isAdmin() || $navUser?->isEditor()],
        ['label' => 'Audit log', 'route' => 'admin.audit-logs.index', 'icon' => 'scroll-text', 'match' => 'admin.audit-logs.*', 'show' => $navUser?->isAdmin()],
    ])->filter(fn (array $item): bool => (bool) ($item['show'] ?? false))->values();
    $adminHome = $navUser?->isAdmin() ? 'admin.dashboard' : 'admin.submissions.index';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['locale-hi' => app()->isLocale('hi')])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-init />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="Staff workspace for journal configuration, users, and published content.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700|source-serif-4:400,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false, userMenu: false }">
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-800 bg-brand-950 text-slate-200 transition-transform lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        @keydown.escape.window="sidebarOpen = false"
    >
        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
            <a href="{{ route($adminHome) }}" class="flex min-w-0 items-center gap-2.5 group">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-900 to-brand-800 text-accent-400 ring-1 ring-white/10 shadow-sm">
                    <x-icon name="book-open" class="h-4.5 w-4.5" />
                </div>
                <div>
                    <span class="truncate font-serif text-base font-bold text-white block leading-tight">{{ $journal?->abbreviation ?: 'Admin' }}</span>
                    <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">Management Console</span>
                </div>
            </a>
            <button type="button" class="rounded-lg p-1 text-slate-400 hover:text-white hover:bg-white/10 lg:hidden cursor-pointer" @click="sidebarOpen = false">
                <span class="sr-only">Close sidebar</span>
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3.5 py-4">
            <p class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Administration</p>
            <ul class="space-y-1.5">
                @foreach ($navItems as $item)
                    @php
                        $active = request()->routeIs($item['match']);
                    @endphp
                    <li>
                        <a
                            href="{{ route($item['route']) }}"
                            @click="sidebarOpen = false"
                            @class([
                                'flex items-center gap-3 rounded-lg px-3.5 py-2.5 text-sm font-medium transition-all duration-150',
                                'bg-white/15 text-white font-semibold shadow-xs ring-1 ring-white/10' => $active,
                                'text-slate-300 hover:bg-white/5 hover:text-white' => ! $active,
                            ])
                        >
                            <x-icon :name="$item['icon']" class="h-4 w-4" />
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="border-t border-white/10 p-4 text-xs text-slate-400 bg-black/20">
            <p class="truncate font-medium text-slate-300">{{ $journal?->name ?? 'No journal record yet' }}</p>
            <a href="{{ route('home') }}" class="mt-2 inline-flex items-center gap-1.5 text-accent-400 hover:text-accent-300 transition-colors">
                <x-icon name="external-link" class="h-3.5 w-3.5" />
                <span>View public site</span>
            </a>
        </div>
    </aside>


    <div class="min-h-screen lg:pl-72">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur-md shadow-xs">
            <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 lg:hidden cursor-pointer"
                        @click="sidebarOpen = true"
                    >
                        <x-icon name="menu" class="h-4 w-4 text-slate-500" />
                        <span>Menu</span>
                    </button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-brand-50 text-brand-700 ring-1 ring-brand-200/50">Admin Console</span>
                        </div>
                        <h1 class="truncate font-serif text-lg font-bold text-slate-900 sm:text-xl">{{ $title }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <x-notification-bell compact class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:text-brand-900" />
                    <a href="{{ route('home') }}" class="hidden items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:text-brand-900 transition-colors sm:inline-flex">
                        <x-icon name="globe" class="h-3.5 w-3.5 text-slate-500" />
                        <span>Public site</span>
                    </a>

                    <div class="relative" @click.outside="userMenu = false">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer"
                            @click="userMenu = !userMenu"
                            :aria-expanded="userMenu.toString()"
                        >
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-900 text-[10px] font-bold text-white">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 2)) }}
                            </span>
                            <span class="hidden max-w-[10rem] truncate font-semibold sm:inline">{{ auth()->user()?->name }}</span>
                            <x-icon name="chevron-down" class="h-3 w-3 text-slate-400" />
                        </button>
                        <div
                            x-show="userMenu"
                            x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl ring-1 ring-slate-900/5 z-50 divide-y divide-slate-100"
                        >
                            <div class="px-3.5 py-2">
                                <p class="text-xs font-semibold text-slate-900 truncate">{{ auth()->user()?->name }}</p>
                                <p class="text-[10px] font-medium text-slate-500 uppercase tracking-wider">Signed in</p>
                            </div>
                            <div class="py-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-brand-900 transition-colors">
                                    <x-icon name="user" class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Profile</span>
                                </a>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-brand-900 transition-colors">
                                    <x-icon name="layout-dashboard" class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Workspace</span>
                                </a>
                            </div>
                            <div class="py-1">
                                <form
                                    method="POST"
                                    action="{{ route('logout') }}"
                                    @submit.prevent="Swal.fire({
                                        title: 'Log out?',
                                        text: 'You can sign in again at any time.',
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonColor: '#1b3654',
                                        confirmButtonText: 'Log out'
                                    }).then((result) => { if (result.isConfirmed) $el.submit() })"
                                >
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                        <x-icon name="log-out" class="h-3.5 w-3.5 text-rose-500" />
                                        <span>Log out</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="px-4 py-8 sm:px-6">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</body>
</html>
