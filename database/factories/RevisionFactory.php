<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Revision>
 */
class RevisionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'submitted_by' => function (array $attributes) {
                return Article::query()->findOrFail($attributes['article_id'])->corresponding_author_id;
            },
            'version' => 1,
            'notes_to_editor' => fake()->optional()->paragraph(),
            'submitted_at' => now(),
        ];
    }
}
