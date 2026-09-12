<?php

namespace App\Http\Controllers\Author;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('access-author');

        $user = $request->user();

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
        ]);
    }
}
