{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ $journal->name }}</title>
    <subtitle>Latest published articles</subtitle>
    <link href="{{ url('/feed') }}" rel="self" type="application/atom+xml"/>
    <link href="{{ url('/') }}" rel="alternate" type="text/html"/>
    <id>{{ url('/') }}</id>
    <updated>{{ $lastUpdated->toAtomString() }}</updated>
    <generator>SRT Journal System</generator>
    @if (filled($journal->publisher))
        <author>
            <name>{{ e($journal->publisher) }}</name>
        </author>
    @endif
    @foreach ($articles as $article)
        <entry>
            <title>{{ e($article->title) }}</title>
            <link href="{{ $article->publicUrl() }}" rel="alternate" type="text/html"/>
            <id>{{ $article->publicUrl() }}</id>
            <published>{{ $article->published_at->toAtomString() }}</published>
            <updated>{{ ($article->updated_at ?? $article->published_at)->toAtomString() }}</updated>
            @foreach ($article->authors->sortBy('sequence') as $author)
                <author>
                    <name>{{ e($author->name) }}</name>
                </author>
            @endforeach
            @if (filled($article->abstract))
                <summary type="html">{{ e($article->abstract) }}</summary>
            @endif
            @if (($article->keywords ?? []) !== [])
                @foreach ($article->keywords as $keyword)
                    <category term="{{ e($keyword) }}"/>
                @endforeach
            @endif
            @if (filled($article->doi))
                <link href="https://doi.org/{{ e($article->doi) }}" rel="related" type="text/html"/>
            @endif
        </entry>
    @endforeach
</feed>
