<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Developer accounts are for beta feedback via Messages only.
 * Any other authenticated route redirects to the messaging inbox.
 */
class RestrictDeveloperAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || ! $user->isDeveloper()) {
            return $next($request);
        }

        if ($request->is([
            'messaging',
            'messaging/*',
            'developer',
            'developer/*',
            'session',
            'session/*',
            'logout',
            'password',
            'confirm-password',
            'verify-email',
            'verify-email/*',
            'email/verification-notification',
        ])) {
            return $next($request);
        }

        return redirect()->route('messaging.index');
    }
}
