<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Campus;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->isSuperAdmin()) {
                abort(403, 'Only Super Admin can access settings.');
            }
            return $next($request);
        });
    }

    /**
     * Display system settings
     */
    public function index()
    {
        $campuses = Campus::where('is_active', true)->get();
        
        // Get current settings
        $systemName = Setting::get('system_name', 'UPAS - University Planning Accomplishment System');
        $systemEmail = Setting::get('system_email', 'admin@psu.edu.ph');
        $defaultPassword = Setting::get('default_password', 'UPAS@2025!');
        
        return view('settings.index', compact('campuses', 'systemName', 'systemEmail', 'defaultPassword'));
    }

    /**
     * Update system settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'system_name' => 'required|string|max:255',
            'system_email' => 'required|email|max:255',
            'default_password' => 'required|string|min:8',
            'approval_required' => 'boolean',
            'auto_approve_creator_editors' => 'boolean',
        ]);

        try {
            // Save settings
            $saved1 = Setting::set('system_name', $request->system_name, 'string', 'System name');
            $saved2 = Setting::set('system_email', $request->system_email, 'string', 'System email address');
            $saved3 = Setting::set('default_password', $request->default_password, 'string', 'Default password for newly created users');
            
            if (!$saved1 || !$saved2 || !$saved3) {
                \Log::error('Failed to save one or more settings', [
                    'system_name' => $saved1,
                    'system_email' => $saved2,
                    'default_password' => $saved3,
                ]);
                
                return redirect()->route('super-admin.settings.index')
                    ->with('error', 'Failed to save some settings. Please try again.')
                    ->withInput();
            }
            
            \Log::info('Settings updated successfully', [
                'system_name' => $request->system_name,
                'system_email' => $request->system_email,
                'default_password' => '***hidden***',
            ]);
            
            // Verify the settings were saved by reloading from database
            $savedPassword = Setting::get('default_password');
            \Log::info('Verified saved default_password', ['value' => $savedPassword]);
            
            return redirect()->route('super-admin.settings.index')
                ->with('success', 'Settings updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Error updating settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('super-admin.settings.index')
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Manage campus settings
     */
    public function campusSettings()
    {
        $campuses = Campus::all();
        
        return view('settings.campus', compact('campuses'));
    }

    /**
     * Update campus settings
     */
    public function updateCampus(Request $request, Campus $campus)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:campuses,code,' . $campus->id,
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $campus->update([
            'name' => $request->name,
            'code' => $request->code,
            'location' => $request->location,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('super-admin.settings.campus')
            ->with('success', 'Campus updated successfully.');
    }
}