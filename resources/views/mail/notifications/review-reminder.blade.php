<x-mail::message>
# Review reminder

**{{ $article->submission_number }}** — {{ $article->title }}

This is a reminder that your review
@if ($assignment->due_at)
is due by **{{ $assignment->due_at->toFormattedDateString() }}**.
@else
is still outstanding.
@endif

Manuscript files are not attached to this message.

<x-mail::button :url="$actionUrl">
Open assignment
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
