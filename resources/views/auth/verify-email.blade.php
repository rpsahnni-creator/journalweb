<x-layouts.public title="Verify email">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xl shadow-slate-200/40 ring-1 ring-slate-900/5 sm:p-8">
        <div class="mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-3">
                <x-icon name="mail-check" class="h-3 w-3" />
                Email Verification
            </span>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Verify your email</h1>
            <p class="mt-1.5 text-xs text-slate-600">
                A verification link has been sent to your email address. Please click the link to confirm your affiliation and activate submission privileges.
            </p>
            <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2.5 text-xs leading-relaxed text-slate-600 ring-1 ring-slate-200/80">
                Once your email is verified you can submit a manuscript. Review the
                <a href="{{ route('author-guidelines') }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline">Author Guidelines</a>
                before sending your work.
            </p>
        </div>

        @if (session('status'))
            <x-alert class="mb-4">{{ session('status') }}</x-alert>
        @endif

        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-form.button :full="true">Resend verification email</x-form.button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-slate-800 hover:underline cursor-pointer">
                    Sign out and return later
                </button>
            </form>
        </div>
    </div>
</x-layouts.public>
