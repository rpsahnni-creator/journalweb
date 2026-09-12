<?php

namespace Tests\Feature\Policies;

use App\Enums\ArticleStatus;
use App\Enums\RoleSlug;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpublished_articles_are_hidden_from_guests_and_unrelated_users(): void
    {
        $article = Article::factory()->create(['status' => ArticleStatus::UnderReview]);
        $stranger = User::factory()->create();

        $this->assertFalse($stranger->can('view', $article));
        $this->assertTrue($article->correspondingAuthor->can('view', $article));
        $this->assertFalse($article->isPubliclyVisible());
    }

    public function test_published_articles_are_visible_to_anyone(): void
    {
        $article = Article::factory()->published()->create();
        $reader = User::factory()->create();

        $this->assertTrue($reader->can('view', $article));
        $this->assertTrue($article->isPubliclyVisible());
    }

    public function test_admins_can_view_unpublished_articles(): void
    {
        $admin = $this->createUserWithRole(RoleSlug::Admin);
        $article = Article::factory()->create(['status' => ArticleStatus::Submitted]);

        $this->assertTrue($admin->can('view', $article));
        $this->assertTrue($admin->hasRole(RoleSlug::Admin));
    }
}
