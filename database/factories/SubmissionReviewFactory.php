<?php

namespace Database\Factories;

use App\Enums\SubmissionReviewStatus;
use App\Models\Submission;
use App\Models\SubmissionReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionReview>
 */
class SubmissionReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'reviewer_id' => User::factory()->reviewer(),
            'recommendation' => null,
            'comments_to_editor' => null,
            'comments_to_author' => null,
            'status' => SubmissionReviewStatus::Assigned,
            'round' => 1,
            'assigned_at' => now(),
            'submitted_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionReviewStatus::InProgress,
        ]);
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionReviewStatus::Submitted,
            'recommendation' => 'accept',
            'comments_to_author' => 'The manuscript is ready for publication after copyediting.',
            'comments_to_editor' => 'Confidential editor note.',
            'submitted_at' => now(),
        ]);
    }
}
