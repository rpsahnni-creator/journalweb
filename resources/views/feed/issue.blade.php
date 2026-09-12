{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ e($journal->name) }} — {{ e($issue->displayLabel()) }}</title>
    <subtitle>Published articles in this issue</subtitle>
    <link href="{{ url('/feed/'.$issue->volume?->number.'/'.$issue->number) }}" rel="self" type="application/atom+xml"/>
    <link href="{{ $issue->publicUrl() }}" rel="alternate" type="text/html"/>
    <id>{{ $issue->publicUrl() }}</id>
    <updated>{{ $lastUpdated->toAtomString() }}</updated>
    @foreach ($articles as $article)
        <entry>
            <title>{{ e($article->title) }}</title>
            <link href="{{ $article->publicUrl() }}" rel="alternate" type="text/html"/>
            <id>{{ $article->publicUrl() }}</id>
            <published>{{ $article->published_at?->toAtomString() }}</published>
            <updated>{{ ($article->updated_at ?? $article->published_at)?->toAtomString() }}</updated>
            @foreach ($article->authors as $author)
                <author><name>{{ e($author->name) }}</name></author>
            @endforeach
            @if (filled($article->abstract))
                <summary type="html">{{ e($article->abstract) }}</summary>
            @endif
        </entry>
    @endforeach
</feed>
