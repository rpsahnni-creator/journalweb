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
>
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 font-serif text-lg font-semibold tracking-tight text-brand-950 sm:text-xl">
            <x-icon name="book-open" class="h-5 w-5 text-accent-600" />
            <span class="truncate">{{ $journalName }}</span>
        </a>

        <button
            type="button"
            class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 lg:hidden"
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

    <nav class="border-t border-slate-200 px-4 py-3 lg:hidden" x-show="open" x-cloak x-transition>
        <div class="flex flex-col gap-3 text-sm font-medium text-slate-700">
            <x-reader-tools />
            <a href="{{ route('home') }}">{{ __('ui.home') }}</a>
            <a href="{{ route('about') }}">{{ __('ui.about') }}</a>
            <a href="{{ route('aims-and-scope') }}">{{ __('ui.aims') }}</a>
            <a href="{{ route('editorial-board') }}">{{ __('ui.board') }}</a>
            <a href="{{ route('author-guidelines') }}">{{ __('ui.guidelines') }}</a>
            @auth
                <a href="{{ route('submissions.create') }}">{{ __('ui.submit') }}</a>
                <a href="{{ route('submissions.index') }}">{{ __('ui.my_submissions') }}</a>
            @endauth
            <a href="{{ route('peer-review-policy') }}">{{ __('ui.peer_review') }}</a>
            <a href="{{ route('publication-ethics') }}">{{ __('ui.ethics') }}</a>
            <a href="{{ route('plagiarism-policy') }}">{{ __('ui.plagiarism') }}</a>
            <a href="{{ route('copyright-and-license') }}">{{ __('ui.copyright') }}</a>
            <a href="{{ route('issues.current') }}">{{ __('ui.current_issue') }}</a>
            <a href="{{ route('issues.index') }}">{{ __('ui.previous_issues') }}</a>
            <a href="{{ route('articles.index') }}">{{ __('ui.articles') }}</a>
            <a href="{{ route('contact') }}">{{ __('ui.contact') }}</a>
            @auth
                <a href="{{ route('notifications.index') }}">{{ __('ui.notifications') }}</a>
                <a href="{{ route('dashboard') }}">{{ __('ui.dashboard') }}</a>
                @can('access-admin')
                    <a href="{{ route('admin.dashboard') }}">{{ __('ui.admin') }}</a>
                @endcan
                @if (auth()->user()?->isEditor())
                    <a href="{{ route('admin.submissions.index') }}">{{ __('ui.editorial_submissions') }}</a>
                @endif
                @if (auth()->user()?->is_reviewer)
                    <a href="{{ route('reviews.index') }}">{{ __('ui.reviews') }}</a>
                @endif
                <a href="{{ route('profile.edit') }}">{{ __('ui.profile') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">{{ __('ui.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}">{{ __('ui.login') }}</a>
                <a href="{{ route('register') }}">{{ __('ui.register') }}</a>
            @endauth
        </div>
    </nav>
</header>
