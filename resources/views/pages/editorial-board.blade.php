<x-layouts.public :title="$title" :meta-description="$metaDescription">
    <x-slot:header>
        <x-page-header
            title="Editorial Board"
            description="Only members marked public are listed. This page does not use placeholder production editors."
            eyebrow="People"
        />
    </x-slot:header>

        @if ($members->isEmpty())
            <x-empty-state
                title="No public editorial board listings"
                description="Names will appear here after they are added, reviewed, and marked public by the journal office."
                icon="users"
            />
        @else
            <div class="space-y-12">
                @foreach ($members as $roleTitle => $group)
                    <section>
                        <div class="flex items-center gap-3 border-b border-slate-200/80 pb-3">
                            <span class="h-5 w-1 rounded-full bg-brand-800"></span>
                            <h2 class="font-serif text-2xl font-semibold text-brand-950">{{ $roleTitle }}</h2>
                        </div>
                        <div class="mt-6 grid gap-6 md:grid-cols-2">
                            @foreach ($group as $member)
                                @php
                                    $initials = collect(explode(' ', $member->name))->map(fn($part) => strtoupper(substr($part, 0, 1)))->take(2)->join('');
                                @endphp
                                <article class="card-hover-lift flex items-start gap-4 rounded-xl border border-slate-200/90 bg-white p-6 shadow-xs">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-900 to-brand-800 text-white font-serif font-bold text-sm shadow-xs ring-1 ring-black/5">
                                        {{ $initials }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-serif text-lg font-semibold text-brand-950 leading-snug">{{ $member->name }}</h3>
                                        @if ($member->department)
                                            <p class="mt-1 text-sm text-slate-600">{{ $member->department }}</p>
                                        @endif
                                        @if ($member->affiliation)
                                            <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-600">
                                                <x-icon name="building-2" class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                                <span>{{ $member->affiliation }}</span>
                                            </p>
                                        @endif
                                        @if ($member->official_address)
                                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $member->official_address }}</p>
                                        @endif
                                        @if ($member->country)
                                            <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500">
                                                <x-icon name="globe" class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                                <span>{{ $member->country }}</span>
                                            </p>
                                        @endif
                                        @if ($member->email && ! str_starts_with($member->email, '['))
                                            <p class="mt-1 text-sm">
                                                <a href="mailto:{{ $member->email }}" class="text-brand-800 hover:underline">{{ $member->email }}</a>
                                            </p>
                                        @endif
                                        @if ($member->bio)
                                            <p class="mt-3 text-sm leading-relaxed text-slate-600 border-t border-slate-100 pt-3">{{ $member->bio }}</p>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
</x-layouts.public>

