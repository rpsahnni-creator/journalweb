<?php

namespace App\Support;

use App\Models\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicationFileStore
{
    public function store(Article $article, UploadedFile $upload): string
    {
        $disk = $article->publicationDisk();
        $filename = Str::uuid()->toString().'.pdf';
        $path = $upload->storeAs((string) $article->id, $filename, $disk);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('The formatted PDF could not be stored.');
        }

        return $path;
    }

    public function download(Article $article): StreamedResponse
    {
        if (! filled($article->pdf_path) || ! Storage::disk($article->publicationDisk())->exists($article->pdf_path)) {
            abort(404);
        }

        $name = $article->pdf_original_filename ?: 'article.pdf';
        $name = basename(str_replace(['\\', "\0", "\r", "\n"], '/', $name));

        return Storage::disk($article->publicationDisk())->download($article->pdf_path, $name !== '' ? $name : 'article.pdf', [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => 'application/pdf',
        ]);
    }
}
