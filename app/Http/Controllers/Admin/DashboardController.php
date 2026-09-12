<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\EditorialBoardMember;
use App\Models\Issue;
use App\Models\JournalPolicy;
use App\Models\Role;
use App\Models\User;
use App\Models\Volume;
use App\Support\CurrentJournal;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('access-admin');

        $journal = CurrentJournal::managed();

        $roles = Role::query()->withCount('users')->orderBy('name')->get();

        return view('admin.dashboard', [
            'journal' => $journal,
            'userCount' => User::query()->count(),
            'activeUserCount' => User::query()->active()->count(),
            'boardCount' => EditorialBoardMember::query()->count(),
            'policyCount' => JournalPolicy::query()->count(),
            'volumeCount' => Volume::query()->count(),
            'issueCount' => Issue::query()->count(),
            'publishedArticleCount' => Article::query()->published()->count(),
            'contactCount' => ContactMessage::query()->count(),
            'roles' => $roles,
            'roleChart' => $roles->map(fn (Role $role): array => [
                'label' => $role->name,
                'count' => $role->users_count,
            ])->values(),
            'recentLogs' => AuditLog::query()->with('user')->latest('created_at')->limit(8)->get(),
        ]);
    }
}
