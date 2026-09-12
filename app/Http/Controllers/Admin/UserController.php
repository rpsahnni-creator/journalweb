<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with('roles')
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->string('role')->toString(), function ($query, string $role): void {
                $query->whereHas('roles', fn ($query) => $query->where('slug', $role));
            })
            ->when($request->string('status')->toString(), function ($query, string $status): void {
                if ($status === 'active') {
                    $query->where('is_active', true);
                }
                if ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'filters' => $request->only(['q', 'role', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['role_ids', 'password_confirmation']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_editor'] = $request->boolean('is_editor');
        $data['is_reviewer'] = $request->boolean('is_reviewer');

        $user = User::query()->create($data);
        $user->markEmailAsVerified();
        $user->syncRoles($request->input('role_ids', []));

        Auditor::log('created', $user, null, $user->only(['name', 'email', 'is_active']));

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user) && ! $request->boolean('is_active')) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        if (! $request->boolean('is_active') && $this->isLastAdmin($user)) {
            return back()->with('error', 'The last administrator cannot be deactivated.');
        }

        if ($this->wouldRemoveLastAdmin($user, $request->input('role_ids', []))) {
            return back()->with('error', 'The last administrator role cannot be removed.');
        }

        $old = $user->only(['name', 'email', 'is_active']);
        $data = $request->safe()->except(['role_ids', 'password', 'password_confirmation']);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_editor'] = $request->boolean('is_editor');
        $data['is_reviewer'] = $request->boolean('is_reviewer');

        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }

        $user->update($data);
        $user->syncRoles($request->input('role_ids', []));

        Auditor::log('updated', $user->fresh(), $old, $user->only(['name', 'email', 'is_active']));

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($request->user()?->is($user)) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($this->isLastAdmin($user)) {
            return back()->with('error', 'The last administrator cannot be deleted.');
        }

        $old = $user->only(['name', 'email']);
        $user->delete();

        Auditor::log('deleted', $user, $old);

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    private function isLastAdmin(User $user): bool
    {
        if (! $user->hasRole(RoleSlug::Admin) || ! $user->is_active) {
            return false;
        }

        return User::query()
            ->active()
            ->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Admin->value))
            ->count() <= 1;
    }

    /**
     * @param  list<int|string>  $roleIds
     */
    private function wouldRemoveLastAdmin(User $user, array $roleIds): bool
    {
        if (! $this->isLastAdmin($user)) {
            return false;
        }

        $adminRoleId = Role::query()->where('slug', RoleSlug::Admin->value)->value('id');

        return $adminRoleId !== null && ! in_array((int) $adminRoleId, array_map('intval', $roleIds), true);
    }
}
