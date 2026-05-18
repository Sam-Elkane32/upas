<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->canApproveForms()) {
                abort(403, 'You do not have permission to approve forms.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of pending approvals
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin can see all pending approvals
            $pendingUsers = User::where('is_approved', false)->with('campusInfo')->latest()->paginate(15);
            $pendingForms = collect([]); // Will be implemented with actual form system
        } else {
            // QA Coordinator can see campus pending approvals
            $pendingUsers = User::where('campus_code', $user->campus_code)
                ->where('is_approved', false)
                ->with('campusInfo')
                ->latest()
                ->paginate(15);
            $pendingForms = collect([]); // Will be implemented with actual form system
        }
        
        return view('approvals.index', compact('pendingUsers', 'pendingForms'));
    }

    /**
     * Approve a user
     */
    public function approveUser(User $user)
    {
        $currentUser = Auth::user();
        
        // Check if user can approve this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only approve users from your campus.');
        }
        
        $user->update([
            'is_approved' => true,
            'approved_by' => $currentUser->id,
            'approved_at' => now(),
        ]);

        return redirect()->route('approvals.index')
            ->with('success', 'User approved successfully.');
    }

    /**
     * Reject a user
     */
    public function rejectUser(User $user)
    {
        $currentUser = Auth::user();
        
        // Check if user can reject this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only reject users from your campus.');
        }
        
        $user->update([
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->route('approvals.index')
            ->with('success', 'User rejected successfully.');
    }

    /**
     * Approve a form
     */
    public function approveForm(Request $request, $formId)
    {
        $user = Auth::user();
        
        // This will be implemented with actual form system
        // For now, return success message
        
        return redirect()->route('approvals.index')
            ->with('success', 'Form approved successfully.');
    }

    /**
     * Reject a form
     */
    public function rejectForm(Request $request, $formId)
    {
        $user = Auth::user();
        
        // This will be implemented with actual form system
        // For now, return success message
        
        return redirect()->route('approvals.index')
            ->with('success', 'Form rejected successfully.');
    }
}