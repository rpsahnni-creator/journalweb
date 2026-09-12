<x-mail::message>
# Revision submitted

**{{ $article->submission_number }}** — {{ $article->title }}

The corresponding author submitted revision version {{ $revision->version }}.

@if (filled($revision->author_response))
**Author response**

{{ \Illuminate\Support\Str::limit($revision->author_response, 500) }}
@endif

<x-mail::button :url="$actionUrl">
Review revision
</x-mail::button>

Unpublished manuscript files remain private. Download them only from the editorial office.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
