<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journal_id' => Journal::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'subject' => fake()->sentence(6),
            'message' => fake()->paragraphs(2, true),
            'ip_address' => '127.0.0.1',
            'read_at' => null,
        ];
    }
}
