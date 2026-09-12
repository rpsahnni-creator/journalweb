<?php

namespace Database\Factories;

use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Journal>
 */
class JournalFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Journal';

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'abbreviation' => strtoupper(fake()->lexify('???')),
            'description' => fake()->paragraph(),
            'issn' => null,
            'eissn' => null,
            'publisher' => fake()->optional()->company(),
            'website_url' => null,
            'is_active' => true,
        ];
    }
}
