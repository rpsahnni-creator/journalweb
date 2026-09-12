@props([
    'class' => '',
])

<x-empty-state
    :class="$class"
    title="Vol. 1, No. 1 — Forthcoming (June 2026)"
    description="SRT Journal of Multidisciplinary Research is currently accepting submissions for its inaugural issue. We welcome original, unpublished research articles in Humanities, Social Sciences, and Natural Sciences from scholars, teachers, and researchers."
    icon="megaphone"
>
    <a href="{{ route('author-guidelines') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-800">
        Submit Your Article
        <x-icon name="arrow-right" class="h-4 w-4" />
    </a>
</x-empty-state>
