<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleAuthor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleAuthor>
 */
class ArticleAuthorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'affiliation' => fake()->optional()->company(),
            'orcid' => null,
            'sequence' => 1,
            'is_corresponding' => false,
        ];
    }

    public function corresponding(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_corresponding' => true,
        ]);
    }
}
