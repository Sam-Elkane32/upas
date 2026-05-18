<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Exports\CampusUser\TsheetExport;
use App\Models\Submission;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class SuperAdminTsheetExportController extends Controller
{
    /** HTML preview only — Excel download still includes all rows. */
    private const PREVIEW_SUBMISSION_LIMIT = 80;

    /**
     * Preview TSHEET export for Campus User (HTML only)
     * Super Admin version - can query across campuses
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

        // Order by SG code (SG1, SG2, SG3, SG4, SG5) ascending, then by other fields
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('sg_code', 'asc')
            ->orderBy('template_code', 'asc')
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

        // Map submissions to TSHEET structure
        $tsheetData = $this->mapToTsheetStructure($submissions);

        // Build query string for filters
        $exportQueryString = http_build_query(array_merge($filters, ['campus_user_filter' => $campusFilter]));

        return view('super-admin.exports.tsheet-preview', [
            'submissions' => $submissions,
            'tsheetData' => $tsheetData,
            'campusName' => $campusName,
            'user' => $user,
            'filters' => $filters,
            'exportQueryString' => $exportQueryString,
        ]);
    }

    /**
     * Export TSHEET to Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
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

        // Get Campus User submissions
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
        // Order by SG code (SG1, SG2, SG3, SG4, SG5) ascending, then by other fields
        $submissions = $query->with(['template', 'submitter', 'approval'])
            ->orderBy('sg_code', 'asc')
            ->orderBy('template_code', 'asc')
            ->orderBy('kra_title', 'asc')
            ->orderBy('kpi_title', 'asc')
            ->orderBy('submitted_at', 'desc')
            ->get();

        // Get campus name
        $campusName = 'All-Campuses';
        $templateCode = 'ALL';
        if ($campusFilter) {
            $campus = Campus::find($campusFilter);
            if ($campus) {
                $campusName = str_replace(' ', '-', $campus->name);
            }
        } else if ($submissions->count() > 0) {
            $campusName = str_replace(' ', '-', $submissions->first()->campus ?? 'Multiple-Campuses');
            $templateCode = $submissions->first()->template_code ?? 'ALL';
        }

        // Map submissions to TSHEET structure
        $tsheetData = $this->mapToTsheetStructure($submissions);

        // Generate filename: {campus}_TSHEET_{templateCode}_{date}.xlsx
        $filename = $campusName . '_TSHEET_' . $templateCode . '_' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new TsheetExport($tsheetData, $campusName, $user), $filename);
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

    /**
     * Map submissions to TSHEET structure
     * Groups by Template Code, SG, KRA, KPI
     * Each group has: title info and main data rows
     * Dynamically extracts ALL fields from table_data based on template schema
     * EXACT SAME LOGIC as CampusUser TsheetExportController
     */
    private function mapToTsheetStructure($submissions)
    {
        $groupedData = [];

        foreach ($submissions as $submission) {
            // Create a unique key for grouping (Template Code, SG, KRA, KPI)
            $groupKey = ($submission->template_code ?? '') . '|' . 
                       ($submission->sg_code ?? '') . '|' . 
                       ($submission->kra_title ?? '') . '|' . 
                       ($submission->kpi_title ?? '');
            
            // Get template schema to know all available fields
            $template = $submission->template;
            $schemaFields = [];
            if ($template && $template->fields_json && isset($template->fields_json['fields'])) {
                $schemaFields = $template->fields_json['fields'];
            }
            
            // Build field mapping: normalized key => original label
            $fieldMapping = [];
            foreach ($schemaFields as $field) {
                $label = $field['label'] ?? '';
                $key = $field['key'] ?? '';
                
                // Generate normalized key (same logic as JavaScript)
                if (!$key || $key === '') {
                    $key = str_replace(['"', "'"], '', $label);
                    $key = strtolower(trim($key));
                    $key = preg_replace('/\s+/', '_', $key);
                    $key = preg_replace('/[^a-z0-9_]/', '_', $key);
                    $key = preg_replace('/_+/', '_', $key);
                    $key = trim($key, '_');
                }
                
                $fieldMapping[$key] = $label;
            }
            
            if (!isset($groupedData[$groupKey])) {
                $groupedData[$groupKey] = [
                    'title' => [
                        'template_code' => $submission->template_code ?? '',
                        'sg_code' => $submission->sg_code ?? '',
                        'kra_title' => $submission->kra_title ?? '',
                        'kpi_title' => $submission->kpi_title ?? '',
                    ],
                    'field_mapping' => $fieldMapping, // Store field mapping for this group
                    'main_data_rows' => []
                ];
            } else {
                // Merge field mappings to ensure we have all fields
                $groupedData[$groupKey]['field_mapping'] = array_merge(
                    $groupedData[$groupKey]['field_mapping'] ?? [],
                    $fieldMapping
                );
            }

            // Extract main data rows from table_data - include ALL fields directly (like PDF export)
            // Normalize table_data first to avoid array_keys on null/non-array
            $tableData = $submission->table_data;
            if (is_string($tableData)) {
                $decoded = json_decode($tableData, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $tableData = $decoded;
                }
            }
            $tableData = is_array($tableData) ? $tableData : [];

            if (!empty($tableData)) {
                // Collect ALL unique keys from ALL rows in this submission
                $allDataKeys = [];
                foreach ($tableData as $dataRow) {
                    if (is_array($dataRow)) {
                        $allDataKeys = array_merge($allDataKeys, array_keys($dataRow));
                    }
                }
                $allDataKeys = array_unique($allDataKeys);
                
                // Store all data keys for this group (to ensure we include ALL fields)
                if (!isset($groupedData[$groupKey]['all_data_keys'])) {
                    $groupedData[$groupKey]['all_data_keys'] = [];
                }
                $groupedData[$groupKey]['all_data_keys'] = array_merge(
                    $groupedData[$groupKey]['all_data_keys'],
                    $allDataKeys
                );
                $groupedData[$groupKey]['all_data_keys'] = array_unique($groupedData[$groupKey]['all_data_keys']);
                
                foreach ($tableData as $dataRow) {
                    if (!is_array($dataRow)) {
                        continue; // skip invalid rows
                    }
                    $rowData = [];
                    
                    // EXACT PDF APPROACH: Preserve keys and values exactly as stored
                    // PDF: @foreach($row as $value) - iterates through values in row order
                    // We preserve the original structure so extraction matches PDF behavior
                    foreach ($dataRow as $key => $value) {
                        // Preserve original key and value exactly as stored
                        // Convert null to empty string for consistency (like PDF: $value ?? '')
                        $rowData[$key] = ($value === null || $value === '') ? '' : $value;
                    }
                    
                    $groupedData[$groupKey]['main_data_rows'][] = $rowData;
                }
            } else {
                // If no table_data, add empty row
                $emptyRow = ['quarter' => $submission->quarter ?? ''];
                $groupedData[$groupKey]['main_data_rows'][] = $emptyRow;
            }
        }

        // Sort grouped data by SG code (SG1, SG2, SG3, SG4, SG5) ascending
        uksort($groupedData, function($a, $b) {
            // Extract SG code from group key (format: template_code|sg_code|kra_title|kpi_title)
            $partsA = explode('|', $a);
            $partsB = explode('|', $b);
            $sgA = $partsA[1] ?? '';
            $sgB = $partsB[1] ?? '';
            
            // Compare SG codes (SG1 < SG2 < SG3 < SG4 < SG5)
            return strcmp($sgA, $sgB);
        });

        return $groupedData;
    }
}

