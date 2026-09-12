<x-mail::message>
# Manuscript accepted

**{{ $article->submission_number }}** — {{ $article->title }}

The editorial office has accepted your manuscript. It will appear on the public site after it is placed in an issue and published.

**Decision letter**

{{ $decision->comments_to_author }}

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
