<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Models\Approval;
use App\Models\Campus;
use App\Models\TemplateEditHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuperAdminValidatedTemplatesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->isSuperAdmin()) {
                abort(403, 'Only Super Admin can access this area.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of validated templates
     * These are submissions that have been approved by QA Coordinator
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get filter parameters
        $filters = [
            'campus' => $request->get('campus'),
            'sg_code' => $request->get('sg_code'),
            'kra_title' => $request->get('kra_title'),
            'kpi_title' => $request->get('kpi_title'),
            'template_code' => $request->get('template_code'),
            'quarter' => $request->get('quarter'),
        ];

        // Build query for validated templates
        // Only show submissions that are Approved and have been validated (have approval with validated_at)
        $query = Submission::with(['template', 'submitter', 'approval'])
            ->where('status', 'Approved')
            ->whereHas('approval', function($q) {
                $q->whereNotNull('validated_at');
            })
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });

        // Apply filters
        if (!empty($filters['campus'])) {
            $query->where('campus', $filters['campus']);
        }

        if (!empty($filters['sg_code'])) {
            $query->where('sg_code', $filters['sg_code']);
        }

        if (!empty($filters['kra_title'])) {
            $query->where('kra_title', $filters['kra_title']);
        }

        if (!empty($filters['kpi_title'])) {
            $query->where('kpi_title', $filters['kpi_title']);
        }

        if (!empty($filters['template_code'])) {
            $query->where('template_code', $filters['template_code']);
        }

        if (!empty($filters['quarter'])) {
            $query->where('quarter', $filters['quarter']);
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->paginate(20);

        // Get filter options
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $strategicGoals = Submission::whereNotNull('sg_code')
            ->where('status', 'Approved')
            ->select('sg_code')
            ->distinct()
            ->orderBy('sg_code')
            ->pluck('sg_code');
        $kraTitles = Submission::whereNotNull('kra_title')
            ->where('status', 'Approved')
            ->select('kra_title')
            ->distinct()
            ->orderBy('kra_title')
            ->pluck('kra_title');
        $kpiTitles = Submission::whereNotNull('kpi_title')
            ->where('status', 'Approved')
            ->select('kpi_title')
            ->distinct()
            ->orderBy('kpi_title')
            ->pluck('kpi_title');
        $templateCodes = Submission::whereNotNull('template_code')
            ->where('status', 'Approved')
            ->select('template_code')
            ->distinct()
            ->orderBy('template_code')
            ->pluck('template_code');
        $quarters = Submission::whereNotNull('quarter')
            ->where('status', 'Approved')
            ->select('quarter')
            ->distinct()
            ->orderBy('quarter')
            ->pluck('quarter');

        // Get statistics
        $totalValidated = Submission::where('status', 'Approved')
            ->whereHas('approval', function($q) {
                $q->whereNotNull('validated_at');
            })
            ->where(function($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            })
            ->count();

        $stats = [
            'total_validated' => $totalValidated,
        ];

        return view('super-admin.validated-templates.index', compact('submissions', 'campuses', 'strategicGoals', 'kraTitles', 'kpiTitles', 'templateCodes', 'quarters', 'filters', 'stats'));
    }

    /**
     * Display the specified validated template
     */
    public function show(Submission $submission)
    {
        // Ensure submission is validated
        if ($submission->status !== 'Approved' || !$submission->approval || !$submission->approval->validated_at) {
            return redirect()->route('super-admin.validated-templates.index')
                ->with('error', 'This submission is not validated.');
        }

        $submission->load(['template', 'submitter', 'approval.validator', 'form']);
        $isPercentageForm = $this->formUsesPercentage($submission->form);

        return view('super-admin.validated-templates.show', compact('submission', 'isPercentageForm'));
    }

    /**
     * Show the form for editing accomplishment values
     */
    public function edit(Submission $submission)
    {
        // Ensure submission is validated
        if ($submission->status !== 'Approved' || !$submission->approval || !$submission->approval->validated_at) {
            return redirect()->route('super-admin.validated-templates.index')
                ->with('error', 'This submission is not validated.');
        }

        $submission->load(['template', 'submitter', 'approval.validator', 'form']);
        $isPercentageForm = $this->formUsesPercentage($submission->form);

        return view('super-admin.validated-templates.edit', compact('submission', 'isPercentageForm'));
    }

    /**
     * Update accomplishment values and performance validation
     * Only Super Admin can edit these values
     */
    public function update(Request $request, Submission $submission)
    {
        // Ensure submission is validated
        if ($submission->status !== 'Approved' || !$submission->approval || !$submission->approval->validated_at) {
            return redirect()->route('super-admin.validated-templates.index')
                ->with('error', 'This submission is not validated.');
        }

        $request->validate([
            'accomp_q1' => 'required|numeric|min:0',
            'accomp_q2' => 'required|numeric|min:0',
            'accomp_q3' => 'required|numeric|min:0',
            'accomp_q4' => 'required|numeric|min:0',
            'rating' => 'nullable|string',
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Calculate totals
            $accompTotal = $request->accomp_q1 + $request->accomp_q2 + $request->accomp_q3 + $request->accomp_q4;
            
            // Get target total from approval
            $approval = $submission->approval;
            $targetTotal = $approval->target_total ?? 0;
            
            $variance = $accompTotal - $targetTotal;
            $rate = $targetTotal > 0 ? ($accompTotal / $targetTotal) * 100 : 0;

            // Auto-calculate rating based on rate if not provided
            $rating = $request->rating;
            if (empty($rating)) {
                if ($rate >= 100) {
                    $rating = 'Outstanding';
                } elseif ($rate >= 90) {
                    $rating = 'Very Satisfactory';
                } elseif ($rate >= 80) {
                    $rating = 'Satisfactory';
                } elseif ($rate >= 70) {
                    $rating = 'Fair';
                } else {
                    $rating = 'Needs Improvement';
                }
            }

            // Update approval - only accomplishment values and performance validation
            // Note: Super Admin can only edit accomplishment values, not target values
            $approval->update([
                'accomp_q1' => $request->accomp_q1,
                'accomp_q2' => $request->accomp_q2,
                'accomp_q3' => $request->accomp_q3,
                'accomp_q4' => $request->accomp_q4,
                'accomp_total' => $accompTotal,
                'variance' => $variance,
                'rate' => $rate,
                'rating' => $rating,
                'remarks' => $request->remarks,
                // Keep validated_by and validated_at as set by QA Coordinator
            ]);

            DB::commit();

            // Log to audit trail
            if ($submission->template_id) {
                $campusName = optional($user->campusInfo)->name ?? $user->campus ?? Campus::where('code', $user->campus_code)->value('name') ?? '';
                $templateCode = $submission->template_code ?? '';
                $quarter = $submission->quarter ?? '';
                $context = trim(implode(' ', array_filter([$templateCode, $quarter])));
                TemplateEditHistory::create([
                    'template_id' => $submission->template_id,
                    'user_id' => $user->id,
                    'what_edited' => "{$user->name} (Super Admin) updated accomplishment values for {$context} — Rate: " . round($rate, 2) . '%, Rating: ' . $rating . '.',
                ]);
            }

            return redirect()->route('super-admin.validated-templates.show', $submission)
                ->with('success', 'Accomplishment values and performance validation updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update: ' . $e->getMessage());
        }
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

