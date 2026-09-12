<?php

namespace App\Models;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\ArticleType;
use App\Services\DoiService;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'issue_id',
        'corresponding_author_id',
        'editor_id',
        'submission_number',
        'title',
        'slug',
        'abstract',
        'keywords',
        'article_type',
        'language',
        'cover_letter',
        'originality_confirmed',
        'conflict_of_interest_declared',
        'conflict_of_interest_statement',
        'author_response',
        'status',
        'is_demo',
        'doi',
        'pdf_path',
        'pdf_original_filename',
        'page_start',
        'page_end',
        'submitted_at',
        'revision_due_at',
        'published_at',
        'confirmed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'article_type' => ArticleType::class,
            'status' => ArticleStatus::class,
            'originality_confirmed' => 'boolean',
            'conflict_of_interest_declared' => 'boolean',
            'is_demo' => 'boolean',
            'page_start' => 'integer',
            'page_end' => 'integer',
            'submitted_at' => 'datetime',
            'revision_due_at' => 'datetime',
            'published_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Article $article): void {
            if (blank($article->submission_number)) {
                $article->submission_number = self::generateSubmissionNumber();
            }

            if (blank($article->slug)) {
                $article->slug = Str::slug(Str::limit($article->title, 50, '')).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public static function generateSubmissionNumber(): string
    {
        do {
            $number = 'MS-'.now()->year.'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::query()->where('submission_number', $number)->exists());

        return $number;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status->isPubliclyVisible();
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('status'), ArticleStatus::Published);
    }

    /**
     * Hide development sample articles on the public site in production.
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeVisibleInEnvironment(Builder $query): Builder
    {
        if (app()->environment('production')) {
            $query->where($query->qualifyColumn('is_demo'), false);
        }

        return $query;
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublishedNonDemo(Builder $query): Builder
    {
        return $query->published()->where($query->qualifyColumn('is_demo'), false);
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePubliclyListed(Builder $query): Builder
    {
        return $query->published()->visibleInEnvironment();
    }

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function correspondingAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corresponding_author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    /**
     * @return HasMany<ArticleAuthor, $this>
     */
    public function authors(): HasMany
    {
        return $this->hasMany(ArticleAuthor::class)->orderBy('sequence');
    }

    /**
     * @return HasMany<ArticleFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(ArticleFile::class);
    }

    /**
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class)->orderBy('version');
    }

    /**
     * @return HasOne<Revision, $this>
     */
    public function currentRevision(): HasOne
    {
        return $this->hasOne(Revision::class)->latestOfMany('version');
    }

    /**
     * @return HasMany<ReviewerAssignment, $this>
     */
    public function reviewerAssignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<EditorialDecision, $this>
     */
    public function editorialDecisions(): HasMany
    {
        return $this->hasMany(EditorialDecision::class);
    }

    /**
     * @return HasMany<ArticleStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(ArticleStatusEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeForAuthor(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user): void {
            $query->where('corresponding_author_id', $user->id)
                ->orWhereHas('authors', fn (Builder $authors) => $authors->where('user_id', $user->id));
        });
    }

    public function isAuthorEditable(): bool
    {
        return $this->status->isAuthorEditable();
    }

    public function isOwnedBy(User $user): bool
    {
        if ($this->corresponding_author_id === $user->id) {
            return true;
        }

        return $this->authors()->where('user_id', $user->id)->exists();
    }

    /**
     * @return HasMany<ArticleFile, $this>
     */
    public function workingFiles(): HasMany
    {
        return $this->hasMany(ArticleFile::class)->whereNull('revision_id');
    }

    public function hasManuscriptFile(): bool
    {
        return $this->files()->where('type', ArticleFileType::Manuscript)->exists();
    }

    public function hasWorkingManuscriptFile(): bool
    {
        return $this->workingFiles()->where('type', ArticleFileType::Manuscript)->exists();
    }

    public function latestAuthorFacingDecision(): ?EditorialDecision
    {
        $this->loadMissing('editorialDecisions');

        $decision = $this->editorialDecisions
            ->filter(fn (EditorialDecision $decision) => $decision->decision->isAuthorFacing())
            ->sortByDesc(fn (EditorialDecision $decision) => $decision->decided_at?->timestamp ?? 0)
            ->first();

        if ($decision !== null) {
            $decision->makeHidden(['internal_notes', 'editor_id']);
            $decision->unsetRelation('editor');
        }

        return $decision;
    }

    /**
     * @return Collection<int, Review>
     */
    public function authorFacingReviews()
    {
        if ($this->latestAuthorFacingDecision() === null) {
            return collect();
        }

        $this->loadMissing('reviews');

        return $this->reviews->map(function (Review $review): Review {
            $review->makeHidden(['comments_to_editor', 'reviewer_id']);
            $review->unsetRelation('reviewer');

            return $review;
        });
    }

    public function isRevisionOverdue(): bool
    {
        if ($this->status !== ArticleStatus::RevisionRequired || $this->revision_due_at === null) {
            return false;
        }

        return $this->revision_due_at->isPast();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function moveTo(ArticleStatus $to, ?User $actor = null, ?string $note = null, array $attributes = []): bool
    {
        if (! $this->status->canTransitionTo($to)) {
            return false;
        }

        $from = $this->status;
        $this->update(array_merge(['status' => $to], $attributes));
        $this->recordStatusChange($from, $to, $actor, $note);

        return true;
    }

    public function keywordsList(): string
    {
        return collect($this->keywords ?? [])->implode(', ');
    }

    /**
     * @return list<string>
     */
    public static function parseKeywords(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function submissionBlockers(): array
    {
        $blockers = [];

        if (blank($this->title)) {
            $blockers[] = 'Add a title.';
        }

        if ($this->article_type === null) {
            $blockers[] = 'Select an article type.';
        }

        if (blank($this->abstract) || str($this->abstract)->length() < 50) {
            $blockers[] = 'Add an abstract of at least 50 characters.';
        }

        if (count($this->keywords ?? []) < 3) {
            $blockers[] = 'Add at least three keywords.';
        }

        if (blank($this->cover_letter) || str($this->cover_letter)->length() < 20) {
            $blockers[] = 'Add a cover letter.';
        }

        if ($this->authors()->count() < 1) {
            $blockers[] = 'Add at least one author.';
        }

        if (! $this->authors()->where('is_corresponding', true)->exists()) {
            $blockers[] = 'Select a corresponding author.';
        }

        if ($this->status === ArticleStatus::RevisionRequired) {
            if (! $this->hasWorkingManuscriptFile()) {
                $blockers[] = 'Upload a revised manuscript file. Previous versions are kept.';
            }

            if (blank($this->author_response) || str($this->author_response)->length() < 20) {
                $blockers[] = 'Add a response to the reviewers of at least 20 characters.';
            }
        } elseif (! $this->hasManuscriptFile()) {
            $blockers[] = 'Upload the manuscript file.';
        }

        if (! $this->originality_confirmed) {
            $blockers[] = 'Confirm the originality declaration.';
        }

        if (! $this->conflict_of_interest_declared || blank($this->conflict_of_interest_statement)) {
            $blockers[] = 'Complete the conflict of interest declaration.';
        }

        $corresponding = $this->correspondingAuthor;

        if ($corresponding === null || blank($corresponding->affiliation)) {
            $blockers[] = 'Add an affiliation to your author profile.';
        }

        return $blockers;
    }

    public function isReadyToSubmit(): bool
    {
        return $this->submissionBlockers() === [];
    }

    public function recordStatusChange(?ArticleStatus $from, ArticleStatus $to, ?User $user = null, ?string $note = null): ArticleStatusEvent
    {
        return $this->statusEvents()->create([
            'user_id' => $user?->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
        ]);
    }

    /**
     * @return BelongsToMany<Issue, $this>
     */
    public function issues(): BelongsToMany
    {
        return $this->belongsToMany(Issue::class, 'issue_articles')
            ->withPivot(['sort_order', 'article_number'])
            ->withTimestamps();
    }

    public function publishedIssue(): ?Issue
    {
        $this->loadMissing(['issue.volume', 'issues.volume']);

        $fromPivot = $this->issues->first(fn (Issue $issue) => $issue->isPublished())
            ?? $this->issues->first();

        if ($fromPivot) {
            return $fromPivot;
        }

        return $this->issue;
    }

    public function pageRange(): ?string
    {
        if ($this->page_start && $this->page_end && $this->page_end !== $this->page_start) {
            return $this->page_start.'–'.$this->page_end;
        }

        if ($this->page_start) {
            return (string) $this->page_start;
        }

        return null;
    }

    public function articleNumber(): ?string
    {
        $issue = $this->publishedIssue();

        $number = $issue?->pivot?->article_number;

        return filled($number) ? (string) $number : null;
    }

    public function publicPdfFile(): ?ArticleFile
    {
        $this->loadMissing('files');

        return $this->files->first(function (ArticleFile $file): bool {
            if (! $file->isVisibleToPublic()) {
                return false;
            }

            $name = strtolower($file->original_filename);

            return $file->mime_type === 'application/pdf' || str_ends_with($name, '.pdf');
        });
    }

    public function hasDownloadablePdf(): bool
    {
        return filled($this->pdf_path) || $this->publicPdfFile() !== null;
    }

    public function publicationDisk(): string
    {
        return 'publications';
    }

    /**
     * @return HasMany<ArticleView, $this>
     */
    public function views(): HasMany
    {
        return $this->hasMany(ArticleView::class);
    }

    public function mintDoi(?DoiService $dois = null): ?string
    {
        return ($dois ?? app(DoiService::class))->assignIfConfigured($this);
    }

    public function doiUrl(): ?string
    {
        return filled($this->doi) ? 'https://doi.org/'.$this->doi : null;
    }

    public function hasResolvableDoi(): bool
    {
        $doi = (string) $this->doi;

        return $doi !== ''
            && ! str_starts_with($doi, '10.XXXX')
            && (bool) preg_match('/^10\.\d{4,5}\//', $doi);
    }

    public function citation(): string
    {
        $this->loadMissing(['authors', 'journal', 'issue.volume', 'issues.volume']);

        $authors = $this->authors
            ->sortBy('sequence')
            ->pluck('name')
            ->filter()
            ->implode(', ');

        if ($authors === '') {
            $authors = $this->correspondingAuthor?->name ?: 'Author';
        }

        $title = rtrim((string) $this->title, '.');
        $issue = $this->publishedIssue();
        $year = $this->published_at?->format('Y')
            ?: $issue?->published_at?->format('Y')
            ?: $issue?->volume?->year
            ?: now()->format('Y');
        $volume = $issue?->volumeNumber() ?? $issue?->volume?->number ?? '—';
        $issueNumber = $issue?->issueNumber() ?? $issue?->number ?? '—';
        $journalName = 'SRT Journal of Multidisciplinary Research';

        return "{$authors}. ({$year}). {$title}. {$journalName}, {$volume}({$issueNumber}).";
    }

    public function citationMla(): string
    {
        $this->loadMissing(['authors', 'issues.volume']);
        $authors = $this->authors->sortBy('sequence')->pluck('name')->filter()->implode(', ');
        $title = rtrim((string) $this->title, '.');
        $issue = $this->publishedIssue();
        $year = $this->published_at?->format('Y') ?: $issue?->published_at?->format('Y') ?: now()->format('Y');
        $volume = $issue?->volumeNumber() ?? $issue?->volume?->number ?? '';
        $issueNumber = $issue?->issueNumber() ?? $issue?->number ?? '';
        $pages = $this->pageRange();

        $parts = array_filter([
            ($authors !== '' ? $authors.'. ' : '')."\"{$title}.\"",
            'SRT Journal of Multidisciplinary Research,',
            $volume !== '' ? 'vol. '.$volume.',' : null,
            $issueNumber !== '' ? 'no. '.$issueNumber.',' : null,
            $year.',',
            $pages ? 'pp. '.$pages.'.' : null,
        ]);

        return trim(preg_replace('/\s+/', ' ', implode(' ', $parts)) ?? '');
    }

    public function citationChicago(): string
    {
        $this->loadMissing(['authors', 'issues.volume']);
        $authors = $this->authors->sortBy('sequence')->pluck('name')->filter()->implode(', ');
        $title = rtrim((string) $this->title, '.');
        $issue = $this->publishedIssue();
        $year = $this->published_at?->format('Y') ?: $issue?->published_at?->format('Y') ?: now()->format('Y');
        $volume = $issue?->volumeNumber() ?? $issue?->volume?->number ?? '';
        $issueNumber = $issue?->issueNumber() ?? $issue?->number ?? '';
        $pages = $this->pageRange();
        $issueBit = ($volume !== '' && $issueNumber !== '') ? $volume.', no. '.$issueNumber : trim($volume.' '.$issueNumber);

        return trim(($authors !== '' ? $authors.'. ' : '')."\"{$title}.\" SRT Journal of Multidisciplinary Research {$issueBit} ({$year})".($pages ? ': '.$pages : '').'.');
    }

    public function publicUrl(): string
    {
        return route('articles.show', $this);
    }

    /**
     * Google Scholar (Highwire) and Dublin Core meta tags for the public article page.
     *
     * @return list<array{name: string, content: string}>
     */
    public function scholarMetaTags(mixed $issue = null, mixed $journal = null, mixed $pdf = null): array
    {
        $this->loadMissing(['authors', 'journal', 'issues.volume', 'files']);

        $journal = $journal instanceof Journal ? $journal : $this->journal;
        $issue = $issue instanceof Issue
            ? $issue
            : ($this->issues->first(fn (Issue $candidate) => $candidate->isPublished()) ?? $this->publishedIssue());
        $pdf = $pdf instanceof ArticleFile ? $pdf : $this->publicPdfFile();
        $journalTitle = filled($journal?->name)
            ? $journal->name
            : 'SRT Journal of Multidisciplinary Research';

        $tags = [
            ['name' => 'citation_title', 'content' => (string) $this->title],
            ['name' => 'DC.Title', 'content' => (string) $this->title],
        ];

        foreach ($this->authors->sortBy('sequence') as $author) {
            if (blank($author->name)) {
                continue;
            }

            $tags[] = ['name' => 'citation_author', 'content' => (string) $author->name];
            $tags[] = ['name' => 'DC.Creator', 'content' => (string) $author->name];
        }

        $publicationDate = $issue?->citationPublicationDate()
            ?: $this->published_at?->format('Y/m');

        if (filled($publicationDate)) {
            $tags[] = ['name' => 'citation_publication_date', 'content' => $publicationDate];
            $tags[] = ['name' => 'DC.Date', 'content' => $publicationDate];
        }

        $tags[] = ['name' => 'citation_journal_title', 'content' => $journalTitle];
        $tags[] = ['name' => 'DC.Source', 'content' => $journalTitle];

        $issn = $journal?->citationIssn();

        if (filled($issn)) {
            $tags[] = ['name' => 'citation_issn', 'content' => $issn];
        }

        $volume = $issue?->volumeNumber();
        if ($volume !== null) {
            $tags[] = ['name' => 'citation_volume', 'content' => (string) $volume];
        }

        $issueNumber = $issue?->issueNumber();
        if ($issueNumber !== null) {
            $tags[] = ['name' => 'citation_issue', 'content' => (string) $issueNumber];
        }

        if ($this->page_start !== null) {
            $tags[] = ['name' => 'citation_firstpage', 'content' => (string) $this->page_start];
        }

        if ($this->page_end !== null) {
            $tags[] = ['name' => 'citation_lastpage', 'content' => (string) $this->page_end];
        }

        if ($pdf !== null || filled($this->pdf_path)) {
            $tags[] = ['name' => 'citation_pdf_url', 'content' => route('articles.pdf', $this)];
        }

        $canonical = $this->publicUrl();
        $tags[] = ['name' => 'citation_abstract_html_url', 'content' => $canonical];
        $tags[] = ['name' => 'DC.Identifier', 'content' => $canonical];

        if (filled($journal?->publisher)) {
            $tags[] = ['name' => 'DC.Publisher', 'content' => (string) $journal->publisher];
        }

        if (filled($this->abstract)) {
            $tags[] = ['name' => 'DC.Description', 'content' => (string) $this->abstract];
        }

        if (filled($this->language)) {
            $tags[] = ['name' => 'DC.Language', 'content' => (string) $this->language];
        }

        $tags[] = ['name' => 'DC.Type', 'content' => 'Text'];

        // Additional Google Scholar signals for improved indexing.
        if (filled($this->doi)) {
            $tags[] = ['name' => 'citation_doi', 'content' => (string) $this->doi];
            $tags[] = ['name' => 'DC.Identifier.DOI', 'content' => (string) $this->doi];
        }

        if (($this->keywords ?? []) !== []) {
            $tags[] = ['name' => 'citation_keywords', 'content' => $this->keywordsList()];
        }

        $lang = filled($this->language) ? $this->language : 'en';
        $tags[] = ['name' => 'citation_language', 'content' => $lang];

        // Signals to Scholar that full text is openly accessible.
        $tags[] = ['name' => 'citation_fulltext_world_readable', 'content' => ''];

        if (filled($journal?->publisher)) {
            $tags[] = ['name' => 'citation_publisher', 'content' => (string) $journal->publisher];
        }

        return $tags;
    }

    public function schemaOrgJson(): string
    {
        return json_encode(
            $this->schemaOrg(),
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaOrg(): array
    {
        $this->loadMissing(['authors', 'journal', 'issue.volume', 'issues.volume']);

        $issue = $this->publishedIssue();
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ScholarlyArticle',
            'headline' => $this->title,
            'name' => $this->title,
            'url' => $this->publicUrl(),
            'inLanguage' => $this->language ?: 'en',
        ];

        if (filled($this->abstract)) {
            $data['abstract'] = $this->abstract;
        }

        if (($this->keywords ?? []) !== []) {
            $data['keywords'] = $this->keywordsList();
        }

        if ($this->published_at) {
            $data['datePublished'] = $this->published_at->toDateString();
        }

        $authors = $this->authors->sortBy('sequence')->map(function (ArticleAuthor $author): array {
            $person = [
                '@type' => 'Person',
                'name' => $author->name,
            ];

            if (filled($author->affiliation)) {
                $person['affiliation'] = [
                    '@type' => 'Organization',
                    'name' => $author->affiliation,
                ];
            }

            if (filled($author->orcid)) {
                $person['identifier'] = 'https://orcid.org/'.$author->orcid;
            }

            return $person;
        })->values()->all();

        if ($authors !== []) {
            $data['author'] = $authors;
        }

        $periodical = [
            '@type' => 'Periodical',
            'name' => $this->journal?->name ?: config('app.name'),
        ];

        $issn = $this->journal?->citationIssn();

        if (filled($issn)) {
            $periodical['issn'] = $issn;
        }

        if ($issue !== null) {
            $volume = [
                '@type' => 'PublicationVolume',
                'volumeNumber' => (string) $issue->volume?->number,
                'isPartOf' => $periodical,
            ];

            $data['isPartOf'] = [
                '@type' => 'PublicationIssue',
                'issueNumber' => (string) $issue->number,
                'isPartOf' => $volume,
            ];

            if ($issue->published_at) {
                $data['isPartOf']['datePublished'] = $issue->published_at->toDateString();
            }
        } else {
            $data['isPartOf'] = $periodical;
        }

        if (filled($this->doi)) {
            $data['identifier'] = 'https://doi.org/'.$this->doi;
            $data['sameAs'] = 'https://doi.org/'.$this->doi;
        }

        if ($this->hasDownloadablePdf()) {
            $data['encoding'] = [
                '@type' => 'MediaObject',
                'contentUrl' => route('articles.pdf', $this),
                'encodingFormat' => 'application/pdf',
            ];
        }

        return $data;
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $term = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('title', 'like', $term)
                ->orWhere('abstract', 'like', $term)
                ->orWhere('keywords', 'like', $term)
                ->orWhereHas('authors', function (Builder $authors) use ($term): void {
                    $authors->where('name', 'like', $term)
                        ->orWhere('affiliation', 'like', $term);
                });
        });
    }
}
