<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Check if user has the required role
        if (!$this->userHasRole($user, $role)) {
            abort(403, 'Access Denied: You do not have permission to view this page.');
        }

        return $next($request);
    }

    /**
     * Check if user has the required role
     */
    private function userHasRole($user, $role): bool
    {
        // Handle multiple roles separated by pipe (e.g., "creator_editor|planning_coordinator")
        if (strpos($role, '|') !== false) {
            $roles = explode('|', $role);
            foreach ($roles as $r) {
                if ($this->checkSingleRole($user, trim($r))) {
                    return true;
                }
            }
            return false;
        }
        
        return $this->checkSingleRole($user, $role);
    }
    
    /**
     * Check if user has a single role
     */
    private function checkSingleRole($user, $role): bool
    {
        switch ($role) {
            case 'super_admin':
                return $user->isSuperAdmin();
            case 'admin':
                return $user->isAdmin();
            case 'creator_editor':
                return $user->isCreatorEditor();
            case 'view_only':
                return $user->isViewOnly();
            case 'planning_coordinator':
                return $user->isPlanningCoordinator();
            case 'developer':
                return $user->isDeveloper();
            default:
                return false;
        }
    }
}
