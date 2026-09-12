<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <ListSets>
        <set>
            <setSpec>srtjmr:articles</setSpec>
            <setName>Published articles</setName>
        </set>
    </ListSets>
</x-oai.layout>
