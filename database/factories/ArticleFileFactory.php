<?php

namespace Database\Factories;

use App\Enums\ArticleFileType;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ArticleFile>
 */
class ArticleFileFactory extends Factory
{
    public function definition(): array
    {
        $filename = fake()->unique()->lexify('manuscript-????.pdf');

        return [
            'article_id' => Article::factory(),
            'revision_id' => null,
            'uploaded_by' => User::factory(),
            'type' => ArticleFileType::Manuscript,
            'original_filename' => $filename,
            'disk' => 'local',
            'path' => 'manuscripts/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(10_000, 5_000_000),
            'is_public' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }
}
