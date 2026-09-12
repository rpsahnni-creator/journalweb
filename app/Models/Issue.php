<?php

namespace App\Models;

use App\Enums\IssueStatus;
use Database\Factories\IssueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Throwable;

class Issue extends Model
{
    /** @use HasFactory<IssueFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'volume_id',
        'volume_number',
        'number',
        'title',
        'publication_month_year',
        'description',
        'status',
        'published_at',
        'is_current',
        'is_special_issue',
        'special_issue_theme',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'volume_number' => 'integer',
            'status' => IssueStatus::class,
            'published_at' => 'datetime',
            'is_current' => 'boolean',
            'is_special_issue' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Issue $issue): void {
            if ($issue->volume_number === null && $issue->volume_id) {
                $issue->volume_number = Volume::query()->whereKey($issue->volume_id)->value('number');
            }
        });
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', IssueStatus::Published);
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * @param  Builder<Issue>  $query
     * @return Builder<Issue>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('status', IssueStatus::Published)
                ->orWhere('is_current', true);
        });
    }

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * @return BelongsTo<Volume, $this>
     */
    public function volume(): BelongsTo
    {
        return $this->belongsTo(Volume::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function assignedArticles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return HasMany<IssueArticle, $this>
     */
    public function issueArticles(): HasMany
    {
        return $this->hasMany(IssueArticle::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'issue_articles')
            ->withPivot(['sort_order', 'article_number'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function publishedNonDemoArticleCount(): int
    {
        return $this->listedArticlesQuery()->count();
    }

    /**
     * @return Builder<Article>
     */
    public function listedArticlesQuery(): Builder
    {
        return Article::query()
            ->published()
            ->where('is_demo', false)
            ->where(function (Builder $query): void {
                $query->where('issue_id', $this->id)
                    ->orWhereHas('issues', fn (Builder $issues) => $issues->where('issues.id', $this->id));
            })
            ->orderByDesc('published_at')
            ->orderBy('id');
    }

    public function markAsCurrent(): void
    {
        static::query()
            ->where('journal_id', $this->journal_id)
            ->whereKeyNot($this->id)
            ->update(['is_current' => false]);

        $this->forceFill(['is_current' => true])->save();
    }

    public function volumeNumber(): ?int
    {
        return $this->volume_number ?? $this->volume?->number;
    }

    public function issueNumber(): ?int
    {
        return $this->number;
    }

    /**
     * Google Scholar citation_publication_date (YYYY/MM) from publication_month_year.
     */
    public function citationPublicationDate(): ?string
    {
        $raw = trim((string) $this->publication_month_year);

        if ($raw !== '') {
            if (preg_match('/^(\d{4})[\/\-](\d{1,2})$/', $raw, $matches) === 1) {
                return sprintf('%04d/%02d', (int) $matches[1], (int) $matches[2]);
            }

            try {
                return Carbon::parse($raw)->format('Y/m');
            } catch (Throwable) {
                // Fall through to published_at.
            }
        }

        return $this->published_at?->format('Y/m');
    }

    public function catalogLabel(): string
    {
        $volume = $this->volumeNumber() ?? '—';
        $label = "Vol. {$volume}, No. {$this->number}";

        if (filled($this->publication_month_year)) {
            $label .= ', '.$this->publication_month_year;
        }

        return $label;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    public function isDraft(): bool
    {
        return $this->status === IssueStatus::Draft;
    }

    public function displayLabel(): string
    {
        $volume = $this->volumeNumber() ?? '—';

        return "Vol. {$volume} No. {$this->number}";
    }

    public function publicUrl(): string
    {
        $this->loadMissing('volume');

        return route('issues.show', [
            'volume' => $this->volumeNumber() ?? $this->volume?->number,
            'issue' => $this->number,
        ]);
    }
}
