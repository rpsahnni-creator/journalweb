<x-layouts.public title="Profile">
    <div class="mb-8">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-2">
            <x-icon name="user" class="h-3 w-3" />
            Account Management
        </span>
        <h1 class="font-serif text-3xl font-bold text-slate-900 tracking-tight">Profile</h1>
        <p class="mt-1 text-sm text-slate-600">Update your academic affiliation, ORCID identifier, and public scholar details. Changing your email address will require re-verification.</p>
    </div>

    @if (session('status'))
        <x-alert class="mb-6">{{ session('status') }}</x-alert>
    @endif

    <div class="grid gap-8 lg:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
            @csrf
            @method('PUT')
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                    <x-icon name="user" class="h-3.5 w-3.5" />
                </div>
                <h2 class="font-semibold text-slate-900 text-sm">Account details</h2>
            </div>

            <x-form.input name="name" label="Full name" value="{{ old('name', $user->name) }}" required />
            <x-form.input name="email" type="email" label="Email Address" value="{{ old('email', $user->email) }}" required />
            <x-form.input name="academic_title" label="Academic title" value="{{ old('academic_title', $user->academic_title) }}" placeholder="Professor, Senior Researcher, Dr." />
            <x-form.input name="affiliation" label="Affiliation" value="{{ old('affiliation', $user->affiliation) }}" placeholder="Department, University or Institute" />
            <x-form.input name="orcid" label="ORCID Identifier" value="{{ old('orcid', $user->orcid) }}" placeholder="0000-0000-0000-0000" />
            
            <x-form.textarea name="biography" label="Biography" :rows="4">{{ old('biography', $user->biography) }}</x-form.textarea>

            <div class="pt-2">
                <x-form.button>Save profile</x-form.button>
            </div>
        </form>

        <div class="space-y-8">
            <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                @csrf
                @method('PUT')
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                        <x-icon name="key-round" class="h-3.5 w-3.5" />
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-900 text-sm">Change password</h2>
                        <p class="text-[11px] text-slate-500">Your new password will be encrypted before storage.</p>
                    </div>
                </div>

                <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                
                <div class="pt-2">
                    <x-form.button>Update password</x-form.button>
                </div>
            </form>

            <div class="rounded-xl border border-slate-200/80 bg-white p-6 shadow-xs">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                    <div class="flex h-7 w-7 items-center justify-center rounded-md bg-brand-50 text-brand-800">
                        <x-icon name="shield" class="h-3.5 w-3.5" />
                    </div>
                    <h2 class="font-semibold text-slate-900 text-sm">Assigned Roles</h2>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @forelse ($user->roles as $role)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60">
                            <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                            {{ $role->name }}
                        </span>
                    @empty
                        <p class="text-xs text-slate-500">No roles currently assigned to this account.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
