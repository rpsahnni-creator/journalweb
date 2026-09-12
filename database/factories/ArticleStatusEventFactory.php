<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleStatusEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleStatusEvent>
 */
class ArticleStatusEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => null,
            'from_status' => null,
            'to_status' => ArticleStatus::Draft,
            'note' => null,
            'created_at' => now(),
        ];
    }
}
