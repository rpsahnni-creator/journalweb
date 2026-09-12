<x-layouts.public title="Register">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-8 shadow-xl shadow-slate-200/40 ring-1 ring-slate-900/5">
        <div class="mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-3">
                <x-icon name="user-plus" class="h-3 w-3" />
                Academic Registration
            </span>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Create an account</h1>
            <p class="mt-1.5 text-xs text-slate-600">Register as an author or reader. Email verification is required before manuscript submission.</p>
            <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2.5 text-xs leading-relaxed text-slate-600 ring-1 ring-slate-200/80">
                After you register, you can submit a manuscript through the author portal. Please read the
                <a href="{{ route('author-guidelines') }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline">Author Guidelines</a>
                first.
            </p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <x-form.input name="name" label="Full name" value="{{ old('name') }}" required autocomplete="name" placeholder="Dr. Jane Doe" />
            <x-form.input name="email" type="email" label="Institutional Email" value="{{ old('email') }}" required autocomplete="username" placeholder="j.doe@university.edu" />
            <x-form.input name="affiliation" label="Primary Affiliation (optional)" value="{{ old('affiliation') }}" autocomplete="organization" placeholder="Department of Computer Science, University of Oxford" />
            <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" placeholder="At least 8 characters" />
            <x-form.input name="password_confirmation" type="password" label="Confirm Password" required autocomplete="new-password" placeholder="Repeat your password" />

            <div class="pt-2">
                <x-form.button :full="true">Register Account</x-form.button>
            </div>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
            <p>
                Already have a journal account?
                <a href="{{ route('login') }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline ml-1">Sign in here</a>
            </p>
        </div>
    </div>
</x-layouts.public>
