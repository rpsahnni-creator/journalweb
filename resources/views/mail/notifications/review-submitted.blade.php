<x-mail::message>
# Review submitted

**{{ $article->submission_number }}** — {{ $article->title }}

{{ $assignment->reviewer->name }} submitted a review. Recommendation: **{{ $review->recommendation->label() }}**.

Confidential comments to the editor are available only in the editorial office.

<x-mail::button :url="$actionUrl">
Open manuscript
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
