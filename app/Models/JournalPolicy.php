<?php

namespace App\Models;

use App\Enums\JournalPolicyType;
use Database\Factories\JournalPolicyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalPolicy extends Model
{
    /** @use HasFactory<JournalPolicyFactory> */
    use HasFactory;

    protected $table = 'policies';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'type',
        'title',
        'slug',
        'body',
        'is_published',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<JournalPolicy>  $query
     * @return Builder<JournalPolicy>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOfType(Builder $query, JournalPolicyType|string $type): Builder
    {
        $value = $type instanceof JournalPolicyType ? $type->value : $type;

        return $query->where('type', $value);
    }

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
}
