<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(8);

        return [
            'journal_id' => Journal::factory(),
            'corresponding_author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug(Str::limit($title, 50, '')).'-'.Str::lower(Str::random(6)),
            'abstract' => fake()->paragraphs(2, true),
            'keywords' => fake()->words(4),
            'language' => 'en',
            'status' => ArticleStatus::Draft,
            'doi' => null,
            'page_start' => null,
            'page_end' => null,
            'submitted_at' => null,
            'published_at' => null,
            'is_demo' => false,
        ];
    }

    public function demo(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_demo' => true,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Published,
            'submitted_at' => now()->subMonths(2),
            'published_at' => now()->subWeek(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Accepted,
            'submitted_at' => now()->subMonth(),
            'published_at' => null,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }
}
