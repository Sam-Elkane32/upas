<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Submission;
use App\Models\Approval;
use App\Models\Form;
use App\Models\User;
use App\Models\TemplateEditHistory;
use App\Services\FormTargetsService;
use App\Services\RollupService;
use App\Services\ComputeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CampusAdminApprovalController extends Controller
{
    public function __construct(
        protected RollupService $rollupService,
        protected FormTargetsService $formTargetsService,
        protected ComputeService $computeService
    ) {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Get the campus name for the authenticated user
     * This ensures proper matching with submissions which store full campus names
     * Uses the exact same logic as DashboardService for consistency
     */
    protected function getCampusName(): string
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \RuntimeException('User must be authenticated.');
        }
        
        // Load campusInfo relationship if not already loaded
        if (!$user->relationLoaded('campusInfo')) {
            $user->load('campusInfo');
        }
        
        // Use the exact same logic as DashboardService::getCampusAdminData()
        // Resolve campus name: campusInfo->name, then user->campus, then lookup by campus_code
        // This ensures consistency between Planning Coordinator submissions and QA approval flow
        $campusName = ($user->campusInfo && $user->campusInfo->name)
            ? $user->campusInfo->name
            : ($user->campus ?: \App\Models\Campus::where('code', $user->campus_code)->value('name') ?: '');
        
        // Log for debugging
        \Log::info('CampusAdminApprovalController::getCampusName', [
            'user_id' => $user->id,
            'campus_code' => $user->campus_code,
            'user_campus_field' => $user->campus,
            'campusInfo_exists' => $user->campusInfo ? 'yes' : 'no',
            'campusInfo_name' => optional($user->campusInfo)->name ?? null,
            'resolved_campus_name' => $campusName,
        ]);
        
        return $campusName;
    }

    /**
     * Ensure the submission belongs to the authenticated user's campus.
     * Uses both campus name and campus_code for data alignment.
     */
    protected function authorizeCampusAccess(Submission $submission): void
    {
        $campusName = $this->getCampusName();
        $campusCode = Auth::user()->campus_code;
        $belongsToCampus = ($submission->campus === $campusName) || ($campusCode && $submission->campus_code === $campusCode);
        if (!$belongsToCampus) {
            abort(403, 'Access Denied: You can only access submissions from your campus.');
        }
    }

    /**
     * Display pending submissions for approval
     */
    public function index()
    {
        $campusName = $this->getCampusName();
        $campusCode = Auth::user()->campus_code;
        
        // Exclude orphaned submissions (template or form was deleted by Super Admin)
        // Get statistics directly
        // Total submissions = Pending Review + Approved (excludes Returned and Drafts)
        // Returned submissions are excluded from total as they need to be resubmitted
        $pendingReview = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Pending Review')
            ->where(function($query) {
                $query->where('is_draft', false)
                      ->orWhereNull('is_draft');
            })
            ->count();
        
        $approved = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Approved')
            ->count();
        
        $returned = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Returned')
            ->count();
        
        $stats = [
            'total_submissions' => $pendingReview + $approved,
            'pending_review' => $pendingReview,
            'approved' => $approved,
            'returned' => $returned,
        ];
        
        // Debug: Log the query (matching DashboardService style)
        \Log::info('CampusAdminApprovalController::index', [
            'campus_name' => $campusName,
            'stats' => $stats,
            'all_pending_campuses' => Submission::where('status', 'Pending Review')->pluck('campus')->toArray(),
            'all_campus_names' => Submission::select('campus')->distinct()->pluck('campus')->toArray(),
        ]);
        
        // Get pending submissions for the admin's campus (exclude drafts and orphaned submissions)
        // Orphaned = template_id null (Form/Template was deleted by Super Admin)
        $submissions = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Pending Review')
            ->where(function($query) {
                $query->where('is_draft', false)
                      ->orWhereNull('is_draft');
            })
            ->with(['template', 'submitter'])
            ->orderBy('submitted_at', 'asc')
            ->paginate(10);

        return view('campus-admin.approvals.index', compact('submissions', 'campusName', 'stats'));
    }

    /**
     * Display submissions list pages for QA Coordinator.
     *
     * This endpoint is reused for separate views:
     * - Approved submissions
     * - Returned submissions
     *
     * Pending submissions are handled by the main index() page.
     */
    public function allSubmissions(Request $request)
    {
        $campusName = $this->getCampusName();
        $campusCode = Auth::user()->campus_code;
        $status = $request->get('status');
        
        // If no status or pending is requested, redirect to the main approvals index
        if (!$status || $status === 'Pending Review') {
            return redirect()->route('campus-admin.approvals.index');
        }
        
        // Build query scoped to campus and status (exclude orphaned submissions)
        $query = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', $status);
        
        // Exclude drafts from the list
        $query->where(function($q) {
            $q->where('is_draft', false)
              ->orWhereNull('is_draft');
        });
        
        $submissions = $query
            ->with(['template', 'submitter', 'approval'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString(); // Preserve query parameters in pagination links

        if ($status === 'Approved') {
            return view('campus-admin.approvals.approved', compact('submissions'));
        }

        if ($status === 'Returned') {
            return view('campus-admin.approvals.returned', compact('submissions'));
        }

        // Fallback: for any other status, send back to index
        return redirect()->route('campus-admin.approvals.index');
    }

    /**
     * Show the form for reviewing a submission
     */
    public function show(Submission $submission)
    {
        // Ensure user can only view submissions from their campus
        $this->authorizeCampusAccess($submission);

        $submission->load(['form', 'template', 'submitter', 'approval']);

        // Compute summary/result rows for Submitted Data (same as planning coordinator view)
        $template = $submission->template;
        $rawTableData = $submission->table_data;
        if (is_array($rawTableData) && !empty($rawTableData) && $template) {
            $schemaFields = $template->getSchemaFields();
            $summaryRules = $template->getSummaryRules();
            $summaryCellMappings = $template->getSummaryCellMappings();
            if (!empty($schemaFields)) {
                $submission->table_data = $this->computeService->computeCalculatedFields(
                    $rawTableData,
                    $schemaFields,
                    $summaryRules,
                    $summaryCellMappings
                );
            }
        }

        return view('campus-admin.approvals.show', compact('submission'));
    }

    /**
     * Show the form for creating/editing approval
     */
    public function create(Submission $submission)
    {
        // Ensure user can only approve submissions from their campus
        $this->authorizeCampusAccess($submission);

        // Check if submission is pending
        if ($submission->status !== 'Pending Review') {
            return redirect()->route('campus-admin.approvals.index')
                ->with('error', 'This submission is not pending review.');
        }

        $submission->load(['form', 'template.form', 'submitter']);
        
        // Get existing approval if any
        $approval = $submission->approval;

        // Always resolve target values from Form (Super Admin KRA/KPI) so QA Coordinator sees set targets
        $formTargets = $this->formTargetsService->getForSubmission($submission) ?? [];

        // Accomplishment values always from planning coordinator's submitted data (submission table_data)
        $submissionAccomplishments = $this->rollupService->extractFromSubmission($submission);

        $suggestedValues = $approval ? null : $submissionAccomplishments;
        if ($suggestedValues !== null && !empty($formTargets)) {
            $suggestedValues['target_q1'] = $formTargets['target_q1'] ?? $suggestedValues['target_q1'] ?? 0;
            $suggestedValues['target_q2'] = $formTargets['target_q2'] ?? $suggestedValues['target_q2'] ?? 0;
            $suggestedValues['target_q3'] = $formTargets['target_q3'] ?? $suggestedValues['target_q3'] ?? 0;
            $suggestedValues['target_q4'] = $formTargets['target_q4'] ?? $suggestedValues['target_q4'] ?? 0;
        }

        $isPercentageForm = $this->formUsesPercentage($submission->form);

        return view('campus-admin.approvals.create', compact('submission', 'approval', 'suggestedValues', 'formTargets', 'submissionAccomplishments', 'isPercentageForm'));
    }

    /**
     * Store a newly created approval
     */
    public function store(Request $request, Submission $submission)
    {
        // Ensure user can only approve submissions from their campus
        $this->authorizeCampusAccess($submission);

        $request->validate([
            'target_q1' => 'required|numeric|min:0',
            'target_q2' => 'required|numeric|min:0',
            'target_q3' => 'required|numeric|min:0',
            'target_q4' => 'required|numeric|min:0',
            'accomp_q1' => 'required|numeric|min:0',
            'accomp_q2' => 'required|numeric|min:0',
            'accomp_q3' => 'required|numeric|min:0',
            'accomp_q4' => 'required|numeric|min:0',
            'rating' => 'nullable|string',
            'remarks' => 'nullable|string|max:1000',
            'action' => 'required|in:approve,return',
            'evidence_qa' => 'nullable|array',
            'evidence_qa.*' => 'nullable|string|in:Yes,No,YES,NO',
        ]);

        $submission->load(['form', 'template']);
        $formTargets = $this->formTargetsService->getForSubmission($submission);
        $targetQ1 = $formTargets !== null ? (float) $formTargets['target_q1'] : (float) $request->target_q1;
        $targetQ2 = $formTargets !== null ? (float) $formTargets['target_q2'] : (float) $request->target_q2;
        $targetQ3 = $formTargets !== null ? (float) $formTargets['target_q3'] : (float) $request->target_q3;
        $targetQ4 = $formTargets !== null ? (float) $formTargets['target_q4'] : (float) $request->target_q4;
        $targetTotal = $formTargets !== null ? (float) $formTargets['target_total'] : ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4);

        $submissionAccomplishments = $this->rollupService->extractFromSubmission($submission);
        $accompQ1 = (float) ($submissionAccomplishments['accomp_q1'] ?? $request->accomp_q1);
        $accompQ2 = (float) ($submissionAccomplishments['accomp_q2'] ?? $request->accomp_q2);
        $accompQ3 = (float) ($submissionAccomplishments['accomp_q3'] ?? $request->accomp_q3);
        $accompQ4 = (float) ($submissionAccomplishments['accomp_q4'] ?? $request->accomp_q4);
        $accompTotal = $accompQ1 + $accompQ2 + $accompQ3 + $accompQ4;

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $variance = $accompTotal - $targetTotal;
            $rate = $targetTotal > 0 ? ($accompTotal / $targetTotal) * 100 : 0;

            // Auto-calculate rating based on rate:
            // Outstanding: 100% and above
            // Very Satisfactory: 90-99%
            // Satisfactory: 80-89%
            // Needs Improvement: <80%
            $rating = 'Needs Improvement';
            if ($rate >= 100) {
                $rating = 'Outstanding';
            } elseif ($rate >= 90) {
                $rating = 'Very Satisfactory';
            } elseif ($rate >= 80) {
                $rating = 'Satisfactory';
            }

            $this->mergeEvidenceQaIntoSubmission($submission, $request);

            // Create or update approval (targets from Form; accomplishments from planning coordinator submission)
            $approval = Approval::updateOrCreate(
                ['submission_id' => (int) $submission->id],
                array_merge($this->approvalMetaForSubmission($submission), [
                    'target_q1' => $targetQ1,
                    'target_q2' => $targetQ2,
                    'target_q3' => $targetQ3,
                    'target_q4' => $targetQ4,
                    'target_total' => $targetTotal,
                    'accomp_q1' => $accompQ1,
                    'accomp_q2' => $accompQ2,
                    'accomp_q3' => $accompQ3,
                    'accomp_q4' => $accompQ4,
                    'accomp_total' => $accompTotal,
                    'variance' => $variance,
                    'rate' => $rate,
                    'rating' => $rating,
                    'remarks' => $request->remarks,
                    'verified_by' => (int) $user->id,
                    'validated_by' => (int) $user->id,
                ])
            );

            // Handle different actions
            switch ($request->action) {
                case 'approve':
                    $approval->validated_at = now();
                    $approval->save();
                    
                    $submission->status = 'Approved';
                    $submission->save();
                    
                    $this->logApprovalOrReturnForAudit($submission, 'approve');
                    $message = 'Submission approved successfully!';
                    break;
                    
                case 'return':
                    $submission->status = 'Returned';
                    $submission->save();
                    
                    $this->logApprovalOrReturnForAudit($submission, 'return');
                    $message = 'Submission returned for revision.';
                    break;
            }

            DB::commit();

            return redirect()->route('campus-admin.approvals.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to process approval: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing an approval
     */
    public function edit(Submission $submission)
    {
        // Ensure user can only edit approvals from their campus
        $this->authorizeCampusAccess($submission);

        $submission->load(['form', 'template', 'submitter', 'approval']);

        if (!$submission->approval) {
            return redirect()->route('campus-admin.approvals.create', $submission);
        }

        $formTargets = $this->formTargetsService->getForSubmission($submission) ?? [];
        $submissionAccomplishments = $this->rollupService->extractFromSubmission($submission);
        $isPercentageForm = $this->formUsesPercentage($submission->form);

        return view('campus-admin.approvals.edit', compact('submission', 'formTargets', 'submissionAccomplishments', 'isPercentageForm'));
    }

    /**
     * Update the specified approval
     */
    public function update(Request $request, Submission $submission)
    {
        // Ensure user can only update approvals from their campus
        $this->authorizeCampusAccess($submission);

        $request->validate([
            'target_q1' => 'required|numeric|min:0',
            'target_q2' => 'required|numeric|min:0',
            'target_q3' => 'required|numeric|min:0',
            'target_q4' => 'required|numeric|min:0',
            'accomp_q1' => 'required|numeric|min:0',
            'accomp_q2' => 'required|numeric|min:0',
            'accomp_q3' => 'required|numeric|min:0',
            'accomp_q4' => 'required|numeric|min:0',
            'rating' => 'nullable|string',
            'remarks' => 'nullable|string|max:1000',
            'action' => 'required|in:approve,return',
            'evidence_qa' => 'nullable|array',
            'evidence_qa.*' => 'nullable|string|in:Yes,No,YES,NO',
        ]);

        $submission->load(['form', 'template']);
        $formTargets = $this->formTargetsService->getForSubmission($submission);
        $targetQ1 = $formTargets !== null ? (float) $formTargets['target_q1'] : (float) $request->target_q1;
        $targetQ2 = $formTargets !== null ? (float) $formTargets['target_q2'] : (float) $request->target_q2;
        $targetQ3 = $formTargets !== null ? (float) $formTargets['target_q3'] : (float) $request->target_q3;
        $targetQ4 = $formTargets !== null ? (float) $formTargets['target_q4'] : (float) $request->target_q4;
        $targetTotal = $formTargets !== null ? (float) $formTargets['target_total'] : ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4);

        $submissionAccomplishments = $this->rollupService->extractFromSubmission($submission);
        $accompQ1 = (float) ($submissionAccomplishments['accomp_q1'] ?? $request->accomp_q1);
        $accompQ2 = (float) ($submissionAccomplishments['accomp_q2'] ?? $request->accomp_q2);
        $accompQ3 = (float) ($submissionAccomplishments['accomp_q3'] ?? $request->accomp_q3);
        $accompQ4 = (float) ($submissionAccomplishments['accomp_q4'] ?? $request->accomp_q4);
        $accompTotal = $accompQ1 + $accompQ2 + $accompQ3 + $accompQ4;

        try {
            DB::beginTransaction();

            $user = Auth::user();

            $variance = $accompTotal - $targetTotal;
            $rate = $targetTotal > 0 ? ($accompTotal / $targetTotal) * 100 : 0;

            // Auto-calculate rating based on rate:
            // Outstanding: 100% and above
            // Very Satisfactory: 90-99%
            // Satisfactory: 80-89%
            // Needs Improvement: <80%
            $rating = 'Needs Improvement';
            if ($rate >= 100) {
                $rating = 'Outstanding';
            } elseif ($rate >= 90) {
                $rating = 'Very Satisfactory';
            } elseif ($rate >= 80) {
                $rating = 'Satisfactory';
            }

            // Update approval (targets from Form; accomplishments from planning coordinator submission)
            $approval = $submission->approval;
            if (!$approval) {
                throw new \Exception('Approval record not found.');
            }

            $this->mergeEvidenceQaIntoSubmission($submission, $request);

            $approval->update(array_merge($this->approvalMetaForSubmission($submission, $approval), [
                'target_q1' => $targetQ1,
                'target_q2' => $targetQ2,
                'target_q3' => $targetQ3,
                'target_q4' => $targetQ4,
                'target_total' => $targetTotal,
                'accomp_q1' => $accompQ1,
                'accomp_q2' => $accompQ2,
                'accomp_q3' => $accompQ3,
                'accomp_q4' => $accompQ4,
                'accomp_total' => $accompTotal,
                'variance' => $variance,
                'rate' => $rate,
                'rating' => $rating,
                'remarks' => $request->remarks,
                'verified_by' => (int) $user->id,
                'validated_by' => (int) $user->id,
            ]));

            // Handle different actions
            switch ($request->action) {
                case 'approve':
                    $approval->validated_at = now();
                    $approval->save();
                    
                    $submission->status = 'Approved';
                    $submission->save();
                    
                    $this->logApprovalOrReturnForAudit($submission, 'approve');
                    $message = 'Submission approved successfully!';
                    break;
                    
                case 'return':
                    $submission->status = 'Returned';
                    $submission->save();
                    
                    $this->logApprovalOrReturnForAudit($submission, 'return');
                    $message = 'Submission returned for revision.';
                    break;
            }

            DB::commit();

            return redirect()->route('campus-admin.approvals.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update approval: ' . $e->getMessage());
        }
    }

    /**
     * Calculate performance metrics (AJAX endpoint)
     */
    public function calculateMetrics(Request $request)
    {
        $request->validate([
            'target_q1' => 'required|numeric|min:0',
            'target_q2' => 'required|numeric|min:0',
            'target_q3' => 'required|numeric|min:0',
            'target_q4' => 'required|numeric|min:0',
            'accomp_q1' => 'required|numeric|min:0',
            'accomp_q2' => 'required|numeric|min:0',
            'accomp_q3' => 'required|numeric|min:0',
            'accomp_q4' => 'required|numeric|min:0',
        ]);

        $targetTotal = $request->target_q1 + $request->target_q2 + $request->target_q3 + $request->target_q4;
        $accompTotal = $request->accomp_q1 + $request->accomp_q2 + $request->accomp_q3 + $request->accomp_q4;
        $variance = $accompTotal - $targetTotal;
        $rate = $targetTotal > 0 ? ($accompTotal / $targetTotal) * 100 : 0;
        
        // Calculate rating
        $rating = 'Needs Improvement';
        if ($rate >= 100) $rating = 'Outstanding';
        elseif ($rate >= 90) $rating = 'Very Satisfactory';
        elseif ($rate >= 80) $rating = 'Satisfactory';
        elseif ($rate >= 70) $rating = 'Fair';

        return response()->json([
            'target_total' => number_format($targetTotal, 2),
            'accomp_total' => number_format($accompTotal, 2),
            'variance' => number_format($variance, 2),
            'rate' => number_format($rate, 2),
            'rating' => $rating
        ]);
    }

    /**
     * Get approval statistics
     */
    public function statistics()
    {
        $user = Auth::user();
        $campusName = $this->getCampusName();

        // Debug: Log campus name and query results for troubleshooting
        $totalSubmissions = Submission::where('campus', $campusName)->count();
        $pendingReview = Submission::where('campus', $campusName)
            ->where('status', 'Pending Review')
            ->count();
        $approved = Submission::where('campus', $campusName)
            ->where('status', 'Approved')
            ->count();
        $returned = Submission::where('campus', $campusName)
            ->where('status', 'Returned')
            ->count();
        
        \Log::info('Statistics request - Campus:', [
            'campus_name' => $campusName,
            'user_campus' => $user->campus,
            'user_campus_code' => $user->campus_code,
            'user_id' => $user->id,
            'total_submissions' => $totalSubmissions,
            'pending_review' => $pendingReview,
            'approved' => $approved,
            'returned' => $returned,
            'all_submission_campuses' => Submission::select('campus')->distinct()->pluck('campus')->toArray(),
        ]);

        $stats = [
            'total_submissions' => $totalSubmissions,
            'pending_review' => $pendingReview,
            'approved' => $approved,
            'returned' => $returned,
            'approval_rate' => 0,
            'average_rating' => 'N/A'
        ];

        // Calculate approval rate
        if ($stats['total_submissions'] > 0) {
            $stats['approval_rate'] = round(($stats['approved'] / $stats['total_submissions']) * 100, 1);
        }

        // Calculate average rating
        $approvedSubmissions = Submission::where('campus', $campusName)
            ->where('status', 'Approved')
            ->with('approval')
            ->get();

        if ($approvedSubmissions->count() > 0) {
            $ratings = $approvedSubmissions->pluck('approval.rating')->filter();
            if ($ratings->count() > 0) {
                $ratingValues = [
                    'Outstanding' => 5,
                    'Very Satisfactory' => 4,
                    'Satisfactory' => 3,
                    'Fair' => 2,
                    'Needs Improvement' => 1
                ];
                
                $averageRatingValue = $ratings->map(function($rating) use ($ratingValues) {
                    return $ratingValues[$rating] ?? 0;
                })->avg();
                
                $stats['average_rating'] = array_search(round($averageRatingValue), $ratingValues) ?: 'N/A';
            }
        }

        return response()->json($stats);
    }

    /**
     * Log approve or return action to template edit history (Audit Trailing).
     */
    protected function logApprovalOrReturnForAudit(Submission $submission, string $action): void
    {
        $templateId = $submission->template_id ?? null;
        if (!$templateId) {
            return;
        }
        $user = Auth::user();
        if (!$user) {
            return;
        }
        $userName = $user->name;
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?? 'Unknown Campus';
        $templateCode = $submission->template_code ?? '';
        $quarter = $submission->quarter ?? '';
        $subId = $submission->id;
        $context = trim(implode(' ', array_filter([$templateCode, $quarter])));
        $whatEdited = $action === 'approve'
            ? "{$userName} (QA Coordinator, {$campusName}) approved {$context} — Submission #{$subId}."
            : "{$userName} (QA Coordinator, {$campusName}) returned {$context} for revision — Submission #{$subId}.";
        $text = strlen($whatEdited) > 500 ? substr($whatEdited, 0, 497) . '...' : $whatEdited;
        TemplateEditHistory::create([
            'template_id' => $templateId,
            'user_id' => $user->id,
            'what_edited' => $text,
        ]);
    }

    /**
     * Default accomp_term / sdp_ref for approvals when the review form does not collect them.
     *
     * @return array{accomp_term: ?string, sdp_ref: ?string}
     */
    protected function approvalMetaForSubmission(Submission $submission, ?Approval $existing = null): array
    {
        $accompTerm = $existing?->accomp_term;
        if ($accompTerm === null || $accompTerm === '') {
            $accompTerm = $submission->quarter
                ?: ($submission->form_title ?? $submission->kpi_title ?? 'N/A');
        }

        return [
            'accomp_term' => $accompTerm,
            'sdp_ref' => $existing?->sdp_ref,
        ];
    }

    /**
     * Persist QA Yes/No evidence selections into submission table_data.
     */
    protected function mergeEvidenceQaIntoSubmission(Submission $submission, Request $request): void
    {
        $evidenceKey = $this->getEvidenceVerifiedByQAColumnKey($submission->table_data);
        if (! $evidenceKey || ! $request->has('evidence_qa') || ! is_array($request->evidence_qa)) {
            return;
        }

        $tableData = $submission->table_data ?? [];
        foreach ($request->evidence_qa as $rowIndex => $value) {
            $rowIndex = (int) $rowIndex;
            if (! isset($tableData[$rowIndex]) || ! is_array($tableData[$rowIndex])) {
                continue;
            }
            if (($tableData[$rowIndex]['_meta']['row_type'] ?? 'data') === 'summary') {
                continue;
            }
            $normalized = in_array($value, ['Yes', 'YES'], true) ? 'Yes' : (in_array($value, ['No', 'NO'], true) ? 'No' : '');
            $tableData[$rowIndex][$evidenceKey] = $normalized;
        }
        $submission->table_data = $tableData;
        $submission->save();
    }

    /**
     * Get the table_data column key for "Evidence Verified By The QA" (QA Coordinator can set Yes/No).
     */
    protected function getEvidenceVerifiedByQAColumnKey(?array $tableData): ?string
    {
        if (!$tableData || empty($tableData)) {
            return null;
        }
        foreach ($tableData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (!is_array($meta)) {
                $meta = [];
            }
            if (($meta['row_type'] ?? 'data') === 'summary') {
                continue;
            }
            foreach (array_keys($row) as $key) {
                if ($key === '_meta' || $key === '_after_separator') {
                    continue;
                }
                $n = strtolower(str_replace(['-', ' '], '_', (string) $key));
                if (str_contains($n, 'evidence') && str_contains($n, 'verified')
                    && (str_contains($n, 'qa') || str_contains($n, 'q_a') || str_contains($n, 'm_e'))) {
                    return $key;
                }
            }
        }
        return null;
    }

    /**
     * Check if the form uses percentage for target/accomplishment (any KPI has is_percentage).
     */
    protected function formUsesPercentage($form): bool
    {
        if (!$form || !is_array($form->kra_kpi_data ?? null)) {
            return false;
        }
        foreach ($form->kra_kpi_data as $kra) {
            foreach ($kra['kpis'] ?? [] as $kpi) {
                if (!empty($kpi['is_percentage'])) {
                    return true;
                }
            }
        }
        return false;
    }
}
