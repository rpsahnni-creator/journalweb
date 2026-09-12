<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Support\WordCounter;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'article_id',
        'title',
        'abstract',
        'keywords',
        'co_authors',
        'manuscript_path',
        'original_filename',
        'status',
        'editor_notes',
        'submitted_at',
        'review_round',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'submitted_at' => 'datetime',
            'review_round' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * @return HasMany<SubmissionReview, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class)->orderBy('round')->orderBy('id');
    }

    /**
     * @return HasMany<SubmissionVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SubmissionVersion::class)->orderByDesc('version_number');
    }

    /**
     * @return HasOne<SubmissionVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(SubmissionVersion::class)->latestOfMany('version_number');
    }

    public function nextVersionNumber(): int
    {
        return (int) ($this->versions()->max('version_number') ?? 0) + 1;
    }

    public function canUploadRevision(): bool
    {
        return $this->status === SubmissionStatus::RevisionRequested;
    }

    public function recordVersion(string $path): SubmissionVersion
    {
        $version = $this->versions()->create([
            'version_number' => $this->nextVersionNumber(),
            'manuscript_path' => $path,
            'uploaded_at' => now(),
        ]);

        $this->forceFill([
            'manuscript_path' => $path,
        ])->save();

        return $version;
    }

    public function assignedReviewsAreComplete(): bool
    {
        $reviews = $this->relationLoaded('reviews') ? $this->reviews : $this->reviews()->get();
        $currentRound = $reviews->where('round', $this->review_round ?: 1);

        return $currentRound->isNotEmpty()
            && $currentRound->every(fn (SubmissionReview $review): bool => $review->isSubmitted());
    }

    /**
     * @return Collection<int, string>
     */
    public function commentsToAuthor(): Collection
    {
        $reviews = $this->relationLoaded('reviews')
            ? $this->reviews
            : $this->reviews()->orderBy('submitted_at')->orderBy('id')->get();

        return $reviews
            ->filter(fn (SubmissionReview $review): bool => $review->isSubmitted() && filled($review->comments_to_author))
            ->sortBy([
                fn (SubmissionReview $review): int => $review->submitted_at?->getTimestamp() ?? 0,
                fn (SubmissionReview $review): int => $review->id,
            ])
            ->pluck('comments_to_author')
            ->values();
    }

    public function compiledCommentsToAuthor(): string
    {
        return $this->commentsToAuthor()->implode("\n\n");
    }

    /**
     * @param  Builder<Submission>  $query
     * @return Builder<Submission>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function isAccepted(): bool
    {
        return $this->status === SubmissionStatus::Accepted;
    }

    public function hasBeenConverted(): bool
    {
        return $this->article_id !== null;
    }

    public function canConvertToArticle(): bool
    {
        return $this->isAccepted() && ! $this->hasBeenConverted();
    }

    public function canPublishToIssue(): bool
    {
        return $this->status === SubmissionStatus::Accepted;
    }

    public function downloadFilename(): string
    {
        $original = trim((string) $this->original_filename);

        if ($original !== '') {
            return basename(str_replace('\\', '/', $original));
        }

        $extension = pathinfo((string) $this->manuscript_path, PATHINFO_EXTENSION);

        return $extension !== '' ? 'manuscript.'.$extension : 'manuscript';
    }

    public function manuscriptExists(): bool
    {
        return filled($this->manuscript_path)
            && Storage::disk($this->storageDisk())->exists($this->manuscript_path);
    }

    public function storageDisk(): string
    {
        return (string) config('submissions.disk', 'submissions');
    }

    public function keywordsList(): array
    {
        return WordCounter::keywords($this->keywords);
    }
}
