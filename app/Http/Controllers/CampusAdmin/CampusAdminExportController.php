<?php

namespace App\Http\Controllers\CampusAdmin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampusAdminExportController extends Controller
{
    /**
     * Preview PDF export before downloading
     */
    public function previewPdf(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus Admin
        if (!$user->isAdmin()) {
            abort(403, 'Only Campus Admin can access this feature.');
        }
        
        // Get filter parameters from request
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
        
        // Get campus name
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
        
        // Build query for campus submissions
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        // Order by template_code (T1, T2, T3, etc.) then by other fields for consistency
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        // Prepare data for preview (same as PDF export)
        $data = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => 'campus_admin',
            'campusName' => $campusName,
            'groupedCampuses' => null,
            'filters' => $filters,
        ];
        
        // Build query string for download action
        $exportQueryString = http_build_query($filters);
        
        return view('campus-admin.reports.preview-pdf', [
            'exportData' => $data,
            'exportView' => 'reports.pdf-export',
            'exportQueryString' => $exportQueryString,
        ]);
    }
    
    /**
     * Preview Excel export before downloading
     */
    public function previewExcel(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus Admin
        if (!$user->isAdmin()) {
            abort(403, 'Only Campus Admin can access this feature.');
        }
        
        // Get filter parameters from request
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
        
        // Get campus name
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
        
        // Build query for campus submissions
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        // Order by template_code (T1, T2, T3, etc.) then by other fields for consistency
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        // Prepare data for preview (same as Excel export)
        $data = [
            'submissions' => $submissions,
            'user' => $user,
            'campusName' => $campusName,
            'filters' => $filters,
        ];
        
        // Build query string for download action
        $exportQueryString = http_build_query($filters);
        
        return view('campus-admin.reports.preview-excel', [
            'exportData' => $data,
            'exportQueryString' => $exportQueryString,
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
        if (!empty($filters['form_title'])) {
            $query->where('form_title', $filters['form_title']);
        }
        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }
        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', $filters['kra_title']);
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

