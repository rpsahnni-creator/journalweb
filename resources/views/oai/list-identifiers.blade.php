<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <ListIdentifiers>
        @foreach ($articles as $article)
            @include('oai.record', ['article' => $article, 'identifiersOnly' => true])
        @endforeach
        @if ($resumptionToken)
            <resumptionToken cursor="{{ $cursor }}" completeListSize="{{ $completeListSize }}">{{ e($resumptionToken) }}</resumptionToken>
        @endif
    </ListIdentifiers>
</x-oai.layout>
