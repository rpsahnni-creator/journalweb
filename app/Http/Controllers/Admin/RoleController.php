<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.roles.index', [
            'roles' => $roles,
            'filters' => $request->only(['q']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::query()->create($request->safe()->except('permission_ids'));
        $role->permissions()->sync($request->input('permission_ids', []));

        Auditor::log('created', $role, null, $role->only(['name', 'slug']));

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $old = $role->only(['name', 'slug', 'description']);
        $role->update($request->safe()->except('permission_ids'));
        $role->permissions()->sync($request->input('permission_ids', []));

        Auditor::log('updated', $role->fresh(), $old, $role->only(['name', 'slug', 'description']));

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        if (RoleSlug::tryFrom($role->slug) !== null) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'A role assigned to users cannot be deleted.');
        }

        $old = $role->only(['name', 'slug']);
        $role->delete();

        Auditor::log('deleted', $role, $old);

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }
}
