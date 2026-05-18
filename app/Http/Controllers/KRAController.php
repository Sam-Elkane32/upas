<?php

namespace App\Http\Controllers;

use App\Models\KRA;
use App\Models\StrategicGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KRAController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|super_admin']);
    }

    /**
     * Display a listing of the KRA.
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            $kras = KRA::with('strategicGoal', 'createdBy')->get();
        } else { // Admin
            $campusCode = $user->campus_code;
            $kras = KRA::whereHas('strategicGoal', function ($query) use ($campusCode) {
                $query->where('campus_code', $campusCode);
            })->with('strategicGoal', 'createdBy')->get();
        }
        return view('kras.index', compact('kras'));
    }

    /**
     * Show the form for creating a new KRA.
     */
    public function create()
    {
        $user = Auth::user();
        if ($user->isSuperAdmin()) {
            $strategicGoals = StrategicGoal::all();
        } else { // Admin
            $strategicGoals = StrategicGoal::where('campus_code', $user->campus_code)->get();
        }
        return view('kras.create', compact('strategicGoals'));
    }

    /**
     * Store a newly created KRA in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'strategic_goal_id' => 'required|exists:strategic_goals,id',
            'code' => 'required|string|max:255|unique:kras,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $kra = KRA::create([
            'strategic_goal_id' => $request->strategic_goal_id,
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('kras.index')->with('success', 'KRA created successfully.');
    }

    /**
     * Display the specified KRA.
     */
    public function show(KRA $kra)
    {
        // Authorization check
        $user = Auth::user();
        if ($user->isAdmin() && $kra->strategicGoal->campus_code !== $user->campus_code) {
            abort(403, 'Access Denied: You do not have permission to view this KRA.');
        }
        return view('kras.show', compact('kra'));
    }

    /**
     * Show the form for editing the specified KRA.
     */
    public function edit(KRA $kra)
    {
        // Authorization check
        $user = Auth::user();
        if ($user->isAdmin() && $kra->strategicGoal->campus_code !== $user->campus_code) {
            abort(403, 'Access Denied: You do not have permission to edit this KRA.');
        }

        if ($user->isSuperAdmin()) {
            $strategicGoals = StrategicGoal::all();
        } else { // Admin
            $strategicGoals = StrategicGoal::where('campus_code', $user->campus_code)->get();
        }
        return view('kras.edit', compact('kra', 'strategicGoals'));
    }

    /**
     * Update the specified KRA in storage.
     */
    public function update(Request $request, KRA $kra)
    {
        // Authorization check
        $user = Auth::user();
        if ($user->isAdmin() && $kra->strategicGoal->campus_code !== $user->campus_code) {
            abort(403, 'Access Denied: You do not have permission to update this KRA.');
        }

        $request->validate([
            'strategic_goal_id' => 'required|exists:strategic_goals,id',
            'code' => 'required|string|max:255|unique:kras,code,' . $kra->id,
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $kra->update([
            'strategic_goal_id' => $request->strategic_goal_id,
            'code' => $request->code,
            'title' => $request->title,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('kras.index')->with('success', 'KRA updated successfully.');
    }

    /**
     * Remove the specified KRA from storage.
     */
    public function destroy(KRA $kra)
    {
        // Authorization check
        $user = Auth::user();
        if ($user->isAdmin() && $kra->strategicGoal->campus_code !== $user->campus_code) {
            abort(403, 'Access Denied: You do not have permission to delete this KRA.');
        }

        $kra->delete();
        return redirect()->route('kras.index')->with('success', 'KRA deleted successfully.');
    }
}