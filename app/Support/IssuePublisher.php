<?php

namespace App\Support;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Enums\IssueStatus;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\Issue;
use App\Models\IssueArticle;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class IssuePublisher
{
    public function assign(
        Issue $issue,
        Article $article,
        User $actor,
        ?string $articleNumber = null,
        ?int $pageStart = null,
        ?int $pageEnd = null,
        ?int $sortOrder = null,
    ): IssueArticle {
        if ($article->issues()->where('issues.id', '!=', $issue->id)->exists()) {
            throw new InvalidArgumentException('That article is already assigned to another issue.');
        }

        if (! $article->status->canBeScheduledForPublication() && $article->status !== ArticleStatus::Published) {
            throw new InvalidArgumentException('Only accepted articles can be placed in an issue.');
        }

        return DB::transaction(function () use ($issue, $article, $actor, $articleNumber, $pageStart, $pageEnd, $sortOrder): IssueArticle {
            $existing = $issue->issueArticles()->where('article_id', $article->id)->first();
            $sortOrder ??= (int) $issue->issueArticles()->max('sort_order') + 1;

            if ($existing) {
                $existing->update([
                    'sort_order' => $sortOrder,
                    'article_number' => $articleNumber ?? $existing->article_number,
                ]);
                $placement = $existing;
            } else {
                $placement = $issue->issueArticles()->create([
                    'article_id' => $article->id,
                    'sort_order' => $sortOrder,
                    'article_number' => $articleNumber,
                ]);
            }

            $article->update([
                'issue_id' => $issue->id,
                'page_start' => $pageStart ?? $article->page_start,
                'page_end' => $pageEnd ?? $article->page_end,
            ]);

            if ($issue->isPublished()) {
                $this->publishArticle($article, $issue, $actor, $issue->published_at ?? now());
            } elseif ($article->status->canTransitionTo(ArticleStatus::Scheduled)) {
                $article->moveTo(ArticleStatus::Scheduled, $actor, 'Scheduled for '.$issue->displayLabel());
            }

            return $placement->fresh();
        });
    }

    public function remove(Issue $issue, Article $article, User $actor): void
    {
        if ($issue->isPublished()) {
            throw new InvalidArgumentException('Unpublish the issue before removing a published article.');
        }

        DB::transaction(function () use ($issue, $article, $actor): void {
            $issue->issueArticles()->where('article_id', $article->id)->delete();

            if ($article->status === ArticleStatus::Scheduled && $article->status->canTransitionTo(ArticleStatus::Accepted)) {
                $article->moveTo(ArticleStatus::Accepted, $actor, 'Removed from '.$issue->displayLabel().'.');
            }
        });
    }

    /**
     * @param  array<int, int|string>  $order
     */
    public function reorder(Issue $issue, array $order): void
    {
        foreach ($order as $articleId => $sortOrder) {
            $issue->issueArticles()
                ->where('article_id', (int) $articleId)
                ->update(['sort_order' => (int) $sortOrder]);
        }
    }

    public function publish(Issue $issue, User $actor): void
    {
        if ($issue->issueArticles()->doesntExist()) {
            throw new InvalidArgumentException('Add at least one accepted article before publishing this issue.');
        }

        $publishedAt = $issue->published_at ?? now();

        DB::transaction(function () use ($issue, $actor, $publishedAt): void {
            $issue->update([
                'status' => IssueStatus::Published,
                'published_at' => $publishedAt,
            ]);

            $issue->markAsCurrent();

            $issue->articles()->orderByPivot('sort_order')->get()->each(function (Article $article) use ($issue, $actor, $publishedAt): void {
                $this->publishArticle($article, $issue, $actor, $publishedAt);
            });
        });

        app(TableOfContentsMailer::class)->sendForIssue($issue->fresh() ?? $issue);
    }

    public function unpublish(Issue $issue, User $actor): void
    {
        DB::transaction(function () use ($issue, $actor): void {
            $issue->articles->each(function (Article $article) use ($issue, $actor): void {
                $this->hidePublicFiles($article);

                if ($article->status === ArticleStatus::Published && $article->status->canTransitionTo(ArticleStatus::Scheduled)) {
                    $article->moveTo(ArticleStatus::Scheduled, $actor, 'Issue '.$issue->displayLabel().' unpublished.', [
                        'published_at' => null,
                    ]);
                }
            });

            $issue->update([
                'status' => IssueStatus::Draft,
            ]);
        });
    }

    public function publishArticle(Article $article, Issue $issue, User $actor, mixed $publishedAt): void
    {
        $article->refresh();

        $alreadyPublished = $article->status === ArticleStatus::Published;

        if ($article->status !== ArticleStatus::Published) {
            if ($article->status->canTransitionTo(ArticleStatus::Scheduled) && $article->status !== ArticleStatus::Scheduled) {
                $article->moveTo(ArticleStatus::Scheduled, $actor);
                $article->refresh();
            }

            if (! $article->status->canTransitionTo(ArticleStatus::Published)) {
                throw new InvalidArgumentException('“'.$article->title.'” cannot be published from its current status.');
            }

            $article->moveTo(ArticleStatus::Published, $actor, 'Published in '.$issue->displayLabel().'.', [
                'published_at' => $publishedAt,
                'issue_id' => $issue->id,
                'is_demo' => false,
            ]);
        } elseif ($article->published_at === null) {
            $article->update([
                'published_at' => $publishedAt,
                'issue_id' => $issue->id,
            ]);
        } else {
            $article->update(['issue_id' => $issue->id]);
        }

        $article = $article->fresh(['correspondingAuthor']);
        $this->exposePublicPdf($article);
        $article->mintDoi();

        if (! $alreadyPublished && $article->status === ArticleStatus::Published && $article->correspondingAuthor) {
            Notifier::notify(
                $article->correspondingAuthor,
                new ArticlePublishedNotification($article, $article->publicUrl())
            );
        }
    }

    public function exposePublicPdf(Article $article): void
    {
        $file = $article->files()
            ->where('type', ArticleFileType::Manuscript)
            ->latest('id')
            ->get()
            ->first(function (ArticleFile $file): bool {
                $name = strtolower($file->original_filename);

                return $file->mime_type === 'application/pdf' || str_ends_with($name, '.pdf');
            });

        if ($file === null) {
            return;
        }

        $article->files()
            ->where('type', ArticleFileType::Manuscript)
            ->where('id', '!=', $file->id)
            ->update(['is_public' => false]);

        $file->update(['is_public' => true]);
    }

    public function hidePublicFiles(Article $article): void
    {
        $article->files()->update(['is_public' => false]);
    }
}
