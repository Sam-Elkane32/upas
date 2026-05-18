<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Form;
use App\Models\Template;
use App\Models\Approval;
use App\Models\FormSubmission;
use App\Models\Submission;

class CampusAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * QA Coordinator Dashboard
     * QA Coordinator can only review and approve submissions - no form/template management
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get campus name (handle both campusInfo relationship and direct campus field)
        $campusName = optional($user->campusInfo)->name ?? $user->campus ?? \App\Models\Campus::where('code', $user->campus_code)->value('name') ?: '';
        $campusCode = $user->campus_code;
        
        // Get submission statistics (exclude orphaned submissions from deleted Forms/Templates)
        $totalSubmissions = Submission::forCampusNameOrCode($campusName, $campusCode)->whereNotNull('template_id')->count();
        $pendingReviews = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Pending Review')
            ->where(function($query) {
                $query->where('is_draft', false)
                      ->orWhereNull('is_draft');
            })
            ->count();
        $approvedSubmissionsCount = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Approved')
            ->count();
        $returnedSubmissions = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Returned')
            ->count();
        
        // Calculate campus accomplishment rate
        $approvedSubs = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Approved')
            ->with('approval')
            ->get();
        
        $campusAccomplishmentRate = 0;
        if ($approvedSubs->count() > 0) {
            $totalRate = 0;
            foreach ($approvedSubs as $sub) {
                if ($sub->approval && $sub->approval->rate) {
                    $totalRate += $sub->approval->rate;
                }
            }
            $campusAccomplishmentRate = $totalRate / $approvedSubs->count();
        }
        
        // QA Coordinator only needs submission-related stats
        $stats = [
            'pending_approvals' => $pendingReviews,
            'approved_this_month' => Submission::forCampusNameOrCode($campusName, $campusCode)
                ->whereNotNull('template_id')
                ->where('status', 'Approved')
                ->whereMonth('submitted_at', now()->month)
                ->count(),
        ];

        // Get pending submissions for review
        $pendingSubmissions = Submission::with(['template', 'submitter'])
            ->forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Pending Review')
            ->where(function($query) {
                $query->where('is_draft', false)
                      ->orWhereNull('is_draft');
            })
            ->orderBy('submitted_at', 'asc')
            ->limit(5)
            ->get();
        
        // Get recent approved submissions
        $approvedSubmissions = Submission::with(['template', 'submitter', 'approval'])
            ->forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Approved')
            ->orderBy('submitted_at', 'desc')
            ->limit(5)
            ->get();
        
        // Calculate quarterly performance
        $quarterlyPerformance = [];
        $quarters = ['1st Q', '2nd Q', '3rd Q', '4th Q'];
        foreach ($quarters as $quarter) {
            $quarterSubs = Submission::forCampusNameOrCode($campusName, $campusCode)
                ->whereNotNull('template_id')
                ->where('quarter', $quarter)
                ->where('status', 'Approved')
                ->with('approval')
                ->get();
            
            $count = $quarterSubs->count();
            $rate = 0;
            if ($count > 0) {
                $totalRate = 0;
                foreach ($quarterSubs as $sub) {
                    if ($sub->approval && $sub->approval->rate) {
                        $totalRate += $sub->approval->rate;
                    }
                }
                $rate = $totalRate / $count;
            }
            
            $quarterlyPerformance[$quarter] = [
                'count' => $count,
                'rate' => $rate
            ];
        }
        
        // Get top performing templates based on approved submissions only
        $topTemplates = Submission::forCampusNameOrCode($campusName, $campusCode)
            ->whereNotNull('template_id')
            ->where('status', 'Approved')
            ->whereNotNull('template_code')
            ->with('approval')
            ->get()
            ->groupBy('template_code')
            ->map(function($subs) {
                $avgRate = 0;
                $count = $subs->count();
                if ($count > 0) {
                    $totalRate = 0;
                    foreach ($subs as $sub) {
                        if ($sub->approval && $sub->approval->rate) {
                            $totalRate += $sub->approval->rate;
                        }
                    }
                    $avgRate = $totalRate / $count;
                }
                return (object) [
                    'template_code' => $subs->first()->template_code,
                    'kpi_title' => $subs->first()->kpi_title ?? $subs->first()->template_code,
                    'average_rate' => $avgRate,
                    'count' => $count
                ];
            })
            ->filter(function($template) {
                return $template->average_rate > 0;
            })
            ->sortByDesc('average_rate')
            ->take(5)
            ->values();

        return view('dashboard.campus-admin', compact(
            'stats', 
            'pendingSubmissions',
            'approvedSubmissions',
            'pendingReviews',
            'approvedSubmissionsCount',
            'returnedSubmissions',
            'campusAccomplishmentRate',
            'quarterlyPerformance',
            'topTemplates'
        ));
    }

    /**
     * RESTRICTED: QA Coordinator cannot create forms - Planning Coordinator only
     */
    public function createForm()
    {
        return redirect()->route('campus-admin.dashboard')
            ->with('error', 'QA Coordinator can only review and approve submissions. Forms must be created by Planning Coordinators.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot store forms - Planning Coordinator only
     */
    public function storeForm(Request $request)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms must be created by Planning Coordinators.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot manage forms - Planning Coordinator only
     */
    public function indexForms(Request $request)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot manage templates - Planning Coordinator only
     */
    public function indexTemplates()
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot create templates - Planning Coordinator only
     */
    public function createTemplate()
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates must be created by Planning Coordinators.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot store templates - Planning Coordinator only
     */
    public function storeTemplate(Request $request)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates must be created by Planning Coordinators.');
    }

    /**
     * Show approvals page
     */
    public function indexApprovals()
    {
        $user = Auth::user();
        
        // Get pending submissions that need approval
        $pendingSubmissions = FormSubmission::where('campus_code', $user->campus_code)
            ->where('status', 'Pending Review')
            ->with(['form', 'creator'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('campus-admin.approvals', compact('pendingSubmissions'));
    }

    /**
     * Show specific approval for editing
     */
    public function showApproval(FormSubmission $submission)
    {
        $user = Auth::user();
        
        if ($submission->campus_code !== $user->campus_code) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $submission->load(['form', 'creator']);

        return view('campus-admin.show-approval', compact('submission'));
    }

    /**
     * Update approval
     */
    public function updateApproval(Request $request, FormSubmission $submission)
    {
        $user = Auth::user();
        
        if ($submission->campus_code !== $user->campus_code) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'accomp_term' => 'nullable|string|max:255',
            'sdp_ref' => 'nullable|string|max:255',
            'accomp_q1' => 'required|numeric|min:0',
            'accomp_q2' => 'required|numeric|min:0',
            'accomp_q3' => 'required|numeric|min:0',
            'accomp_q4' => 'required|numeric|min:0',
            'rating' => 'nullable|string|in:Outstanding,Very Satisfactory,Satisfactory,Fair,Needs Improvement',
            'remarks' => 'nullable|string',
            'status' => 'required|string|in:Approved,Returned',
        ]);

        try {
            DB::beginTransaction();

            // Calculate total accomplishment
            $accompTotal = $request->accomp_q1 + $request->accomp_q2 + $request->accomp_q3 + $request->accomp_q4;

            // Get target total from the linked form
            $form = $submission->form;
            $targetTotal = $form->target_total ?? 0;

            // Calculate variance and rate
            $variance = $accompTotal - $targetTotal;
            $rate = $targetTotal > 0 ? ($accompTotal / $targetTotal) * 100 : 0;

            // Update submission record
            $submission->update([
                'accomp_term' => $request->accomp_term,
                'sdp_ref' => $request->sdp_ref,
                'accomp_q1' => $request->accomp_q1,
                'accomp_q2' => $request->accomp_q2,
                'accomp_q3' => $request->accomp_q3,
                'accomp_q4' => $request->accomp_q4,
                'accomp_total' => $accompTotal,
                'variance' => $variance,
                'rate_of_accomplishment' => $rate,
                'descriptive_rating' => $request->rating,
                'remarks' => $request->remarks,
                'status' => $request->status,
                'reviewer_id' => $user->id,
                'reviewed_at' => now(),
            ]);

            DB::commit();

            $message = $request->status === 'Approved' 
                ? 'Submission approved successfully!'
                : 'Submission returned for revision.';

            return redirect()->route('campus-admin.approvals')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update submission: ' . $e->getMessage());
        }
    }

    /**
     * RESTRICTED: QA Coordinator cannot toggle form status - Planning Coordinator only
     */
    public function toggleFormStatus(Form $form)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot toggle template status - Planning Coordinator only
     */
    public function toggleTemplateStatus(Template $template)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot view forms - Planning Coordinator only
     */
    public function showForm(Form $form)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot edit forms - Planning Coordinator only
     */
    public function editForm(Form $form)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot update forms - Planning Coordinator only
     */
    public function updateForm(Request $request, Form $form)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot delete forms - Planning Coordinator only
     */
    public function destroyForm(Form $form)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Forms management is for Planning Coordinators only.');
    }

    /**
     * Show template details
     */
    /**
     * RESTRICTED: QA Coordinator cannot view templates - Planning Coordinator only
     */
    public function showTemplate(Template $template)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates management is for Planning Coordinators only.');
    }

    /**
     * Show edit template page
     */
    public function editTemplate(Template $template)
    {
        $user = Auth::user();
        
        if ($template->campus_code !== $user->campus_code) {
            return redirect()->back()->with('error', 'Unauthorized access.');
        }

        if (!$template->canBeEdited()) {
            return redirect()->back()->with('error', 'This template cannot be edited.');
        }

        $strategicGoals = [
            'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
            'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
            'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
            'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
            'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
        ];

        // Get unique KRA titles from existing forms
        $kraTitles = Form::where('campus_code', $user->campus_code)
            ->distinct()
            ->pluck('kra_title')
            ->filter()
            ->values()
            ->toArray();

        // Get unique KPI titles from existing forms
        $kpiTitles = Form::where('campus_code', $user->campus_code)
            ->distinct()
            ->pluck('kpi_title')
            ->filter()
            ->values()
            ->toArray();

        // Get unique template codes from existing forms
        $templateCodes = Form::where('campus_code', $user->campus_code)
            ->whereNotNull('template_code')
            ->distinct()
            ->pluck('template_code')
            ->filter()
            ->values()
            ->toArray();

        return view('campus-admin.edit-template', compact('template', 'strategicGoals', 'kraTitles', 'kpiTitles', 'templateCodes'));
    }

    /**
     * RESTRICTED: QA Coordinator cannot update templates - Planning Coordinator only
     */
    public function updateTemplate(Request $request, Template $template)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates management is for Planning Coordinators only.');
    }

    /**
     * RESTRICTED: QA Coordinator cannot delete templates - Planning Coordinator only
     */
    public function destroyTemplate(Template $template)
    {
        abort(403, 'QA Coordinator can only review and approve submissions. Templates management is for Planning Coordinators only.');
    }
}