<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <error code="{{ e($error) }}">{{ e($message) }}</error>
</x-oai.layout>
