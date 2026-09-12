<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileStore
{
    public function store(UploadedFile $upload, int $userId): string
    {
        $disk = (string) config('submissions.disk', 'submissions');
        $extension = $this->safeExtension($upload);
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $upload->storeAs((string) $userId, $filename, $disk);

        if (! is_string($path) || $path === '') {
            throw new InvalidArgumentException('The manuscript file could not be stored.');
        }

        return $path;
    }

    public function download(Submission $submission): StreamedResponse
    {
        if (! $submission->manuscriptExists()) {
            abort(404);
        }

        return Storage::disk($submission->storageDisk())->download(
            $submission->manuscript_path,
            $this->safeFilename($submission->downloadFilename()),
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => $this->contentType($submission->downloadFilename()),
            ]
        );
    }

    public function downloadVersion(Submission $submission, SubmissionVersion $version): StreamedResponse
    {
        abort_unless($version->submission_id === $submission->id, 404);

        if (! $version->existsOnDisk()) {
            abort(404);
        }

        return Storage::disk($submission->storageDisk())->download(
            $version->manuscript_path,
            $this->safeFilename($version->downloadFilename()),
            [
                'X-Content-Type-Options' => 'nosniff',
                'Content-Type' => $this->contentType($version->downloadFilename()),
            ]
        );
    }

    public function delete(Submission $submission): void
    {
        if ($submission->manuscript_path !== '') {
            Storage::disk($submission->storageDisk())->delete($submission->manuscript_path);
        }
    }

    private function safeExtension(UploadedFile $upload): string
    {
        $allowed = config('submissions.manuscript.extensions', ['pdf', 'docx']);
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

        return $name !== '' ? $name : 'manuscript';
    }

    private function contentType(string $filename): string
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}
