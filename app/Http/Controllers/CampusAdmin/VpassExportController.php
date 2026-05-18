<?php

namespace App\Http\Controllers\CampusAdmin;

use App\Http\Controllers\Controller;
use App\Models\KPI;
use App\Models\KRA;
use App\Models\StrategicGoal;
use App\Models\Campus;
use App\Models\Submission;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CampusAdmin\VpassExcelExport;

class VpassExportController extends Controller
{
    /**
     * Get VPASS data from approved submissions
     * 
     * @param array $filters
     * @param string $campusCode
     * @return array
     */
    private function getVpassDataFromApprovals(array $filters, string $campusCode): array
    {
        // Get campus name from campus code
        $campus = Campus::where('code', $campusCode)->first();
        $campusName = $campus ? $campus->name : null;
        
        if (!$campusName) {
            return []; // Return empty if campus not found
        }
        
        // Get approved submissions based on filters
        // Note: submissions.campus stores the campus NAME, not code
        // Get approval IDs first to ensure we only get submissions with validated approvals
        $approvalIds = Approval::whereNotNull('validated_at')
            ->pluck('submission_id')
            ->toArray();
        
        if (empty($approvalIds)) {
            return []; // No approved submissions
        }
        
        $submissionsQuery = Submission::where('campus', $campusName)
            ->where('status', 'Approved')
            ->whereIn('id', $approvalIds)
            ->with(['submitter', 'approval']);
        
        // Apply filters (only if provided)
        if (!empty($filters['form_title'])) {
            $submissionsQuery->where('form_title', 'like', '%' . $filters['form_title'] . '%');
        }
        if (!empty($filters['sg_code'])) {
            $submissionsQuery->where('sg_code', $filters['sg_code']);
        }
        if (!empty($filters['kra_title'])) {
            // Use LIKE for KRA title to handle extra spaces
            $submissionsQuery->where('kra_title', 'like', '%' . trim($filters['kra_title']) . '%');
        }
        
        $submissions = $submissionsQuery->get();
        
        // Organize data by SG -> KRA -> KPI
        $vpassData = [];
        $processedKPIs = [];
        
        foreach ($submissions as $submission) {
            // Get approval for this submission (already loaded via with(['approval']))
            $approval = $submission->approval;
            
            // If not loaded, try to get it directly
            if (!$approval) {
                $approval = Approval::where('submission_id', $submission->id)
                    ->whereNotNull('validated_at')
                    ->first();
            }
            
            if (!$approval) continue;
            
            $sgCode = $submission->sg_code;
            $kraTitle = $submission->kra_title;
            $kpiTitle = $submission->kpi_title;
            
            // Create unique key for this KPI
            $kpiKey = $sgCode . '|' . $kraTitle . '|' . $kpiTitle;
            
            // Skip if already processed
            if (isset($processedKPIs[$kpiKey])) {
                continue;
            }
            
            // Find or create SG
            $sgIndex = null;
            foreach ($vpassData as $index => $sg) {
                if ($sg['sg_code'] === $sgCode) {
                    $sgIndex = $index;
                    break;
                }
            }
            
            if ($sgIndex === null) {
                $sgData = [
                    'sg_code' => $sgCode,
                    'sg_title' => $sgCode, // Use code as title if not available
                    'kras' => []
                ];
                $vpassData[] = $sgData;
                $sgIndex = count($vpassData) - 1;
            }
            
            // Find or create KRA
            $kraIndex = null;
            foreach ($vpassData[$sgIndex]['kras'] as $index => $kra) {
                if ($kra['kra_title'] === $kraTitle) {
                    $kraIndex = $index;
                    break;
                }
            }
            
            if ($kraIndex === null) {
                // Extract KRA code from title (e.g., "KRA1.2" from "KRA1.2 INNOVATION AND INDUSTRY INTEGRATION")
                $kraCode = '';
                if (preg_match('/^(KRA\d+\.\d+)/i', $kraTitle, $matches)) {
                    $kraCode = strtoupper($matches[1]);
                }
                
                $kraData = [
                    'kra_code' => $kraCode,
                    'kra_title' => $kraTitle,
                    'kpis' => []
                ];
                $vpassData[$sgIndex]['kras'][] = $kraData;
                $kraIndex = count($vpassData[$sgIndex]['kras']) - 1;
            }
            
            // Get KPI code from KPI model if available
            $kpi = KPI::where('campus_code', $campusCode)
                ->where('title', $kpiTitle)
                ->first();
            
            // Extract KPI number from title (e.g., "8" from "8 - Number of programs...")
            $kpiNo = '';
            if (preg_match('/^(\d+)\s*-\s*/', $kpiTitle, $matches)) {
                $kpiNo = $matches[1];
            } elseif ($kpi && $kpi->code) {
                $kpiNo = $kpi->code;
            }
            
            // Set responsible work units to the position of the campus user (submitter)
            $responsibleUnit = $submission->submitter && $submission->submitter->position 
                ? $submission->submitter->position 
                : 'N/A';
            
            // Create KPI data from approval
            $kpiData = [
                'kpi_no' => $kpiNo,
                'sdp_kpi_no' => $kpiNo,
                'key_performance_indicator' => $kpiTitle,
                'description' => $kpi->description ?? '',
                'responsible_work_units' => $responsibleUnit,
                'target_q1' => $approval->target_q1 ?? 0,
                'target_q2' => $approval->target_q2 ?? 0,
                'target_q3' => $approval->target_q3 ?? 0,
                'target_q4' => $approval->target_q4 ?? 0,
                'target_total' => $approval->target_total ?? 0,
                'accomplishment_q1' => $approval->accomp_q1 ?? 0,
                'accomplishment_q2' => $approval->accomp_q2 ?? 0,
                'accomplishment_q3' => $approval->accomp_q3 ?? 0,
                'accomplishment_q4' => $approval->accomp_q4 ?? 0,
                'accomplishment_total' => $approval->accomp_total ?? 0,
                'variance' => $approval->variance ?? 0,
                'rate_of_accomplishment' => round($approval->rate ?? 0, 2),
                'descriptive_rating' => $approval->rating ?? 'Needs Improvement',
            ];
            
            $vpassData[$sgIndex]['kras'][$kraIndex]['kpis'][] = $kpiData;
            $processedKPIs[$kpiKey] = true;
        }
        
        return $vpassData;
    }

