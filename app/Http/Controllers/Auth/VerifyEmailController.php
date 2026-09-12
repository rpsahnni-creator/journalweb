<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SafeRedirect;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        $fallback = route($user->dashboardRoute()).'?verified=1';

        if ($user->hasVerifiedEmail()) {
            return SafeRedirect::intended($request, $fallback);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return SafeRedirect::intended($request, $fallback);
    }
}
