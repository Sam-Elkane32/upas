<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            // Clear any intended URL to ensure users always go to dashboard first
            $request->session()->forget('url.intended');
            
            $user = $request->user();
            if ($user->isSuperAdmin()) {
                return redirect()->route('super-admin.dashboard');
            } elseif ($user->isAdmin()) {
                return redirect()->route('campus-admin.dashboard');
            } elseif ($user->isViewOnly()) {
                return redirect()->route('view-only.dashboard');
            } elseif ($user->hasRole('creator_editor')) {
                return redirect()->route('campus-user.dashboard');
            }
            return redirect()->route('dashboard');
        }
        return view('auth.verify-email');
    }
}
