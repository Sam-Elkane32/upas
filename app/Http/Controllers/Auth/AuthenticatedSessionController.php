<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Clear any intended URL to ensure users always go to dashboard first
        $request->session()->forget('url.intended');

        // Flash flag so welcome pop-up shows only on the immediate next request (dashboard after login), not when navigating back later
        $request->session()->flash('show_welcome_popup', true);

        // Redirect based on user role - always to dashboard
        $user = Auth::user();
        if ($user->isDeveloper()) {
            return redirect()->route('messaging.index');
        }
        if ($user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        } elseif ($user->isAdmin()) {
            return redirect()->route('campus-admin.dashboard');
        } elseif ($user->isViewOnly()) {
            return redirect()->route('view-only.dashboard');
        } elseif ($user->isPlanningCoordinator()) {
            return redirect()->route('campus-user.dashboard');
        } elseif ($user->hasRole('creator_editor')) {
            return redirect()->route('campus-user.dashboard');
        }

        // Fallback to generic dashboard (will redirect to role-specific dashboard)
        return redirect()->route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
