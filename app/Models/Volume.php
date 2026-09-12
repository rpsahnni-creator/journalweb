<?php

namespace App\Models;

use Database\Factories\VolumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Volume extends Model
{
    /** @use HasFactory<VolumeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'journal_id',
        'number',
        'year',
        'title',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'year' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function displayLabel(): string
    {
        return 'Volume '.$this->number.' ('.$this->year.')';
    }
}
