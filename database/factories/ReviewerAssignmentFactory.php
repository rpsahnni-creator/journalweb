<?php

namespace Database\Factories;

use App\Enums\ReviewerAssignmentStatus;
use App\Models\Article;
use App\Models\ReviewerAssignment;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewerAssignment>
 */
class ReviewerAssignmentFactory extends Factory
{
    public function definition(): array
    {
        $article = Article::factory()->create();
        $revision = Revision::factory()->create([
            'article_id' => $article->id,
            'submitted_by' => $article->corresponding_author_id,
            'version' => 1,
        ]);

        return [
            'article_id' => $article->id,
            'revision_id' => $revision->id,
            'reviewer_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => ReviewerAssignmentStatus::Invited,
            'invited_at' => now(),
            'responded_at' => null,
            'due_at' => now()->addWeeks(3),
            'completed_at' => null,
        ];
    }
}
