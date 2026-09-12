<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionVersion>
 */
class SubmissionVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'version_number' => 1,
            'manuscript_path' => 'portal/'.fake()->uuid().'.pdf',
            'uploaded_at' => now(),
        ];
    }
}
