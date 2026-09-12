<x-oai.layout :verb="$verb" :request-url="$requestUrl" :query="$query ?? []">
    <Identify>
        <repositoryName>{{ e($repositoryName) }}</repositoryName>
        <baseURL>{{ e($baseUrl) }}</baseURL>
        <protocolVersion>2.0</protocolVersion>
        <adminEmail>{{ e($adminEmail) }}</adminEmail>
        <earliestDatestamp>{{ e($earliestDatestamp) }}</earliestDatestamp>
        <deletedRecord>no</deletedRecord>
        <granularity>YYYY-MM-DD</granularity>
    </Identify>
</x-oai.layout>
