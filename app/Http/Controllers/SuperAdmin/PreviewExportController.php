<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PreviewExportController extends Controller
{
    /**
     * Preview consolidated PDF export (HTML only, no PDF generation)
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function preview(Request $request)
    {
        // Ensure user is Super Admin
        if (!Auth::user()->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filters from request (same as exportPdf)
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

        // Apply date preset if selected (same logic as exportPdf)
        if ($filters['date_preset']) {
            $dateRange = $this->getDatePresetRange($filters['date_preset']);
            $filters['date_from'] = $dateRange['from'];
            $filters['date_to'] = $dateRange['to'];
        }

        // Build query using Submission model (same as exportPdf)
        $query = Submission::with(['template', 'submitter', 'approval'])
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });
        $query = $this->applySubmissionFilters($query, $filters);
        $submissions = $query->orderBy('submitted_at', 'desc')->get();

        // Get statistics (same as exportPdf)
        $stats = $this->getEnhancedConsolidatedStats($filters);
        $analytics = $this->getAdvancedAnalytics($filters);

        // Determine which view to use (same logic as exportPdf)
        $exportView = 'super-admin.pdf.consolidated-report';
        if (!view()->exists($exportView)) {
            $exportView = 'reports.export-pdf';
        }

        $user = auth()->user();

        // Return preview page with export data
        return view('super-admin.exports.preview', [
            'submissions' => $submissions,
            'stats' => $stats,
            'analytics' => $analytics,
            'filters' => $filters,
            'user' => $user,
            'exportView' => $exportView,
            'exportQueryString' => http_build_query($filters),
        ]);
    }

    /**
     * Apply submission filters (reuse from SuperAdminController logic)
     */
    private function applySubmissionFilters($query, $filters)
    {
        // Replicate filter logic from SuperAdminController
        if (!empty($filters['campuses']) && is_array($filters['campuses'])) {
            $campusNames = \App\Models\Campus::whereIn('id', $filters['campuses'])
                ->orWhereIn('code', $filters['campuses'])
                ->pluck('name')
                ->toArray();
            if (!empty($campusNames)) {
                $query->whereIn('campus', $campusNames);
            }
        } elseif (!empty($filters['campus'])) {
            $query->where('campus', $filters['campus']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }

        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', 'like', '%' . $filters['kra_title'] . '%');
        }

        if (!empty($filters['kpi_title'])) {
            $query->where('kpi_title', 'like', '%' . $filters['kpi_title'] . '%');
        }

        if (!empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }

        if (!empty($filters['user_role'])) {
            // Filter by user role through submitter relationship
            $query->whereHas('submitter', function($q) use ($filters) {
                if ($filters['user_role'] == 'campus_admin') {
                    $q->where('role', 'admin');
                } elseif ($filters['user_role'] == 'campus_user') {
                    $q->where('role', 'creator_editor');
                }
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
        }

        return $query;
    }

    /**
     * Get date preset range
     */
    private function getDatePresetRange($preset)
    {
        $now = now();
        return match($preset) {
            'this_month' => ['from' => $now->startOfMonth()->format('Y-m-d'), 'to' => $now->endOfMonth()->format('Y-m-d')],
            'last_month' => ['from' => $now->subMonth()->startOfMonth()->format('Y-m-d'), 'to' => $now->endOfMonth()->format('Y-m-d')],
            'this_quarter' => ['from' => $now->startOfQuarter()->format('Y-m-d'), 'to' => $now->endOfQuarter()->format('Y-m-d')],
            'last_quarter' => ['from' => $now->subQuarter()->startOfQuarter()->format('Y-m-d'), 'to' => $now->endOfQuarter()->format('Y-m-d')],
            'this_year' => ['from' => $now->startOfYear()->format('Y-m-d'), 'to' => $now->endOfYear()->format('Y-m-d')],
            'last_year' => ['from' => $now->subYear()->startOfYear()->format('Y-m-d'), 'to' => $now->endOfYear()->format('Y-m-d')],
            default => ['from' => null, 'to' => null],
        };
    }

    /**
     * Get enhanced consolidated stats
     */
    private function getEnhancedConsolidatedStats($filters)
    {
        $query = Submission::where(function($q) {
            $q->where('is_draft', false)->orWhereNull('is_draft');
        });
        $query = $this->applySubmissionFilters($query, $filters);

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'Pending Review')->count();
        $approved = (clone $query)->where('status', 'Approved')->count();
        $returned = (clone $query)->where('status', 'Returned')->count();

        // Calculate overall achievement from approved submissions
        $approvedSubs = (clone $query)->where('status', 'Approved')->with('approval')->get();
        $totalRate = 0;
        $rateCount = 0;
        foreach ($approvedSubs as $sub) {
            if ($sub->approval && $sub->approval->rate) {
                $totalRate += $sub->approval->rate;
                $rateCount++;
            }
        }
        $overallAchievement = $rateCount > 0 ? ($totalRate / $rateCount) : 0;

        return [
            'total_submissions' => $total,
            'pending_submissions' => $pending,
            'approved_submissions' => $approved,
            'returned_submissions' => $returned,
            'overall_achievement' => round($overallAchievement, 2),
        ];
    }

    /**
     * Get advanced analytics
     */
    private function getAdvancedAnalytics($filters)
    {
        $query = Submission::where(function($q) {
            $q->where('is_draft', false)->orWhereNull('is_draft');
        });
        $query = $this->applySubmissionFilters($query, $filters);
        
        $approvedSubs = (clone $query)->where('status', 'Approved')->with('approval')->get();
        
        // Group by KPI title and calculate averages
        $kpiStats = [];
        foreach ($approvedSubs as $sub) {
            $kpiTitle = $sub->kpi_title ?? 'Unknown';
            if (!isset($kpiStats[$kpiTitle])) {
                $kpiStats[$kpiTitle] = [
                    'kpi_title' => $kpiTitle,
                    'total_rate' => 0,
                    'count' => 0,
                    'avg_achievement' => 0,
                    'approved_submissions' => 0,
                ];
            }
            $kpiStats[$kpiTitle]['approved_submissions']++;
            if ($sub->approval && $sub->approval->rate) {
                $kpiStats[$kpiTitle]['total_rate'] += $sub->approval->rate;
                $kpiStats[$kpiTitle]['count']++;
            }
        }

        // Calculate averages
        foreach ($kpiStats as &$stat) {
            if ($stat['count'] > 0) {
                $stat['avg_achievement'] = round($stat['total_rate'] / $stat['count'], 2);
            }
        }

        // Sort by average achievement
        usort($kpiStats, function($a, $b) {
            return $b['avg_achievement'] <=> $a['avg_achievement'];
        });

        $bestPerforming = array_slice($kpiStats, 0, 10);
        $worstPerforming = array_slice(array_reverse($kpiStats), 0, 10);

        return [
            'best_performing' => $bestPerforming,
            'worst_performing' => $worstPerforming,
        ];
    }
}

