<?php

namespace App\Http\Controllers\Editorial;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\JournalNotification;
use App\Models\ReviewerAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('access-editorial');

        $queue = Article::query()->whereIn('status', [
            ArticleStatus::Submitted,
            ArticleStatus::InitialScreening,
            ArticleStatus::UnderReview,
            ArticleStatus::Resubmitted,
        ]);

        return view('editorial.dashboard', [
            'user' => $request->user(),
            'submittedCount' => Article::query()->where('status', ArticleStatus::Submitted)->count(),
            'screeningCount' => Article::query()->where('status', ArticleStatus::InitialScreening)->count(),
            'underReviewCount' => Article::query()->where('status', ArticleStatus::UnderReview)->count(),
            'queueCount' => (clone $queue)->count(),
            'revisionRequiredCount' => Article::query()->where('status', ArticleStatus::RevisionRequired)->count(),
            'pendingInvitations' => ReviewerAssignment::query()->where('status', 'invited')->count(),
            'notifications' => JournalNotification::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->limit(8)
                ->get(),
            'recentQueue' => $queue->with('correspondingAuthor')->latest('submitted_at')->limit(8)->get(),
        ]);
    }
}
