@php
    $journalName = \App\Support\CurrentJournal::get()?->name ?? config('app.name');
@endphp

<header
    class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur"
    x-data="{
        open: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
        }
    }"
    x-init="$watch('open', value => document.body.classList.toggle('overflow-hidden', value))"
    @keydown.escape.window="open = false"
>
    <div class="mx-auto flex max-w-public items-center justify-between gap-3 px-4 py-3 sm:gap-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 flex-1 items-center gap-2 font-serif text-base font-semibold tracking-tight text-brand-950 sm:text-lg lg:text-xl">
            <x-icon name="book-open" class="h-5 w-5 shrink-0 text-accent-600" />
            <span class="truncate">{{ $journalName }}</span>
        </a>

        <button
            type="button"
            class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 touch-manipulation lg:hidden"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-controls="primary-navigation"
        >
            <x-icon name="menu" class="h-4 w-4" />
            <span class="ml-2" x-text="open ? @js(__('ui.close')) : @js(__('ui.menu'))"></span>
        </button>

        <nav id="primary-navigation" class="hidden items-center gap-5 text-sm font-medium text-slate-600 lg:flex">
            <a href="{{ route('home') }}" class="hover:text-brand-800">{{ __('ui.home') }}</a>

            <x-dropdown :label="__('ui.journal')">
                <x-dropdown-link :href="route('about')">{{ __('ui.about') }}</x-dropdown-link>
                <x-dropdown-link :href="route('aims-and-scope')">{{ __('ui.aims') }}</x-dropdown-link>
                <x-dropdown-link :href="route('editorial-board')">{{ __('ui.board') }}</x-dropdown-link>
                <x-dropdown-link :href="route('publication-ethics')">{{ __('ui.ethics') }}</x-dropdown-link>
                <x-dropdown-link :href="route('contact')">{{ __('ui.contact') }}</x-dropdown-link>
            </x-dropdown>

            <x-dropdown :label="__('ui.for_authors')">
                <x-dropdown-link :href="route('author-guidelines')">{{ __('ui.guidelines') }}</x-dropdown-link>
                <x-dropdown-link :href="route('peer-review-policy')">{{ __('ui.peer_review') }}</x-dropdown-link>
                <x-dropdown-link :href="route('plagiarism-policy')">{{ __('ui.plagiarism') }}</x-dropdown-link>
                <x-dropdown-link :href="route('copyright-and-license')">{{ __('ui.copyright') }}</x-dropdown-link>
                @auth
                    <x-dropdown-link :href="route('submissions.create')">{{ __('ui.submit') }}</x-dropdown-link>
                    <x-dropdown-link :href="route('submissions.index')">{{ __('ui.my_submissions') }}</x-dropdown-link>
                @else
                    <x-dropdown-link :href="route('register')">{{ __('ui.register') }}</x-dropdown-link>
                @endauth
            </x-dropdown>

            <x-dropdown :label="__('ui.issues')">
                <x-dropdown-link :href="route('issues.current')">{{ __('ui.current_issue') }}</x-dropdown-link>
                <x-dropdown-link :href="route('issues.index')">{{ __('ui.previous_issues') }}</x-dropdown-link>
                <x-dropdown-link :href="route('articles.index')">{{ __('ui.articles') }}</x-dropdown-link>
            </x-dropdown>

            <x-reader-tools />

            @auth
                <x-notification-bell class="hover:text-brand-800" />
                <x-dropdown label="{{ auth()->user()->name }}" align="right">
                    <x-dropdown-link :href="route('dashboard')">{{ __('ui.dashboard') }}</x-dropdown-link>
                    @can('access-admin')
                        <x-dropdown-link :href="route('admin.dashboard')">{{ __('ui.admin') }}</x-dropdown-link>
                    @endcan
                    @if (auth()->user()?->isEditor())
                        <x-dropdown-link :href="route('admin.submissions.index')">{{ __('ui.editorial_submissions') }}</x-dropdown-link>
                    @endif
                    @if (auth()->user()?->is_reviewer)
                        <x-dropdown-link :href="route('reviews.index')">{{ __('ui.reviews') }}</x-dropdown-link>
                    @endif
                    <x-dropdown-link :href="route('profile.edit')">{{ __('ui.profile') }}</x-dropdown-link>
                    <div class="my-1 border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-900">
                            {{ __('ui.logout') }}
                        </button>
                    </form>
                </x-dropdown>
            @else
                <a href="{{ route('login') }}" class="hover:text-brand-800">{{ __('ui.login') }}</a>
                <a href="{{ route('register') }}" class="inline-flex items-center rounded-md bg-brand-900 px-3 py-1.5 text-white hover:bg-brand-800">{{ __('ui.register') }}</a>
            @endauth
        </nav>
    </div>

    <nav class="max-h-[min(70vh,32rem)] overflow-y-auto overscroll-contain border-t border-slate-200 px-4 py-3 lg:hidden" x-show="open" x-cloak x-transition>
        <div class="flex flex-col text-sm font-medium text-slate-700">
            <div class="mb-2 pb-2">
                <x-reader-tools />
            </div>
            <a class="flex min-h-11 items-center" href="{{ route('home') }}" @click="open = false">{{ __('ui.home') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('about') }}" @click="open = false">{{ __('ui.about') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('aims-and-scope') }}" @click="open = false">{{ __('ui.aims') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('editorial-board') }}" @click="open = false">{{ __('ui.board') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('author-guidelines') }}" @click="open = false">{{ __('ui.guidelines') }}</a>
            @auth
                <a class="flex min-h-11 items-center" href="{{ route('submissions.create') }}" @click="open = false">{{ __('ui.submit') }}</a>
                <a class="flex min-h-11 items-center" href="{{ route('submissions.index') }}" @click="open = false">{{ __('ui.my_submissions') }}</a>
            @endauth
            <a class="flex min-h-11 items-center" href="{{ route('peer-review-policy') }}" @click="open = false">{{ __('ui.peer_review') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('publication-ethics') }}" @click="open = false">{{ __('ui.ethics') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('plagiarism-policy') }}" @click="open = false">{{ __('ui.plagiarism') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('copyright-and-license') }}" @click="open = false">{{ __('ui.copyright') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('issues.current') }}" @click="open = false">{{ __('ui.current_issue') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('issues.index') }}" @click="open = false">{{ __('ui.previous_issues') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('articles.index') }}" @click="open = false">{{ __('ui.articles') }}</a>
            <a class="flex min-h-11 items-center" href="{{ route('contact') }}" @click="open = false">{{ __('ui.contact') }}</a>
            @auth
                <a class="flex min-h-11 items-center" href="{{ route('notifications.index') }}" @click="open = false">{{ __('ui.notifications') }}</a>
                <a class="flex min-h-11 items-center" href="{{ route('dashboard') }}" @click="open = false">{{ __('ui.dashboard') }}</a>
                @can('access-admin')
                    <a class="flex min-h-11 items-center" href="{{ route('admin.dashboard') }}" @click="open = false">{{ __('ui.admin') }}</a>
                @endcan
                @if (auth()->user()?->isEditor())
                    <a class="flex min-h-11 items-center" href="{{ route('admin.submissions.index') }}" @click="open = false">{{ __('ui.editorial_submissions') }}</a>
                @endif
                @if (auth()->user()?->is_reviewer)
                    <a class="flex min-h-11 items-center" href="{{ route('reviews.index') }}" @click="open = false">{{ __('ui.reviews') }}</a>
                @endif
                <a class="flex min-h-11 items-center" href="{{ route('profile.edit') }}" @click="open = false">{{ __('ui.profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex min-h-11 w-full items-center text-left">{{ __('ui.logout') }}</button>
                </form>
            @else
                <a class="flex min-h-11 items-center" href="{{ route('login') }}" @click="open = false">{{ __('ui.login') }}</a>
                <a class="flex min-h-11 items-center font-semibold text-brand-900" href="{{ route('register') }}" @click="open = false">{{ __('ui.register') }}</a>
            @endauth
        </div>
    </nav>
</header>
