<x-mail::message>
# Editorial decision: not accepted

**{{ $article->submission_number }}** — {{ $article->title }}

The editorial office has declined this manuscript.

**Decision letter**

{{ $decision->comments_to_author }}

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Unpublished files remain private and are not attached.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
