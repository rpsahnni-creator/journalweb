<x-layouts.public title="Set a new password">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xl shadow-slate-200/40 ring-1 ring-slate-900/5 sm:p-8">
        <div class="mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-3">
                <x-icon name="key-round" class="h-3 w-3" />
                Security Credentials
            </span>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Set a new password</h1>
            <p class="mt-1.5 text-xs text-slate-600">Choose a secure password for your account. Passwords are encrypted with one-way hashing.</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form.input name="email" type="email" label="Email Address" value="{{ old('email', $request->email) }}" required />
            <x-form.input name="password" type="password" label="New Password" required autocomplete="new-password" placeholder="At least 8 characters" />
            <x-form.input name="password_confirmation" type="password" label="Confirm New Password" required autocomplete="new-password" placeholder="Repeat your new password" />
            <div class="pt-2">
                <x-form.button :full="true">Reset Password</x-form.button>
            </div>
        </form>
    </div>
</x-layouts.public>
