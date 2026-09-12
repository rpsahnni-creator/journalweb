@props([
    'title' => null,
    'metaDescription' => null,
])

@php
    $pageTitle = $title ? $title.' — '.config('app.name') : config('app.name');
    $description = $metaDescription ?? 'Signed-in workspace for the academic journal management system.';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['locale-hi' => app()->isLocale('hi')])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-init />
    <x-pwa-meta />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700|source-serif-4:400,600,700|noto-sans-devanagari:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    <div class="flex min-h-screen flex-col">
        <x-navigation />
        <main class="flex-1">
            <x-public-container class="py-10">
                {{ $slot }}
            </x-public-container>
        </main>
        <x-site-footer />
    </div>
    <x-chatbot />
</body>
</html>
