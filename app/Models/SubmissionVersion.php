<?php

namespace App\Models;

use Database\Factories\SubmissionVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SubmissionVersion extends Model
{
    /** @use HasFactory<SubmissionVersionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'submission_id',
        'version_number',
        'manuscript_path',
        'uploaded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function existsOnDisk(): bool
    {
        $submission = $this->relationLoaded('submission') ? $this->submission : $this->submission()->first();

        if ($submission === null || $this->manuscript_path === '') {
            return false;
        }

        return Storage::disk($submission->storageDisk())->exists($this->manuscript_path);
    }

    public function downloadFilename(): string
    {
        $this->loadMissing('submission');

        $extension = strtolower((string) pathinfo($this->manuscript_path, PATHINFO_EXTENSION));
        $base = $this->submission?->downloadFilename() ?: 'manuscript';
        $baseName = pathinfo($base, PATHINFO_FILENAME) ?: 'manuscript';

        return $extension !== ''
            ? $baseName.'-v'.$this->version_number.'.'.$extension
            : $baseName.'-v'.$this->version_number;
    }
}
