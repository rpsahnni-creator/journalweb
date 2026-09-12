<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEditorialBoardMemberRequest;
use App\Http\Requests\Admin\UpdateEditorialBoardMemberRequest;
use App\Models\EditorialBoardMember;
use App\Models\User;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditorialBoardMemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EditorialBoardMember::class);

        $members = EditorialBoardMember::query()
            ->with('user')
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('role_title', 'like', '%'.$search.'%')
                        ->orWhere('affiliation', 'like', '%'.$search.'%')
                        ->orWhere('department', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('visibility')->toString(), function ($query, string $visibility): void {
                if ($visibility === 'public') {
                    $query->where('is_public', true);
                }
                if ($visibility === 'private') {
                    $query->where('is_public', false);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $boardQuery = EditorialBoardMember::query()
            ->when(CurrentJournal::managed()?->id, fn ($query, $journalId) => $query->where('journal_id', $journalId));

        return view('admin.editorial-board.index', [
            'members' => $members,
            'filters' => $request->only(['q', 'visibility']),
            'pendingNameCount' => (clone $boardQuery)->get()->filter(fn (EditorialBoardMember $member): bool => $member->needsRealName())->count(),
            'boardMemberCount' => (clone $boardQuery)->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EditorialBoardMember::class);

        return view('admin.editorial-board.create', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(StoreEditorialBoardMemberRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return redirect()->route('admin.journal.edit')->with('error', 'Create the journal before adding board members.');
        }

        $member = EditorialBoardMember::query()->create([
            ...$request->safe()->except(['is_public', 'is_active', 'sort_order']),
            'journal_id' => $journal->id,
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_public' => $request->boolean('is_public'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Auditor::log('created', $member, null, $member->only(['name', 'role_title', 'is_public']), $journal->id);

        return redirect()->route('admin.editorial-board.index')->with('status', 'Editorial board member added.');
    }

    public function edit(EditorialBoardMember $member): View
    {
        $this->authorize('update', $member);

        return view('admin.editorial-board.edit', [
            'member' => $member,
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(UpdateEditorialBoardMemberRequest $request, EditorialBoardMember $member): RedirectResponse
    {
        $old = $member->only(['name', 'role_title', 'is_public', 'is_active']);
        $member->update([
            ...$request->safe()->except(['is_public', 'is_active', 'sort_order']),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_public' => $request->boolean('is_public'),
            'is_active' => $request->boolean('is_active'),
        ]);

        Auditor::log('updated', $member->fresh(), $old, $member->only(['name', 'role_title', 'is_public', 'is_active']), $member->journal_id);

        return redirect()->route('admin.editorial-board.index')->with('status', 'Editorial board member updated.');
    }

    public function destroy(EditorialBoardMember $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $old = $member->only(['name', 'role_title']);
        $member->delete();

        Auditor::log('deleted', $member, $old, null, $member->journal_id);

        return redirect()->route('admin.editorial-board.index')->with('status', 'Editorial board member removed.');
    }
}
