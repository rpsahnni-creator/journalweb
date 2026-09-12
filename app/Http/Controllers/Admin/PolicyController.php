<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JournalPolicyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJournalPolicyRequest;
use App\Http\Requests\Admin\UpdateJournalPolicyRequest;
use App\Models\JournalPolicy;
use App\Support\Auditor;
use App\Support\CurrentJournal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', JournalPolicy::class);

        $policies = JournalPolicy::query()
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('type')->toString(), fn ($query, string $type) => $query->where('type', $type))
            ->when($request->string('status')->toString(), function ($query, string $status): void {
                if ($status === 'published') {
                    $query->where('is_published', true);
                }
                if ($status === 'draft') {
                    $query->where('is_published', false);
                }
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return view('admin.policies.index', [
            'policies' => $policies,
            'types' => JournalPolicyType::cases(),
            'filters' => $request->only(['q', 'type', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', JournalPolicy::class);

        return view('admin.policies.create', [
            'types' => JournalPolicyType::cases(),
        ]);
    }

    public function store(StoreJournalPolicyRequest $request): RedirectResponse
    {
        $journal = CurrentJournal::managed();

        if ($journal === null) {
            return redirect()->route('admin.journal.edit')->with('error', 'Create the journal before adding policies.');
        }

        $policy = JournalPolicy::query()->create([
            ...$request->safe()->except(['is_published']),
            'journal_id' => $journal->id,
            'slug' => Str::slug($request->string('slug')->toString()),
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
        ]);

        Auditor::log('created', $policy, null, $policy->only(['title', 'type', 'is_published']), $journal->id);

        return redirect()->route('admin.policies.index')->with('status', 'Policy created.');
    }

    public function edit(JournalPolicy $policy): View
    {
        $this->authorize('update', $policy);

        return view('admin.policies.edit', [
            'policy' => $policy,
            'types' => JournalPolicyType::cases(),
        ]);
    }

    public function update(UpdateJournalPolicyRequest $request, JournalPolicy $policy): RedirectResponse
    {
        $old = $policy->only(['title', 'type', 'is_published']);
        $published = $request->boolean('is_published');

        $policy->update([
            ...$request->safe()->except(['is_published']),
            'slug' => Str::slug($request->string('slug')->toString()),
            'is_published' => $published,
            'published_at' => $published ? ($policy->published_at ?? now()) : null,
        ]);

        Auditor::log('updated', $policy->fresh(), $old, $policy->only(['title', 'type', 'is_published']), $policy->journal_id);

        return redirect()->route('admin.policies.index')->with('status', 'Policy updated.');
    }

    public function destroy(JournalPolicy $policy): RedirectResponse
    {
        $this->authorize('delete', $policy);

        $old = $policy->only(['title', 'type']);
        $policy->delete();

        Auditor::log('deleted', $policy, $old, null, $policy->journal_id);

        return redirect()->route('admin.policies.index')->with('status', 'Policy deleted.');
    }
}
