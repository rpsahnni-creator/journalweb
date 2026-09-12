<x-mail::message>
# {{ $forAuthor ? 'We received your manuscript' : 'New manuscript submitted' }}

**{{ $article->submission_number }}** — {{ $article->title }}

@if ($forAuthor)
Thank you for submitting your work. The editorial office will screen the manuscript. Unpublished files remain private.
@else
A new manuscript is ready for editorial screening. Unpublished files remain private and are not attached to this message.
@endif

<x-mail::button :url="$actionUrl">
{{ $forAuthor ? 'Open your manuscript' : 'Open in the editorial office' }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
