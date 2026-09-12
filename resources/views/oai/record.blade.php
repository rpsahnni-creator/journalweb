@php
    $identifier = app(\App\Http\Controllers\OaiPmhController::class)->identifierFor($article);
    $datestamp = $article->published_at?->toDateString() ?? now()->toDateString();
@endphp
<header>
    <identifier>{{ e($identifier) }}</identifier>
    <datestamp>{{ e($datestamp) }}</datestamp>
    <setSpec>srtjmr:articles</setSpec>
</header>
@unless ($identifiersOnly ?? false)
<metadata>
    <oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
               xmlns:dc="http://purl.org/dc/elements/1.1/"
               xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
        <dc:title>{{ e($article->title) }}</dc:title>
        @foreach ($article->authors as $author)
            <dc:creator>{{ e($author->name) }}</dc:creator>
        @endforeach
        @if (filled($article->abstract))
            <dc:description>{{ e($article->abstract) }}</dc:description>
        @endif
        <dc:publisher>{{ e($article->journal?->publisher ?: \App\Support\JournalCopy::PUBLISHER_NAME) }}</dc:publisher>
        <dc:date>{{ e($datestamp) }}</dc:date>
        <dc:type>Text</dc:type>
        <dc:format>text/html</dc:format>
        <dc:identifier>{{ e($article->publicUrl()) }}</dc:identifier>
        @if (filled($article->doi))
            <dc:identifier>https://doi.org/{{ e($article->doi) }}</dc:identifier>
        @endif
        <dc:language>{{ e($article->language ?: 'en') }}</dc:language>
        <dc:rights>Diamond Open Access</dc:rights>
        @foreach ($article->keywords ?? [] as $keyword)
            <dc:subject>{{ e($keyword) }}</dc:subject>
        @endforeach
    </oai_dc:dc>
</metadata>
@endunless
