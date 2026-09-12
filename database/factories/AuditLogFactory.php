<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        $article = Article::factory()->create();

        return [
            'user_id' => User::factory(),
            'journal_id' => $article->journal_id,
            'auditable_type' => Article::class,
            'auditable_id' => $article->id,
            'action' => 'created',
            'old_values' => null,
            'new_values' => ['status' => $article->status->value],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'created_at' => now(),
        ];
    }
}
