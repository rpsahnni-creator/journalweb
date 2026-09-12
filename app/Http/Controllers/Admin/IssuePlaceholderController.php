<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Support\CurrentJournal;
use Illuminate\View\View;

class IssuePlaceholderController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Issue::class);

        $journal = CurrentJournal::managed();

        $issues = Issue::query()
            ->with('volume')
            ->when($journal, fn ($query) => $query->where('journal_id', $journal->id))
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('admin.issues.index', [
            'issues' => $issues,
        ]);
    }
}
