<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DoiService
{
    public function hasValidPrefix(): bool
    {
        $prefix = (string) config('services.crossref.prefix', '');

        return (bool) preg_match('/^10\.\d{4,5}$/', $prefix);
    }

    public function canDeposit(): bool
    {
        return $this->hasValidPrefix()
            && filled(config('services.crossref.username'))
            && filled(config('services.crossref.password'));
    }

    public function proposedIdentifier(Article $article): ?string
    {
        if (! $this->hasValidPrefix()) {
            return null;
        }

        $article->loadMissing(['issues.volume', 'issue.volume']);
        $issue = $article->publishedIssue() ?? $article->issue;
        $year = $article->published_at?->format('Y')
            ?: $issue?->published_at?->format('Y')
            ?: now()->format('Y');
        $volume = $issue?->volumeNumber() ?? $issue?->volume?->number ?? 0;
        $issueNumber = $issue?->issueNumber() ?? $issue?->number ?? 0;
        $articleNumber = $article->articleNumber() ?: (string) $article->id;
        $articleNumber = preg_replace('/[^A-Za-z0-9]+/', '', (string) $articleNumber) ?: (string) $article->id;

        return config('services.crossref.prefix').'/srtjmr.'.$year.'.'.$volume.'.'.$issueNumber.'.'.$articleNumber;
    }

    public function assignIfConfigured(Article $article): ?string
    {
        if (filled($article->doi)) {
            return $article->doi;
        }

        $doi = $this->proposedIdentifier($article);

        if ($doi === null || ! $article->isPubliclyVisible()) {
            return null;
        }

        $article->forceFill(['doi' => $doi])->save();

        if ($this->canDeposit()) {
            $this->deposit($article->fresh() ?? $article);
        }

        return $doi;
    }

    public function crossrefDepositXml(Article $article): string
    {
        $article->loadMissing(['authors', 'journal', 'issues.volume', 'issue.volume']);
        $issue = $article->publishedIssue() ?? $article->issue;
        $journal = $article->journal;
        $doi = $article->doi ?: $this->proposedIdentifier($article);
        $year = $article->published_at?->format('Y') ?: now()->format('Y');
        $month = $article->published_at?->format('m');

        $contributors = '';
        foreach ($article->authors->sortBy('sequence')->values() as $index => $author) {
            $sequence = $index === 0 ? 'first' : 'additional';
            $parts = preg_split('/\s+/', trim((string) $author->name)) ?: ['Author'];
            $given = e(implode(' ', array_slice($parts, 0, -1)) ?: $parts[0]);
            $surname = e($parts[array_key_last($parts)]);
            $contributors .= <<<XML
        <person_name sequence="{$sequence}" contributor_role="author">
          <given_name>{$given}</given_name>
          <surname>{$surname}</surname>
        </person_name>

XML;
        }

        $issn = e((string) ($journal?->citationIssn() ?: ''));
        $issnXml = $issn !== '' ? "      <issn media_type=\"electronic\">{$issn}</issn>\n" : '';
        $volume = e((string) ($issue?->volumeNumber() ?? $issue?->volume?->number ?? ''));
        $issueNumber = e((string) ($issue?->issueNumber() ?? $issue?->number ?? ''));
        $title = e((string) $article->title);
        $journalTitle = e((string) ($journal?->name ?: 'SRT Journal of Multidisciplinary Research'));
        $depositor = e((string) config('services.crossref.depositor_name'));
        $email = e((string) config('services.crossref.depositor_email'));
        $timestamp = now()->format('YmdHis');
        $doiXml = e((string) $doi);
        $url = e($article->publicUrl());
        $monthXml = $month ? "          <month>{$month}</month>\n" : '';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<doi_batch version="4.4.2" xmlns="http://www.crossref.org/schema/4.4.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.crossref.org/schema/4.4.2 https://www.crossref.org/schemas/crossref4.4.2.xsd">
  <head>
    <doi_batch_id>srtjmr-{$article->id}-{$timestamp}</doi_batch_id>
    <timestamp>{$timestamp}</timestamp>
    <depositor>
      <depositor_name>{$depositor}</depositor_name>
      <email_address>{$email}</email_address>
    </depositor>
    <registrant>{$depositor}</registrant>
  </head>
  <body>
    <journal>
      <journal_metadata>
        <full_title>{$journalTitle}</full_title>
{$issnXml}      </journal_metadata>
      <journal_issue>
        <publication_date media_type="online">
{$monthXml}          <year>{$year}</year>
        </publication_date>
        <journal_volume><volume>{$volume}</volume></journal_volume>
        <issue>{$issueNumber}</issue>
      </journal_issue>
      <journal_article publication_type="full_text">
        <titles><title>{$title}</title></titles>
        <contributors>
{$contributors}        </contributors>
        <publication_date media_type="online">
{$monthXml}          <year>{$year}</year>
        </publication_date>
        <doi_data>
          <doi>{$doiXml}</doi>
          <resource>{$url}</resource>
        </doi_data>
      </journal_article>
    </journal>
  </body>
</doi_batch>

XML;
    }

    public function deposit(Article $article): bool
    {
        if (! $this->canDeposit() || blank($article->doi)) {
            return false;
        }

        $response = Http::timeout(20)
            ->withBasicAuth(
                (string) config('services.crossref.username'),
                (string) config('services.crossref.password'),
            )
            ->attach('fname', $this->crossrefDepositXml($article), 'srtjmr-'.$article->id.'.xml')
            ->post((string) config('services.crossref.deposit_url'), [
                'operation' => 'doMDUpload',
            ]);

        if (! $response->successful()) {
            Log::warning('CrossRef DOI deposit failed.', [
                'article_id' => $article->id,
                'doi' => $article->doi,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
