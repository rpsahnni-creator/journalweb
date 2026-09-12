<?php

namespace App\Models;

use App\Enums\RoleSlug;
use App\Notifications\ResetPasswordNotification;
use App\Support\Notifier;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'academic_title',
        'affiliation',
        'orcid',
        'biography',
        'email',
        'password',
        'is_active',
        'is_editor',
        'is_reviewer',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_editor' => 'boolean',
            'is_reviewer' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return MorphMany<DatabaseNotification, $this>
     */
    public function notifications(): MorphMany
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeEditors(Builder $query): Builder
    {
        return $query->active()->where('is_editor', true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeReviewerAccounts(Builder $query): Builder
    {
        return $query->active()->where('is_reviewer', true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeReviewers(Builder $query): Builder
    {
        return $query->active()->whereHas('roles', fn ($roles) => $roles->where('slug', RoleSlug::Reviewer->value));
    }

    /**
     * @return HasMany<SubmissionReview, $this>
     */
    public function submissionReviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class, 'reviewer_id');
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('journal_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function correspondingArticles(): HasMany
    {
        return $this->hasMany(Article::class, 'corresponding_author_id');
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * @return HasMany<ArticleAuthor, $this>
     */
    public function articleAuthorships(): HasMany
    {
        return $this->hasMany(ArticleAuthor::class);
    }

    /**
     * @return HasMany<ReviewerAssignment, $this>
     */
    public function reviewerAssignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class, 'reviewer_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    /**
     * @return HasMany<EditorialDecision, $this>
     */
    public function editorialDecisions(): HasMany
    {
        return $this->hasMany(EditorialDecision::class, 'editor_id');
    }

    /**
     * @return HasMany<ArticleFile, $this>
     */
    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(ArticleFile::class, 'uploaded_by');
    }

    /**
     * @return HasMany<JournalNotification, $this>
     */
    public function journalNotifications(): HasMany
    {
        return $this->hasMany(JournalNotification::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function assignRole(Role|string|RoleSlug $role, ?Journal $journal = null): void
    {
        $roleId = match (true) {
            $role instanceof Role => $role->id,
            $role instanceof RoleSlug => Role::query()->where('slug', $role->value)->valueOrFail('id'),
            default => Role::query()->where('slug', $role)->valueOrFail('id'),
        };

        $alreadyAssigned = $this->roles()
            ->where('roles.id', $roleId)
            ->wherePivot('journal_id', $journal?->id)
            ->exists();

        if (! $alreadyAssigned) {
            $this->roles()->attach($roleId, ['journal_id' => $journal?->id]);
        }
    }

    /**
     * @param  list<int|string>  $roleIds
     */
    public function syncRoles(array $roleIds, ?int $journalId = null): void
    {
        $payload = [];

        foreach ($roleIds as $roleId) {
            $payload[(int) $roleId] = ['journal_id' => $journalId];
        }

        $this->roles()->sync($payload);
        $this->unsetRelation('roles');
    }

    public function hasRole(string|RoleSlug $slug, ?int $journalId = null): bool
    {
        $this->loadMissing('roles');
        $roleSlug = $slug instanceof RoleSlug ? $slug->value : $slug;

        return $this->roles
            ->contains(function (Role $role) use ($roleSlug, $journalId): bool {
                if ($role->slug !== $roleSlug) {
                    return false;
                }

                if ($journalId === null) {
                    return true;
                }

                return (int) $role->pivot->journal_id === $journalId
                    || $role->pivot->journal_id === null;
            });
    }

    public function hasAnyRole(string|RoleSlug ...$roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $slug, ?int $journalId = null): bool
    {
        $this->loadMissing('roles.permissions');

        return $this->roles
            ->filter(function (Role $role) use ($journalId): bool {
                if ($journalId === null) {
                    return true;
                }

                return $role->pivot->journal_id === null
                    || (int) $role->pivot->journal_id === $journalId;
            })
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('slug', $slug);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleSlug::Admin);
    }

    public function isEditor(): bool
    {
        return $this->is_editor === true;
    }

    public function isEditorial(): bool
    {
        return $this->hasAnyRole(...RoleSlug::editorial());
    }

    public function isReviewer(): bool
    {
        return $this->is_reviewer === true || $this->hasRole(RoleSlug::Reviewer);
    }

    public function isAuthor(): bool
    {
        return $this->hasRole(RoleSlug::Author);
    }

    public function isReader(): bool
    {
        return $this->hasRole(RoleSlug::Reader);
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->isAdmin() => 'admin.dashboard',
            $this->isEditorial() => 'editorial.dashboard',
            $this->isReviewer() => 'reviewer.dashboard',
            $this->isAuthor() => 'author.dashboard',
            default => 'dashboard',
        };
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        Notifier::notify($this, new ResetPasswordNotification($token));
    }
}
