<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\CurrentJournal;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CitationExportController extends Controller
{
    public function bibtex(string $article): StreamedResponse
    {
        $record = $this->findArticle($article);
        $record->loadMissing(['authors', 'issues.volume']);

        $issue = $record->publishedIssue();
        $journal = $record->journal ?? CurrentJournal::get();
        $key = 'srtjmr_'.($record->published_at?->year ?? 'nd').'_'.str($record->title)->slug('_')->limit(30, '')->toString();

        $authors = $record->authors->sortBy('sequence')->pluck('name')->join(' and ');

        $lines = [
            "@article{{$key},",
            "  title = {".addcslashes($record->title, '{}')."},",
        ];

        if ($authors !== '') {
            $lines[] = "  author = {{$authors}},";
        }

        $lines[] = '  journal = {'.addcslashes($journal?->name ?? 'SRT Journal of Multidisciplinary Research', '{}').'},';

        if ($record->published_at) {
            $lines[] = '  year = {'.$record->published_at->year.'},';
            $lines[] = '  month = {'.$record->published_at->format('M').'},';
        }

        if ($issue?->volumeNumber() !== null) {
            $lines[] = '  volume = {'.$issue->volumeNumber().'},';
        }

        if ($issue?->issueNumber() !== null) {
            $lines[] = '  number = {'.$issue->issueNumber().'},';
        }

        if ($record->page_start !== null) {
            $pages = $record->page_end !== null
                ? "{$record->page_start}--{$record->page_end}"
                : (string) $record->page_start;
            $lines[] = "  pages = {{$pages}},";
        }

        if (filled($record->doi)) {
            $lines[] = "  doi = {{$record->doi}},";
        }

        $lines[] = '  url = {'.$record->publicUrl().'},';

        if (filled($record->abstract)) {
            $lines[] = '  abstract = {'.addcslashes(str($record->abstract)->limit(500, '...')->toString(), '{}').'},';
        }

        if (($record->keywords ?? []) !== []) {
            $lines[] = '  keywords = {'.implode(', ', $record->keywords).'},';
        }

        $lines[] = '}';

        $content = implode("\n", $lines);
        $filename = $key.'.bib';

        return $this->downloadResponse($content, $filename, 'application/x-bibtex');
    }

    public function ris(string $article): StreamedResponse
    {
        $record = $this->findArticle($article);
        $record->loadMissing(['authors', 'issues.volume']);

        $issue = $record->publishedIssue();
        $journal = $record->journal ?? CurrentJournal::get();

        $lines = [
            'TY  - JOUR',
            'TI  - '.$record->title,
        ];

        foreach ($record->authors->sortBy('sequence') as $author) {
            $lines[] = 'AU  - '.$author->name;
        }

        $lines[] = 'T2  - '.($journal?->name ?? 'SRT Journal of Multidisciplinary Research');

        if ($record->published_at) {
            $lines[] = 'PY  - '.$record->published_at->format('Y');
            $lines[] = 'DA  - '.$record->published_at->format('Y/m/d');
        }

        if ($issue?->volumeNumber() !== null) {
            $lines[] = 'VL  - '.$issue->volumeNumber();
        }

        if ($issue?->issueNumber() !== null) {
            $lines[] = 'IS  - '.$issue->issueNumber();
        }

        if ($record->page_start !== null) {
            $lines[] = 'SP  - '.$record->page_start;
        }

        if ($record->page_end !== null) {
            $lines[] = 'EP  - '.$record->page_end;
        }

        if (filled($record->doi)) {
            $lines[] = 'DO  - '.$record->doi;
        }

        $lines[] = 'UR  - '.$record->publicUrl();

        if (filled($record->abstract)) {
            $lines[] = 'AB  - '.$record->abstract;
        }

        if (($record->keywords ?? []) !== []) {
            foreach ($record->keywords as $keyword) {
                $lines[] = 'KW  - '.$keyword;
            }
        }

        $issn = $journal?->citationIssn();
        if (filled($issn)) {
            $lines[] = 'SN  - '.$issn;
        }

        $lines[] = 'ER  - ';

        $content = implode("\r\n", $lines);
        $filename = str($record->title)->slug()->limit(50, '')->toString().'.ris';

        return $this->downloadResponse($content, $filename, 'application/x-research-info-systems');
    }

    public function apa(string $article): StreamedResponse
    {
        $record = $this->findArticle($article);

        return $this->downloadResponse($record->citation(), str($record->title)->slug()->limit(50, '')->toString().'-apa.txt', 'text/plain');
    }

    public function mla(string $article): StreamedResponse
    {
        $record = $this->findArticle($article);

        return $this->downloadResponse($record->citationMla(), str($record->title)->slug()->limit(50, '')->toString().'-mla.txt', 'text/plain');
    }

    public function chicago(string $article): StreamedResponse
    {
        $record = $this->findArticle($article);

        return $this->downloadResponse($record->citationChicago(), str($record->title)->slug()->limit(50, '')->toString().'-chicago.txt', 'text/plain');
    }

    private function findArticle(string $slug): Article
    {
        $journal = CurrentJournal::get();

        abort_if($journal === null, 404);

        return Article::query()
            ->publiclyListed()
            ->where('journal_id', $journal->id)
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function downloadResponse(string $content, string $filename, string $mimeType): StreamedResponse
    {
        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, $filename, [
            'Content-Type' => $mimeType.'; charset=UTF-8',
        ]);
    }
}
