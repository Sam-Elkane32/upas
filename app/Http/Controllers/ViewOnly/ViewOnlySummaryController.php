<?php

namespace App\Http\Controllers\ViewOnly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Template;
use App\Models\Campus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewOnlySummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:view_only']);
    }

    /**
     * Display summary of accomplishments (read-only)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Base query for approved submissions only
        $baseQuery = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });
        
        if ($user->restrictsViewOnlyToSingleCampus()) {
            $baseQuery->where('campus', optional($user->campusInfo)->name ?? $user->campus ?? '');
        }
        
        // Apply filters
        if ($request->filled('campus')) {
            $baseQuery->where('campus', $request->campus);
        }
        
        if ($request->filled('sg_code')) {
            $baseQuery->where('sg_code', $request->sg_code);
        }
        
        if ($request->filled('quarter')) {
            $baseQuery->where('quarter', $request->quarter);
        }
        
        if ($request->filled('template_code')) {
            $baseQuery->where('template_code', $request->template_code);
        }
        
        // Get summary statistics
        $stats = [
            'total_approved_submissions' => (clone $baseQuery)->count(),
            'total_campuses' => $user->restrictsViewOnlyToSingleCampus() ? 1 : Campus::where('is_active', true)->count(),
            'total_templates' => Template::where('status', 'Published')
                ->when($user->restrictsViewOnlyToSingleCampus(), function ($q) use ($user) {
                    return $q->where(function ($q2) use ($user) {
                        $q2->whereNull('campus_code')->orWhere('campus_code', '')->orWhere('campus_code', $user->campus_code);
                    });
                })
                ->count(),
        ];
        
        // Campus-wise summary
        $campusSummary = [];
        $campuses = $user->restrictsViewOnlyToSingleCampus()
            ? Campus::where('code', $user->campus_code)->get()
            : Campus::where('is_active', true)->get();
        
        foreach ($campuses as $campus) {
            $campusSubs = (clone $baseQuery)
                ->where('campus', $campus->name)
                ->count();
            
            $campusSummary[] = [
                'campus' => $campus->name,
                'code' => $campus->code,
                'submissions_count' => $campusSubs,
            ];
        }
        
        // Strategic Goal summary
        $sgSummary = (clone $baseQuery)
            ->select('sg_code', DB::raw('count(*) as count'))
            ->groupBy('sg_code')
            ->orderBy('sg_code')
            ->get();
        
        // Quarter summary
        $quarterSummary = (clone $baseQuery)
            ->select('quarter', DB::raw('count(*) as count'))
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->get();
        
        // Get filter options
        $allCampuses = $user->restrictsViewOnlyToSingleCampus()
            ? Campus::where('code', $user->campus_code)->get()
            : Campus::where('is_active', true)->get();
        
        $sgCodes = (clone $baseQuery)
            ->distinct()
            ->pluck('sg_code')
            ->filter()
            ->sort()
            ->values();
        
        $quarters = (clone $baseQuery)
            ->distinct()
            ->pluck('quarter')
            ->filter()
            ->sort()
            ->values();
        
        $templateCodes = (clone $baseQuery)
            ->distinct()
            ->pluck('template_code')
            ->filter()
            ->sort()
            ->values();
        
        return view('view-only.summary.index', compact(
            'stats',
            'campusSummary',
            'sgSummary',
            'quarterSummary',
            'allCampuses',
            'sgCodes',
            'quarters',
            'templateCodes'
        ));
    }
}
