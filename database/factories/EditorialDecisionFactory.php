<?php

namespace Database\Factories;

use App\Enums\EditorialDecisionType;
use App\Models\Article;
use App\Models\EditorialDecision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialDecision>
 */
class EditorialDecisionFactory extends Factory
{
    public function definition(): array
    {
        $article = Article::factory()->create();

        return [
            'article_id' => $article->id,
            'revision_id' => null,
            'editor_id' => User::factory(),
            'decision' => EditorialDecisionType::SendToReview,
            'comments_to_author' => fake()->optional()->paragraph(),
            'internal_notes' => fake()->optional()->sentence(),
            'decided_at' => now(),
        ];
    }
}
