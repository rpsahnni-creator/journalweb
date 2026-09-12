<?php

namespace App\Models;

use App\Support\JournalCopy;
use Database\Factories\EditorialBoardMemberFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialBoardMember extends Model
{
    /** @use HasFactory<EditorialBoardMemberFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'user_id',
        'name',
        'role_title',
        'department',
        'affiliation',
        'official_address',
        'country',
        'email',
        'bio',
        'sort_order',
        'is_public',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<EditorialBoardMember>  $query
     * @return Builder<EditorialBoardMember>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->where('is_active', true);
    }

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function needsRealName(): bool
    {
        $name = trim((string) $this->name);

        return $name === ''
            || $name === JournalCopy::PLACEHOLDER_BOARD_NAME
            || str_starts_with($name, '[')
            || str_contains($name, 'Name Pending');
    }

    public static function syncRoster(Journal $journal): void
    {
        foreach (JournalCopy::editorialBoardRoster() as $member) {
            $match = $member['sort_order'] === 1
                ? ['journal_id' => $journal->id, 'email' => $member['email']]
                : ['journal_id' => $journal->id, 'sort_order' => $member['sort_order']];

            self::query()->updateOrCreate($match, [
                ...$member,
                'is_active' => true,
            ]);
        }

        self::query()
            ->where('journal_id', $journal->id)
            ->where('name', JournalCopy::PLACEHOLDER_BOARD_NAME)
            ->delete();
    }
}
