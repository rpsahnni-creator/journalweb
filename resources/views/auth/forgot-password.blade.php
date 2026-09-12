<x-layouts.public title="Forgot password">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xl shadow-slate-200/40 ring-1 ring-slate-900/5 sm:p-8">
        <div class="mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-3">
                <x-icon name="key-round" class="h-3 w-3" />
                Account Recovery
            </span>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Reset password</h1>
            <p class="mt-1.5 text-xs text-slate-600">Enter your registered email address and we will dispatch a secure password reset link.</p>
        </div>

        @if (session('status'))
            <x-alert class="mb-4">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <x-form.input name="email" type="email" label="Institutional Email" value="{{ old('email') }}" required placeholder="name@institution.edu" />
            <div class="pt-2">
                <x-form.button :full="true">Send password reset link</x-form.button>
            </div>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
            <p>
                Remembered your credentials?
                <a href="{{ route('login') }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline ml-1">Back to login</a>
            </p>
        </div>
    </div>
</x-layouts.public>
