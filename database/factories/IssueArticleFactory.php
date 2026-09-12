<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Issue;
use App\Models\IssueArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueArticle>
 */
class IssueArticleFactory extends Factory
{
    public function definition(): array
    {
        $issue = Issue::factory()->create();

        return [
            'issue_id' => $issue->id,
            'article_id' => Article::factory()->create([
                'journal_id' => $issue->journal_id,
            ]),
            'sort_order' => 1,
            'article_number' => null,
        ];
    }
}
