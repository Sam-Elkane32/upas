<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Campus;
use App\Models\Submission;
use App\Models\Template;
use App\Models\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of reports
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
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
        
        if ($user->isSuperAdmin()) {
            // Super Admin can see all reports
            $campuses = Campus::where('is_active', true)->get();
            $reports = collect([]);
            
            // Get university-wide statistics with filters
            $stats = $this->getUniversityStats($filters);
            $quarterlyStats = $this->getQuarterlyStats($filters);
            $statusStats = $this->getStatusStats($filters);
            $performanceMetrics = $this->getPerformanceMetrics($filters);
        } elseif ($user->isAdmin()) {
            // QA Coordinator can see campus reports
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
            $campuses = Campus::where('code', $user->campus_code)->get();
            $reports = collect([]);
            
            // Get campus-specific statistics with filters
            $stats = $this->getCampusStats($campusName, $filters);
            $quarterlyStats = $this->getCampusQuarterlyStats($campusName, $filters);
            $statusStats = $this->getCampusStatusStats($campusName, $filters);
            $performanceMetrics = $this->getCampusPerformanceMetrics($campusName, $filters);
            
            // Get KPI accomplishment by SG
            $kpiBySG = $this->getKPIBySG($campusName, $filters);
            
            // Get available filter options from campus forms and submissions
            $campusSubmissions = Submission::where('campus', $campusName)->get();
            $campusForms = Form::where('campus_code', $user->campus_code)->get();
            
            // Get available form titles
            $availableFormTitles = $campusForms->whereNotNull('form_title')
                ->pluck('form_title')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
            
            // Get available strategic goals
            $availableSGs = $campusForms->whereNotNull('sg_code')
                ->pluck('sg_code')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
            
            // Get available KRA titles (filtered by selected SG if provided)
            $availableKRAs = [];
            if (!empty($filters['sg_code'])) {
                $availableKRAs = $campusForms->where('sg_code', $filters['sg_code'])
                    ->whereNotNull('kra_title')
                    ->pluck('kra_title')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            } else {
                $availableKRAs = $campusForms->whereNotNull('kra_title')
                    ->pluck('kra_title')
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
            }
        } else {
            // Creator/Editor can see their own reports
            $campuses = collect([]);
            $reports = collect([]);
            
            // Get user-specific statistics with filters
            $stats = $this->getUserStats($user->id, $filters);
            $quarterlyStats = $this->getUserQuarterlyStats($user->id, $filters);
            $statusStats = $this->getUserStatusStats($user->id, $filters);
            $performanceMetrics = $this->getUserPerformanceMetrics($user->id, $filters);
            
            // Get available filter options from user's submissions
            $userSubmissions = Submission::where('submitted_by', $user->id)->get();
            $availableSGs = $userSubmissions->whereNotNull('sg_code')->pluck('sg_code')->unique()->sort()->values()->toArray();
            $availableKRAs = $userSubmissions->whereNotNull('kra_title')->pluck('kra_title')->unique()->sort()->values()->toArray();
            $availableTemplateCodes = $userSubmissions->whereNotNull('template_code')->pluck('template_code')->unique()->sort()->values()->toArray();
        }
        
        // Get available filter options
        $availableQuarters = ['1st Q', '2nd Q', '3rd Q', '4th Q'];
        $availableStatuses = ['Pending Review', 'Approved', 'Returned', 'Unpublished'];
        
        // For non-Planning Coordinators, use default SGs
        if (!isset($availableSGs)) {
            $availableSGs = ['SG1', 'SG2', 'SG3', 'SG4', 'SG5'];
        }
        
        $viewData = compact('reports', 'campuses', 'stats', 'quarterlyStats', 'statusStats', 'performanceMetrics', 'filters', 'availableQuarters', 'availableStatuses', 'availableSGs');
        
        // Add Planning Coordinator specific filter options
        if ($user->isPlanningCoordinator()) {
            $viewData['availableKRAs'] = $availableKRAs ?? [];
            $viewData['availableTemplateCodes'] = $availableTemplateCodes ?? [];
        }
        
        // Add QA Coordinator specific filter options
        if ($user->isAdmin()) {
            $viewData['availableFormTitles'] = $availableFormTitles ?? [];
            $viewData['availableKRAs'] = $availableKRAs ?? [];
        }
        
        if (isset($kpiBySG)) {
            $viewData['kpiBySG'] = $kpiBySG;
        }
        
        return view('reports.index', $viewData);
    }

    /**
     * Generate a specific report
     */
    public function generate(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'report_type' => 'required|in:user_summary,campus_summary,form_summary,kpi_summary',
            'campus_code' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);
        
        // Check permissions
        if (!$user->isSuperAdmin() && $request->campus_code && $request->campus_code !== $user->campus_code) {
            abort(403, 'You can only generate reports for your campus.');
        }
        
        // This will be implemented with actual report system
        // For now, return success message
        
        return redirect()->route('reports.index')
            ->with('success', 'Report generated successfully. This feature will be fully implemented in the next phase.');
    }

    /**
     * Export report data
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'format' => 'required|in:pdf,excel,csv',
            'report_type' => 'nullable|in:user_summary,campus_summary,form_summary,kpi_summary,summary',
        ]);
        
        // Determine report type based on user role if not provided or if "summary" is sent
        $reportType = $request->get('report_type');
        if (empty($reportType) || $reportType === 'summary') {
            if ($user->isSuperAdmin()) {
                $reportType = 'campus_summary'; // Super admin sees all campuses
            } elseif ($user->isAdmin()) {
                $reportType = 'campus_summary'; // Campus admin sees their campus
            } else {
                $reportType = 'user_summary'; // Campus user sees their own submissions
            }
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
            'form_title' => $request->get('form_title'),
        ];
        
        // Get data based on user role
        if ($user->isSuperAdmin()) {
            $query = Submission::query();
        } elseif ($user->isAdmin()) {
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
            $query = Submission::where('campus', $campusName);
        } else {
            $query = Submission::where('submitted_by', $user->id);
        }
        
        $query = $this->applyFilters($query, $filters);
        // Order by template_code (T1, T2, T3, etc.) then by other fields for consistency
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('template_code', 'asc')
            ->orderBy('sg_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();
        
        $format = $request->get('format');
        $filename = 'uaps-report-' . now()->format('Y-m-d-His') . '.' . $format;
        
        try {
            if ($format === 'csv') {
                return $this->exportCsv($submissions, $filename);
            } elseif ($format === 'excel') {
                return $this->exportExcel($submissions, $filename);
            } else {
                return $this->exportPdf($submissions, $filename, $user, $filters);
            }
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'format' => $format,
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('reports.index')
                ->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Export to CSV
     */
    private function exportCsv($submissions, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($submissions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Submission ID', 'Template Code', 'SG Code', 'KRA Title', 'KPI Title',
                'Campus', 'Quarter', 'Status', 'Submitted By', 'Submitted At',
                'Approval Rate', 'Remarks'
            ]);
            
            // Data rows
            foreach ($submissions as $submission) {
                fputcsv($file, [
                    $submission->submission_id ?? $submission->id,
                    $submission->template_code ?? 'N/A',
                    $submission->sg_code ?? 'N/A',
                    $submission->kra_title ?? 'N/A',
                    $submission->kpi_title ?? 'N/A',
                    $submission->campus ?? 'N/A',
                    $submission->quarter ?? 'N/A',
                    $submission->status ?? 'N/A',
                    $submission->submitter->name ?? 'N/A',
                    $submission->submitted_at ? $submission->submitted_at->format('Y-m-d H:i:s') : 'N/A',
                    ($submission->approval && $submission->approval->rate) ? $submission->approval->rate : 'N/A',
                    ($submission->approval && $submission->approval->remarks) ? $submission->approval->remarks : 'N/A',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Export to Excel
     */
    private function exportExcel($submissions, $filename)
    {
        // For Excel export, we'll use CSV format for now
        // In production, you can use Maatwebsite\Excel package
        return $this->exportCsv($submissions, str_replace('.excel', '.csv', $filename));
    }
    
    /**
     * Export to PDF
     */
    private function exportPdf($submissions, $filename, $user, $filters = [])
    {
        // Determine user role
        $userRole = 'campus_user';
        if ($user->isSuperAdmin()) {
            $userRole = 'super_admin';
        } elseif ($user->isAdmin()) {
            $userRole = 'campus_admin';
        }
        
        // Get campus name for single-campus reports
        $campusName = null;
        if ($user->isAdmin()) {
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';
        } elseif (!$user->isSuperAdmin() && $submissions->count() > 0) {
            // For campus users, get their campus from first submission
            $campusName = $submissions->first()->campus ?? null;
        }
        
        // Group submissions by campus for super admin (maintain collection structure)
        $groupedCampuses = null;
        if ($user->isSuperAdmin() && $submissions->count() > 0) {
            // Group by campus but keep as collection for easier iteration
            $grouped = $submissions->groupBy('campus');
            $groupedCampuses = [];
            foreach ($grouped as $campus => $campusSubmissions) {
                $groupedCampuses[$campus] = $campusSubmissions;
            }
        }
        
        // Prepare data for PDF view
        $data = [
            'submissions' => $submissions,
            'user' => $user,
            'userRole' => $userRole,
            'campusName' => $campusName,
            'groupedCampuses' => $groupedCampuses,
            'filters' => $filters,
        ];
        
        // Generate PDF
        $pdf = Pdf::loadView('reports.pdf-export', $data)
            ->setPaper('legal', 'landscape')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);
        
        return $pdf->download($filename);
    }

    /**
     * Apply filters to query
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

    /**
     * Get university-wide statistics
     */
    private function getUniversityStats($filters = [])
    {
        $query = Submission::query();
        $query = $this->applyFilters($query, $filters);
        
        return [
            'total_submissions' => (clone $query)->count(),
            'pending_review' => (clone $query)->where('status', 'Pending Review')->count(),
            'approved' => (clone $query)->where('status', 'Approved')->count(),
            'returned' => (clone $query)->where('status', 'Returned')->count(),
        ];
    }

    /**
     * Get quarterly statistics
     */
    private function getQuarterlyStats($filters = [])
    {
        $query = Submission::query();
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('quarter, COUNT(*) as count')
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->pluck('count', 'quarter');
    }

    /**
     * Get status statistics
     */
    private function getStatusStats($filters = [])
    {
        $query = Submission::query();
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($filters = [])
    {
        $query = Submission::query();
        $query = $this->applyFilters($query, $filters);
        
        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'Approved')->count();
        $pending = (clone $query)->where('status', 'Pending Review')->count();
        $returned = (clone $query)->where('status', 'Returned')->count();
        
        return [
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'success_rate' => $total > 0 ? round((($approved + $pending) / $total) * 100, 1) : 0,
            'return_rate' => $total > 0 ? round(($returned / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get campus-specific statistics
     */
    private function getCampusStats($campusName, $filters = [])
    {
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        
        return [
            'total_submissions' => (clone $query)->count(),
            'pending_review' => (clone $query)->where('status', 'Pending Review')->count(),
            'approved' => (clone $query)->where('status', 'Approved')->count(),
            'returned' => (clone $query)->where('status', 'Returned')->count(),
        ];
    }

    /**
     * Get campus quarterly statistics
     */
    private function getCampusQuarterlyStats($campusName, $filters = [])
    {
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('quarter, COUNT(*) as count')
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->pluck('count', 'quarter');
    }

    /**
     * Get campus status statistics
     */
    private function getCampusStatusStats($campusName, $filters = [])
    {
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    /**
     * Get campus performance metrics
     */
    private function getCampusPerformanceMetrics($campusName, $filters = [])
    {
        $query = Submission::where('campus', $campusName);
        $query = $this->applyFilters($query, $filters);
        
        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'Approved')->count();
        $pending = (clone $query)->where('status', 'Pending Review')->count();
        $returned = (clone $query)->where('status', 'Returned')->count();
        
        return [
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'success_rate' => $total > 0 ? round((($approved + $pending) / $total) * 100, 1) : 0,
            'return_rate' => $total > 0 ? round(($returned / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Get KPI accomplishment by SG
     */
    private function getKPIBySG($campusName, $filters = [])
    {
        $query = Submission::where('campus', $campusName)
            ->where('status', 'Approved')
            ->with('approval');
        $query = $this->applyFilters($query, $filters);
        
        $submissions = $query->get();
        
        $kpiBySG = [];
        foreach ($submissions as $submission) {
            $sg = $submission->sg_code ?? 'Unknown';
            if (!isset($kpiBySG[$sg])) {
                $kpiBySG[$sg] = [
                    'total' => 0,
                    'total_rate' => 0,
                    'average_rate' => 0,
                ];
            }
            $kpiBySG[$sg]['total']++;
            if ($submission->approval && $submission->approval->rate) {
                $kpiBySG[$sg]['total_rate'] += $submission->approval->rate;
            }
        }
        
        foreach ($kpiBySG as $sg => &$data) {
            if ($data['total'] > 0) {
                $data['average_rate'] = round($data['total_rate'] / $data['total'], 1);
            }
        }
        
        return $kpiBySG;
    }

    /**
     * Get user-specific statistics
     */
    private function getUserStats($userId, $filters = [])
    {
        $query = Submission::where('submitted_by', $userId);
        $query = $this->applyFilters($query, $filters);
        
        return [
            'total_submissions' => (clone $query)->count(),
            'pending_review' => (clone $query)->where('status', 'Pending Review')->count(),
            'approved' => (clone $query)->where('status', 'Approved')->count(),
            'returned' => (clone $query)->where('status', 'Returned')->count(),
        ];
    }

    /**
     * Get user quarterly statistics
     */
    private function getUserQuarterlyStats($userId, $filters = [])
    {
        $query = Submission::where('submitted_by', $userId);
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('quarter, COUNT(*) as count')
            ->groupBy('quarter')
            ->orderBy('quarter')
            ->pluck('count', 'quarter');
    }

    /**
     * Get user status statistics
     */
    private function getUserStatusStats($userId, $filters = [])
    {
        $query = Submission::where('submitted_by', $userId);
        $query = $this->applyFilters($query, $filters);
        
        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }

    /**
     * Get user performance metrics
     */
    private function getUserPerformanceMetrics($userId, $filters = [])
    {
        $query = Submission::where('submitted_by', $userId);
        $query = $this->applyFilters($query, $filters);
        
        $total = (clone $query)->count();
        $approved = (clone $query)->where('status', 'Approved')->count();
        $pending = (clone $query)->where('status', 'Pending Review')->count();
        $returned = (clone $query)->where('status', 'Returned')->count();
        
        return [
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'success_rate' => $total > 0 ? round((($approved + $pending) / $total) * 100, 1) : 0,
            'return_rate' => $total > 0 ? round(($returned / $total) * 100, 1) : 0,
        ];
    }
}