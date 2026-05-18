<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Submission;
use App\Models\Approval;
use App\Models\KPI;
use App\Models\KRA;
use App\Models\StrategicGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CampusAdmin\VpassExcelExport;

class SuperAdminVpassExportController extends Controller
{
    /**
     * Get VPASS data from approved submissions (Super Admin version - can query across campuses)
     * 
     * @param array $filters
     * @param string|null $campusFilter
     * @return array
     */
    private function getVpassDataFromApprovals(array $filters, $campusFilter = null): array
    {
        // Get approved submissions based on filters
        $submissionsQuery = Submission::where('status', 'Approved')
            ->with(['submitter']);
        
        // Apply campus filter if provided
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $submissionsQuery->where('campus', $campus->name);
            }
        }
        
        // Get approvals separately and join
        $approvalIds = Approval::whereNotNull('validated_at')
            ->pluck('submission_id')
            ->toArray();
        
        $submissionsQuery->whereIn('id', $approvalIds);
        
        // Apply filters
        if (!empty($filters['form_title'])) {
            $submissionsQuery->where('form_title', $filters['form_title']);
        }
        if (!empty($filters['sg_code'])) {
            $submissionsQuery->where('sg_code', $filters['sg_code']);
        }
        if (!empty($filters['kra_title'])) {
            $submissionsQuery->where('kra_title', $filters['kra_title']);
        }
        
        $submissions = $submissionsQuery->get();
        
        // Organize data by SG -> KRA -> KPI
        $vpassData = [];
        $processedKPIs = [];
        
        foreach ($submissions as $submission) {
            $sgCode = $submission->sg_code ?? 'N/A';
            $kraTitle = $submission->kra_title ?? 'N/A';
            $kpiTitle = $submission->kpi_title ?? 'N/A';
            
            // Find or create SG entry
            $sgIndex = null;
            foreach ($vpassData as $index => $sg) {
                if ($sg['sg_code'] === $sgCode) {
                    $sgIndex = $index;
                    break;
                }
            }
            
            if ($sgIndex === null) {
                $vpassData[] = [
                    'sg_code' => $sgCode,
                    'kras' => []
                ];
                $sgIndex = count($vpassData) - 1;
            }
            
            // Find or create KRA entry within SG
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
                } else {
                    // If regex doesn't match, try to find KRA in database by title (with error handling)
                    try {
                        $hasTitleColumn = Schema::hasColumn('k_r_a_s', 'title');
                        if ($hasTitleColumn) {
                            // First, try to get Strategic Goal to narrow down search
                            $strategicGoal = StrategicGoal::where('code', $sgCode)->first();
                            if ($strategicGoal) {
                                // Try exact match first
                                $kra = KRA::where('strategic_goal_id', $strategicGoal->id)
                                    ->where('title', $kraTitle)
                                    ->first();
                                
                                // If exact match fails, try partial match (case-insensitive)
                                if (!$kra) {
                                    $kra = KRA::where('strategic_goal_id', $strategicGoal->id)
                                        ->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kraTitle) . '%'])
                                        ->first();
                                }
                                
                                if ($kra && isset($kra->code) && $kra->code) {
                                    $kraCode = $kra->code;
                                }
                            } else {
                                // Fallback: search all KRAs without SG filter
                                $kra = KRA::where('title', $kraTitle)->first();
                                if (!$kra) {
                                    $kra = KRA::whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kraTitle) . '%'])->first();
                                }
                                if ($kra && isset($kra->code) && $kra->code) {
                                    $kraCode = $kra->code;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        // Silently fail if table structure is incomplete
                        // KRA code will remain empty and show as 'N/A'
                    }
                }
                
                $vpassData[$sgIndex]['kras'][] = [
                    'kra_title' => $kraTitle,
                    'kra_code' => $kraCode ?: 'N/A',
                    'kpis' => []
                ];
                $kraIndex = count($vpassData[$sgIndex]['kras']) - 1;
            }
            
            // Create unique key for KPI to avoid duplicates
            $kpiKey = $sgCode . '|' . $kraTitle . '|' . $kpiTitle;
            
            if (isset($processedKPIs[$kpiKey])) {
                continue;
            }
            
            // Get approval data
            $approval = Approval::where('submission_id', $submission->id)->first();
            $rate = $approval ? $approval->rate : 0;
            
            // Get campus code from submission to look up KPI model
            $campusName = $submission->campus;
            $campus = Campus::where('name', $campusName)->first();
            $campusCode = $campus ? $campus->code : null;
            
            // Extract KPI number from title (e.g., "8" from "8 - Number of programs...")
            $kpiNo = '';
            $kpi = null; // Initialize for later use in description
            
            if (preg_match('/^(\d+)\s*-\s*/', $kpiTitle, $matches)) {
                $kpiNo = $matches[1];
                // Still try to get KPI model for description (with error handling)
                try {
                    if ($campusCode) {
                        // Check if table has title column before querying
                        $hasTitleColumn = Schema::hasColumn('k_p_i_s', 'title');
                        if ($hasTitleColumn) {
                            $kpi = KPI::where('campus_code', $campusCode)
                                ->where(function($query) use ($kpiTitle) {
                                    $query->where('title', $kpiTitle)
                                          ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kpiTitle) . '%']);
                                })
                                ->first();
                        }
                    }
                    if (!$kpi && Schema::hasColumn('k_p_i_s', 'title')) {
                        $kpi = KPI::where('title', $kpiTitle)
                            ->orWhereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kpiTitle) . '%'])
                            ->first();
                    }
                } catch (\Exception $e) {
                    // Silently fail if table structure is incomplete
                    $kpi = null;
                }
            } else {
                // If regex doesn't match, try to find KPI in database by title (with error handling)
                try {
                    $hasTitleColumn = Schema::hasColumn('k_p_i_s', 'title');
                    if (!$hasTitleColumn) {
                        // If title column doesn't exist, skip database lookup
                        // Last resort: try to extract any number from the title
                        if (preg_match('/(\d+)/', $kpiTitle, $numberMatches)) {
                            $kpiNo = $numberMatches[1];
                        }
                    } else {
                        if ($campusCode) {
                            // Try exact match first with campus code
                            $kpi = KPI::where('campus_code', $campusCode)
                                ->where('title', $kpiTitle)
                                ->first();
                            
                            // If exact match fails, try partial match (case-insensitive) with campus code
                            if (!$kpi) {
                                $kpi = KPI::where('campus_code', $campusCode)
                                    ->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kpiTitle) . '%'])
                                    ->first();
                            }
                        }
                        
                        // If still not found, try without campus code filter
                        if (!$kpi) {
                            $kpi = KPI::where('title', $kpiTitle)->first();
                            if (!$kpi) {
                                $kpi = KPI::whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($kpiTitle) . '%'])->first();
                            }
                        }
                        
                        // Get KPI code from model if found
                        if ($kpi && isset($kpi->code) && $kpi->code) {
                            $kpiNo = $kpi->code;
                        } else {
                            // Last resort: try to extract any number from the title
                            if (preg_match('/(\d+)/', $kpiTitle, $numberMatches)) {
                                $kpiNo = $numberMatches[1];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Silently fail if table structure is incomplete or query fails
                    // Last resort: try to extract any number from the title
                    if (preg_match('/(\d+)/', $kpiTitle, $numberMatches)) {
                        $kpiNo = $numberMatches[1];
                    }
                }
            }
            
            // Set responsible work units to the position of the campus user (submitter)
            $responsibleUnit = $submission->submitter && $submission->submitter->position 
                ? $submission->submitter->position 
                : 'N/A';
            
            // Extract quarterly data from approval (not submission)
            // If approval doesn't exist, use 0 as defaults
            $targetQ1 = $approval ? ($approval->target_q1 ?? 0) : 0;
            $targetQ2 = $approval ? ($approval->target_q2 ?? 0) : 0;
            $targetQ3 = $approval ? ($approval->target_q3 ?? 0) : 0;
            $targetQ4 = $approval ? ($approval->target_q4 ?? 0) : 0;
            $accomplishmentQ1 = $approval ? ($approval->accomp_q1 ?? 0) : 0;
            $accomplishmentQ2 = $approval ? ($approval->accomp_q2 ?? 0) : 0;
            $accomplishmentQ3 = $approval ? ($approval->accomp_q3 ?? 0) : 0;
            $accomplishmentQ4 = $approval ? ($approval->accomp_q4 ?? 0) : 0;
            
            $targetTotal = $approval ? ($approval->target_total ?? 0) : 0;
            $accomplishmentTotal = $approval ? ($approval->accomp_total ?? 0) : 0;
            
            // Use variance from approval if available, otherwise calculate it
            $variance = $approval && isset($approval->variance) ? $approval->variance : ($targetTotal - $accomplishmentTotal);
            
            // Use rate from approval, or calculate if not available
            $rateOfAccomplishment = $rate;
            if ($rate == 0 && $targetTotal > 0) {
                $rateOfAccomplishment = ($accomplishmentTotal / $targetTotal) * 100;
            }
            
            $kpiData = [
                'kpi_no' => $kpiNo ?: 'N/A',
                'sdp_kpi_no' => $kpiNo ?: 'N/A',
                'key_performance_indicator' => $kpiTitle,
                'description' => ($kpi && $kpi->description) ? $kpi->description : ($submission->description ?? ''),
                'responsible_work_units' => $responsibleUnit,
                'target_q1' => $targetQ1,
                'target_q2' => $targetQ2,
                'target_q3' => $targetQ3,
                'target_q4' => $targetQ4,
                'target_total' => $targetTotal,
                'accomplishment_q1' => $accomplishmentQ1,
                'accomplishment_q2' => $accomplishmentQ2,
                'accomplishment_q3' => $accomplishmentQ3,
                'accomplishment_q4' => $accomplishmentQ4,
                'accomplishment_total' => $accomplishmentTotal,
                'variance' => $variance,
                'rate' => $rate,
                'rate_of_accomplishment' => round($rateOfAccomplishment, 2),
                'descriptive_rating' => $approval ? ($approval->rating ?? $this->getDescriptiveRating($rate, $targetTotal, $accomplishmentTotal)) : $this->getDescriptiveRating($rate, $targetTotal, $accomplishmentTotal),
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
    public function exportPdf(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Super Admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filter parameters
        $campusFilter = $request->get('campus_admin_filter');
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusFilter);

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
    public function exportExcel(Request $request)
    {
        $user = Auth::user();
        
        // Ensure user is Super Admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filter parameters
        $campusFilter = $request->get('campus_admin_filter');
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusFilter);

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
        
        // Ensure user is Super Admin
        if (!$user->isSuperAdmin()) {
            abort(403, 'Only Super Admin can access this feature.');
        }

        // Get filter parameters
        $campusFilter = $request->get('campus_admin_filter');
        $filters = [
            'form_title' => $request->get('form_title'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
        ];
        
        // Get Form Title for report title
        $formTitle = $filters['form_title'] ?? 'Performance Report';
        
        // Get VPASS data from approved submissions
        $vpassData = $this->getVpassDataFromApprovals($filters, $campusFilter);

        // Return preview page with VPASS data (NO PDF generation)
        return view('super-admin.exports.vpass-preview', [
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

