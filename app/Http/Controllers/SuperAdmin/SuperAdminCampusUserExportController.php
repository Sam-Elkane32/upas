<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class SuperAdminCampusUserExportController extends Controller
{
    /** Browser preview only — full export PDF still includes all rows. */
    private const PREVIEW_SUBMISSION_LIMIT = 80;

    /**
     * Preview PDF export for Campus User (HTML only, no PDF generation)
     * Super Admin version - can query across campuses and filter by campus user submissions
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function preview(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Super Admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filter parameters
        $campusFilter = $request->get('campus_user_filter');
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'quarter' => $request->get('quarter'),
            'status' => $request->get('status'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
        ];

        // Get Campus User submissions (creator_editor role only)
        $query = Submission::whereHas('submitter', function($q) {
            $q->where('role', 'creator_editor');
        });
        
        // Apply campus filter if provided
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $query->where('campus', $campus->name);
            }
        }
        
        $query = $this->applyFilters($query, $filters);

        // Order by template_code (T1, T2, T3, etc.) then by other fields for consistency
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->limit(self::PREVIEW_SUBMISSION_LIMIT)
            ->get();

        // Get campus name
        $campusName = 'All Campuses';
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $campusName = $campus->name;
            }
        } else if ($submissions->count() > 0) {
            $campusName = $submissions->first()->campus ?? 'Multiple Campuses';
        }

        // Prepare data for preview (same structure as Campus User)
        $exportData = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => 'super_admin_campus_user',
            'campusName' => $campusName,
            'groupedCampuses' => null,
            'filters' => $filters,
            'compact_preview_footer' => true,
        ];

        // Build query string with format included
        $queryParams = array_merge($filters, ['format' => $request->get('format', 'pdf'), 'campus_user_filter' => $campusFilter]);
        $exportQueryString = http_build_query($queryParams);

        // Return Super Admin specific preview page (NO PDF generation)
        return view('super-admin.exports.campus-user-preview', [
            'exportData' => $exportData,
            'exportView' => 'reports.pdf-export',
            'exportQueryString' => $exportQueryString,
            'format' => $request->get('format', 'pdf'),
        ]);
    }

    /**
     * Export PDF for Campus User
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Super Admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filter parameters
        $campusFilter = $request->get('campus_user_filter');
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'quarter' => $request->get('quarter'),
            'status' => $request->get('status'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
        ];

        // Get Campus User submissions (creator_editor role only)
        $query = Submission::whereHas('submitter', function($q) {
            $q->where('role', 'creator_editor');
        });
        
        // Apply campus filter if provided
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $query->where('campus', $campus->name);
            }
        }
        
        $query = $this->applyFilters($query, $filters);
        
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Get campus name
        $campusName = 'All Campuses';
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $campusName = $campus->name;
            }
        } else if ($submissions->count() > 0) {
            $campusName = $submissions->first()->campus ?? 'Multiple Campuses';
        }

        $data = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => 'super_admin_campus_user',
            'campusName' => $campusName,
            'groupedCampuses' => null,
            'filters' => $filters,
        ];

        $pdf = Pdf::loadView('reports.pdf-export', $data);
        $pdf->setPaper('legal', 'landscape');
        
        $filename = 'campus-user-reports-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Apply filters to query (same logic as Campus User ReportController)
     */
    private function applyFilters($query, $filters)
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
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
        return $query;
    }
}

