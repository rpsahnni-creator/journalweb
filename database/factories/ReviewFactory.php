<?php

namespace Database\Factories;

use App\Enums\ReviewRecommendation;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        $assignment = ReviewerAssignment::factory()->create();

        return [
            'reviewer_assignment_id' => $assignment->id,
            'article_id' => $assignment->article_id,
            'revision_id' => $assignment->revision_id,
            'reviewer_id' => $assignment->reviewer_id,
            'recommendation' => fake()->randomElement(ReviewRecommendation::cases()),
            'comments_to_author' => fake()->paragraph(),
            'comments_to_editor' => fake()->paragraph(),
            'submitted_at' => now(),
        ];
    }
}
