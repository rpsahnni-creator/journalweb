@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['locale-hi' => app()->isLocale('hi')])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-init />
    <x-pwa-meta />
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700|source-serif-4:400,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased flex flex-col justify-between relative selection:bg-brand-900 selection:text-white">
    <!-- Ambient background pattern -->
    <div class="pointer-events-none absolute inset-0 bg-academic-grid opacity-60"></div>
    <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 h-96 w-[48rem] rounded-full bg-gradient-to-tr from-brand-200/40 via-accent-200/20 to-transparent blur-3xl"></div>

    <header class="relative z-10 border-b border-slate-200/80 bg-white/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5 sm:px-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 text-accent-400 shadow-sm ring-1 ring-white/10 group-hover:shadow-md transition-all">
                    <x-icon name="book-open" class="h-4.5 w-4.5" />
                </div>
                <div>
                    <span class="font-serif text-base font-bold text-slate-900 tracking-tight group-hover:text-brand-900 transition-colors block leading-tight">
                        {{ config('app.name') }}
                    </span>
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Peer-Reviewed Open Access</span>
                </div>
            </a>
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-brand-900 transition-colors">
                <x-icon name="arrow-left" class="h-3.5 w-3.5" />
                <span>Return to Journal</span>
            </a>
        </div>
    </header>

    <main class="relative z-10 flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
        {{ $slot }}
    </main>

    <footer class="relative z-10 border-t border-slate-200/60 bg-white/60 backdrop-blur-sm py-4">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-1.5">
                    <x-icon name="shield-check" class="h-3.5 w-3.5 text-emerald-600" />
                    <span>Encrypted & Audited Access</span>
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <x-icon name="lock" class="h-3.5 w-3.5 text-slate-400" />
                    <span>Argon2 Password Hashing</span>
                </span>
            </div>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
