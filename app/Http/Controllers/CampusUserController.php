<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Template;
use App\Models\TemplateEditHistory;
use App\Models\User;
use App\Notifications\TemplateSubmissionNotification;
use App\Services\ComputeService;
use App\Services\SubmissionService;
use App\Services\TableDataAuditHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampusUserController extends Controller
{
    protected SubmissionService $submissionService;

    protected ComputeService $computeService;

    public function __construct(SubmissionService $submissionService, ComputeService $computeService)
    {
        $this->middleware(['auth', 'role:creator_editor|planning_coordinator']);
        $this->submissionService = $submissionService;
        $this->computeService = $computeService;
    }
    
    /**
     * Get all possible campus codes for a user (handles variations like 'ALAM' vs 'ALAMINOS')
     */
    private function getUserCampusCodes($user): array
    {
        $campusCode = $user->campus_code;
        $campus = $user->campusInfo;
        
        $campusCodes = [];
        if ($campusCode) {
            $campusCodes[] = $campusCode;
        }
        
        if ($campus) {
            $campusCodes[] = $campus->code;
            if ($campusCode) {
                $campusCodes[] = strtoupper($campusCode);
                $campusCodes[] = strtolower($campusCode);
            }
            $campusCodes[] = strtoupper($campus->code);
            $campusCodes[] = strtolower($campus->code);
        }
        
        // Remove duplicates and filter out null/empty values
        return array_unique(array_filter($campusCodes));
    }

    /**
     * Planning Coordinator Dashboard
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get user's recent submissions
        $recentSubmissions = Submission::forUser($user->id)
            ->with(['form', 'template'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get published forms count for user's campus
        $publishedFormsCount = Form::getPublishedForCampus($user->campus_code)->count();

        // Get assigned templates count (for Planning Coordinators)
        // Exclude templates that have returned submissions
        $assignedTemplatesCount = 0;
        $returnedTemplatesCount = 0;
        if ($user->isPlanningCoordinator()) {
            // Get all assigned templates (multi-assign or legacy single)
            $allAssignedTemplates = Template::assignedToUser($user->id)
                ->where('status', 'Published')
                ->pluck('template_code')
                ->toArray();
            
            // Get template codes that have returned submissions
            $returnedTemplateCodes = Submission::where('submitted_by', $user->id)
                ->whereIn('template_code', $allAssignedTemplates)
                ->where('status', 'Returned')
                ->pluck('template_code')
                ->unique()
                ->toArray();
            
            // Count assigned templates (excluding those with returned submissions)
            $assignedTemplatesCount = Template::assignedToUser($user->id)
                ->where('status', 'Published')
                ->whereNotIn('template_code', $returnedTemplateCodes)
                ->count();
            
            // Count returned templates
            $returnedTemplatesCount = !empty($returnedTemplateCodes) 
                ? Template::whereIn('template_code', $returnedTemplateCodes)
                    ->where('status', 'Published')
                    ->count()
                : 0;
        }

        // Get submission statistics
        $submissionStats = [
            'total' => Submission::forUser($user->id)->count(),
            'pending' => Submission::forUser($user->id)->pendingReview()->count(),
            'approved' => Submission::forUser($user->id)->approved()->count(),
            'returned' => Submission::forUser($user->id)->returned()->count(),
            'assigned_templates' => $assignedTemplatesCount,
            'returned_templates' => $returnedTemplatesCount,
        ];

        return view('campus-user.dashboard', compact(
            'recentSubmissions',
            'publishedFormsCount',
            'submissionStats',
        ));
    }

    /**
     * Show the form for creating a new submission
     */
    public function createSubmission()
    {
        $user = Auth::user();
        
        // For Planning Coordinators: Get assigned templates and returned templates separately
        // For Creator/Editors: Get templates by campus code
        if ($user->isPlanningCoordinator()) {
            // Get all assigned templates (multi-assign or legacy single)
            $allAssignedTemplates = Template::assignedToUser($user->id)
                ->where('status', 'Published')
                ->pluck('template_code')
                ->toArray();
            
            // Get returned submissions for assigned templates
            $returnedSubmissions = Submission::where('submitted_by', $user->id)
                ->whereIn('template_code', $allAssignedTemplates)
                ->where('status', 'Returned')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get unique template codes from returned submissions
            $returnedTemplateCodes = $returnedSubmissions->pluck('template_code')->unique()->toArray();
            
            // Get returned templates (with returned submissions); load form to distinguish same-code templates
            $returnedTemplates = Template::with('form')
                ->whereIn('template_code', $returnedTemplateCodes)
                ->assignedToUser($user->id)
                ->orderBy('template_code')
                ->orderBy('id')
                ->get();
            
            // Get assigned templates (excluding those with returned submissions); load form to show which form each template belongs to
            $assignedTemplates = Template::with('form')
                ->assignedToUser($user->id)
                ->where('status', 'Published')
                ->whereNotIn('template_code', $returnedTemplateCodes)
                ->orderBy('template_code')
                ->orderBy('id')
                ->get();
            
            // Get all submissions for assigned templates (scoped by template_id so each template has its own data)
            $allSubmissions = Submission::where('submitted_by', $user->id)
                ->whereIn('template_id', $assignedTemplates->pluck('id')->filter()->toArray())
                ->get()
                ->groupBy('template_id');
            // Include submissions with null template_id (legacy) grouped by template_code for templates that match
            $legacySubmissions = Submission::where('submitted_by', $user->id)
                ->whereNull('template_id')
                ->whereIn('template_code', $allAssignedTemplates)
                ->get()
                ->groupBy('template_code');

            $assignedSubmissionsByTemplate = [];
            foreach ($assignedTemplates as $template) {
                $byId = $allSubmissions->get($template->id, collect());
                // Only use submissions with valid template_id - orphaned drafts (template deleted) cannot be edited
                $latestSubmission = $byId->sortByDesc('updated_at')->first();
                if ($latestSubmission) {
                    $assignedSubmissionsByTemplate[$template->id] = $latestSubmission;
                }
            }

            $returnedSubmissionsByTemplate = [];
            foreach ($returnedTemplates as $template) {
                $returnedSubmission = $returnedSubmissions
                    ->where('template_id', $template->id)
                    ->sortByDesc('created_at')
                    ->first();
                if ($returnedSubmission) {
                    $returnedSubmission->load('approval');
                    $returnedSubmissionsByTemplate[$template->id] = $returnedSubmission;
                }
            }
            
            return view('campus-user.create-submission', compact(
                'assignedTemplates', 
                'returnedTemplates', 
                'assignedSubmissionsByTemplate', 
                'returnedSubmissionsByTemplate'
            ));
        } else {
            // For Creator/Editors: Get templates by campus code
            $campusCodes = $this->getUserCampusCodes($user);
            
            // Build query to match templates by campus code
            $templatesQuery = Template::where('status', 'Published');
            
            // Include templates for user's campus OR templates with null campus_code (All Campuses)
            $templatesQuery->where(function ($q) use ($campusCodes) {
                if (!empty($campusCodes)) {
                    $q->whereIn('campus_code', $campusCodes);
                }
                $q->orWhereNull('campus_code');
            });
            
            $templates = $templatesQuery->orderBy('template_code')->get();
            
            // Get existing submissions for each template to determine action
            $submissionsByTemplate = [];
            $submissions = Submission::where('submitted_by', $user->id)
                ->whereIn('template_code', $templates->pluck('template_code'))
                ->get()
                ->groupBy('template_code');
            
            foreach ($templates as $template) {
                $templateSubmissions = $submissions->get($template->template_code, collect());
                $latestSubmission = $templateSubmissions->sortByDesc('updated_at')->first();
                if ($latestSubmission) {
                    $submissionsByTemplate[$template->template_code] = $latestSubmission;
                }
            }
            
            return view('campus-user.create-submission', compact('templates', 'submissionsByTemplate'));
        }
    }

    /**
     * Display returned templates for Planning Coordinator
     * Shows only templates that have returned submissions
     */
    public function returnedTemplates()
    {
        $user = Auth::user();
        
        // Only for Planning Coordinators
        if (!$user->isPlanningCoordinator()) {
            return redirect()->route('campus-user.create-submission');
        }
        
        // Get templates assigned to this Planning Coordinator (multi-assign or legacy single)
        $assignedTemplates = Template::assignedToUser($user->id)
            ->where('status', 'Published')
            ->pluck('template_code')
            ->toArray();
        
        // Get returned submissions for assigned templates
        $returnedSubmissions = Submission::where('submitted_by', $user->id)
            ->whereIn('template_code', $assignedTemplates)
            ->where('status', 'Returned')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Get unique template codes from returned submissions
        $returnedTemplateCodes = $returnedSubmissions->pluck('template_code')->unique()->toArray();
        
        // Get templates that have returned submissions
        $templates = Template::whereIn('template_code', $returnedTemplateCodes)
            ->assignedToUser($user->id)
            ->orderBy('template_code')
            ->get();
        
        // Build submissionsByTemplate array for returned submissions only
        $submissionsByTemplate = [];
        foreach ($templates as $template) {
            // Get the most recent returned submission for this template
            $returnedSubmission = $returnedSubmissions
                ->where('template_code', $template->template_code)
                ->sortByDesc('created_at')
                ->first();
            
            if ($returnedSubmission) {
                // Load approval relationship to get remarks
                $returnedSubmission->load('approval');
                $submissionsByTemplate[$template->template_code] = $returnedSubmission;
            }
        }
        
        return view('campus-user.returned-templates', compact('templates', 'submissionsByTemplate'));
    }

    /**
     * Open a template for input (create draft submission if needed)
     */
    public function openTemplate(Request $request)
    {
        $user = Auth::user();
        $templateId = $request->input('template_id');
        $templateCode = $request->input('template_code');

        // Resolve template: prefer template_id so the correct template instance is used
        $template = null;
        if ($templateId) {
            $template = Template::find($templateId);
            if ($template) {
                $templateCode = $template->template_code;
            }
        }
        if (!$template && $templateCode) {
            // Fallback: first template with this code (existing behavior)
            if ($user->isPlanningCoordinator()) {
                $template = Template::where('template_code', $templateCode)
                    ->assignedToUser($user->id)
                    ->where('status', 'Published')
                    ->first();
            } else {
                $primaryCampusCode = $user->campus_code;
                $candidates = Template::where('template_code', $templateCode)
                    ->where('status', 'Published')
                    ->get();
                $template = $primaryCampusCode
                    ? $candidates->first(fn ($t) => $t->allowsCampus($primaryCampusCode))
                    : $candidates->first(fn ($t) => $t->allowsAllCampuses());
            }
        }

        if (!$template) {
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'Template code is required or template not found.');
        }

        // Block access if the template has been locked by the Super Admin
        if ($template->isLocked()) {
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'This template has been locked by the administrator. Access is not allowed at this time.');
        }

        $templateCode = $template->template_code;

        // Check existing draft / returned by template_id (so each template has its own data)
        $existingDraft = Submission::where('template_id', $template->id)
            ->where('submitted_by', $user->id)
            ->where(function ($query) {
                $query->where('status', 'Unpublished')->orWhere('is_draft', true);
            })
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($existingDraft) {
            return redirect()->route('campus-user.edit-submission', $existingDraft);
        }

        $returnedSubmission = Submission::where('template_id', $template->id)
            ->where('submitted_by', $user->id)
            ->where('status', 'Returned')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($returnedSubmission) {
            return redirect()->route('campus-user.edit-submission', $returnedSubmission);
        }

        try {
            DB::beginTransaction();

            // Use same campus resolution as createSubmissionRecord so QA Coordinator can find submissions
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? '';
            $campusCode = $user->campus_code ?? null;
            $formTitle = $template->sg_code . ' - ' . $template->template_code;

            $submission = Submission::create([
                'template_id' => $template->id,
                'form_id' => $template->form_id,
                'template_code' => $templateCode,
                'form_title' => $formTitle,
                'sg_code' => $template->sg_code,
                'kra_title' => $template->kra_title,
                'kpi_title' => $template->kpi_title,
                'campus' => $campusName,
                'campus_code' => $campusCode,
                'quarter' => '', // Will be set when user fills the form
                'table_data' => [],
                'status' => 'Unpublished',
                'submitted_by' => $user->id,
                'is_draft' => true,
            ]);
            
            DB::commit();
            
            return redirect()->route('campus-user.edit-submission', $submission);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating draft submission', [
                'error' => $e->getMessage(),
                'template_code' => $templateCode,
                'user_id' => $user->id,
            ]);
            
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'Failed to open template: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created submission
     */
    public function storeSubmission(Request $request)
    {
        $isDraft = ($request->input('action', $request->input('submit_action', 'submit')) === 'draft');
        $templateCode = $this->getTemplateCode($request);
        
        if (!$templateCode) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['template_code' => 'Template code is required. Please select a template.']);
        }
        
        $request->merge(['template_code' => $templateCode]);
        
        try {
            $this->validateSubmissionRequest($request, $isDraft);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed in storeSubmission', ['errors' => $e->errors()]);
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }

        try {
            DB::beginTransaction();
            
            $user = Auth::user();

            // Prefer template_id so the correct template instance is used (e.g. new form's T1 vs old form's T1)
            $template = null;
            if ($request->filled('template_id')) {
                $template = Template::find($request->template_id);
                if ($template && $template->template_code !== $templateCode) {
                    $template = null;
                }
                if ($template && $user->isPlanningCoordinator() && !$template->isAssignedToUser($user->id)) {
                    abort(403, 'You do not have permission to create submissions for this template.');
                }
            }
            if (!$template) {
                if ($user->isPlanningCoordinator()) {
                    $template = Template::where('template_code', $templateCode)
                        ->assignedToUser($user->id)
                        ->where('status', 'Published')
                        ->firstOrFail();
                    if (!$template->isAssignedToUser($user->id)) {
                        abort(403, 'You do not have permission to create submissions for this template.');
                    }
                } else {
                    $template = $this->getTemplate($templateCode, $user->campus_code);
                }
            }

            // Block submission if the template has been locked by the Super Admin
            if ($template && $template->isLocked()) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['template_locked' => 'This template has been locked by the administrator. Submissions are not allowed.']);
            }
            
            // Log raw request data for debugging
            \Log::info('storeSubmission - Raw request data:', [
                'table_data_type' => gettype($request->table_data),
                'table_data_count' => is_array($request->table_data) ? count($request->table_data) : 0,
                'is_draft' => $isDraft,
                'template_code' => $templateCode,
            ]);
            
            // Log first row structure if available
            if (is_array($request->table_data) && !empty($request->table_data)) {
                $firstRow = $request->table_data[0] ?? [];
                \Log::info('storeSubmission - First row structure:', [
                    'keys' => is_array($firstRow) ? array_keys($firstRow) : [],
                    'sample_values' => is_array($firstRow) ? array_map(function($v) {
                        return is_string($v) ? substr($v, 0, 50) : $v;
                    }, array_slice($firstRow, 0, 5)) : [],
                ]);
            }
            
            $tableData = $this->processTableData($request->table_data, $isDraft);

            // Compute calculated fields (formulas, summaries) based on template schema
            $schemaFields = $template->getSchemaFields();
            $summaryRules = $template->getSummaryRules();
            $summaryCellMappings = $template->getSummaryCellMappings();
            if (!empty($schemaFields)) {
                $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
            }
            
            // Log processed data
            \Log::info('storeSubmission - Processed table data:', [
                'row_count' => count($tableData),
                'first_row_keys' => !empty($tableData) && is_array($tableData[0]) ? array_keys($tableData[0]) : [],
            ]);
            
            $quarter = $this->extractQuarter($tableData);
            
            if (!$isDraft) {
                $this->checkDuplicateSubmission($template->id, $templateCode, $user->id, $quarter, $user);
                $this->validateTableData($tableData, $template->getSchemaFields());
            }
            
            $submission = $this->createSubmissionRecord($template, $user, $templateCode, $quarter, $tableData, $isDraft);
            
            // Log what was saved
            \Log::info('storeSubmission - Submission created:', [
                'submission_id' => $submission->id,
                'table_data_saved' => is_array($submission->table_data) ? count($submission->table_data) : 0,
                'is_draft' => $submission->is_draft,
            ]);
            
            if ($isDraft) {
                $submission->submitted_at = null;
                $submission->save();
            }

            $campusNameForAudit = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? 'Unknown Campus';
            $quarterForAudit = $quarter ?: ($submission->quarter ?? '');
            $auditMsg = $isDraft
                ? "{$user->name} ({$campusNameForAudit}) saved a draft for {$template->template_code}" . ($quarterForAudit ? ", {$quarterForAudit}" : '') . '.'
                : "{$user->name} ({$campusNameForAudit}) submitted {$template->template_code}" . ($quarterForAudit ? ", {$quarterForAudit}" : '') . ' — now pending QA review.';
            $this->logSubmissionEditForAudit($template->id, $user->id, $auditMsg);

            DB::commit();

            // Notify QA Coordinator(s) of the specific campus when template is submitted
            if (!$isDraft) {
                $this->notifyQACoordinatorsOfCampus($submission);
            }
            
            $message = $isDraft 
                ? 'Submission saved as draft successfully!'
                : 'Submission created successfully! It is now pending review by the QA Coordinator of your campus.';
            
            return redirect()->route('campus-user.create-submission')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating submission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create submission: ' . $e->getMessage());
        }
    }
    
    /**
     * Get template code from request
     */
    private function getTemplateCode(Request $request): ?string
    {
        return $request->input('template_code') ?: $request->input('template_code_hidden');
    }
    
    /**
     * Validate submission request
     */
    private function validateSubmissionRequest(Request $request, bool $isDraft): void
    {
        $rules = [
            'template_code' => 'required|exists:templates,template_code',
        ];
        
        if ($isDraft) {
            $rules['table_data'] = 'nullable|array';
        } else {
            $rules['table_data'] = 'required|array|min:1';
        }
        
        $request->validate($rules, [
            'template_code.required' => 'Please select a template.',
            'template_code.exists' => 'The selected template does not exist.',
            'table_data.required' => 'Please add at least one row of data.',
            'table_data.array' => 'Table data must be in the correct format.',
            'table_data.min' => 'Please add at least one row of data.',
        ]);
    }
    
    /**
     * Get and validate template (must allow the given campus code).
     */
    private function getTemplate(string $templateCode, string $campusCode): Template
    {
        $candidates = Template::where('template_code', $templateCode)->where('status', 'Published')->get();
        $template = $candidates->first(fn ($t) => $t->allowsCampus($campusCode));
        if (!$template) {
            throw new \Exception('Template not found or not available for your campus.');
        }
        return $template;
    }

    /**
     * Log a submission edit for Audit Trailing (Super Admin template show → Editing History).
     */
    private function logSubmissionEditForAudit(int $templateId, int $userId, string $whatEdited): void
    {
        $text = strlen($whatEdited) > 500 ? substr($whatEdited, 0, 497) . '...' : $whatEdited;
        TemplateEditHistory::create([
            'template_id' => $templateId,
            'user_id' => $userId,
            'what_edited' => $text,
        ]);
    }

    /**
     * Notify QA Coordinators of the specific campus when a template is submitted.
     * Ensures the submission is routed to the correct campus's QA Coordinator(s).
     */
    private function notifyQACoordinatorsOfCampus(Submission $submission): void
    {
        $campusCode = $submission->campus_code;
        $campusName = $submission->campus;

        if (!$campusCode && !$campusName) {
            \Log::warning('Submission has no campus_code or campus - cannot notify QA Coordinators', [
                'submission_id' => $submission->id,
            ]);
            return;
        }

        // QA Coordinators are users with role 'admin' assigned to this campus
        // Match by campus_code (primary) or campus name for alignment with approval flow
        $query = User::where('role', 'admin');
        if ($campusCode) {
            $query->where('campus_code', $campusCode);
        } elseif ($campusName) {
            $query->where('campus', $campusName);
        }
        $qaCoordinators = $query->get();

        foreach ($qaCoordinators as $coordinator) {
            $coordinator->notify(new TemplateSubmissionNotification($submission));
        }
    }

    /**
     * Process and clean table data
     */
    private function processTableData($tableData, bool $isDraft): array
    {
        \Log::info('processTableData - Input:', [
            'type' => gettype($tableData),
            'is_array' => is_array($tableData),
            'count' => is_array($tableData) ? count($tableData) : 0,
            'is_draft' => $isDraft,
        ]);
        
        if ($isDraft && (empty($tableData) || !is_array($tableData))) {
            \Log::info('processTableData - Draft mode with empty/invalid data, initializing empty array');
            $tableData = [];
        }
        
        if (!is_array($tableData)) {
            \Log::error('processTableData - Invalid format:', [
                'type' => gettype($tableData),
                'value' => $tableData,
            ]);
            throw new \Exception('Invalid table_data format. Expected array, got: ' . gettype($tableData));
        }
        
        $processedData = [];
        foreach ($tableData as $index => $row) {
            \Log::info("processTableData - Processing row {$index}:", [
                'row_type' => gettype($row),
                'is_object' => is_object($row),
                'is_array' => is_array($row),
            ]);
            
            if (is_object($row)) {
                $row = (array) $row;
            }
            
            if (!is_array($row)) {
                \Log::warning("processTableData - Row {$index} is not an array, skipping");
                continue;
            }
            
            // Log row structure
            \Log::info("processTableData - Row {$index} structure:", [
                'keys' => array_keys($row),
                'key_count' => count($row),
                'has_values' => !empty(array_filter($row, function ($v, $k) {
                    if ($k === '_meta' || $k === '_after_separator') {
                        return false;
                    }

                    return $this->tableCellToTrimmedString($v) !== '';
                }, ARRAY_FILTER_USE_BOTH)),
            ]);
            
            if ($isDraft) {
                // For drafts, preserve ALL rows and ALL fields (including _after_separator for section structure)
                // Works for any data type, with or without auto-calculated columns
                $preservedRow = [];
                foreach ($row as $key => $value) {
                    // Preserve the value exactly as it is, even if empty
                    $preservedRow[$key] = $value === null ? '' : $value;
                }
                $processedData[] = $preservedRow;
                \Log::info("processTableData - Draft mode: Preserved row {$index} with " . count($preservedRow) . " fields", [
                    'keys' => array_keys($preservedRow),
                    'has_empty_values' => in_array('', $preservedRow, true) || in_array(null, $preservedRow, true),
                ]);
            } else {
                // Blue summary rows mark section boundaries; they must survive final submit even if every
                // cell is still "—" or the client sent only _meta (QA / approvals rely on row_type=summary).
                if ($this->tableDataRowIsSummary($row)) {
                    $processedData[] = $row;
                    \Log::info("processTableData - Submission mode: Preserved summary (blue) row {$index}");

                    continue;
                }
                // For submissions, only include rows with at least one non-empty value
                $hasData = false;
                foreach ($row as $key => $value) {
                    if ($key === '_meta' || $key === '_after_separator') {
                        continue;
                    }
                    if ($this->tableCellToTrimmedString($value) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $processedData[] = $row;
                    \Log::info("processTableData - Submission mode: Accepted row {$index}");
                } else {
                    \Log::info("processTableData - Submission mode: Rejected row {$index} (no data)");
                }
            }
        }
        
        $tableData = array_values($processedData);
        
        \Log::info('processTableData - Output:', [
            'row_count' => count($tableData),
            'is_draft' => $isDraft,
        ]);
        
        if ($isDraft && empty($tableData)) {
            \Log::info('processTableData - Draft mode: No rows, creating empty row with quarter');
            $tableData = [['quarter' => '1st Q']];
        }
        
        if (empty($tableData)) {
            \Log::error('processTableData - No valid rows after processing');
            throw new \Exception('At least one row of data is required. Please ensure you have filled in at least one field in the table.');
        }
        
        return $tableData;
    }
    
    /**
     * Extract quarter from table data
     */
    private function extractQuarter(array $tableData): string
    {
        $firstRow = $tableData[0] ?? [];
        $quarter = $firstRow['quarter'] 
            ?? $firstRow['Quarter'] 
            ?? $firstRow['QUARTER']
            ?? $firstRow['quarter_']
            ?? null;
        
        if (!$quarter) {
            throw new \Exception('Quarter is required in the table data. Available fields: ' . implode(', ', array_keys($firstRow)));
        }
        
        return $quarter;
    }
    
    /**
     * Check for duplicate submission (per template instance, quarter, campus)
     */
    private function checkDuplicateSubmission($templateId, string $templateCode, int $userId, string $quarter, $user): void
    {
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? '';

        $query = Submission::where('submitted_by', $userId)
            ->where('quarter', $quarter)
            ->where('campus', $campusName)
            ->where('status', '!=', 'Unpublished');
        if ($templateId) {
            $query->where('template_id', $templateId);
        } else {
            $query->where('template_code', $templateCode);
        }
        $existing = $query->first();

        if ($existing) {
            throw new \Exception('A submission for this template, quarter, and campus already exists.');
        }
    }
    
    /**
     * Create submission record
     */
    private function createSubmissionRecord(Template $template, $user, string $templateCode, string $quarter, array $tableData, bool $isDraft): Submission
    {
        $formTitle = $template->sg_code . ' - ' . $template->template_code;
        $relatedForm = $template->forms()
            ->where('campus_code', $user->campus_code)
            ->where('status', 'Published')
            ->first();
        
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? '';
        $campusCode = $user->campus_code;
        $status = $isDraft ? 'Unpublished' : 'Pending Review';
        
        $submissionData = [
            'template_id' => $template->id,
            'form_id' => $template->form_id ?? ($relatedForm ? $relatedForm->id : null),
            'template_code' => $templateCode,
            'form_title' => $formTitle,
            'sg_code' => $template->sg_code,
            'kra_title' => $template->kra_title,
            'kpi_title' => $template->kpi_title,
            'campus' => $campusName,
            'campus_code' => $campusCode,
            'quarter' => $quarter,
            'table_data' => $tableData,
            'status' => $status,
            'submitted_by' => $user->id,
            'is_draft' => $isDraft,
        ];
        
        return Submission::create($submissionData);
    }


    /**
     * Display the specified submission.
     * Owners can view and edit (when editable). Other campuses/planning coordinators with template access see read-only.
     */
    public function showSubmission(Submission $submission)
    {
        $user = Auth::user();
        $isOwner = (string)$submission->submitted_by === (string)$user->id;

        if (!$isOwner) {
            // Non-owner: allow read-only view if they have access (campus OR assigned to this template as Planning Coordinator)
            $submission->load(['template', 'submitter']);
            $template = $submission->template;
            if (!$template) {
                abort(403, 'Access Denied: This submission is not available.');
            }
            $userCampusCode = $user->campus_code;
            $hasAccessByCampus = $userCampusCode
                ? $template->allowsCampus($userCampusCode)
                : $template->allowsAllCampuses();
            $hasAccessByAssignment = $user->isPlanningCoordinator() && $template->isAssignedToUser($user->id);
            if (!$hasAccessByCampus && !$hasAccessByAssignment) {
                abort(403, 'Access Denied: You do not have access to this submission.');
            }
            // Read-only for non-owners: do not show Edit
            $canEdit = false;
        } else {
            $submission->load(['template', 'submitter']);
            $canEdit = $submission->isEditable();
        }

        // Read-only fetch on GET: never recompute table_data here.
        // Planning Coordinator should see exactly what Super Admin (or last save path) persisted.

        return view('campus-user.show-submission', compact('submission', 'canEdit'));
    }

    /**
     * Show the form for editing the specified submission.
     * Only the owner can edit; non-owners are redirected to read-only view.
     */
    public function editSubmission(Submission $submission)
    {
        // Non-owners: redirect to read-only view instead of 403
        if ((string)$submission->submitted_by !== (string)Auth::user()->id) {
            $submission->load(['template', 'submitter']);
            $user = Auth::user();
            $template = $submission->template;
            if ($template) {
                $hasAccessByCampus = $user->campus_code
                    ? $template->allowsCampus($user->campus_code)
                    : $template->allowsAllCampuses();
                $hasAccessByAssignment = $user->isPlanningCoordinator() && $template->isAssignedToUser($user->id);
                if ($hasAccessByCampus || $hasAccessByAssignment) {
                    return redirect()->route('campus-user.show-submission', $submission)
                        ->with('info', 'This submission is read-only for you. You can only edit your own submissions.');
                }
            }
            abort(403, 'Access Denied: You can only edit your own submissions.');
        }

        // Block editing if the related template has been locked by the Super Admin
        $submissionTemplate = $submission->template;
        if ($submissionTemplate && $submissionTemplate->isLocked()) {
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'This template has been locked by the administrator. Editing is not allowed at this time.');
        }

        // Only allow editing if status is Returned or Draft
        // Once submitted (Pending Review or Approved), it cannot be edited unless QA Coordinator returns it
        if (!in_array($submission->status, ['Returned', 'Unpublished'])) {
            $msg = match ($submission->status) {
                'Pending Review' => 'This template is already submitted and is pending review by your QA Coordinator. You can open it with View Submission on Assigned Templates. It can only be edited again if QA returns it for changes.',
                'Approved' => 'This submission has been approved and is no longer editable. Use View Submission to review it.',
                default => 'This submission cannot be edited. Only drafts or submissions returned by QA Coordinator can be edited.',
            };

            return redirect()->route('campus-user.create-submission')->with('info', $msg);
        }

        // If there are multiple submissions for this template by this user (e.g. an empty one plus
        // another created/updated via Super Admin edits), always redirect to the most recent
        // submission that actually has table_data so the Planning Coordinator sees the same rows
        // the Super Admin edited.
        $user = Auth::user();
        if ($submission->template) {
            $siblings = Submission::where('template_id', $submission->template->id)
                ->where('submitted_by', $user->id)
                ->orderBy('updated_at', 'desc')
                ->get();
            if ($siblings->count() > 0) {
                $preferred = $siblings->first(function ($s) {
                    $data = $s->table_data;
                    if (is_string($data)) {
                        $data = json_decode($data, true);
                    }
                    return is_array($data) && !empty($data);
                }) ?: $siblings->first();
                if ($preferred && $preferred->id !== $submission->id) {
                    return redirect()->route('campus-user.edit-submission', $preferred)
                        ->with('info', 'Loaded your latest saved data for this template.');
                }
            }
        }

        // Reload from DB so table_data reflects any saves made by Super Admin
        $submission->refresh();

        // Force fresh load of template (bypass any relation cache) so Super Admin edits to fields_json always reflect
        $submission->unsetRelation('template');
        $template = Template::find($submission->template_id);
        if ($template) {
            $submission->setRelation('template', $template);
        } else {
            $submission->load(['template']);
        }
        
        // Ensure template has fields_json - if missing (e.g. template was deleted), offer to open fresh
        if (!$submission->template || !$submission->template->fields_json) {
            $templateCode = $submission->template_code;
            $authUser = Auth::user();
            $fallbackQuery = Template::where('template_code', $templateCode)->where('status', 'Published');
            if ($authUser->campus_code) {
                $fallbackQuery->forCampus($authUser->campus_code);
            }
            if ($fallbackQuery->first()) {
                return redirect()->route('campus-user.create-submission')
                    ->with('error', 'Your previous draft was linked to a form that no longer exists. Please click "Open Template" to create a new submission.');
            }
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'Template structure not found. Please contact the administrator.');
        }
        
        // Ensure this coordinator sees persisted Super Admin data:
        // if current draft is empty, hydrate it from the latest meaningful sibling submission
        // (prefer same submitted_by, then same campus) and persist to this submission.
        $rawTableData = $submission->table_data;
        $rawTableDataArr = is_string($rawTableData) ? (json_decode($rawTableData, true) ?? []) : (is_array($rawTableData) ? $rawTableData : []);
        if ($submission->template && ! $this->tableDataHasMeaningfulRows($rawTableDataArr)) {
            $templateForHydrate = $submission->template;
            $userCampusCode = strtoupper(trim((string) ($user->campus_code ?? '')));
            $userCampusName = strtoupper(trim((string) (optional($user->campusInfo)->name ?? $user->campus ?? '')));
            $normalizeCampusToken = static function (?string $value): string {
                $key = trim((string) $value);
                if ($key === '') {
                    return '';
                }
                $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
                $key = preg_replace('/\s*campus\s*$/i', '', $key);
                $key = preg_replace('/^psu\s+/i', '', $key);
                $key = preg_replace('/^pel\.?\s*/i', '', $key);
                $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
                $key = strtoupper(trim((string) preg_replace('/\s+/', ' ', $key)));
                $key = str_replace('.', '', $key);
                return trim((string) preg_replace('/[^A-Z0-9]/', '', $key));
            };
            $userCampusToken = $normalizeCampusToken($userCampusCode !== '' ? $userCampusCode : $userCampusName);

            $siblings = Submission::where('template_id', $templateForHydrate->id)
                ->orderByDesc('updated_at')
                ->with('submitter.campusInfo')
                ->get();
            $bestSameUser = null;
            $bestSameCampus = null;
            foreach ($siblings as $sib) {
                if ((int) $sib->id === (int) $submission->id) {
                    continue;
                }
                $sibData = $sib->table_data;
                if (is_string($sibData)) {
                    $sibData = json_decode($sibData, true) ?? [];
                }
                if (! is_array($sibData) || ! $this->tableDataHasMeaningfulRows($sibData)) {
                    continue;
                }
                if ((int) $sib->submitted_by === (int) $user->id) {
                    $bestSameUser = $sib;
                    break;
                }
                $sibCampus = strtoupper(trim((string) ($sib->campus ?? $sib->submitter->campus_code ?? optional($sib->submitter->campusInfo)->name ?? '')));
                $sibCampusToken = $normalizeCampusToken($sibCampus);
                if ($bestSameCampus === null && $userCampusToken !== '' && $sibCampusToken !== '' && $sibCampusToken === $userCampusToken) {
                    $bestSameCampus = $sib;
                }
            }
            // Never hydrate from unrelated campuses/users; only same user or same campus.
            $hydrateFrom = $bestSameUser ?? $bestSameCampus;
            if ($hydrateFrom) {
                $hydrateData = $hydrateFrom->table_data;
                if (is_string($hydrateData)) {
                    $hydrateData = json_decode($hydrateData, true) ?? [];
                }
                if (is_array($hydrateData) && $this->tableDataHasMeaningfulRows($hydrateData)) {
                    $submission->update(['table_data' => $hydrateData]);
                    $submission->refresh();
                    $rawTableData = $submission->table_data;
                }
            }
        }

        // Never persist row filtering on GET (removed): it erased section 2+ for every campus. Saves no longer
        // filter rows either — only the submission owner can update, and all coordinators share this path.

        // Read-only fetch on GET: never recompute table_data here.
        // Keep persisted rows authoritative for Planning Coordinator display.
        
        // Use the same campus code matching logic as createSubmission (templates that allow user's campus)
        $user = Auth::user();
        $primaryCampusCode = $user->campus_code;
        $templatesQuery = Template::where('status', 'Published');
        if ($primaryCampusCode) {
            $templatesQuery->forCampus($primaryCampusCode);
        } else {
            $templatesQuery->where(function ($q) {
                $q->where(function ($emptyCodes) {
                    $emptyCodes->whereNull('campus_codes')
                        ->orWhere('campus_codes', '')
                        ->orWhere('campus_codes', '[]');
                })->where(function ($noLegacyCampus) {
                    $noLegacyCampus->whereNull('campus_code')
                        ->orWhere('campus_code', '');
                });
            });
        }
        $templates = $templatesQuery->orderBy('template_code')->get();

        // Read-only accomplishment data: show blocks for ALL active Planning Coordinator accounts
        // (same scope as Super Admin field structure), excluding current user.
        $otherCoordinatorRows = [];
        if ($submission->template) {
            $template = $submission->template;
            $allPlanningCoordinators = User::where(function ($query) {
                $query->where('position', 'Planning Coordinator')
                    ->orWhere('position', 'planning_coordinator')
                    ->orWhere('position', 'planning-coordinator')
                    ->orWhere('role', User::ROLE_PLANNING_COORDINATOR)
                    ->orWhere('role', 'planning_coordinator')
                    ->orWhere('role', 'planning-coordinator')
                    ->orWhere('role', User::ROLE_CREATOR_EDITOR);
            })
                ->where('is_active', true)
                ->with('campusInfo')
                ->orderBy('name')
                ->get();
            $currentUserCampusToken = $this->normalizeCampusToken((string) ($user->campus_code ?? ''));
            if ($currentUserCampusToken === '') {
                $currentUserCampusToken = $this->normalizeCampusToken((string) (optional($user->campusInfo)->name ?? $user->campus ?? ''));
            }
            // Read-only side should list other campuses only; current campus stays editable below.
            $otherUserIds = $allPlanningCoordinators
                ->pluck('id')
                ->filter(fn ($id) => (string) $id !== (string) $user->id)
                ->values()
                ->toArray();

            if (empty($otherUserIds)) {
                $otherCoordinatorRows = [];
            } else {
                $allSubmissions = Submission::where('template_id', $template->id)
                    ->whereIn('submitted_by', $otherUserIds)
                    ->with(['submitter.campusInfo'])
                    ->orderBy('updated_at', 'desc')
                    ->get();
                // Include all template submissions as a fallback source so Super Admin-saved rows
                // remain visible even when submitted_by is not a coordinator account.
                $allTemplateSubmissions = Submission::where('template_id', $template->id)
                    ->with(['submitter.campusInfo'])
                    ->orderBy('updated_at', 'desc')
                    ->get();

                // Build one block per planning coordinator (by canonical campus key) so the list is complete
                $byCampus = [];
                $planningUsersById = $allPlanningCoordinators->keyBy('id');
                foreach ($otherUserIds as $uid) {
                    $coordinator = $planningUsersById->get($uid) ?? User::with('campusInfo')->find($uid);
                    if (!$coordinator) {
                        continue;
                    }
                    $campusCode = strtoupper(trim((string) ($coordinator->campus_code ?? '')));
                    $campusName = optional($coordinator->campusInfo)->name ?? $coordinator->campus ?? $campusCode;
                    $campusLabel = trim($campusName !== '' ? $campusName : $campusCode);
                    $displayLabel = $campusLabel !== ''
                        ? (strtoupper($campusLabel) . ' Planning Coordinator')
                        : (($coordinator->name ?? 'Planning Coordinator') . ' (read-only)');
                    $key = $this->canonicalCampusKeyForCoordinator($campusLabel, $displayLabel);
                    if ($currentUserCampusToken !== '' && $key === $currentUserCampusToken) {
                        continue;
                    }
                    $displayLabel = $key !== 'unknown' ? (ucwords(strtolower(str_replace('_', ' ', $key))) . ' Planning Coordinator') : $displayLabel;

                    // Best submission for this user: latest with meaningful data, or latest any
                    $userSubmissions = $allSubmissions->where('submitted_by', $uid)->values();
                    $bestSubmission = null;
                    $tableData = [];
                    foreach ($userSubmissions as $other) {
                        if ((int) $other->id === (int) $submission->id) {
                            continue;
                        }
                        $tableData = $other->table_data;
                        if (is_string($tableData)) {
                            $tableData = json_decode($tableData, true);
                        }
                        if (is_array($tableData) && !empty($tableData) && $this->tableDataHasMeaningfulRows($tableData)) {
                            $bestSubmission = $other;
                            break;
                        }
                        if ($bestSubmission === null) {
                            $bestSubmission = $other;
                        }
                    }
                    if ($bestSubmission && is_array($tableData) && !empty($tableData) && $this->tableDataHasMeaningfulRows($tableData)) {
                        // Ensure summary row is present for read-only display (blue result row)
                        $schemaFields = $template->getSchemaFields();
                        $summaryRules = $template->getSummaryRules();
                        $summaryCellMappings = $template->getSummaryCellMappings();
                        if (!empty($schemaFields)) {
                            $storedTableData = $tableData;
                            $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                            $tableData = $this->computeService->mergeStoredSummaryClearedCells($tableData, $storedTableData);
                            $tableData = $this->computeService->mergeStoredExtraSummaryRows($tableData, $storedTableData);
                            $tableData = $this->computeService->applyPersistedSummaryRowsFromSource($tableData, $storedTableData);
                            $tableData = $this->computeService->finalizeTableDataAfterComputeMerges($tableData, $storedTableData);
                        }
                        $byCampus[$key] = [
                            'submitter_name' => $displayLabel,
                            'table_data' => $tableData,
                            'has_data' => true,
                        ];
                    } else {
                        // No meaningful data: still show this coordinator so the list is complete
                        $byCampus[$key] = [
                            'submitter_name' => $displayLabel,
                            'table_data' => [],
                            'has_data' => false,
                        ];
                    }
                }

                // Orphan visibility pass: add campus blocks from any meaningful submission not
                // already represented by the coordinator-ID pass above.
                foreach ($allTemplateSubmissions as $sub) {
                    $tableData = $sub->table_data;
                    if (is_string($tableData)) {
                        $tableData = json_decode($tableData, true);
                    }
                    if (!is_array($tableData) || empty($tableData) || !$this->tableDataHasMeaningfulRows($tableData)) {
                        continue;
                    }

                    $campusLabel = trim((string) ($sub->campus ?? $sub->submitter->campus_code ?? optional($sub->submitter->campusInfo)->name ?? ''));
                    $displayLabel = $campusLabel !== ''
                        ? (strtoupper($campusLabel) . ' Planning Coordinator')
                        : (($sub->submitter->name ?? 'Planning Coordinator') . ' (read-only)');
                    $key = $this->canonicalCampusKeyForCoordinator($campusLabel, $displayLabel);
                    if ($currentUserCampusToken !== '' && $key === $currentUserCampusToken) {
                        continue;
                    }
                    if (isset($byCampus[$key])) {
                        continue;
                    }

                    $schemaFields = $template->getSchemaFields();
                    $summaryRules = $template->getSummaryRules();
                    $summaryCellMappings = $template->getSummaryCellMappings();
                    if (!empty($schemaFields)) {
                        $storedTableData = $tableData;
                        $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                        $tableData = $this->computeService->mergeStoredSummaryClearedCells($tableData, $storedTableData);
                        $tableData = $this->computeService->mergeStoredExtraSummaryRows($tableData, $storedTableData);
                        $tableData = $this->computeService->applyPersistedSummaryRowsFromSource($tableData, $storedTableData);
                        $tableData = $this->computeService->finalizeTableDataAfterComputeMerges($tableData, $storedTableData);
                    }
                    $byCampus[$key] = [
                        'submitter_name' => $key !== 'unknown'
                            ? (ucwords(strtolower(str_replace('_', ' ', $key))) . ' Planning Coordinator')
                            : $displayLabel,
                        'table_data' => $tableData,
                        'has_data' => true,
                    ];
                }

                $otherCoordinatorRows = array_values(array_map(function ($b) {
                    return [
                        'submitter_name' => $b['submitter_name'],
                        'table_data' => $b['table_data'],
                        'has_data' => $b['has_data'] ?? true,
                    ];
                }, collect($byCampus)->sortBy(fn ($b) => (string) $b['submitter_name'], SORT_NATURAL | SORT_FLAG_CASE)->values()->all()));
            }
        }

        $editableSeedData = $submission->table_data;
        // Always prefer latest meaningful rows from the coordinator's campus block.
        // This keeps Planning Coordinator view aligned with the last Super Admin-saved campus values.
        $userCampusToken = $this->normalizeCampusToken((string) ($user->campus_code ?? ''));
        if ($userCampusToken === '') {
            $userCampusToken = $this->normalizeCampusToken((string) (optional($user->campusInfo)->name ?? $user->campus ?? ''));
        }
        if ($submission->template && $userCampusToken !== '') {
            $sameCampusSubs = Submission::where('template_id', $submission->template->id)
                ->orderByDesc('updated_at')
                ->get(['id', 'campus', 'campus_code', 'table_data']);
            $preferredWithSummary = null;
            $preferredMeaningful = null;
            foreach ($sameCampusSubs as $sub) {
                $subToken = $this->normalizeCampusToken((string) ($sub->campus_code ?? $sub->campus ?? ''));
                if ($subToken !== '' && $subToken !== $userCampusToken) {
                    continue;
                }
                $rows = $sub->table_data;
                if (is_string($rows)) {
                    $rows = json_decode($rows, true) ?? [];
                }
                if (!is_array($rows) || !$this->tableDataHasMeaningfulRows($rows)) {
                    continue;
                }
                if ($this->tableDataHasSummaryRows($rows)) {
                    $preferredWithSummary = $rows;
                    break;
                }
                if ($preferredMeaningful === null) {
                    $preferredMeaningful = $rows;
                }
            }
            if ($preferredWithSummary !== null) {
                $editableSeedData = $preferredWithSummary;
            } elseif ($preferredMeaningful !== null) {
                $editableSeedData = $preferredMeaningful;
            }
        }

        $response = response()->view('campus-user.edit-submission', compact('submission', 'templates', 'otherCoordinatorRows', 'editableSeedData'));
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        return $response;
    }

    /**
     * Canonical campus key for grouping read-only coordinator blocks (aligns with Super Admin view).
     */
    private function canonicalCampusKeyForCoordinator(string $campus, string $displayLabel): string
    {
        $campus = trim($campus);
        $label = trim($displayLabel);
        $raw = $campus !== '' ? $campus : $label;
        if ($raw === '') {
            return 'unknown';
        }
        $key = $raw;
        $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
        $key = preg_replace('/\s*campus\s*$/i', '', $key);
        $key = preg_replace('/^psu\s+/i', '', $key);
        $key = preg_replace('/^pel\.?\s*/i', '', $key);
        $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
        $key = strtoupper(trim(preg_replace('/\s+/', ' ', $key)));
        $key = preg_replace('/\./', '', $key);
        $key = trim($key);
        return $key !== '' ? $key : 'unknown';
    }

    private function normalizeCampusToken(string $value): string
    {
        $key = trim($value);
        if ($key === '') {
            return '';
        }
        $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
        $key = preg_replace('/\s*campus\s*$/i', '', $key);
        $key = preg_replace('/^psu\s+/i', '', $key);
        $key = preg_replace('/^pel\.?\s*/i', '', $key);
        $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
        $key = strtoupper(trim((string) preg_replace('/\s+/', ' ', $key)));
        $key = str_replace('.', '', $key);
        $key = preg_replace('/[^A-Z0-9]/', '', $key);
        return trim((string) $key);
    }

    /**
     * Return true if table_data has at least one data row with at least one non-empty, non-placeholder value
     * in a "content" column (quarter and No. alone do not count — avoids showing blocks that are only placeholders).
     * Used to hide read-only blocks that are only placeholder rows (all "—" or just quarter filled).
     */
    private function tableDataHasMeaningfulRows(array $tableData): bool
    {
        $structuralKeys = ['quarter', 'quarter_', 'no', 'no_', '_meta', '_after_separator'];
        $isStructuralKey = function (string $k) use ($structuralKeys): bool {
            $n = strtolower(trim(preg_replace('/[^a-z0-9_]/', '', $k)));
            return $n === '' || in_array($n, $structuralKeys, true)
                || preg_match('/^quarter$/i', $n) || preg_match('/^no_?$/i', $n);
        };

        foreach ($tableData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? null;
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            if (is_array($meta) && (($meta['row_type'] ?? 'data') === 'summary')) {
                continue;
            }
            foreach ($row as $k => $v) {
                if ($k === '_meta' || $k === '_after_separator' || $isStructuralKey($k)) {
                    continue;
                }
                $v = $this->tableCellToTrimmedString($v);
                if ($v !== '' && $v !== '—') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Update the specified submission
     */
    public function updateSubmission(Request $request, Submission $submission)
    {
        // Ensure user can only update their own submissions
        if ((string)$submission->submitted_by !== (string)Auth::user()->id) {
            abort(403, 'Access Denied: You can only update your own submissions.');
        }

        // Block update if the related template has been locked by the Super Admin
        $submissionTemplate = $submission->template;
        if ($submissionTemplate && $submissionTemplate->isLocked()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This template has been locked by the administrator. No updates are allowed.'], 403);
            }
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'This template has been locked by the administrator. No updates are allowed.');
        }

        // Only allow updating if status is Returned or Draft
        // Once submitted (Pending Review or Approved), it cannot be updated unless QA Coordinator returns it
        if (!in_array($submission->status, ['Returned', 'Unpublished'])) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'This submission cannot be updated. Only draft or returned submissions can be edited.'], 403);
            }
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'This submission cannot be updated. Only draft submissions or submissions returned by QA Coordinator can be updated.');
        }

        // Determine action (draft or submit) - use submit_action to avoid form.action shadowing
        $action = $request->input('action', $request->input('submit_action', 'submit'));
        $isDraft = ($action === 'draft');

        // Log request data for debugging
        \Log::info('updateSubmission - Request data:', [
            'action' => $action,
            'is_draft' => $isDraft,
            'template_code' => $request->input('template_code'),
            'has_table_data' => $request->has('table_data'),
            'table_data_type' => gettype($request->table_data),
            'table_data_count' => is_array($request->table_data) ? count($request->table_data) : 0,
        ]);

        // Validation - more lenient for drafts
        if ($isDraft) {
            $request->validate([
                'template_code' => 'required|string',
                'table_data' => 'nullable|array',
            ]);
        } else {
            $request->validate([
                'template_code' => 'required|string',
                'table_data' => 'required|array|min:1',
            ]);
        }

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $primaryCampusCode = $user->campus_code ?? null;

            // Get the template: use the submission's linked template for schema/summary so formulas match Super Admin config
            $template = $submission->template;
            if (!$template) {
                $candidates = Template::where('template_code', $request->template_code)->where('status', 'Published')->get();
                $template = $primaryCampusCode
                    ? $candidates->first(fn ($t) => $t->allowsCampus($primaryCampusCode))
                    : $candidates->first(fn ($t) => $t->allowsAllCampuses());
            }
            if (!$template || $template->template_code !== $request->template_code) {
                throw new \Exception('Template not found or not available for your campus.');
            }

            // Clean and validate table_data - ensure it's a proper array of arrays
            $tableData = $request->table_data ?? [];
            
            // Log raw table_data
            \Log::info('Raw table_data in updateSubmission:', [
                'type' => gettype($tableData),
                'is_array' => is_array($tableData),
                'count' => is_array($tableData) ? count($tableData) : 0,
                'first_row_keys' => is_array($tableData) && !empty($tableData) && is_array($tableData[0]) ? array_keys($tableData[0]) : [],
            ]);
            
            if (!is_array($tableData)) {
                throw new \Exception('Invalid table_data format.');
            }
            
            // Process table_data - convert objects to arrays and clean up
            $processedData = [];
            foreach ($tableData as $index => $row) {
                // Convert object to array if needed
                if (is_object($row)) {
                    $row = (array) $row;
                }
                
                if (!is_array($row)) {
                    \Log::warning('Skipping invalid row at index ' . $index . ': not an array', ['row' => $row]);
                    continue;
                }
                
                // Log the row being processed
                \Log::info('Processing row ' . $index, [
                    'row_keys' => array_keys($row),
                    'row_values' => array_map(function($v) { 
                        return is_string($v) ? substr($v, 0, 50) : $v; 
                    }, array_values($row)),
                    'row_count' => count($row),
                ]);
                
                // For drafts, accept any row that exists (even if mostly empty)
                // For submissions, require at least one field with data
                if ($isDraft) {
                    // Preserve ALL fields exactly as they are, even if empty
                    $preservedRow = [];
                    foreach ($row as $key => $value) {
                        // Preserve the value exactly as it is, even if empty or null
                        $preservedRow[$key] = $value === null ? '' : $value;
                    }
                    $processedData[] = $preservedRow;
                    \Log::info('Draft mode: Accepted row ' . $index . ' with ' . count($preservedRow) . ' fields', [
                        'keys' => array_keys($preservedRow),
                    ]);
                } else {
                    // For submissions, check if row has at least one non-empty value
                    $hasAnyValue = false;
                    $nonEmptyFields = [];
                    foreach ($row as $key => $value) {
                        if ($key === '_meta' || $key === '_after_separator') {
                            continue;
                        }
                        // Never cast arrays/objects to string (_meta is skipped; stray nested keys must not throw)
                        if ($this->tableCellToTrimmedString($value) !== '') {
                            $hasAnyValue = true;
                            $nonEmptyFields[] = $key;
                        }
                    }
                    
                    \Log::info('Submission mode: Row ' . $index . ' hasAnyValue: ' . ($hasAnyValue ? 'YES' : 'NO'), [
                        'non_empty_fields' => $nonEmptyFields,
                        'all_fields' => array_keys($row),
                    ]);
                    
                    if ($hasAnyValue) {
                        $processedData[] = $row;
                        \Log::info('Submission mode: Accepted row ' . $index);
                    } else {
                        \Log::warning('Submission mode: Rejected row ' . $index . ' - no non-empty values');
                    }
                }
            }
            
            // Re-index array to ensure sequential keys
            $tableData = array_values($processedData);
            
            \Log::info('After processing:', [
                'original_count' => count($request->table_data ?? []),
                'processed_count' => count($tableData),
                'is_draft' => $isDraft,
            ]);
            
            // For drafts, create at least one empty row if none exist
            if ($isDraft && empty($tableData)) {
                \Log::info('Draft mode: No rows found, creating one empty row');
                // Extract quarter from request or use existing
                $quarter = $request->input('quarter') ?? $submission->quarter ?? '1st Q';
                $tableData = [['quarter' => $quarter]];
            }
            
            if (empty($tableData)) {
                \Log::error('No valid rows after processing', [
                    'original_count' => count($request->table_data ?? []),
                    'is_draft' => $isDraft,
                    'original_table_data' => $request->table_data,
                    'processed_data' => $processedData,
                ]);
                throw new \Exception('At least one row of data is required. Please ensure you have filled in at least one field in the table.');
            }
            
            \Log::info('Processed table_data in updateSubmission:', [
                'original_count' => count($request->table_data ?? []),
                'processed_count' => count($tableData),
                'is_draft' => $isDraft,
                'first_row_keys' => !empty($tableData) ? array_keys($tableData[0]) : [],
            ]);

            // Compute calculated fields (formulas, summaries) based on template schema
            $schemaFields = $template->getSchemaFields();
            $summaryRules = $template->getSummaryRules();
            $summaryCellMappings = $template->getSummaryCellMappings();
            // Use existing submission table_data as merge source so Super Admin–cleared cells ("—") persist when PC saves
            $existingTableData = $submission->table_data;
            if (is_string($existingTableData)) {
                $existingTableData = json_decode($existingTableData, true) ?? [];
            }
            if (!is_array($existingTableData)) {
                $existingTableData = [];
            }
            if (!empty($schemaFields)) {
                $incomingSummaryCount = $this->computeService->countSummaryRowsInPayload($tableData);
                $incomingTableDataSnapshot = $tableData;
                $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                $tableData = $this->computeService->mergeStoredSummaryClearedCells($tableData, $existingTableData);
                $tableData = $this->computeService->mergeStoredExtraSummaryRows(
                    $tableData,
                    $existingTableData,
                    $incomingSummaryCount
                );
                $tableData = $this->computeService->applyPersistedSummaryRowsFromSource($tableData, $incomingTableDataSnapshot);
                $tableData = $this->computeService->finalizeTableDataAfterComputeMerges($tableData, $incomingTableDataSnapshot);
            }

            // Validate table data against template schema (only for submit, not draft)
            if (!$isDraft && $template->fields_json && isset($template->fields_json['fields'])) {
                $this->validateTableData($tableData, $template->fields_json['fields']);
            }

            // Extract quarter: request first (from visible Quarter selector), then table_data (case-insensitive)
            $quarter = $request->input('quarter');
            if (!$quarter || trim((string) $quarter) === '') {
                $quarter = null;
                foreach ($tableData as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach (['quarter', 'Quarter', 'QUARTER'] as $key) {
                        if (isset($row[$key]) && $this->tableCellToTrimmedString($row[$key]) !== '') {
                            $quarter = $this->tableCellToTrimmedString($row[$key]);
                            break 2;
                        }
                    }
                    foreach (array_keys($row) as $k) {
                        if (strtolower($k) === 'quarter' && $this->tableCellToTrimmedString($row[$k] ?? '') !== '') {
                            $quarter = $this->tableCellToTrimmedString($row[$k]);
                            break 2;
                        }
                    }
                }
            }
            $quarter = $quarter ?: $submission->quarter ?: '1st Q';

            // Determine status based on action
            $status = $isDraft ? 'Unpublished' : 'Pending Review';

            // Ensure campus and campus_code are set so QA Coordinator can find the submission
            // Use same resolution as createSubmissionRecord for consistency
            $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? '';
            $campusCode = $user->campus_code ?? null;

            // Update the submission
            $updateData = [
                'template_code' => $request->template_code,
                'table_data' => $tableData,
                'quarter' => $quarter,
                'status' => $status,
                'is_draft' => $isDraft,
                'campus' => $campusName,
                'campus_code' => $campusCode,
                'last_updated' => now(),
            ];
            
            // For drafts, don't set submitted_at
            if ($isDraft) {
                $updateData['submitted_at'] = null;
            } else {
                // For submissions, set submitted_at if not already set
                if (!$submission->submitted_at) {
                    $updateData['submitted_at'] = now();
                }
            }
            
            \Log::info('Updating submission:', [
                'submission_id' => $submission->id,
                'is_draft' => $isDraft,
                'status' => $status,
                'table_data_count' => count($tableData),
            ]);

            $oldTableData = $submission->table_data;
            if (!is_array($oldTableData)) {
                $oldTableData = [];
            }
            $oldStatus = $submission->status;

            $submission->update($updateData);

            \Log::info('Submission updated successfully:', [
                'submission_id' => $submission->id,
                'status' => $submission->status,
            ]);

            $templateId = $submission->template_id;
            if ($templateId) {
                $template = Template::find($templateId);
                $columnLabels = TableDataAuditHelper::getColumnLabelsFromTemplate($template);
                $changes = TableDataAuditHelper::describeTableDataChanges($oldTableData, $tableData, $columnLabels);
                $action = $isDraft ? 'saved draft' : ($oldStatus === 'Returned' ? 're-submitted' : 'submitted');
                $auditPrefix = "{$user->name} ({$campusName}) {$action} {$submission->template_code}" . ($quarter ? ", {$quarter}" : '') . ': ';
                $message = TableDataAuditHelper::buildAuditMessage($auditPrefix, $changes);
                $this->logSubmissionEditForAudit($templateId, Auth::id(), $message);
            }

            DB::commit();

            // Notify QA Coordinator(s) of the specific campus when template is submitted
            if (!$isDraft) {
                $this->notifyQACoordinatorsOfCampus($submission);
            }

            $message = $isDraft 
                ? 'Submission saved as draft successfully!'
                : 'Template submitted successfully! It is now pending review by the QA Coordinator of your campus.';

            // For AJAX draft saves, return JSON so the page can show "Draft saved" without reload
            if ($isDraft && $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('campus-user.edit-submission', $submission),
                ]);
            }

            return $isDraft 
                ? redirect()->to(route('campus-user.edit-submission', $submission) . '?saved=' . time())->with('success', $message)
                : redirect()->route('campus-user.create-submission')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating submission:', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update submission: ' . $e->getMessage()], 422);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update submission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified submission
     */
    public function destroySubmission(Submission $submission)
    {
        // Ensure user can only delete their own submissions
        if ((string)$submission->submitted_by !== (string)Auth::user()->id) {
            abort(403, 'Access Denied: You can only delete your own submissions.');
        }

        // Only allow deletion if status is Draft
        if ($submission->status !== 'Unpublished') {
            return redirect()->route('campus-user.create-submission')
                ->with('error', 'This submission cannot be deleted because it is already pending review.');
        }

        $submission->delete();

        return redirect()->route('campus-user.create-submission')
            ->with('success', 'Submission deleted successfully.');
    }

    /**
     * Get template details for AJAX requests
     */
    public function getTemplateDetails(Request $request)
    {
        $user = Auth::user();
        $candidates = Template::where('template_code', $request->template_code)->get();
        $template = $user->campus_code
            ? $candidates->first(fn ($t) => $t->allowsCampus($user->campus_code))
            : $candidates->first(fn ($t) => $t->allowsAllCampuses());

        if (!$template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        return response()->json([
            'template_code' => $template->template_code,
            'sg_code' => $template->sg_code,
            'kra_title' => $template->kra_title,
            'kpi_title' => $template->kpi_title,
            'template_schema' => $template->getSchemaFields(),
            'summary_rules' => $template->getSummaryRules(),
            'summary_cell_mappings' => $template->getSummaryCellMappings(),
        ]);
    }

    /**
     * Save draft submission
     * Unified method for both create and edit modes using SubmissionService
     */
    public function saveDraft(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'template_code' => 'required|exists:templates,template_code',
                'table_data' => 'nullable|array',
                'submission_id' => 'nullable|exists:submissions,id',
                'quarter' => 'nullable|string'
            ]);

            // Block draft save if the template has been locked by the Super Admin
            $draftTemplate = \App\Models\Template::where('template_code', $request->template_code)->first();
            if ($draftTemplate && $draftTemplate->isLocked()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This template has been locked by the administrator. Saving drafts is not allowed.',
                ], 403);
            }

            $submission = null;
            
            // Get existing submission if in edit mode
            if ($request->has('submission_id') && $request->submission_id) {
                $submission = Submission::where('id', $request->submission_id)
                    ->where('submitted_by', Auth::id())
                    ->first();
                
                if (!$submission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Submission not found or you do not have permission to edit it.',
                        'errors' => ['submission_id' => ['Submission not found or access denied.']]
                    ], 422);
                }
            }

            // Use SubmissionService to save draft
            $submission = $this->submissionService->saveDraft([
                'template_code' => $request->template_code,
                'table_data' => $request->table_data ?? [],
                'quarter' => $request->quarter,
            ], $submission);
            
            return response()->json([
                'success' => true,
                'message' => 'Draft saved successfully',
                'submission_id' => $submission->id,
                'draft_version' => $submission->draft_version,
                'last_draft_at' => $submission->last_draft_at?->toIso8601String(),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_map(function($errors) {
                    return is_array($errors) ? implode(', ', $errors) : $errors;
                }, array_values($e->errors()))),
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found. Please select a valid template.',
                'errors' => ['template_code' => ['The selected template does not exist.']]
            ], 422);
        } catch (\Exception $e) {
            // Log errors
            \Log::error('Save Draft Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reports page
     */
    public function reports(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $filters = [
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'template_code' => $request->get('template_code'),
        ];
        
        // Build base query with filters
        $query = Submission::forUser($user->id);
        
        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }
        
        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', 'like', '%' . $filters['kra_title'] . '%');
        }
        
        if (!empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }
        
        // Get submission statistics with filters applied
        $stats = [
            'total_submissions' => (clone $query)->count(),
            'pending_review' => (clone $query)->pendingReview()->count(),
            'approved' => (clone $query)->approved()->count(),
            'returned' => (clone $query)->returned()->count(),
        ];

        // Get submissions by quarter with filters
        $quarterlyStats = (clone $query)
            ->selectRaw('quarter, COUNT(*) as count')
            ->groupBy('quarter')
            ->get()
            ->pluck('count', 'quarter');

        // Get submissions by status with filters
        $statusStats = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Get available filter options
        $userSubmissions = Submission::forUser($user->id);
        $availableSGs = $userSubmissions->whereNotNull('sg_code')
            ->distinct()
            ->orderBy('sg_code')
            ->pluck('sg_code')
            ->filter();
        
        $availableKRAs = $userSubmissions->whereNotNull('kra_title')
            ->distinct()
            ->orderBy('kra_title')
            ->pluck('kra_title')
            ->filter();
        
        $availableTemplateCodes = $userSubmissions->whereNotNull('template_code')
            ->distinct()
            ->orderBy('template_code')
            ->pluck('template_code')
            ->filter();

        return view('campus-user.reports', compact('stats', 'quarterlyStats', 'statusStats', 'filters', 'availableSGs', 'availableKRAs', 'availableTemplateCodes'));
    }

    /**
     * Validate table data against template schema
     */
    private function validateTableData(array $tableData, array $schemaFields)
    {
        if (empty($tableData)) {
            throw new \Exception('At least one data row is required.');
        }

        foreach ($tableData as $rowIndex => $row) {
            // Skip validation for summary rows (they have computed/placeholder values like —)
            $meta = $row['_meta'] ?? null;
            if (is_array($meta) && ($meta['row_type'] ?? null) === 'summary') {
                continue;
            }
            // Debug: Log the actual field names being sent
            \Log::info("Row {$rowIndex} field names: " . implode(', ', array_keys($row)));
            
            foreach ($schemaFields as $field) {
                $fieldValue = null;
                $fieldFound = false;
                
                // CRITICAL: Generate field key using the SAME logic as JavaScript
                // This must match exactly how keys are generated in create-submission.blade.php and edit-submission.blade.php
                // IMPORTANT: Even if field['key'] exists, we need to normalize it the same way JavaScript does
                $originalFieldKey = $field['key'] ?? null;
                $fieldKey = null;
                
                // Always generate the normalized key from the label (this is what JavaScript does)
                // EXACT JavaScript logic: .replace(/"/g, '').replace(/'/g, '').toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '')
                $fieldKey = $field['label'] ?? '';
                // Remove quotes (single and double)
                $fieldKey = str_replace(['"', "'"], '', $fieldKey);
                // Convert to lowercase
                $fieldKey = strtolower($fieldKey);
                // Trim whitespace
                $fieldKey = trim($fieldKey);
                // Replace spaces with underscores
                $fieldKey = preg_replace('/\s+/', '_', $fieldKey);
                // Replace special characters with underscores (this removes periods, parentheses, etc.)
                // JavaScript: .replace(/[^a-z0-9_]/g, '_') - THIS REPLACES PARENTHESES WITH UNDERSCORES
                $fieldKey = preg_replace('/[^a-z0-9_]/', '_', $fieldKey);
                // Replace multiple underscores with single
                // JavaScript: .replace(/_+/g, '_')
                $fieldKey = preg_replace('/_+/', '_', $fieldKey);
                // Remove leading/trailing underscores
                // JavaScript: .replace(/^_|_$/g, '')
                $fieldKey = preg_replace('/^_+|_+$/', '', $fieldKey);
                
                \Log::info("validateTableData: Field '{$field['label']}' -> original key: '{$originalFieldKey}' -> normalized key: '{$fieldKey}'");
                
                // First, try to find the field by the normalized key
                if (isset($row[$fieldKey])) {
                    $fieldValue = $row[$fieldKey];
                    $fieldFound = true;
                    \Log::info("validateTableData: Found field '{$field['label']}' using normalized key '{$fieldKey}' in row " . ($rowIndex + 1));
                } else {
                    // Try various field name variations for backward compatibility
                    // Generate all possible variations that might exist in saved data
                    $label = $field['label'] ?? '';
                    
                    // Generate key with parentheses/special chars preserved (might be how it was saved)
                    $keyWithSpecialChars = strtolower(trim(str_replace(['"', "'"], '', preg_replace('/\s+/', '_', $label))));
                    
                    // Generate key with only spaces normalized (preserves periods, parentheses, etc.)
                    $keySpacesOnly = str_replace(' ', '_', strtolower(trim($label)));
                    
                    // CRITICAL: Also generate the key that JavaScript would create
                    // JavaScript does: remove quotes -> lowercase -> trim -> spaces to _ -> special chars to _ -> multiple _ to single -> trim _
                    $jsNormalizedKey = $label;
                    $jsNormalizedKey = str_replace(['"', "'"], '', $jsNormalizedKey); // Remove quotes
                    $jsNormalizedKey = strtolower($jsNormalizedKey); // Lowercase
                    $jsNormalizedKey = trim($jsNormalizedKey); // Trim
                    $jsNormalizedKey = preg_replace('/\s+/', '_', $jsNormalizedKey); // Spaces to underscore
                    $jsNormalizedKey = preg_replace('/[^a-z0-9_]/', '_', $jsNormalizedKey); // Special chars to underscore (THIS REMOVES PARENTHESES)
                    $jsNormalizedKey = preg_replace('/_+/', '_', $jsNormalizedKey); // Multiple underscores to single
                    $jsNormalizedKey = preg_replace('/^_+|_+$/', '', $jsNormalizedKey); // Remove leading/trailing underscores
                    
                    $possibleNames = [
                        $fieldKey, // Normalized key (should match $jsNormalizedKey)
                        $jsNormalizedKey, // JavaScript-normalized key (CRITICAL - this is what's actually saved)
                        $keyWithSpecialChars, // Key with special chars preserved
                        $keySpacesOnly, // Key with only spaces normalized
                        $field['key'] ?? '', // Original key from field definition
                        // Original label variations
                        $label,
                        strtolower($label),
                        str_replace(' ', '_', strtolower($label)),
                        strtoupper($fieldKey),
                        strtoupper(str_replace('_', ' ', $fieldKey)),
                    ];
                    
                    // Special case: For "No." field, also try "no" (without period/underscore)
                    if (stripos($label, 'no.') !== false || stripos($label, 'no ') !== false) {
                        $possibleNames[] = 'no';
                        $possibleNames[] = 'no_';
                    }
                    
                    // Special case: For fields with parentheses like "Name of Researcher(s)"
                    // The saved key will be "name_of_researcher_s" (parentheses -> underscore)
                    // But we might be looking for "name_of_researcher(s)" (with parentheses)
                    // So try both variations
                    if (strpos($label, '(') !== false || strpos($label, ')') !== false) {
                        // Try with parentheses replaced by underscore (what JavaScript creates)
                        $parensToUnderscore = preg_replace('/[()]/', '_', $keyWithSpecialChars);
                        $parensToUnderscore = preg_replace('/_+/', '_', $parensToUnderscore);
                        $parensToUnderscore = preg_replace('/^_+|_+$/', '', $parensToUnderscore);
                        $possibleNames[] = $parensToUnderscore;
                        
                        // Also try the exact key with parentheses (in case it was saved that way)
                        $possibleNames[] = $keyWithSpecialChars;
                    }
                    
                    // Remove empty values and duplicates
                    $possibleNames = array_unique(array_filter($possibleNames, function($name) {
                        return !empty($name);
                    }));
                    
                    \Log::info("validateTableData: Trying variations for '{$field['label']}': " . implode(', ', $possibleNames));
                    
                    foreach ($possibleNames as $name) {
                        if (isset($row[$name])) {
                            $fieldValue = $row[$name];
                            $fieldFound = true;
                            $valPreview = is_scalar($fieldValue) ? substr(trim((string) $fieldValue), 0, 50) : substr(json_encode($fieldValue), 0, 50);
                            \Log::info("validateTableData: Found field '{$field['label']}' using variation '{$name}' in row " . ($rowIndex + 1) . " with value: '" . $valPreview . "'");
                            break;
                        }
                    }
                    
                    // If still not found, try a more aggressive search by normalizing all row keys
                    if (!$fieldFound) {
                        foreach ($row as $rowKey => $rowValue) {
                            if ($rowKey === '_meta' || $rowKey === '_after_separator') {
                                continue;
                            }
                            // Normalize the row key the same way
                            $normalizedRowKey = strtolower(trim(str_replace(['"', "'"], '', preg_replace('/\s+/', '_', $rowKey))));
                            $normalizedRowKey = preg_replace('/[^a-z0-9_]/', '_', $normalizedRowKey);
                            $normalizedRowKey = preg_replace('/_+/', '_', $normalizedRowKey);
                            $normalizedRowKey = preg_replace('/^_+|_+$/', '', $normalizedRowKey);
                            
                            // Check if normalized keys match
                            if ($normalizedRowKey === $fieldKey || $normalizedRowKey === $jsNormalizedKey) {
                                $fieldValue = $rowValue;
                                $fieldFound = true;
                                \Log::info("validateTableData: Found field '{$field['label']}' using normalized row key '{$rowKey}' -> '{$normalizedRowKey}' in row " . ($rowIndex + 1));
                                break;
                            }
                        }
                    }
                }
                
                // If still not found, try to match by normalizing all row keys and comparing
                if (!$fieldFound) {
                    foreach ($row as $rowKey => $rowValue) {
                        if ($rowKey === '_meta' || $rowKey === '_after_separator') {
                            continue;
                        }
                        // Normalize the row key the EXACT same way as JavaScript
                        $normalizedRowKey = $rowKey;
                        $normalizedRowKey = str_replace(['"', "'"], '', $normalizedRowKey);
                        $normalizedRowKey = strtolower($normalizedRowKey);
                        $normalizedRowKey = trim($normalizedRowKey);
                        $normalizedRowKey = preg_replace('/\s+/', '_', $normalizedRowKey);
                        $normalizedRowKey = preg_replace('/[^a-z0-9_]/', '_', $normalizedRowKey);
                        $normalizedRowKey = preg_replace('/_+/', '_', $normalizedRowKey);
                        $normalizedRowKey = preg_replace('/^_+|_+$/', '', $normalizedRowKey);
                        
                        // Check if normalized keys match
                        if ($normalizedRowKey === $fieldKey || $normalizedRowKey === $jsNormalizedKey) {
                            $fieldValue = $rowValue;
                            $fieldFound = true;
                            \Log::info("validateTableData: Found field '{$field['label']}' using normalized row key '{$rowKey}' -> '{$normalizedRowKey}' in row " . ($rowIndex + 1));
                            break;
                        }
                    }
                }
                
                // If still not found, try to match by partial name or common variations
                if (!$fieldFound) {
                    $labelLower = strtolower($field['label']);
                    $keyLower = strtolower($fieldKey);
                    
                    foreach ($row as $key => $value) {
                        if ($key === '_meta' || $key === '_after_separator') {
                            continue;
                        }
                        $rowKeyLower = strtolower($key);
                        
                        // Map common field variations
                        $fieldMappings = [
                            'responsible_work_units' => ['responsible', 'work', 'units', 'campus', 'ci', 'office', 'comments'],
                            'quarter' => ['quarter'],
                            'program_name' => ['program', 'name', 'title', 'activity', 'programs_with_course_syllabi'],
                            'major_name' => ['major', 'name'],
                            'target_output' => ['target', 'output'],
                            'actual_output' => ['actual', 'output'],
                            'google_drive_link' => ['supporting', 'document', 'link', 'drive', 'google', 'google_drive_link'],
                            'evidence_verified' => ['evidence', 'verified', 'qa', 'm&e', 'evidence_verified_by_the_qa'],
                            'ci_office_comments' => ['remarks', 'comments', 'ci', 'office'],
                            'name_of_researcher' => ['name', 'researcher', 'researchers'],
                        ];
                        
                        // Special handling for specific field mappings
                        if ($fieldKey === 'quarter' && strpos($rowKeyLower, 'quarter') !== false) {
                            $fieldValue = $value;
                            $fieldFound = true;
                            break;
                        }
                        
                        // Check for field mappings
                        foreach ($fieldMappings as $expectedKey => $keywords) {
                            if (strpos($keyLower, $expectedKey) !== false || strpos($labelLower, str_replace('_', ' ', $expectedKey)) !== false) {
                                $matchCount = 0;
                                foreach ($keywords as $keyword) {
                                    if (strpos($rowKeyLower, $keyword) !== false) {
                                        $matchCount++;
                                    }
                                }
                                if ($matchCount >= 1) { // At least one keyword match
                                    $fieldValue = $value;
                                    $fieldFound = true;
                                    \Log::info("validateTableData: Found field '{$field['label']}' using field mapping '{$expectedKey}' -> '{$key}' in row " . ($rowIndex + 1));
                                    break 2; // Break both loops
                                }
                            }
                        }
                    }
                }
                
                // Check required fields
                if (isset($field['required']) && $field['required']) {
                    // For required fields, check if field was found AND has a non-empty value
                    $isEmpty = $fieldFound ? ($this->tableCellToTrimmedString($fieldValue ?? '') === '') : true;
                    
                    if (!$fieldFound || $isEmpty) {
                        $availableFields = implode(', ', array_keys($row));
                        $suggestion = $this->suggestFieldMatch($field['label'], array_keys($row));
                        
                        // Log detailed information for debugging
                        \Log::error("validateTableData: Required field missing or empty", [
                            'field_label' => $field['label'],
                            'normalized_key' => $fieldKey,
                            'original_key' => $originalFieldKey ?? null,
                            'field_found' => $fieldFound,
                            'field_value' => $fieldValue ?? null,
                            'is_empty' => $isEmpty,
                            'available_fields' => array_keys($row),
                            'row_index' => $rowIndex + 1,
                            'tried_variations' => $possibleNames ?? [],
                        ]);
                        
                        $errorMsg = "Required field '{$field['label']}' (normalized key: {$fieldKey}) is " . 
                                   (!$fieldFound ? "missing" : "empty") . 
                                   " in row " . ($rowIndex + 1) . 
                                   ". Available fields: {$availableFields}" . 
                                   ($suggestion ? ". Did you mean: {$suggestion}" : "");
                        throw new \Exception($errorMsg);
                    }
                }
                
                // Validate field types
                if ($fieldFound && $this->tableCellToTrimmedString($fieldValue ?? '') !== '') {
                    switch ($field['type']) {
                        case 'link':
                            $linkStr = $this->tableCellToTrimmedString($fieldValue);
                            if ($linkStr === '' || !filter_var($linkStr, FILTER_VALIDATE_URL)) {
                                throw new \Exception("Invalid URL format for field '{$field['label']}' in row " . ($rowIndex + 1));
                            }
                            break;
                        case 'dropdown':
                            $dropdownStr = $this->tableCellToTrimmedString($fieldValue);
                            if (isset($field['options']) && !in_array($dropdownStr, $field['options'])) {
                                $labelLower = strtolower($field['label'] ?? '');
                                $valueNormalized = strtoupper($dropdownStr);
                                // Evidence Verified by QA: accept Yes/No in any casing
                                $isEvidenceVerifiedField = (str_contains($labelLower, 'evidence') && str_contains($labelLower, 'verified') && str_contains($labelLower, 'qa'));
                                if ($isEvidenceVerifiedField && in_array($valueNormalized, ['YES', 'NO'], true)) {
                                    break;
                                }
                                // Quarter field: accept standard options, common variants, summary placeholders (—, -), and numeric 1-4
                                $isQuarterField = (strpos($labelLower, 'quarter') !== false);
                                if ($isQuarterField) {
                                    $val = $dropdownStr;
                                    $validQuarters = ['1st Q', '2nd Q', '3rd Q', '4th Q'];
                                    $quarterVariants = ['1st q', '2nd q', '3rd q', '4th q', '1st quarter', '2nd quarter', '3rd quarter', '4th quarter', 'q1', 'q2', 'q3', 'q4', '1', '2', '3', '4'];
                                    $isPlaceholder = in_array($val, ['—', '-', '–'], true) || preg_match('/^[\s\-–—]+$/', $val);
                                    $matchesVariant = in_array(strtolower($val), $quarterVariants, true) || in_array($val, $validQuarters, true);
                                    if ($matchesVariant || $isPlaceholder) {
                                        break;
                                    }
                                }
                                throw new \Exception("Invalid option for field '{$field['label']}' in row " . ($rowIndex + 1));
                            }
                            break;
                    }
                }
            }
        }
    }
    
    /**
     * Whether a table_data row is a blue summary row (section aggregate / separator).
     */
    private function tableDataRowIsSummary(array $row): bool
    {
        $meta = $row['_meta'] ?? null;
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($meta)) {
            return false;
        }

        return ($meta['row_type'] ?? '') === 'summary';
    }

    private function tableDataHasSummaryRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (is_array($row) && $this->tableDataRowIsSummary($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Trim/cast only scalar table cells. Arrays (e.g. _meta) must never be cast to string — avoids "Array to string conversion" on save.
     */
    private function tableCellToTrimmedString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_int($value) || is_float($value)) {
            return trim((string) $value);
        }
        if (is_string($value)) {
            return trim($value);
        }

        return '';
    }

    private function suggestFieldMatch($expectedField, $availableFields)
    {
        $expectedLower = strtolower($expectedField);
        $suggestions = [];
        
        foreach ($availableFields as $field) {
            $fieldLower = strtolower($field);
            
            // Check for exact word matches
            $expectedWords = explode(' ', $expectedLower);
            $fieldWords = explode(' ', $fieldLower);
            
            $matchCount = 0;
            foreach ($expectedWords as $word) {
                foreach ($fieldWords as $fieldWord) {
                    if (strpos($fieldWord, $word) !== false || strpos($word, $fieldWord) !== false) {
                        $matchCount++;
                        break;
                    }
                }
            }
            
            if ($matchCount > 0) {
                $suggestions[] = $field;
            }
        }
        
        return !empty($suggestions) ? implode(', ', array_slice($suggestions, 0, 3)) : null;
    }
}