    /**
     * Export VPASS Master KPI Matrix format PDF
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportVpassFormat(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus Admin
        if (!$user->isAdmin()) {
            abort(403, 'Only Campus Admin can access this feature.');
        }

        // Get campus information
        $campusCode = $user->campus_code;
        $campus = Campus::where('code', $campusCode)->first();
        $campusName = $campus ? $campus->name : $user->campus;

        // Get filter parameters
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusCode);

        // Generate PDF
        $pdf = Pdf::loadView('campus-admin.exports.vpass-format', [
            'vpassData' => $vpassData,
            'formTitle' => $formTitle,
        ])->setPaper('legal', 'landscape');

        $filename = str_replace(' ', '-', $formTitle) . '-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export VPASS Master KPI Matrix format Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportVpassExcel(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus Admin
        if (!$user->isAdmin()) {
            abort(403, 'Only Campus Admin can access this feature.');
        }

        // Get campus information
        $campusCode = $user->campus_code;
        $campus = Campus::where('code', $campusCode)->first();
        $campusName = $campus ? $campus->name : $user->campus;

        // Get filter parameters
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusCode);

        $filename = str_replace(' ', '-', $formTitle) . '-' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new VpassExcelExport($vpassData, $formTitle), $filename);
    }

    /**
     * Preview VPASS Master KPI Matrix format (HTML only, no PDF generation)
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function preview(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Campus Admin
        if (!$user->isAdmin()) {
            abort(403, 'Only Campus Admin can access this feature.');
        }

        // Get campus information
        $campusCode = $user->campus_code;
        $campus = Campus::where('code', $campusCode)->first();
        $campusName = $campus ? $campus->name : $user->campus;

        // Get filter parameters
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusCode);

        // Return preview page with VPASS data (NO PDF generation)
        return view('campus-admin.exports.vpass-preview', [
            'vpassData' => $vpassData,
            'formTitle' => $formTitle,
            'format' => $request->get('format', 'pdf'),
        ]);
    }

    /**
     * Get descriptive rating based on rate and values
     * 
     * @param float $rate
     * @param float $targetTotal
     * @param float $accomplishmentTotal
     * @return string
     */
    private function getDescriptiveRating($rate, $targetTotal, $accomplishmentTotal)
    {
        if ($targetTotal == 0) {
            return 'NO TARGET';
        }
        
        if ($accomplishmentTotal == 0) {
            return 'NO ACCOMPLISHMENT';
        }
        
        if ($rate > 100) {
            return 'ABOVE TARGET';
        }
        
        if ($rate == 100) {
            return 'MET TARGET';
        }
        
        if ($rate > 0 && $rate < 100) {
            return 'BELOW TARGET';
        }
        
        return 'NO ACCOMPLISHMENT';
    }
}

