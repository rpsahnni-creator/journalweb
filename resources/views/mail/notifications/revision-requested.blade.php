<x-mail::message>
# Revision requested: {{ $decision->decision->label() }}

**{{ $article->submission_number }}** — {{ $article->title }}

@if ($decision->revision_due_at)
Please submit your revision by **{{ $decision->revision_due_at->toFormattedDateString() }}**.
@endif

**Decision letter**

{{ $decision->comments_to_author }}

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Unpublished files remain private and are not attached.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
