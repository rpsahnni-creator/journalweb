<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\CurrentJournal;
use App\Support\JournalCopy;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class OaiPmhController extends Controller
{
    private const PAGE_SIZE = 50;

    public function __invoke(Request $request): Response
    {
        $verb = (string) $request->query('verb', '');

        $payload = match ($verb) {
            'Identify' => $this->identify(),
            'ListMetadataFormats' => $this->listMetadataFormats($request),
            'ListSets' => ['view' => 'oai.list-sets', 'data' => []],
            'ListIdentifiers' => $this->listRecords($request, identifiersOnly: true),
            'ListRecords' => $this->listRecords($request, identifiersOnly: false),
            'GetRecord' => $this->getRecord($request),
            default => $this->error('badVerb', 'The verb argument is missing or illegal.'),
        };

        if (isset($payload['error'])) {
            return $this->xml('oai.error', array_merge($payload, [
                'verb' => $verb !== '' ? $verb : null,
                'requestUrl' => $this->baseUrl(),
                'query' => $request->query(),
            ]));
        }

        return $this->xml($payload['view'], array_merge($payload['data'], [
            'verb' => $verb,
            'requestUrl' => $this->baseUrl(),
            'query' => $request->query(),
        ]));
    }

    /**
     * @return array{view: string, data: array<string, mixed>}
     */
    private function identify(): array
    {
        $journal = CurrentJournal::get();
        $earliest = Article::query()
            ->publiclyListed()
            ->whereNotNull('published_at')
            ->orderBy('published_at')
            ->value('published_at');

        return [
            'view' => 'oai.identify',
            'data' => [
                'repositoryName' => $journal?->name ?: config('app.name'),
                'baseUrl' => $this->baseUrl(),
                'adminEmail' => $journal?->setting('publisher_email') ?: JournalCopy::PUBLISHER_EMAIL,
                'earliestDatestamp' => $earliest
                    ? Carbon::parse($earliest)->toDateString()
                    : now()->toDateString(),
            ],
        ];
    }

    /**
     * @return array{view: string, data: array<string, mixed>}|array{error: string, message: string}
     */
    private function listMetadataFormats(Request $request): array
    {
        if ($request->filled('identifier')) {
            $article = $this->findByIdentifier((string) $request->query('identifier'));
            if ($article === null) {
                return $this->error('idDoesNotExist', 'The identifier does not exist or is not public.');
            }
        }

        return ['view' => 'oai.list-metadata-formats', 'data' => []];
    }

    /**
     * @return array{view: string, data: array<string, mixed>}|array{error: string, message: string}
     */
    private function listRecords(Request $request, bool $identifiersOnly): array
    {
        $token = $this->decodeToken($request->query('resumptionToken'));
        $metadataPrefix = $token['metadataPrefix'] ?? $request->query('metadataPrefix');
        $from = $token['from'] ?? $request->query('from');
        $until = $token['until'] ?? $request->query('until');
        $offset = (int) ($token['offset'] ?? 0);

        if ($request->filled('resumptionToken') && $token === null) {
            return $this->error('badResumptionToken', 'The resumption token is invalid.');
        }

        if ($metadataPrefix !== 'oai_dc') {
            return $this->error('cannotDisseminateFormat', 'This repository only supports oai_dc.');
        }

        $query = Article::query()
            ->publiclyListed()
            ->whereNotNull('published_at')
            ->with(['authors', 'journal', 'issues.volume'])
            ->orderBy('published_at')
            ->orderBy('id');

        if (is_string($from) && $from !== '') {
            $query->whereDate('published_at', '>=', $from);
        }
        if (is_string($until) && $until !== '') {
            $query->whereDate('published_at', '<=', $until);
        }

        $total = (clone $query)->count();
        $articles = $query->skip($offset)->take(self::PAGE_SIZE)->get();

        $nextOffset = $offset + $articles->count();
        $resumption = $nextOffset < $total
            ? $this->encodeToken([
                'metadataPrefix' => 'oai_dc',
                'from' => $from,
                'until' => $until,
                'offset' => $nextOffset,
            ])
            : null;

        return [
            'view' => $identifiersOnly ? 'oai.list-identifiers' : 'oai.list-records',
            'data' => [
                'articles' => $articles,
                'resumptionToken' => $resumption,
                'cursor' => $offset,
                'completeListSize' => $total,
            ],
        ];
    }

    /**
     * @return array{view: string, data: array<string, mixed>}|array{error: string, message: string}
     */
    private function getRecord(Request $request): array
    {
        if ($request->query('metadataPrefix') !== 'oai_dc') {
            return $this->error('cannotDisseminateFormat', 'This repository only supports oai_dc.');
        }

        $article = $this->findByIdentifier((string) $request->query('identifier', ''));

        if ($article === null) {
            return $this->error('idDoesNotExist', 'The identifier does not exist or is not public.');
        }

        return [
            'view' => 'oai.get-record',
            'data' => ['article' => $article],
        ];
    }

    public function identifierFor(Article $article): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return 'oai:'.$host.':article/'.$article->id;
    }

    private function findByIdentifier(string $identifier): ?Article
    {
        if (! preg_match('/:article\/(\d+)$/', $identifier, $matches)) {
            return null;
        }

        return Article::query()
            ->publiclyListed()
            ->whereKey((int) $matches[1])
            ->with(['authors', 'journal', 'issues.volume'])
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function encodeToken(?array $payload): ?string
    {
        if ($payload === null) {
            return null;
        }

        return rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeToken(mixed $token): ?array
    {
        if (! is_string($token) || $token === '') {
            return null;
        }

        $decoded = json_decode((string) base64_decode(strtr($token, '-_', '+/'), true), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @return array{error: string, message: string}
     */
    private function error(string $code, string $message): array
    {
        return ['error' => $code, 'message' => $message];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }

    private function baseUrl(): string
    {
        return url('/oai');
    }
}
