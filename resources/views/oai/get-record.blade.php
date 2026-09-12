<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <GetRecord>
        <record>
            @include('oai.record', ['article' => $article, 'identifiersOnly' => false])
        </record>
    </GetRecord>
</x-oai.layout>
