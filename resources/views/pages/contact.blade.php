<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header
            title="Contact"
            description="Use this form for editorial correspondence. Do not upload or paste unpublished manuscripts here."
            eyebrow="Editorial office"
        />
    </x-slot:header>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-1">
            <div class="rounded-xl border border-slate-200/90 bg-white p-6 sm:p-7 shadow-xs">
                <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-800 ring-1 ring-brand-700/10">
                        <x-icon name="building-2" class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="font-serif text-lg font-semibold text-brand-950">Office details</h2>
                        <p class="text-xs text-slate-500">Editorial Secretariat</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4 text-sm text-slate-600">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Postal Address</span>
                        <p class="leading-relaxed">{{ $journal?->setting('contact_address') ?: 'Contact details will be published here once the journal office configures them.' }}</p>
                    </div>

                    @if ($journal?->setting('contact_email'))
                        <div class="border-t border-slate-100 pt-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Email Correspondence</span>
                            <a href="mailto:{{ $journal->setting('contact_email') }}" class="font-medium text-brand-800 hover:underline flex items-center gap-1.5">
                                <x-icon name="mail" class="h-3.5 w-3.5 text-slate-400" />
                                <span>{{ $journal->setting('contact_email') }}</span>
                            </a>
                        </div>
                    @endif

                    <div class="border-t border-slate-100 pt-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 block mb-1">Editorial Response</span>
                        <p class="text-xs text-slate-500">Inquiries are generally addressed within 2 business days during editorial office hours.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <div class="rounded-xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-xs">
                <h2 class="font-serif text-2xl font-semibold text-brand-950">Send an inquiry</h2>
                <p class="mt-1 text-sm text-slate-500">Complete the form below to reach the editorial staff.</p>

                <form
                    method="POST"
                    action="{{ route('contact.store') }}"
                    class="mt-6 space-y-5"
                    x-data
                    @submit.prevent="Swal.fire({
                        title: 'Send this message?',
                        text: 'Your message will be stored for the editorial office.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#1b3654',
                        confirmButtonText: 'Send'
                    }).then((result) => { if (result.isConfirmed) $el.submit() })"
                >
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input name="name" label="Your full name" value="{{ old('name') }}" required placeholder="e.g. Dr. Jane Smith" />
                        <x-form.input name="email" type="email" label="Email address" value="{{ old('email') }}" required placeholder="jane.smith@institution.edu" />
                    </div>
                    
                    <x-form.input name="subject" label="Subject" value="{{ old('subject') }}" required placeholder="e.g. Inquiry regarding special issue on..." />
                    
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="6"
                            required
                            placeholder="Write your detailed inquiry here..."
                            class="mt-1.5 block w-full rounded-lg border border-slate-300/90 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:border-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-700/20 leading-relaxed"
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
                                <x-icon name="alert-circle" class="h-3.5 w-3.5" />
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="pt-2">
                        <x-form.button>Send message</x-form.button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.Swal?.fire({
                    icon: 'success',
                    title: 'Message received',
                    text: @json(session('status')),
                    confirmButtonColor: '#1b3654',
                });
            });
        </script>
    @endif
</x-layouts.public>

