<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Support\AuthSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $this->authorize('update', $user);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $emailChanged = $user->email !== $request->string('email')->toString();

        $user->fill($request->safe()->only([
            'name',
            'academic_title',
            'affiliation',
            'orcid',
            'biography',
            'email',
        ]));

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $this->authorize('update', $user);

        $user->update([
            'password' => $request->string('password')->toString(),
        ]);

        Auth::logoutOtherDevices($request->string('password')->toString());
        AuthSession::invalidateOthers($user, $request->session()->getId());
        $request->session()->regenerate();

        return back()->with('status', 'Password updated.');
    }
}
