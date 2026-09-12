<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'article_id' => null,
            'title' => fake()->sentence(8),
            'abstract' => implode(' ', array_fill(0, 180, 'word')),
            'keywords' => 'history, education, society, culture',
            'co_authors' => null,
            'manuscript_path' => 'portal/'.fake()->uuid().'.pdf',
            'original_filename' => 'manuscript.pdf',
            'status' => SubmissionStatus::Submitted,
            'editor_notes' => null,
            'review_round' => 1,
            'submitted_at' => now(),
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubmissionStatus::Accepted,
        ]);
    }
}
