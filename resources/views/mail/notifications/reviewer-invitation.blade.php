<x-mail::message>
# Review invitation

You are invited to review **{{ $article->submission_number }}** — {{ $article->title }}.

@if ($assignment->due_at)
Please respond and, if you accept, submit your review by **{{ $assignment->due_at->toFormattedDateString() }}**.
@endif

Manuscript files are not attached. If you accept, you can download them from the reviewer portal.

<x-mail::button :url="$actionUrl">
Respond to invitation
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
