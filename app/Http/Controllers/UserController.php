<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Campus;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->canManageUsers()) {
                abort(403, 'Unauthorized access.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of users (grouped by campus for compact view)
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $campuses = Campus::where('is_active', true)->orderBy('name')->get();
            $divisionUsers = User::with(['departmentInfo'])
                ->where('role', User::ROLE_VIEW_ONLY)
                ->where(function ($q) {
                    $q->where('position', 'Division CED')
                      ->orWhere('campus_code', 'DIVISION')
                      ->orWhere('email', 'like', 'division.%@psu.edu.ph');
                })
                ->orderBy('name')
                ->get();

            $developerUsers = User::where('role', User::ROLE_DEVELOPER)
                ->orderBy('name')
                ->get();

            $allUsers = User::with(['campusInfo'])
                ->where('role', '!=', 'super_admin')
                ->where('role', '!=', User::ROLE_DEVELOPER)
                ->whereNotIn('id', $divisionUsers->pluck('id'))
                ->orderBy('campus_code')
                ->get();
            $superAdmins = User::where('role', 'super_admin')->orderBy('name')->get();
            $usersByCampus = $allUsers->groupBy('campus_code');
        } else {
            $campuses = Campus::where('code', $user->campus_code)->get();
            $divisionUsers = collect();
            $developerUsers = collect();
            $allUsers = User::where('campus_code', $user->campus_code)
                ->where('role', '!=', 'super_admin')
                ->where('role', '!=', User::ROLE_DEVELOPER)
                ->with(['campusInfo'])
                ->orderBy('name')
                ->get();
            $superAdmins = collect();
            $usersByCampus = $allUsers->groupBy('campus_code');
        }

        return view('users.index', compact('campuses', 'usersByCampus', 'superAdmins', 'divisionUsers', 'developerUsers'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            $campuses = Campus::where('is_active', true)->get();
        } else {
            $campuses = Campus::where('code', $user->campus_code)->get();
        }

        $campusCodeMap = [];
        foreach ($campuses as $campus) {
            $campusCodeMap[$campus->code] = $this->sanitizeCampusCode($campus->code ?? $campus->name);
        }

        return view('users.create', compact('campuses', 'campusCodeMap'));
    }

    /**
     * Sanitize campus code: uppercase, alphanumeric and underscores only.
     */
    private function sanitizeCampusCode(string $value): string
    {
        $s = preg_replace('/[^A-Za-z0-9]/', '_', trim($value));
        $s = strtoupper($s);
        return preg_replace('/_+/', '_', trim($s, '_')) ?: $value;
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'campus_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|in:admin,creator_editor,planning_coordinator,view_only',
        ]);

        // Role must be consistent with username (server-side safety)
        $name = trim($request->name);
        $role = $request->role;
        if (str_ends_with($name, 'Planning Coordinator')) {
            if (!in_array($role, ['creator_editor', 'planning_coordinator'], true)) {
                return redirect()->back()->withErrors(['role' => 'Role must be Planning Coordinator for this User Name.'])->withInput();
            }
        } elseif (str_ends_with($name, 'QA Coordinator')) {
            if ($role !== 'admin') {
                return redirect()->back()->withErrors(['role' => 'Role must be QA Coordinator for this User Name.'])->withInput();
            }
        } elseif (str_ends_with($name, 'CED')) {
            if ($role !== 'view_only') {
                return redirect()->back()->withErrors(['role' => 'Role must be View Only for this User Name.'])->withInput();
            }
        } else {
            return redirect()->back()->withErrors(['name' => 'User Name must be one of the generated options (e.g. CAMPUS Planning Coordinator, CAMPUS QA Coordinator, or CAMPUS CED).'])->withInput();
        }

        // QA Coordinator can only create users for their campus
        if (!$user->isSuperAdmin() && $request->campus_code !== $user->campus_code) {
            abort(403, 'You can only create users for your campus.');
        }

        // Prevent duplicate: same name (username) per campus
        $exists = User::where('campus_code', $request->campus_code)->where('name', $name)->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'A user with this User Name already exists for the selected campus.'])->withInput();
        }

        $campusName = null;
        if ($request->campus_code) {
            $campusName = Campus::where('code', $request->campus_code)->first()->name ?? $request->campus_code;
        }

        $defaultPassword = Setting::get('default_password', 'UPAS@2025!');

        do {
            $employeeId = 'EMP-' . date('Ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::where('employee_id', $employeeId)->exists());

        if (str_ends_with($name, 'QA Coordinator')) {
            $positionForDb = 'QA Coordinator';
        } elseif (str_ends_with($name, 'CED')) {
            $positionForDb = 'Campus CED';
        } else {
            $positionForDb = 'Planning Coordinator';
        }

        $newUser = User::create([
            'name' => $name,
            'email' => $request->email,
            'password' => Hash::make($defaultPassword),
            'employee_id' => $employeeId,
            'position' => $positionForDb,
            'role' => $role,
            'campus_code' => $request->campus_code,
            'campus' => $campusName,
            'is_active' => true,
            'is_approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        // Note: the UserObserver automatically creates a Super Admin ↔ new user
        // conversation via app/Observers/UserObserver.php — no manual call needed here.

        $routeName = $user->isSuperAdmin() ? 'super-admin.users' : 'campus-admin.users';
        return redirect()->route($routeName)
            ->with('success', "User created successfully. They can now login with the default password: {$defaultPassword}");
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $currentUser = Auth::user();
        
        // Prevent QA Coordinator from viewing super admin users
        if (!$currentUser->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'You do not have permission to view this user.');
        }
        
        // Check if user can view this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only view users from your campus.');
        }
        
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $currentUser = Auth::user();
        
        // Prevent QA Coordinator from editing super admin users
        if (!$currentUser->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'You do not have permission to edit this user.');
        }
        
        // Check if user can edit this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only edit users from your campus.');
        }
        
        if ($currentUser->isSuperAdmin()) {
            $campuses = Campus::where('is_active', true)->get();
        } else {
            $campuses = Campus::where('code', $currentUser->campus_code)->get();
        }

        $campusCodeMap = [];
        foreach ($campuses as $campus) {
            $campusCodeMap[$campus->code] = $this->sanitizeCampusCode($campus->code ?? $campus->name);
        }

        return view('users.edit', compact('user', 'campuses', 'campusCodeMap'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();
        
        // Prevent QA Coordinator from updating super admin users
        if (!$currentUser->isSuperAdmin() && $user->isSuperAdmin()) {
            abort(403, 'You do not have permission to update this user.');
        }
        
        // Check if user can edit this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only edit users from your campus.');
        }
        
        $request->validate([
            'campus_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,creator_editor,planning_coordinator,super_admin,view_only',
            'is_active' => 'boolean',
        ]);

        $name = trim($request->name);
        $role = $request->role;

        if (!$user->isSuperAdmin()) {
            if (str_ends_with($name, 'Planning Coordinator')) {
                if (!in_array($role, ['creator_editor', 'planning_coordinator'], true)) {
                    return redirect()->back()->withErrors(['role' => 'Role must be Planning Coordinator for this User Name.'])->withInput();
                }
            } elseif (str_ends_with($name, 'QA Coordinator')) {
                if ($role !== 'admin') {
                    return redirect()->back()->withErrors(['role' => 'Role must be QA Coordinator for this User Name.'])->withInput();
                }
            } elseif (str_ends_with($name, 'CED')) {
                if ($role !== 'view_only') {
                    return redirect()->back()->withErrors(['role' => 'Role must be View Only for this User Name.'])->withInput();
                }
            } else {
                return redirect()->back()->withErrors(['name' => 'User Name must be one of the generated options (e.g. CAMPUS Planning Coordinator, CAMPUS QA Coordinator, or CAMPUS CED).'])->withInput();
            }
        }

        $exists = User::where('campus_code', $request->campus_code)->where('name', $name)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            return redirect()->back()->withErrors(['name' => 'A user with this User Name already exists for the selected campus.'])->withInput();
        }

        $campusName = Campus::where('code', $request->campus_code)->first()->name ?? $request->campus_code;
        if ($role === 'admin' || str_ends_with($name, 'QA Coordinator')) {
            $positionForDb = 'QA Coordinator';
        } elseif (str_ends_with($name, 'CED')) {
            $positionForDb = 'Campus CED';
        } else {
            $positionForDb = 'Planning Coordinator';
        }
        if ($user->isSuperAdmin()) {
            $positionForDb = $user->position ?? '';
        }

        $user->update([
            'name' => $name,
            'email' => $request->email,
            'position' => $positionForDb,
            'role' => $role,
            'campus_code' => $request->campus_code,
            'campus' => $campusName,
            'is_active' => $request->has('is_active'),
        ]);

        $routeName = $currentUser->isSuperAdmin() ? 'super-admin.users' : 'campus-admin.users';
        return redirect()->route($routeName)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        $currentUser = Auth::user();
        
        // Check if user can delete this user
        if (!$currentUser->isSuperAdmin() && $user->campus_code !== $currentUser->campus_code) {
            abort(403, 'You can only delete users from your campus.');
        }
        
        // Prevent deleting super admin
        if ($user->isSuperAdmin()) {
            $routeName = $currentUser->isSuperAdmin() ? 'super-admin.users' : 'campus-admin.users';
            return redirect()->route($routeName)
                ->with('error', 'Cannot delete super admin user.');
        }
        
        $user->delete();

        $routeName = $currentUser->isSuperAdmin() ? 'super-admin.users' : 'campus-admin.users';
        return redirect()->route($routeName)
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Store a custom position (Super Admin only)
     */
    public function storePosition(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only Super Admin can add custom positions.'
            ], 403);
        }
        
        $request->validate([
            'position' => 'required|string|max:255|min:1',
        ]);
        
        $newPosition = trim($request->position);
        
        // Get existing custom positions
        $customPositions = Setting::get('custom_positions', []);
        if (!is_array($customPositions)) {
            $customPositions = [];
        }
        
        // Check if position already exists (predefined or custom)
        $predefinedPositions = ['Planning Coordinator', 'QA Coordinator'];
        if (in_array($newPosition, $predefinedPositions) || in_array($newPosition, $customPositions)) {
            return response()->json([
                'success' => false,
                'message' => 'This position already exists.'
            ], 400);
        }
        
        // Add new position
        $customPositions[] = $newPosition;
        $customPositions = array_unique($customPositions); // Remove duplicates
        sort($customPositions); // Sort alphabetically
        
        // Save to settings
        $saved = Setting::set('custom_positions', $customPositions, 'json', 'Custom user positions added by Super Admin');
        
        if ($saved) {
            return response()->json([
                'success' => true,
                'message' => 'Position added successfully.',
                'position' => $newPosition
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save position. Please try again.'
            ], 500);
        }
    }
}