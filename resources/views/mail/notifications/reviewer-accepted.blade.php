<x-mail::message>
# Reviewer accepted an assignment

**{{ $article->submission_number }}** — {{ $article->title }}

{{ $assignment->reviewer->name }} accepted the invitation and can now download the manuscript from the reviewer portal.

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
