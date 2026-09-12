<?php

namespace Database\Factories;

use App\Enums\NotificationChannel;
use App\Models\Article;
use App\Models\JournalNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalNotification>
 */
class JournalNotificationFactory extends Factory
{
    public function definition(): array
    {
        $article = Article::factory()->create();

        return [
            'user_id' => User::factory(),
            'type' => 'article.submitted',
            'channel' => NotificationChannel::Database,
            'subject' => 'Manuscript received',
            'body' => 'A manuscript has been recorded in the journal system.',
            'data' => ['article_id' => $article->id],
            'related_type' => Article::class,
            'related_id' => $article->id,
            'read_at' => null,
            'sent_at' => now(),
        ];
    }
}
