@props([
    'title' => null,
    'metaDescription' => null,
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'fullBleed' => false,
])

@php
    $pageTitle = $title ? $title.' — '.config('app.name') : config('app.name');
    $description = $metaDescription ?? 'An academic journal website for published articles, issues, and editorial policy. Unpublished manuscripts are not displayed.';
    $canonicalUrl = $canonical ?: url()->current();
    $shareImage = $ogImage ?: url('/images/journal-og-default.png');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['locale-hi' => app()->isLocale('hi')])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-theme-init />
    <x-pwa-meta />
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $shareImage }}">
    @endif
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="alternate" type="application/atom+xml" title="{{ config('app.name') }} — Latest Articles" href="{{ url('/feed') }}">
    <link rel="alternate" type="application/atom+xml" title="{{ config('app.name') }} — Issues" href="{{ url('/feed/issues') }}">
    @isset($head)
        {{ $head }}
    @endisset
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=source-sans-3:400,500,600,700|source-serif-4:400,600,700|noto-sans-devanagari:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased" x-data>
    <div class="flex min-h-screen flex-col">
        <x-navigation />
        <main class="flex-1">
            @isset($header)
                {{ $header }}
            @endisset

            @if ($fullBleed)
                {{ $slot }}
            @else
                <x-public-container class="py-10">
                    {{ $slot }}
                </x-public-container>
            @endif

            @isset($after)
                {{ $after }}
            @endisset
        </main>
        <x-site-footer />
    </div>
</body>
</html>
