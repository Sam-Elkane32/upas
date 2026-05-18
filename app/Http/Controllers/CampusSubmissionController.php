<?php

namespace App\Http\Controllers;

use App\Models\CampusSubmission;
use App\Models\Campus;
use App\Models\User;
use App\Notifications\SubmissionStatusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampusSubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the form creation page for Planning Coordinators
     */
    public function create()
    {
        $user = Auth::user();
        
        // Only Creator/Editor role can create submissions
        if (!$user->canCreateForms()) {
            abort(403, 'You do not have permission to create submissions.');
        }

        $campus = Campus::where('code', $user->campus_code)->first();
        
        return view('campus-submissions.create', compact('campus'));
    }

    /**
     * Store a new campus submission
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        // Only Creator/Editor role can create submissions
        if (!$user->canCreateForms()) {
            abort(403, 'You do not have permission to create submissions.');
        }

        $request->validate([
            'strategic_goal' => 'required|string|max:500',
            'kra' => 'required|string|max:500',
            'kpi' => 'required|string|max:500',
            'target_value' => 'required|numeric|min:0',
            'actual_value' => 'required|numeric|min:0|lte:target_value',
            'justification' => 'nullable|string|max:2000',
            'supporting_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
            'google_drive_link' => 'nullable|url|max:500',
        ], [
            'actual_value.lte' => 'Actual value cannot exceed target value.',
            'supporting_file.max' => 'File size cannot exceed 10MB.',
        ]);

        $campus = Campus::where('code', $user->campus_code)->first();
        
        if (!$campus) {
            return back()->withErrors(['error' => 'Campus not found.']);
        }

        $filePath = null;
        if ($request->hasFile('supporting_file')) {
            $file = $request->file('supporting_file');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('campus-submissions', $fileName, 'public');
        }

        $submission = CampusSubmission::create([
            'campus_id' => $campus->id,
            'user_id' => $user->id,
            'strategic_goal' => $request->strategic_goal,
            'kra' => $request->kra,
            'kpi' => $request->kpi,
            'target_value' => $request->target_value,
            'actual_value' => $request->actual_value,
            'justification' => $request->justification,
            'file_path' => $filePath,
            'google_drive_link' => $request->google_drive_link,
            'status' => 'pending',
        ]);

        // Send notification to QA Coordinator
        $campusAdmins = User::where('campus_code', $user->campus_code)
            ->where('role', 'admin')
            ->get();
            
        foreach ($campusAdmins as $admin) {
            $admin->notify(new SubmissionStatusNotification($submission, 'submitted'));
        }

        return redirect()->route('campus-submissions.my-submissions')
            ->with('success', 'Submission created successfully and sent for approval.');
    }

    /**
     * Display user's submissions
     */
    public function mySubmissions()
    {
        $user = Auth::user();
        
        if (!$user->canCreateForms()) {
            abort(403, 'You do not have permission to view submissions.');
        }

        $submissions = CampusSubmission::where('user_id', $user->id)
            ->with(['campus', 'approver'])
            ->latest()
            ->paginate(15);

        return view('campus-submissions.my-submissions', compact('submissions'));
    }

    /**
     * Show the form for editing a submission
     */
    public function edit(CampusSubmission $campusSubmission)
    {
        $user = Auth::user();
        
        // Check if user can edit this submission
        if ($campusSubmission->user_id !== $user->id) {
            abort(403, 'You can only edit your own submissions.');
        }

        if (!$campusSubmission->is_editable) {
            abort(403, 'This submission cannot be edited.');
        }

        $campus = Campus::where('code', $user->campus_code)->first();
        
        return view('campus-submissions.edit', compact('campusSubmission', 'campus'));
    }

    /**
     * Update a submission
     */
    public function update(Request $request, CampusSubmission $campusSubmission)
    {
        $user = Auth::user();
        
        // Check if user can edit this submission
        if ($campusSubmission->user_id !== $user->id) {
            abort(403, 'You can only edit your own submissions.');
        }

        if (!$campusSubmission->is_editable) {
            abort(403, 'This submission cannot be edited.');
        }

        $request->validate([
            'strategic_goal' => 'required|string|max:500',
            'kra' => 'required|string|max:500',
            'kpi' => 'required|string|max:500',
            'target_value' => 'required|numeric|min:0',
            'actual_value' => 'required|numeric|min:0|lte:target_value',
            'justification' => 'nullable|string|max:2000',
            'supporting_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'google_drive_link' => 'nullable|url|max:500',
        ], [
            'actual_value.lte' => 'Actual value cannot exceed target value.',
            'supporting_file.max' => 'File size cannot exceed 10MB.',
        ]);

        $filePath = $campusSubmission->file_path;
        if ($request->hasFile('supporting_file')) {
            // Delete old file if exists
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            
            $file = $request->file('supporting_file');
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('campus-submissions', $fileName, 'public');
        }

        $campusSubmission->update([
            'strategic_goal' => $request->strategic_goal,
            'kra' => $request->kra,
            'kpi' => $request->kpi,
            'target_value' => $request->target_value,
            'actual_value' => $request->actual_value,
            'justification' => $request->justification,
            'file_path' => $filePath,
            'google_drive_link' => $request->google_drive_link,
            'status' => 'pending', // Reset to pending when edited
            'admin_remarks' => null, // Clear admin remarks
            'returned_at' => null,
        ]);

        return redirect()->route('campus-submissions.my-submissions')
            ->with('success', 'Submission updated successfully and sent for re-approval.');
    }

    /**
     * Delete a submission
     */
    public function destroy(CampusSubmission $campusSubmission)
    {
        $user = Auth::user();
        
        // Check if user can delete this submission
        if ($campusSubmission->user_id !== $user->id) {
            abort(403, 'You can only delete your own submissions.');
        }

        if (!$campusSubmission->is_editable) {
            abort(403, 'This submission cannot be deleted.');
        }

        // Delete file if exists
        if ($campusSubmission->file_path && Storage::disk('public')->exists($campusSubmission->file_path)) {
            Storage::disk('public')->delete($campusSubmission->file_path);
        }

        $campusSubmission->delete();

        return redirect()->route('campus-submissions.my-submissions')
            ->with('success', 'Submission deleted successfully.');
    }

    /**
     * Download supporting file
     */
    public function downloadFile(CampusSubmission $campusSubmission)
    {
        $user = Auth::user();
        
        // Check if user can access this file
        if ($campusSubmission->user_id !== $user->id && !$user->canApproveForms()) {
            abort(403, 'You do not have permission to download this file.');
        }

        if (!$campusSubmission->file_path || !Storage::disk('public')->exists($campusSubmission->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($campusSubmission->file_path);
    }
}