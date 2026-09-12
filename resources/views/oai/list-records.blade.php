<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <ListRecords>
        @forelse ($articles as $article)
            <record>
                @include('oai.record', ['article' => $article, 'identifiersOnly' => false])
            </record>
        @empty
        @endforelse
        @if ($resumptionToken)
            <resumptionToken cursor="{{ $cursor }}" completeListSize="{{ $completeListSize }}">{{ e($resumptionToken) }}</resumptionToken>
        @endif
    </ListRecords>
</x-oai.layout>
