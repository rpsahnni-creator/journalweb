<?php

namespace Database\Factories;

use App\Models\EditorialBoardMember;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialBoardMember>
 */
class EditorialBoardMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'role_title' => 'Associate Editor',
            'department' => null,
            'affiliation' => fake()->optional()->company(),
            'official_address' => null,
            'country' => fake()->optional()->country(),
            'email' => fake()->optional()->safeEmail(),
            'bio' => null,
            'sort_order' => fake()->numberBetween(0, 20),
            'is_public' => false,
            'is_active' => true,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }
}
