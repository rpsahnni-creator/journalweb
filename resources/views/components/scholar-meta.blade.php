@props([
    'article',
    'issue' => null,
    'journal' => null,
    'pdfFile' => null,
])

@foreach ($article->scholarMetaTags($issue, $journal, $pdfFile) as $tag)
    <meta name="{{ $tag['name'] }}" content="{{ $tag['content'] }}">
@endforeach
