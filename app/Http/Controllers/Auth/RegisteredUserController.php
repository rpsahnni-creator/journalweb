<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'affiliation' => $request->input('affiliation'),
            'password' => $request->string('password')->toString(),
            'is_active' => true,
        ]);

        $user->assignRole(RoleSlug::Reader);
        $user->assignRole(RoleSlug::Author);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
