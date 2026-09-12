<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user')
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('action', 'like', '%'.$search.'%')
                        ->orWhere('auditable_type', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('action')->toString(), fn ($query, string $action) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['q', 'action']),
        ]);
    }
}
