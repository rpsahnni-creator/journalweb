<x-layouts.public title="Log in">
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xl shadow-slate-200/40 ring-1 ring-slate-900/5 sm:p-8">
        <div class="mb-6">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-800 ring-1 ring-brand-200/60 mb-3">
                <x-icon name="lock" class="h-3 w-3" />
                Journal Access Portal
            </span>
            <h1 class="font-serif text-2xl font-bold text-slate-900">Sign in to your account</h1>
            <p class="mt-1.5 text-xs text-slate-600">Access your author submissions, editorial queue, or reviewer dashboard.</p>
        </div>

        @if (session('status'))
            <x-alert class="mb-4">{{ session('status') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <x-form.input name="email" type="email" label="Email Address" value="{{ old('email') }}" required autocomplete="username" placeholder="name@institution.edu" />
            
            <div>
                <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" placeholder="••••••••" />
                <div class="mt-1 text-right">
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-brand-800 hover:text-brand-900 hover:underline">
                        Forgot password?
                    </a>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-brand-900 focus:ring-brand-500/20">
                <span>Remember me on this workstation</span>
            </label>

            <div class="pt-2">
                <x-form.button :full="true">Sign in</x-form.button>
            </div>
        </form>

        <div class="mt-6 border-t border-slate-100 pt-5 text-center text-xs text-slate-600">
            <p>
                Don't have an author or reviewer account?
                <a href="{{ route('register') }}" class="font-semibold text-brand-800 hover:text-brand-900 hover:underline ml-1">Create an account</a>
            </p>
        </div>
    </div>
</x-layouts.public>
