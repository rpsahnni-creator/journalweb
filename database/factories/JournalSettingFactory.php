<?php

namespace Database\Factories;

use App\Models\Journal;
use App\Models\JournalSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalSetting>
 */
class JournalSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'key' => fake()->slug(2),
            'value' => fake()->sentence(),
        ];
    }
}
