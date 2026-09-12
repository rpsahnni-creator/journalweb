<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Volume>
 */
class VolumeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'number' => fake()->numberBetween(1, 500),
            'year' => (int) now()->year,
            'title' => null,
        ];
    }
}
