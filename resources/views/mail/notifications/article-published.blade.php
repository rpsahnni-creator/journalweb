<x-mail::message>
# Your article is published

**{{ $article->title }}**

The article is now listed on the public site. Each published article has its own webpage.

@if ($article->published_at)
Publication date: **{{ $article->published_at->toFormattedDateString() }}**
@endif

<x-mail::button :url="$actionUrl">
View article
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
