<?php

namespace App\Support;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManuscriptFileStore
{
    public function store(Article $article, UploadedFile $upload, ArticleFileType $type, User $uploader): ArticleFile
    {
        $disk = (string) config('manuscripts.disk', 'manuscripts');
        $extension = $this->safeExtension($upload, $type);
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = (string) $article->id;
        $path = $upload->storeAs($directory, $filename, $disk);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('The manuscript file could not be stored.');
        }

        if ($type === ArticleFileType::Manuscript) {
            $article->files()
                ->where('type', ArticleFileType::Manuscript)
                ->whereNull('revision_id')
                ->get()
                ->each(fn (ArticleFile $file) => $this->delete($file));
        }

        return $article->files()->create([
            'revision_id' => null,
            'uploaded_by' => $uploader->id,
            'type' => $type,
            'original_filename' => $this->safeFilename((string) $upload->getClientOriginalName()),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $upload->getMimeType() ?: $upload->getClientMimeType(),
            'size_bytes' => $upload->getSize() ?: 0,
            'is_public' => false,
        ]);
    }

    public function delete(ArticleFile $file): void
    {
        Storage::disk($file->disk)->delete($file->path);
        $file->delete();
    }

    public function download(ArticleFile $file): StreamedResponse
    {
        return Storage::disk($file->disk)->download($file->path, $this->safeFilename($file->original_filename), [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ]);
    }

    public function stream(ArticleFile $file): StreamedResponse
    {
        return Storage::disk($file->disk)->response($file->path, $this->safeFilename($file->original_filename), [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
        ], 'inline');
    }

    private function safeExtension(UploadedFile $upload, ArticleFileType $type): string
    {
        $key = match ($type) {
            ArticleFileType::Manuscript => 'manuscript',
            ArticleFileType::CoverLetter => 'cover_letter',
            default => 'supplementary',
        };

        $allowed = config('manuscripts.'.$key.'.extensions', []);
        $extension = strtolower((string) $upload->getClientOriginalExtension());

        if (! in_array($extension, $allowed, true)) {
            throw new InvalidArgumentException('That file extension is not allowed.');
        }

        return $extension;
    }

    private function safeFilename(string $name): string
    {
        $name = str_replace(["\0", "\r", "\n"], '', $name);
        $name = basename(str_replace('\\', '/', $name));
        $name = trim($name);

        return $name !== '' ? $name : 'download';
    }
}
