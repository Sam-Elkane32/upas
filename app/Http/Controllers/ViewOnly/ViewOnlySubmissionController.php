<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;

class ViewOnlySubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    /**
     * Display all approved submissions (read-only)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Only show approved submissions
        $query = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->with(['template', 'submitter']);
        
        if ($user->restrictsViewOnlyToSingleCampus()) {
            $query->where('campus', optional($user->campusInfo)->name ?? $user->campus ?? '');
        }
        
        // Apply filters
        if ($request->filled('campus')) {
            $query->where('campus', $request->campus);
        }
        
        if ($request->filled('template_code')) {
            $query->where('template_code', 'like', '%' . $request->template_code . '%');
        }
        
        if ($request->filled('quarter')) {
            $query->where('quarter', $request->quarter);
        }
        
        if ($request->filled('sg_code')) {
            $query->where('sg_code', $request->sg_code);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'recent');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('submitted_at', 'asc');
                break;
            case 'campus':
                $query->orderBy('campus', 'asc')->orderBy('submitted_at', 'desc');
                break;
            case 'template':
                $query->orderBy('template_code', 'asc')->orderBy('submitted_at', 'desc');
                break;
            case 'recent':
            default:
                $query->orderBy('submitted_at', 'desc');
                break;
        }
        
        $submissions = $query->paginate(20)->withQueryString();
        
        // Get filter options
        $campuses = $user->restrictsViewOnlyToSingleCampus()
            ? Campus::where('code', $user->campus_code)->get()
            : Campus::where('is_active', true)->get();
        
        $templateCodes = Submission::where('status', 'Approved')
            ->distinct()
            ->pluck('template_code')
            ->filter()
            ->sort()
            ->values();
        
        $quarters = Submission::where('status', 'Approved')
            ->distinct()
            ->pluck('quarter')
            ->filter()
            ->sort()
            ->values();
        
        $sgCodes = Submission::where('status', 'Approved')
            ->distinct()
            ->pluck('sg_code')
            ->filter()
            ->sort()
            ->values();
        
        return view('view-only.submissions.index', compact(
            'submissions', 
            'campuses', 
            'templateCodes', 
            'quarters', 
            'sgCodes'
        ));
    }

    /**
     * Display a single approved submission (read-only)
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $submission = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->with(['template', 'submitter', 'approval'])
            ->findOrFail($id);
        
        if ($user->restrictsViewOnlyToSingleCampus()) {
            $expected = optional($user->campusInfo)->name ?? $user->campus ?? '';
            if ($submission->campus !== $expected) {
                abort(403, 'You do not have permission to view this submission.');
            }
        }
        
        return view('view-only.submissions.show', compact('submission'));
    }
}
