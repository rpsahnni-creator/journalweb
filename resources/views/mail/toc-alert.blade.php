<x-mail::message>
# {{ $issue->displayLabel() }} is now available

@if ($issue->title)
**{{ $issue->title }}**
@endif

The latest issue of {{ config('app.name') }} has been published. Published articles only are listed below.

@foreach ($articles as $article)
- [{{ $article->title }}]({{ $article->publicUrl() }})@if ($article->authors->isNotEmpty()) — {{ $article->authors->pluck('name')->join(', ') }}@endif

@endforeach

<x-mail::button :url="$issue->publicUrl()">
Read the issue
</x-mail::button>

You can [unsubscribe from issue alerts]({{ $unsubscribeUrl }}) at any time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
