<?php

namespace App\Http\Controllers;

use App\Models\CampusSubmission; // Legacy model - kept for backward compatibility
use App\Models\Submission; // New unified model - primary data source
use App\Models\Campus;
use App\Models\User;
use App\Models\Template;
use App\Models\Approval;
use App\Models\KPI;
use App\Services\KpiSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SuperAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->isSuperAdmin()) {
                abort(403, 'Only Super Admin can access this area.');
            }
            return $next($request);
        });
    }

    /**
     * Overview Dashboard — standalone page
     */
    public function overviewDashboard(Request $request)
    {
        $filters = [
            'campuses' => $request->get('campuses', []),
            'campus' => $request->get('campus'),
            'status' => $request->get('status'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
            'user_role' => $request->get('user_role'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'date_preset' => $request->get('date_preset'),
            'quarter' => $request->get('quarter'),
        ];
        if ($filters['date_preset']) {
            $dateRange = $this->getDatePresetRange($filters['date_preset']);
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
        }
        $query = Submission::with(['template', 'submitter', 'approval']);
        $query = $this->applySubmissionFilters($query, $filters);
        $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20);
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $strategicGoals = Submission::whereNotNull('sg_code')->select('sg_code')->distinct()->orderBy('sg_code')->pluck('sg_code');
        $kraTitles = Submission::whereNotNull('kra_title')->select('kra_title')->distinct()->orderBy('kra_title')->pluck('kra_title');
        $kpiTitles = Submission::whereNotNull('kpi_title')->select('kpi_title')->distinct()->orderBy('kpi_title')->pluck('kpi_title');
        $templateCodes = Template::where('status', 'Published')->select('template_code')->distinct()->orderBy('template_code')->pluck('template_code');
        $stats = $this->getEnhancedConsolidatedStats($filters);
        $analytics = $this->getAdvancedAnalytics($filters);
        $trends = $this->getTrendAnalysis($filters);
        $kpiAnalytics = $this->getKPIAnalytics($filters);
        return view('super-admin.consolidated-reports.overview', compact(
            'submissions', 'campuses', 'strategicGoals', 'kraTitles', 'kpiTitles', 'templateCodes',
            'stats', 'analytics', 'trends', 'kpiAnalytics', 'filters'
        ));
    }

    /**
     * QA Coordinator Reports — standalone page
     */
    public function qaCoordinatorReports(Request $request)
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $campusAdminReports = $this->getCampusAdminReportsData($request);
        return view('super-admin.consolidated-reports.qa-coordinator-reports', compact('campuses', 'campusAdminReports'));
    }

    /**
     * Planning Coordinator Reports — standalone page
     */
    public function planningCoordinatorReports(Request $request)
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $campusUserReports = $this->getCampusUserReportsData($request);
        $planningCoordinatorTemplateCodes = $this->getPlanningCoordinatorReportTemplateCodes();
        return view('super-admin.consolidated-reports.planning-coordinator-reports', compact(
            'campuses',
            'campusUserReports',
            'planningCoordinatorTemplateCodes'
        ));
    }

    /**
     * Distinct template codes present on planning-coordinator report scope (for filter dropdown).
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function getPlanningCoordinatorReportTemplateCodes()
    {
        return Submission::query()
            ->whereHas('submitter', function ($q) {
                $q->where('role', 'creator_editor');
            })
            ->where(function ($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->whereIn('status', ['Pending Review', 'Approved', 'Returned'])
            ->whereNotNull('template_code')
            ->where('template_code', '!=', '')
            ->distinct()
            ->orderBy('template_code')
            ->pluck('template_code');
    }

    /**
     * Summary of Accomplishments — standalone page
     */
    public function summaryAccomplishments()
    {
        return view('super-admin.consolidated-reports.summary');
    }
    
    /**
     * Get QA Coordinator Reports Data
     * Returns data for QA Coordinator Reports tab
     */
    private function getCampusAdminReportsData(Request $request, $paginate = true)
    {
        $selectedCampus = $request->get('campus_admin_filter');

        // Only formally submitted records (exclude drafts)
        $query = Submission::with(['template', 'submitter', 'approval'])
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->whereIn('status', ['Pending Review', 'Approved', 'Returned']);
        
        if ($selectedCampus) {
            $campus = Campus::find($selectedCampus);
            if ($campus) {
                $query->where('campus', $campus->name);
            }
        }
        
        // Get submissions (paginated or all)
        if ($paginate) {
            $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20, ['*'], 'campus_admin_page');
        } else {
            $submissions = $query->orderBy('submitted_at', 'desc')->get();
        }
        
        // Get statistics
        $stats = [
            'total_submissions' => (clone $query)->count(),
            'approved' => (clone $query)->where('status', 'Approved')->count(),
            'pending_review' => (clone $query)->where('status', 'Pending Review')->count(),
            'returned' => (clone $query)->where('status', 'Returned')->count(),
        ];
        
        return [
            'submissions' => $submissions,
            'stats' => $stats,
            'selectedCampus' => $selectedCampus,
        ];
    }
    
    /**
     * Get Planning Coordinator Reports Data  
     * Returns data for Planning Coordinator Reports tab
     */
    private function getCampusUserReportsData(Request $request, $paginate = true)
    {
        $selectedCampus = $request->get('campus_user_filter');
        $selectedTemplate = $request->get('template_code');

        // Only formally submitted records (exclude drafts)
        $query = Submission::with(['template', 'submitter', 'approval'])
            ->whereHas('submitter', function($q) {
                $q->where('role', 'creator_editor');
            })
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->whereIn('status', ['Pending Review', 'Approved', 'Returned']);
        
        if ($selectedCampus) {
            $campus = Campus::find($selectedCampus);
            if ($campus) {
                $query->where('campus', $campus->name);
            }
        }

        if ($selectedTemplate !== null && $selectedTemplate !== '') {
            $query->where('template_code', $selectedTemplate);
        }
        
        // Get submissions (paginated or all)
        if ($paginate) {
            $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20, ['*'], 'campus_user_page');
        } else {
            $submissions = $query->orderBy('submitted_at', 'desc')->get();
        }
        
        // Get statistics
        $stats = [
            'total_submissions' => (clone $query)->count(),
            'approved' => (clone $query)->where('status', 'Approved')->count(),
            'pending_review' => (clone $query)->where('status', 'Pending Review')->count(),
            'returned' => (clone $query)->where('status', 'Returned')->count(),
        ];
        
        // Get quarterly stats (reorder() clears ORDER BY so GROUP BY is valid in MySQL ONLY_FULL_GROUP_BY mode)
        $quarterlyStats = (clone $query)
            ->reorder()
            ->selectRaw('quarter, COUNT(*) as count')
            ->whereNotNull('quarter')
            ->groupBy('quarter')
            ->pluck('count', 'quarter');
        
        // Get status stats
        $statusStats = (clone $query)
            ->reorder()
            ->selectRaw('status, COUNT(*) as count')
            ->whereNotNull('status')
            ->groupBy('status')
            ->pluck('count', 'status');
        
        return [
            'submissions' => $submissions,
            'stats' => $stats,
            'selectedCampus' => $selectedCampus,
            'selectedTemplate' => $selectedTemplate,
            'quarterlyStats' => $quarterlyStats,
            'statusStats' => $statusStats,
        ];
    }

    /**
     * Apply filters to submission query
     * Unified filtering method for all report queries
     */
    private function applySubmissionFilters($query, $filters)
    {
        // Multi-campus or single campus filter
        // If campuses array is provided and not empty, filter by selected campuses
        if (!empty($filters['campuses']) && is_array($filters['campuses']) && count($filters['campuses']) > 0) {
            // Get campus names from IDs or codes
            $campusNames = Campus::where(function($q) use ($filters) {
                $q->whereIn('id', $filters['campuses'])
                  ->orWhereIn('code', $filters['campuses']);
            })->pluck('name')->toArray();
            
            if (!empty($campusNames)) {
                $query->whereIn('campus', $campusNames);
            }
        } elseif (!empty($filters['campus'])) {
            // Backward compatibility: single campus filter
            $campus = Campus::find($filters['campus']);
            if ($campus) {
                $query->where('campus', $campus->name);
            }
        }
        // If no campuses are selected (empty array or null), show all campuses (no filter applied)

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Strategic Goal filter
        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }

        // KRA Title filter
        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', 'like', '%' . $filters['kra_title'] . '%');
        }

        // KPI Title filter
        if (!empty($filters['kpi_title'])) {
            $query->where('kpi_title', 'like', '%' . $filters['kpi_title'] . '%');
        }

        // Template Code filter
        if (!empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }

        // Quarter filter
        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        // User role filter (QA Coordinator vs Planning Coordinator)
        if (!empty($filters['user_role'])) {
            $roleMap = [
                'admin' => 'admin',
                'campus_admin' => 'admin',
                'creator_editor' => 'creator_editor',
                'campus_user' => 'creator_editor',
            ];
            $role = $roleMap[$filters['user_role']] ?? $filters['user_role'];
            $query->whereHas('submitter', function($q) use ($role) {
                $q->where('role', $role);
            });
        }

        return $query;
    }

    /**
     * Get date preset range
     * Helper for date filter presets
     */
    private function getDatePresetRange($preset)
    {
        $now = Carbon::now();
        
        return match($preset) {
            'this_month' => [
                'from' => $now->startOfMonth()->format('Y-m-d'),
                'to' => $now->endOfMonth()->format('Y-m-d'),
            ],
            'last_month' => [
                'from' => $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                'to' => $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'this_quarter' => [
                'from' => $now->startOfQuarter()->format('Y-m-d'),
                'to' => $now->endOfQuarter()->format('Y-m-d'),
            ],
            'last_quarter' => [
                'from' => $now->copy()->subQuarter()->startOfQuarter()->format('Y-m-d'),
                'to' => $now->copy()->subQuarter()->endOfQuarter()->format('Y-m-d'),
            ],
            'this_year' => [
                'from' => $now->startOfYear()->format('Y-m-d'),
                'to' => $now->endOfYear()->format('Y-m-d'),
            ],
            'last_year' => [
                'from' => $now->copy()->subYear()->startOfYear()->format('Y-m-d'),
                'to' => $now->copy()->subYear()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'from' => null,
                'to' => null,
            ],
        };
    }

    /**
     * Get enhanced consolidated statistics
     * Uses Submission model (unified data source)
     */
    private function getEnhancedConsolidatedStats($filters = [])
    {
        // Only formally submitted records (exclude drafts)
        $query = Submission::query()
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->whereIn('status', ['Pending Review', 'Approved', 'Returned']);
        $query = $this->applySubmissionFilters($query, $filters);

        // Basic counts
        $totalSubmissions = (clone $query)->count();
        $pendingSubmissions = (clone $query)->where('status', 'Pending Review')->count();
        $approvedSubmissions = (clone $query)->where('status', 'Approved')->count();
        $returnedSubmissions = (clone $query)->where('status', 'Returned')->count();

        // Calculate achievement rates from approvals
        $approvedQuery = (clone $query)->where('status', 'Approved')->with('approval');
        $approvedSubs = $approvedQuery->get();
        
        $totalRate = 0;
        $rateCount = 0;
        foreach ($approvedSubs as $sub) {
            if ($sub->approval && $sub->approval->rate) {
                $totalRate += $sub->approval->rate;
                $rateCount++;
            }
        }
        $overallAchievement = $rateCount > 0 ? ($totalRate / $rateCount) : 0;

        // Campus-wise statistics (using campus name matching)
        $campusStats = [];
        $campusSubmissions = (clone $query)->get()->groupBy('campus');
        
        foreach ($campusSubmissions as $campusName => $subs) {
            $campus = Campus::where('name', $campusName)->first();
            if (!$campus) {
                // Try to find by code or create placeholder
                $campus = Campus::where('code', $campusName)->first();
            }
            
            $approvedSubs = $subs->where('status', 'Approved');
            $campusRate = 0;
            $campusRateCount = 0;
            foreach ($approvedSubs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $campusRate += $sub->approval->rate;
                    $campusRateCount++;
                }
            }
            $avgAchievement = $campusRateCount > 0 ? ($campusRate / $campusRateCount) : 0;
            
            $campusStats[] = (object) [
                'campus_name' => $campusName,
                'campus_code' => $campus->code ?? 'N/A',
                'total_submissions' => $subs->count(),
                'pending_submissions' => $subs->where('status', 'Pending Review')->count(),
                'approved_submissions' => $approvedSubs->count(),
                'returned_submissions' => $subs->where('status', 'Returned')->count(),
                'avg_achievement' => round($avgAchievement, 2),
            ];
        }

        // Strategic Goal statistics
        $sgStats = [];
        $sgSubmissions = (clone $query)->get()->groupBy('sg_code');
        
        foreach ($sgSubmissions as $sgCode => $subs) {
            $approvedSubs = $subs->where('status', 'Approved');
            $sgRate = 0;
            $sgRateCount = 0;
            foreach ($approvedSubs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $sgRate += $sub->approval->rate;
                    $sgRateCount++;
                }
            }
            $avgAchievement = $sgRateCount > 0 ? ($sgRate / $sgRateCount) : 0;
            
            $sgStats[] = (object) [
                'sg_code' => $sgCode,
                'total_submissions' => $subs->count(),
                'approved_submissions' => $approvedSubs->count(),
                'avg_achievement' => round($avgAchievement, 2),
            ];
        }

        return [
            'total_submissions' => $totalSubmissions,
            'pending_submissions' => $pendingSubmissions,
            'approved_submissions' => $approvedSubmissions,
            'returned_submissions' => $returnedSubmissions,
            'overall_achievement' => round($overallAchievement, 2),
            'campus_stats' => collect($campusStats),
            'strategic_goal_stats' => collect($sgStats),
        ];
    }

    /**
     * Get consolidated statistics (legacy method - kept for backward compatibility)
     * @deprecated Use getEnhancedConsolidatedStats instead
     */
    private function getConsolidatedStats($campusFilter = null)
    {
        // Legacy method - kept for backward compatibility
        // This method uses CampusSubmission model for old reports
        $query = CampusSubmission::approved();

        if ($campusFilter) {
            $query->where('campus_id', $campusFilter);
        }

        $totalSubmissions = $query->count();
        $totalTargetValue = $query->sum('target_value');
        $totalActualValue = $query->sum('actual_value');
        $overallAchievement = $totalTargetValue > 0 ? ($totalActualValue / $totalTargetValue) * 100 : 0;

        // Campus-wise statistics
        $campusStats = CampusSubmission::approved()
            ->join('campuses', 'campus_submissions.campus_id', '=', 'campuses.id')
            ->select(
                'campuses.name as campus_name',
                'campuses.code as campus_code',
                DB::raw('COUNT(*) as total_submissions'),
                DB::raw('SUM(target_value) as total_target'),
                DB::raw('SUM(actual_value) as total_actual'),
                DB::raw('AVG((actual_value / target_value) * 100) as avg_achievement')
            )
            ->groupBy('campuses.id', 'campuses.name', 'campuses.code')
            ->get();

        // Strategic Goal statistics
        $sgStats = CampusSubmission::approved()
            ->select(
                'strategic_goal',
                DB::raw('COUNT(*) as total_submissions'),
                DB::raw('SUM(target_value) as total_target'),
                DB::raw('SUM(actual_value) as total_actual'),
                DB::raw('AVG((actual_value / target_value) * 100) as avg_achievement')
            )
            ->groupBy('strategic_goal')
            ->get();

        return [
            'total_submissions' => $totalSubmissions,
            'total_target_value' => $totalTargetValue,
            'total_actual_value' => $totalActualValue,
            'overall_achievement' => $overallAchievement,
            'campus_stats' => $campusStats,
            'strategic_goal_stats' => $sgStats,
        ];
    }

    /**
     * Get advanced analytics
     * Comparative performance, user activity, etc.
     */
    private function getAdvancedAnalytics($filters = [])
    {
        // Include drafts for Super Admin visibility
        $query = Submission::query();
        $query = $this->applySubmissionFilters($query, $filters);

        // QA Coordinator activity
        // First, get filtered submission IDs
        $filteredSubmissionIds = (clone $query)->pluck('id')->toArray();
        
        $campusAdminActivity = User::where('role', 'admin')
            ->where('is_active', true)
            ->withCount(['submissions' => function($q) use ($filteredSubmissionIds) {
                if (!empty($filteredSubmissionIds)) {
                    $q->whereIn('id', $filteredSubmissionIds);
                } else {
                    $q->whereRaw('1 = 0'); // No results if no filtered submissions
                }
            }])
            ->get()
            ->map(function($admin) {
                return [
                    'name' => $admin->name,
                    'campus' => $admin->campus,
                    'submissions_count' => $admin->submissions_count ?? 0,
                ];
            });

        // Planning Coordinator activity
        $campusUserActivity = User::where('role', 'creator_editor')
            ->where('is_active', true)
            ->withCount(['submissions' => function($q) use ($filteredSubmissionIds) {
                if (!empty($filteredSubmissionIds)) {
                    $q->whereIn('id', $filteredSubmissionIds);
                } else {
                    $q->whereRaw('1 = 0'); // No results if no filtered submissions
                }
            }])
            ->get()
            ->map(function($user) {
                return [
                    'name' => $user->name,
                    'campus' => $user->campus,
                    'submissions_count' => $user->submissions_count ?? 0,
                ];
            });

        // Performance metrics
        $approvedSubs = (clone $query)->where('status', 'Approved')->with('approval')->get();
        $totalApproved = $approvedSubs->count();
        $totalWithRate = $approvedSubs->filter(function($sub) {
            return $sub->approval && $sub->approval->rate;
        })->count();
        
        $outstanding = $approvedSubs->filter(function($sub) {
            return $sub->approval && $sub->approval->rate >= 100;
        })->count();
        $verySatisfactory = $approvedSubs->filter(function($sub) {
            return $sub->approval && $sub->approval->rate >= 90 && $sub->approval->rate < 100;
        })->count();
        $satisfactory = $approvedSubs->filter(function($sub) {
            return $sub->approval && $sub->approval->rate >= 80 && $sub->approval->rate < 90;
        })->count();
        $needsImprovement = $approvedSubs->filter(function($sub) {
            return $sub->approval && $sub->approval->rate < 80;
        })->count();

        return [
            'campus_admin_activity' => $campusAdminActivity,
            'campus_user_activity' => $campusUserActivity,
            'performance_metrics' => [
                'outstanding' => $outstanding,
                'very_satisfactory' => $verySatisfactory,
                'satisfactory' => $satisfactory,
                'needs_improvement' => $needsImprovement,
                'total_approved' => $totalApproved,
                'total_with_rate' => $totalWithRate,
            ],
        ];
    }

    /**
     * Get trend analysis
     * Monthly, quarterly, and yearly trends
     */
    private function getTrendAnalysis($filters = [])
    {
        $query = Submission::query();
        $query = $this->applySubmissionFilters($query, $filters);

        // Monthly trends (last 12 months)
        $monthlyTrends = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $monthSubs = (clone $query)->whereBetween('submitted_at', [$monthStart, $monthEnd])->get();
            $monthApproved = $monthSubs->where('status', 'Approved');
            
            $monthRate = 0;
            $monthRateCount = 0;
            foreach ($monthApproved as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $monthRate += $sub->approval->rate;
                    $monthRateCount++;
                }
            }
            $avgRate = $monthRateCount > 0 ? ($monthRate / $monthRateCount) : 0;
            
            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'month_key' => $month->format('Y-m'),
                'total_submissions' => $monthSubs->count(),
                'approved_submissions' => $monthApproved->count(),
                'pending_submissions' => $monthSubs->where('status', 'Pending Review')->count(),
                'returned_submissions' => $monthSubs->where('status', 'Returned')->count(),
                'avg_achievement' => round($avgRate, 2),
            ];
        }

        // Quarterly trends (last 4 quarters)
        $quarterlyTrends = [];
        $quarters = ['1st Q', '2nd Q', '3rd Q', '4th Q'];
        $currentYear = Carbon::now()->year;
        
        for ($year = $currentYear - 1; $year <= $currentYear; $year++) {
            foreach ($quarters as $quarter) {
                $quarterSubs = (clone $query)
                    ->where('quarter', $quarter)
                    ->whereYear('submitted_at', $year)
                    ->get();
                $quarterApproved = $quarterSubs->where('status', 'Approved');
                
                $quarterRate = 0;
                $quarterRateCount = 0;
                foreach ($quarterApproved as $sub) {
                    if ($sub->approval && $sub->approval->rate) {
                        $quarterRate += $sub->approval->rate;
                        $quarterRateCount++;
                    }
                }
                $avgRate = $quarterRateCount > 0 ? ($quarterRate / $quarterRateCount) : 0;
                
                $quarterlyTrends[] = [
                    'quarter' => $quarter,
                    'year' => $year,
                    'label' => $quarter . ' ' . $year,
                    'total_submissions' => $quarterSubs->count(),
                    'approved_submissions' => $quarterApproved->count(),
                    'avg_achievement' => round($avgRate, 2),
                ];
            }
        }

        // Yearly trends (last 3 years)
        $yearlyTrends = [];
        for ($i = 2; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $yearStart = Carbon::create($year, 1, 1)->startOfYear();
            $yearEnd = Carbon::create($year, 12, 31)->endOfYear();
            
            $yearSubs = (clone $query)->whereBetween('submitted_at', [$yearStart, $yearEnd])->get();
            $yearApproved = $yearSubs->where('status', 'Approved');
            
            $yearRate = 0;
            $yearRateCount = 0;
            foreach ($yearApproved as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $yearRate += $sub->approval->rate;
                    $yearRateCount++;
                }
            }
            $avgRate = $yearRateCount > 0 ? ($yearRate / $yearRateCount) : 0;
            
            $yearlyTrends[] = [
                'year' => $year,
                'total_submissions' => $yearSubs->count(),
                'approved_submissions' => $yearApproved->count(),
                'avg_achievement' => round($avgRate, 2),
            ];
        }

        return [
            'monthly' => $monthlyTrends,
            'quarterly' => $quarterlyTrends,
            'yearly' => $yearlyTrends,
        ];
    }

    /**
     * Get KPI-level analytics
     * Best/worst performing KPIs, KPI trends
     */
    private function getKPIAnalytics($filters = [])
    {
        $query = Submission::query();
        $query = $this->applySubmissionFilters($query, $filters);

        // Group by KPI Title
        $kpiSubmissions = (clone $query)->get()->groupBy('kpi_title');
        
        $kpiStats = [];
        foreach ($kpiSubmissions as $kpiTitle => $subs) {
            $approvedSubs = $subs->where('status', 'Approved');
            $kpiRate = 0;
            $kpiRateCount = 0;
            foreach ($approvedSubs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $kpiRate += $sub->approval->rate;
                    $kpiRateCount++;
                }
            }
            $avgRate = $kpiRateCount > 0 ? ($kpiRate / $kpiRateCount) : 0;
            
            $kpiStats[] = [
                'kpi_title' => $kpiTitle,
                'sg_code' => $subs->first()->sg_code ?? 'N/A',
                'kra_title' => $subs->first()->kra_title ?? 'N/A',
                'total_submissions' => $subs->count(),
                'approved_submissions' => $approvedSubs->count(),
                'pending_submissions' => $subs->where('status', 'Pending Review')->count(),
                'returned_submissions' => $subs->where('status', 'Returned')->count(),
                'avg_achievement' => round($avgRate, 2),
                'campuses_count' => $subs->pluck('campus')->unique()->count(),
            ];
        }

        // Sort by average achievement
        usort($kpiStats, function($a, $b) {
            return $b['avg_achievement'] <=> $a['avg_achievement'];
        });

        // Best and worst performing KPIs
        $bestKPIs = array_slice($kpiStats, 0, 10); // Top 10
        $worstKPIs = array_slice(array_reverse($kpiStats), 0, 10); // Bottom 10

        return [
            'all_kpis' => $kpiStats,
            'best_performing' => $bestKPIs,
            'worst_performing' => $worstKPIs,
            'total_unique_kpis' => count($kpiStats),
        ];
    }

    /**
     * Export consolidated data to Excel
     * Enhanced with unified Submission model and filters
     */
    public function exportExcel(Request $request)
    {
        // Get filters from request
        $filters = [
            'campuses' => $request->get('campuses', []),
            'campus' => $request->get('campus'),
            'status' => $request->get('status'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
            'user_role' => $request->get('user_role'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'date_preset' => $request->get('date_preset'),
            'quarter' => $request->get('quarter'),
        ];

        // Apply date preset if selected
        if ($filters['date_preset']) {
            $dateRange = $this->getDatePresetRange($filters['date_preset']);
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
        }

        // Build query using Submission model (include drafts for Super Admin)
        $query = Submission::with(['template', 'submitter', 'approval']);
        $query = $this->applySubmissionFilters($query, $filters);
        $submissions = $query->orderBy('submitted_at', 'desc')->get();

        // Get statistics
        $stats = $this->getEnhancedConsolidatedStats($filters);

        // Generate CSV (Excel-compatible)
        $filename = 'uaps-consolidated-report-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($submissions, $stats) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Submission ID',
                'Campus',
                'SG Code',
                'KRA Title',
                'KPI Title',
                'Template Code',
                'Quarter',
                'Status',
                'Submitted By',
                'Submitted At',
                'Approval Rate (%)',
                'Approval Remarks',
                'Approved By',
                'Approved At'
            ]);

            // Data rows
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->submission_id ?? $submission->id,
                    $submission->campus ?? 'N/A',
                    $submission->sg_code ?? 'N/A',
                    $submission->kra_title ?? 'N/A',
                    $submission->kpi_title ?? 'N/A',
                    $submission->template_code ?? 'N/A',
                    $submission->quarter ?? 'N/A',
                    $submission->status ?? 'N/A',
                    $submission->submitter->name ?? 'N/A',
                    $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : 'N/A',
                    $submission->approval->rate ?? 'N/A',
                    $submission->approval->remarks ?? 'N/A',
                    $submission->approval->validator->name ?? 'N/A',
                    $submission->approval->validated_at ? $submission->approval->validated_at->format('Y-m-d H:i:s') : 'N/A',
                ]);
            }

            // Summary section
            fputcsv($file, []);
            fputcsv($file, ['Summary Statistics']);
            fputcsv($file, ['Total Submissions', $stats['total_submissions']]);
            fputcsv($file, ['Pending Submissions', $stats['pending_submissions']]);
            fputcsv($file, ['Approved Submissions', $stats['approved_submissions']]);
            fputcsv($file, ['Returned Submissions', $stats['returned_submissions']]);
            fputcsv($file, ['Overall Achievement (%)', $stats['overall_achievement']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export consolidated data to CSV
     * Simple CSV export for quick data access
     */
    public function exportCsv(Request $request)
    {
        // Same as Excel export (CSV format)
        return $this->exportExcel($request);
    }

    /**
     * Export consolidated data to PDF
     * Enhanced with unified Submission model
     */
    public function exportPdf(Request $request)
    {
        // Get filters from request
        $filters = [
            'campuses' => $request->get('campuses', []),
            'campus' => $request->get('campus'),
            'status' => $request->get('status'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
            'user_role' => $request->get('user_role'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'date_preset' => $request->get('date_preset'),
            'quarter' => $request->get('quarter'),
        ];

        // Apply date preset if selected
        if ($filters['date_preset']) {
            $dateRange = $this->getDatePresetRange($filters['date_preset']);
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
        }

        // Build query using Submission model (include drafts for Super Admin)
        $query = Submission::with(['template', 'submitter', 'approval']);
        $query = $this->applySubmissionFilters($query, $filters);
        $submissions = $query->orderBy('submitted_at', 'desc')->get();

        // Get statistics
        $stats = $this->getEnhancedConsolidatedStats($filters);
        $analytics = $this->getAdvancedAnalytics($filters);

        // Check if PDF view exists, otherwise use a simple view
        $view = 'super-admin.pdf.consolidated-report';
        if (!view()->exists($view)) {
            // Create a simple PDF view
            $view = 'reports.export-pdf';
        }

        $user = auth()->user();
        $pdf = Pdf::loadView($view, compact('submissions', 'stats', 'analytics', 'filters', 'user'));
        
        return $pdf->download('uaps-consolidated-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export KPI Summary PDF
     * Generates automatic "SUMMARY OF ACCOMPLISHMENTS" report
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportSummaryPdf(Request $request)
    {
        // Get all active KPIs with their KRA relationships
        $kpis = KPI::with('kra')
            ->where('is_active', true)
            ->get();

        // Generate summary using service
        $summaryService = new KpiSummaryService();
        $summary = $summaryService->generateSummary($kpis);

        // Get authenticated user
        $user = auth()->user();

        // Load PDF view
        $view = 'super-admin.summary-pdf';
        $pdf = Pdf::loadView($view, compact('summary', 'user'));
        
        return $pdf->download('uaps-summary-of-accomplishments-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get university overview statistics
     * Enhanced to use Submission model
     */
    public function getUniversityStats()
    {
        // Include drafts so Super Admin sees full picture
        $totalSubmissions = Submission::count();
        
        $approvedSubs = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->with('approval')
            ->get();
        
        $totalRate = 0;
        $rateCount = 0;
        foreach ($approvedSubs as $sub) {
            if ($sub->approval && $sub->approval->rate) {
                $totalRate += $sub->approval->rate;
                $rateCount++;
            }
        }
        $overallAchievement = $rateCount > 0 ? ($totalRate / $rateCount) : 0;

        $stats = [
            'total_campuses' => Campus::where('is_active', true)->count(),
            'total_users' => User::where('is_active', true)->count(),
            'total_submissions' => $totalSubmissions,
            'approved_submissions' => Submission::where('status', 'Approved')
                ->where(function($q) {
                    $q->where('is_draft', false)->orWhereNull('is_draft');
                })->count(),
            'pending_submissions' => Submission::where('status', 'Pending Review')->count(),
            'returned_submissions' => Submission::where('status', 'Returned')->count(),
            'draft_submissions' => Submission::where('is_draft', true)->count(),
            'overall_achievement' => round($overallAchievement, 2),
        ];

        return response()->json($stats);
    }

    /**
     * Get campus performance comparison
     * Enhanced to use Submission model
     */
    public function getCampusPerformance()
    {
        $submissions = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->with('approval')
            ->get()
            ->groupBy('campus');

        $performance = [];
        foreach ($submissions as $campusName => $subs) {
            $campus = Campus::where('name', $campusName)->first();
            if (!$campus) {
                $campus = Campus::where('code', $campusName)->first();
            }
            
            $campusRate = 0;
            $campusRateCount = 0;
            foreach ($subs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $campusRate += $sub->approval->rate;
                    $campusRateCount++;
                }
            }
            $achievementPercentage = $campusRateCount > 0 ? ($campusRate / $campusRateCount) : 0;
            
            $performance[] = [
                'campus_name' => $campusName,
                'campus_code' => $campus->code ?? 'N/A',
                'total_submissions' => $subs->count(),
                'achievement_percentage' => round($achievementPercentage, 2),
            ];
        }

        // Sort by achievement percentage
        usort($performance, function($a, $b) {
            return $b['achievement_percentage'] <=> $a['achievement_percentage'];
        });

        return response()->json($performance);
    }

    /**
     * Get strategic goal performance
     * Enhanced to use Submission model
     */
    public function getStrategicGoalPerformance()
    {
        $submissions = Submission::where('status', 'Approved')
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->whereNotNull('sg_code')
            ->with('approval')
            ->get()
            ->groupBy('sg_code');

        $performance = [];
        foreach ($submissions as $sgCode => $subs) {
            $sgRate = 0;
            $sgRateCount = 0;
            foreach ($subs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $sgRate += $sub->approval->rate;
                    $sgRateCount++;
                }
            }
            $achievementPercentage = $sgRateCount > 0 ? ($sgRate / $sgRateCount) : 0;
            
            $performance[] = [
                'sg_code' => $sgCode,
                'total_submissions' => $subs->count(),
                'achievement_percentage' => round($achievementPercentage, 2),
            ];
        }

        // Sort by achievement percentage
        usort($performance, function($a, $b) {
            return $b['achievement_percentage'] <=> $a['achievement_percentage'];
        });

        return response()->json($performance);
    }
    
    /**
     * Delete a submission (for Super Admin)
     */
    public function deleteSubmission(Submission $submission)
    {
        try {
            $submissionId = $submission->id;
            $templateCode = $submission->template_code;
            
            Approval::where('submission_id', $submission->id)->delete();
            $submission->delete();
            
            $message = "Submission deleted successfully.";
            if ($templateCode) {
                $message .= " Template code: {$templateCode}";
            }
            
            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }
            
            return redirect()->back()
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Error deleting submission: ' . $e->getMessage());
            
            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete submission: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to delete submission: ' . $e->getMessage());
        }
    }
    
}