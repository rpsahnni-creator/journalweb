<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Http\Requests\Author\UpdateAuthorProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorize('access-author');

        $user = $request->user();
        $this->authorize('update', $user);

        return view('author.profile', [
            'user' => $user,
        ]);
    }

    public function update(UpdateAuthorProfileRequest $request): RedirectResponse
    {
        $this->authorize('access-author');

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

        return redirect()->route('author.profile.edit')->with('status', 'Author profile updated.');
    }
}
