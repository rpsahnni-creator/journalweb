{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>{{ $journal->name }} — Issues</title>
    <subtitle>Published issues</subtitle>
    <link href="{{ url('/feed/issues') }}" rel="self" type="application/atom+xml"/>
    <link href="{{ route('issues.index') }}" rel="alternate" type="text/html"/>
    <id>{{ route('issues.index') }}</id>
    <updated>{{ $lastUpdated->toAtomString() }}</updated>
    <generator>SRT Journal System</generator>
    @foreach ($issues as $issue)
        <entry>
            <title>{{ e($issue->displayLabel()) }}{{ $issue->title ? ' — '.e($issue->title) : '' }}</title>
            <link href="{{ $issue->publicUrl() }}" rel="alternate" type="text/html"/>
            <id>{{ $issue->publicUrl() }}</id>
            <published>{{ $issue->published_at->toAtomString() }}</published>
            <updated>{{ ($issue->updated_at ?? $issue->published_at)->toAtomString() }}</updated>
            @if ($issue->description)
                <summary type="html">{{ e($issue->description) }}</summary>
            @endif
        </entry>
    @endforeach
</feed>
