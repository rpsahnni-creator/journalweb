<?php

namespace App\Http\Controllers\Author;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('access-author');

        $user = $request->user();

        $statusCounts = Article::query()
            ->forAuthor($user)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = function (ArticleStatus ...$statuses) use ($statusCounts): int {
            return collect($statuses)->sum(
                fn (ArticleStatus $status): int => (int) $statusCounts->get($status->value, 0)
            );
        };

        $manuscripts = Article::query()
            ->forAuthor($user)
            ->withCount('files')
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('submission_number', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('status')->toString(), function ($query, string $status): void {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('author.dashboard', [
            'user' => $user,
            'manuscripts' => $manuscripts,
            'filters' => $request->only(['q', 'status']),
            'statuses' => ArticleStatus::cases(),
            'totalCount' => (int) $statusCounts->sum(),
            'draftCount' => $countFor(ArticleStatus::Draft),
            'inReviewCount' => $countFor(
                ArticleStatus::Submitted,
                ArticleStatus::InitialScreening,
                ArticleStatus::UnderReview,
                ArticleStatus::Resubmitted,
            ),
            'revisionCount' => $countFor(ArticleStatus::RevisionRequired),
            'acceptedCount' => $countFor(
                ArticleStatus::Accepted,
                ArticleStatus::Copyediting,
                ArticleStatus::Scheduled,
            ),
            'publishedCount' => $countFor(ArticleStatus::Published),
            'statusChart' => $this->statusChart($statusCounts),
        ]);
    }

    /**
     * @param  Collection<string, int|string>  $statusCounts
     * @return Collection<int, array{label: string, count: int}>
     */
    private function statusChart(Collection $statusCounts): Collection
    {
        return collect(ArticleStatus::cases())
            ->map(fn (ArticleStatus $status): array => [
                'label' => $status->label(),
                'count' => (int) $statusCounts->get($status->value, 0),
            ])
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values();
    }
}
