<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'volume_id' => Volume::factory(),
            'journal_id' => function (array $attributes) {
                return Volume::query()->findOrFail($attributes['volume_id'])->journal_id;
            },
            'volume_number' => function (array $attributes) {
                return Volume::query()->findOrFail($attributes['volume_id'])->number;
            },
            'number' => fake()->numberBetween(1, 12),
            'title' => null,
            'publication_month_year' => null,
            'description' => null,
            'status' => IssueStatus::Draft,
            'published_at' => null,
            'is_current' => false,
            'is_special_issue' => false,
            'special_issue_theme' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IssueStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
