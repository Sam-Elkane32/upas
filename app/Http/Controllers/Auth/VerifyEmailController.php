<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        // Clear any intended URL to ensure users always go to dashboard first
        $request->session()->forget('url.intended');

        if ($request->user()->hasVerifiedEmail()) {
            $user = $request->user()->fresh();
            if ($user->isSuperAdmin()) {
                return redirect()->route('super-admin.dashboard')->with('verified', true);
            } elseif ($user->isAdmin()) {
                return redirect()->route('campus-admin.dashboard')->with('verified', true);
            } elseif ($user->isViewOnly()) {
                return redirect()->route('view-only.dashboard')->with('verified', true);
            } elseif ($user->isPlanningCoordinator()) {
                return redirect()->route('campus-user.dashboard')->with('verified', true);
            }
            return redirect()->route('dashboard')->with('verified', true);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        $user = $request->user()->fresh();
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard')->with('verified', true);
        } elseif ($user->isAdmin()) {
            return redirect()->route('campus-admin.dashboard')->with('verified', true);
        } elseif ($user->isViewOnly()) {
            return redirect()->route('view-only.dashboard')->with('verified', true);
        } elseif ($user->isPlanningCoordinator()) {
            return redirect()->route('campus-user.dashboard')->with('verified', true);
        }
        return redirect()->route('dashboard')->with('verified', true);
    }
}
