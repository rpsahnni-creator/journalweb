@props([
    'background' => 'bg-slate-50',
])

<section {{ $attributes->class($background) }}>
    <x-public-container class="py-10 sm:py-16">
        <div class="text-center">
            <h2 class="font-serif text-2xl font-bold tracking-tight text-brand-950 sm:text-3xl lg:text-4xl">{{ __('ui.areas') }}</h2>
            <p class="mt-3 text-sm text-slate-500 sm:text-base">
                {{ __('ui.areas_intro') }}
            </p>
        </div>

        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <x-feature-card icon="book-open" :title="__('ui.humanities')">
                {{ __('ui.humanities_blurb') }}
            </x-feature-card>
            <x-feature-card icon="users" :title="__('ui.social_sciences')">
                {{ __('ui.social_sciences_blurb') }}
            </x-feature-card>
            <x-feature-card icon="leaf" :title="__('ui.natural_sciences')">
                {{ __('ui.natural_sciences_blurb') }}
            </x-feature-card>
            <x-feature-card icon="graduation-cap" :title="__('ui.education')">
                {{ __('ui.education_blurb') }}
            </x-feature-card>
            <x-feature-card icon="globe" :title="__('ui.society')">
                {{ __('ui.society_blurb') }}
            </x-feature-card>
            <x-feature-card icon="share-2" :title="__('ui.interdisciplinary')">
                {{ __('ui.interdisciplinary_blurb') }}
            </x-feature-card>
        </div>
    </x-public-container>
</section>
