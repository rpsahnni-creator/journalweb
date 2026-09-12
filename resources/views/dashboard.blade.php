<x-layouts.public title="Dashboard">
    <!-- User Profile Hero Header -->
    <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-900 to-brand-800 text-white font-serif font-bold text-xl shadow-xs ring-4 ring-brand-50">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="font-serif text-2xl sm:text-3xl font-semibold text-brand-950">Welcome back, {{ $user->name }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $user->email }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($user->roles as $role)
                    <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-700/15">
                        <x-icon name="shield" class="h-3 w-3 text-brand-700" />
                        {{ $role->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Available Workspaces</h2>
        
        <div class="mt-4 grid gap-5 sm:grid-cols-2">
            @can('access-admin')
                <a href="{{ route('admin.dashboard') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-700 ring-1 ring-purple-700/10 group-hover:scale-105 transition-transform">
                        <x-icon name="shield" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Admin panel</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Users, system roles, journal settings, and audit logs.</p>
                    </div>
                </a>
            @endcan

            @can('access-editorial')
                <a href="{{ route('editorial.dashboard') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-800 ring-1 ring-brand-700/10 group-hover:scale-105 transition-transform">
                        <x-icon name="layout-dashboard" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Editorial office</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Submissions queue, peer review management, volumes & issues.</p>
                    </div>
                </a>
            @endcan

            @if (auth()->user()?->is_reviewer)
                <a href="{{ route('reviews.index') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700 ring-1 ring-emerald-700/10 group-hover:scale-105 transition-transform">
                        <x-icon name="clipboard-check" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Assigned reviews</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Double-blind reviews of assigned manuscript submissions.</p>
                    </div>
                </a>
            @endif

            @if (auth()->user()?->hasRole(\App\Enums\RoleSlug::Reviewer))
                <a href="{{ route('reviewer.dashboard') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 ring-1 ring-teal-700/10 group-hover:scale-105 transition-transform">
                        <x-icon name="files" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Reviewer portal</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Assigned manuscripts, evaluation rubrics, and confidential comments.</p>
                    </div>
                </a>
            @endif

            @if (auth()->user()?->isEditor())
                <a href="{{ route('admin.submissions.index') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 ring-1 ring-teal-700/10 group-hover:scale-105 transition-transform">
                        <x-icon name="inbox" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Manuscript submissions</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Review uploaded manuscripts, update status, and convert accepted work to articles.</p>
                    </div>
                </a>
            @endif

            <a href="{{ route('submissions.create') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700 ring-1 ring-accent-600/20 group-hover:scale-105 transition-transform">
                    <x-icon name="upload" class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Submit a manuscript</h3>
                        <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Upload a PDF or DOCX file and track its editorial status.</p>
                </div>
            </a>

            @can('access-author')
                <a href="{{ route('author.dashboard') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-700 ring-1 ring-accent-600/20 group-hover:scale-105 transition-transform">
                        <x-icon name="file-text" class="h-6 w-6" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Author portal</h3>
                            <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Your manuscript drafts, submission status, and editorial decisions.</p>
                    </div>
                </a>
            @endcan

            <a href="{{ route('profile.edit') }}" class="card-hover-lift group relative flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 group-hover:scale-105 transition-transform">
                    <x-icon name="settings" class="h-6 w-6" />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-serif text-lg font-semibold text-brand-950 group-hover:text-brand-700 transition-colors">Profile settings</h3>
                        <x-icon name="arrow-right" class="h-4 w-4 text-slate-400 group-hover:translate-x-1 transition-transform" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">Account credentials, affiliation, and password security.</p>
                </div>
            </a>
        </div>
    </div>
</x-layouts.public>

