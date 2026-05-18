<?php

namespace App\Http\Controllers\CampusUser;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampusUserReportController extends Controller
{
    /**
     * Preview export for Campus User (HTML only, no PDF/Excel generation)
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function preview(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus User
        if (!$user->isCreatorEditor()) {
            abort(403, 'Only Campus User can access this feature.');
        }

        // Get format parameter (pdf or excel), default to pdf
        $format = $request->get('format', 'pdf');
        if (!in_array($format, ['pdf', 'excel'])) {
            $format = 'pdf';
        }

        // Get filter parameters (same as ReportController)
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

        // Get user-specific submissions (same logic as ReportController)
        $query = Submission::where('submitted_by', $user->id);
        $query = $this->applyFilters($query, $filters);
        // Order by template_code (T1, T2, T3, etc.) then by other fields for consistency
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Get campus name
        $campusName = null;
        if ($submissions->count() > 0) {
            $campusName = $submissions->first()->campus ?? null;
        }

        // Prepare data for preview (same structure as ReportController exportPdf)
        $exportData = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => 'campus_user',
            'campusName' => $campusName,
            'groupedCampuses' => null,
            'filters' => $filters,
        ];

        // Build query string with format included
        $queryParams = array_merge($filters, ['format' => $format]);
        $exportQueryString = http_build_query($queryParams);

        // Return preview page with export data (NO PDF/Excel generation)
        return view('campus-user.reports.preview', [
            'exportData' => $exportData,
            'exportView' => 'reports.pdf-export',
            'exportQueryString' => $exportQueryString,
            'format' => $format,
        ]);
    }

    /**
     * Apply filters to query (same logic as ReportController)
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

