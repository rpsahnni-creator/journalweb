<?php

namespace App\Models;

use App\Enums\JournalPolicyType;
use Database\Factories\JournalFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Journal extends Model
{
    /** @use HasFactory<JournalFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'abbreviation',
        'description',
        'issn',
        'eissn',
        'publisher',
        'website_url',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Journal>  $query
     * @return Builder<Journal>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<JournalSetting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(JournalSetting::class);
    }

    /**
     * @return HasMany<EditorialBoardMember, $this>
     */
    public function editorialBoardMembers(): HasMany
    {
        return $this->hasMany(EditorialBoardMember::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Volume, $this>
     */
    public function volumes(): HasMany
    {
        return $this->hasMany(Volume::class);
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * @return HasMany<JournalPolicy, $this>
     */
    public function policies(): HasMany
    {
        return $this->hasMany(JournalPolicy::class);
    }

    /**
     * @return HasMany<ContactMessage, $this>
     */
    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function publishedPolicy(JournalPolicyType|string $type): ?JournalPolicy
    {
        $value = $type instanceof JournalPolicyType ? $type->value : $type;

        return $this->policies()
            ->published()
            ->where('type', $value)
            ->first();
    }

    /**
     * @return HasOne<Issue, $this>
     */
    public function currentIssue(): HasOne
    {
        return $this->hasOne(Issue::class)
            ->ofMany(
                ['id' => 'max'],
                fn (Builder $query) => $query->where('is_current', true)
            );
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function publishedIssues(): HasMany
    {
        return $this->hasMany(Issue::class)
            ->published()
            ->orderByDesc('published_at');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function publishedArticles(): HasMany
    {
        return $this->hasMany(Article::class)
            ->publiclyListed()
            ->orderByDesc('published_at');
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $value = $this->settings()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    /**
     * ISSN used in public citation tags. Prefer journal_settings; fall back to the journals column.
     */
    public function citationIssn(): ?string
    {
        $fromSettings = $this->setting('issn');

        if (filled($fromSettings)) {
            return trim((string) $fromSettings);
        }

        return filled($this->issn) ? trim((string) $this->issn) : null;
    }
}
