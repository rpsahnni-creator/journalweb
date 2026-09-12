<x-mail::message>
# Reviewer declined an assignment

**{{ $article->submission_number }}** — {{ $article->title }}

{{ $assignment->reviewer->name }} declined the invitation to review this manuscript.

@if (filled($assignment->response_note))
**Note from the reviewer**

{{ $assignment->response_note }}
@endif

You can invite another reviewer from the editorial office.

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
