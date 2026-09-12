<?php

namespace App\Http\Controllers\Editorial;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editorial\StoreVolumeRequest;
use App\Http\Requests\Editorial\UpdateVolumeRequest;
use App\Models\Journal;
use App\Models\Volume;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VolumeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Volume::class);

        $journal = $this->journal();

        $volumes = Volume::query()
            ->where('journal_id', $journal->id)
            ->withCount('issues')
            ->orderByDesc('year')
            ->orderBy('number')
            ->paginate(15);

        return view('editorial.volumes.index', [
            'volumes' => $volumes,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Volume::class);

        return view('editorial.volumes.create');
    }

    public function store(StoreVolumeRequest $request): RedirectResponse
    {
        $journal = $this->journal();
        $volume = Volume::query()->create([
            ...$request->safe()->only(['number', 'year', 'title']),
            'journal_id' => $journal->id,
        ]);

        Auditor::log('created', $volume, null, $volume->only(['number', 'year', 'title']), $journal->id);

        return redirect()
            ->route('editorial.volumes.index')
            ->with('status', 'Volume '.$volume->number.' created.');
    }

    public function edit(Volume $volume): View
    {
        $this->authorize('update', $volume);

        return view('editorial.volumes.edit', [
            'volume' => $volume,
        ]);
    }

    public function update(UpdateVolumeRequest $request, Volume $volume): RedirectResponse
    {
        $old = $volume->only(['number', 'year', 'title']);
        $volume->update($request->safe()->only(['number', 'year', 'title']));

        Auditor::log('updated', $volume->fresh(), $old, $volume->only(['number', 'year', 'title']), $volume->journal_id);

        return redirect()
            ->route('editorial.volumes.index')
            ->with('status', 'Volume '.$volume->number.' saved.');
    }

    public function destroy(Volume $volume): RedirectResponse
    {
        $this->authorize('delete', $volume);

        $old = $volume->only(['number', 'year', 'title']);
        $volume->delete();

        Auditor::log('deleted', $volume, $old, null, $volume->journal_id);

        return redirect()
            ->route('editorial.volumes.index')
            ->with('status', 'Volume deleted.');
    }

    private function journal(): Journal
    {
        $journal = CurrentJournal::managed();

        abort_if($journal === null, 404);

        return $journal;
    }
}
