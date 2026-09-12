<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Volume;
use App\Support\CurrentJournal;
use Illuminate\View\View;

class VolumePlaceholderController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', Volume::class);

        $journal = CurrentJournal::managed();

        $volumes = Volume::query()
            ->when($journal, fn ($query) => $query->where('journal_id', $journal->id))
            ->withCount('issues')
            ->orderByDesc('year')
            ->orderBy('number')
            ->paginate(15);

        return view('admin.volumes.index', [
            'volumes' => $volumes,
        ]);
    }
}
