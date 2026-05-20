<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Template;
use App\Models\TemplateEditHistory;
use App\Models\Submission;
use App\Models\Campus;
use App\Models\Form;
use App\Models\User;
use App\Services\ComputeService;
use App\Services\TableDataAuditHelper;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SuperAdminTemplateController extends Controller
{
    public function __construct(
        protected ComputeService $computeService
    ) {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->isSuperAdmin()) {
                abort(403, 'Only Super Admin can access this area.');
            }
            return $next($request);
        });
    }

    /**
     * Build sequential template codes T1, T2, … skipping any already present in $usedCodes.
     * Always includes at least the next free T{n} after the highest T* number in use.
     *
     * @param  list<string>  $usedCodes
     * @return array{0: list<string>, 1: string}
     */
    private function availableSequentialTemplateCodesFromUsed(array $usedCodes): array
    {
        $usedUpper = array_values(array_unique(array_map(
            static fn ($c) => strtoupper(trim((string) $c)),
            $usedCodes
        )));

        $maxN = 0;
        foreach ($usedUpper as $c) {
            if (preg_match('/^T(\d+)$/', $c, $m)) {
                $maxN = max($maxN, (int) $m[1]);
            }
        }

        $candidateEnd = max(5, $maxN + 1);
        $available = [];
        for ($i = 1; $i <= $candidateEnd; $i++) {
            $code = 'T'.$i;
            if (! in_array(strtoupper($code), $usedUpper, true)) {
                $available[] = $code;
            }
        }

        $safety = 0;
        while ($available === [] && $safety < 10000) {
            $candidateEnd++;
            $code = 'T'.$candidateEnd;
            if (! in_array(strtoupper($code), $usedUpper, true)) {
                $available[] = $code;
            }
            $safety++;
        }

        $default = $available[0] ?? ('T'.($maxN + 1));

        return [$available, $default];
    }

    /**
     * Display unified templates/forms management page
     */
    public function index(Request $request)
    {
        // Get active tab (default to 'forms'; stay on create after failed form submission)
        $activeTab = $request->get('tab');
        if ($activeTab === null) {
            $activeTab = ($request->session()->has('errors') || $request->session()->get('form_create_failed'))
                ? 'create'
                : 'forms';
        }

        // Templates Query (load form so list can show which form each template belongs to)
        $templatesQuery = Template::with(['creator', 'assignedUser', 'assignedUsers', 'form']);
        
        // Apply template filters
        if ($request->filled('campus_code')) {
            if ($request->campus_code !== 'ALL') {
                $templatesQuery->where('campus_code', $request->campus_code);
            }
        }

        if ($request->filled('status')) {
            $templatesQuery->where('status', $request->status);
        }

        if ($request->filled('sg_code')) {
            $templatesQuery->where('sg_code', $request->sg_code);
        }

        if ($request->filled('kra_title')) {
            $templatesQuery->where('kra_title', 'like', '%' . $request->kra_title . '%');
        }

        $templates = $templatesQuery->orderBy('created_at', 'desc')->paginate(20, ['*'], 'templates_page')->withQueryString();
        
        // Get all planning coordinators for assignment dropdowns
        // Include: Planning Coordinator position/role OR Creator/Editor (they act as Planning Coordinators)
        $planningCoordinators = \App\Models\User::where(function($query) {
                $query->where('position', 'Planning Coordinator')
                      ->orWhere('position', 'planning_coordinator')
                      ->orWhere('position', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                      ->orWhere('role', 'planning_coordinator')
                      ->orWhere('role', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Forms Query (withCount so Forms tab reflects template rows per form after create/delete)
        $formsQuery = Form::with(['creator'])->withCount('templates');
        
        // Apply form filters
        if ($request->filled('form_status')) {
            $formsQuery->where('status', $request->form_status);
        }

        if ($request->filled('form_sg_code')) {
            $formsQuery->where('sg_code', $request->form_sg_code);
        }

        if ($request->filled('form_kra_title')) {
            $formsQuery->where('kra_title', 'like', '%' . $request->form_kra_title . '%');
        }

        if ($request->filled('form_search')) {
            $search = $request->form_search;
            $formsQuery->where(function($q) use ($search) {
                $q->where('form_title', 'like', '%' . $search . '%')
                  ->orWhere('kpi_title', 'like', '%' . $search . '%');
            });
        }

        $forms = $formsQuery->orderBy('created_at', 'desc')->paginate(20, ['*'], 'forms_page')->withQueryString();

        // Get filter options
        $campuses = Campus::where('is_active', true)->get();
        $statuses = ['Unpublished', 'Published'];
        $sgCodes = Template::distinct()->pluck('sg_code')->filter();
        
        // Strategic Goals for create form
        $strategicGoals = [
            'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
            'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
            'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
            'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
            'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
        ];

        // Get unique values for dropdowns
        $kraTitles = Template::distinct()->pluck('kra_title')->filter()->values()->toArray();
        $kpiTitles = Template::distinct()->pluck('kpi_title')->filter()->values()->toArray();
        $templateCodes = Template::distinct()->pluck('template_code')->filter()->values()->toArray();
        $formStrategicGoals = Form::distinct()->pluck('sg_code')->filter();
        $formKraTitles = Form::distinct()->pluck('kra_title')->filter();

        // Statistics
        $templateStats = [
            'total' => Template::count(),
            'published' => Template::where('status', 'Published')->count(),
            'draft' => Template::where('status', 'Unpublished')->count(),
        ];

        // On Forms tab this should match the sum of per-form template counts (exclude standalone / orphaned rows).
        $templatesLinkedToForms = Template::whereHas('form')->count();

        $formStats = [
            'total' => Form::count(),
            'published' => Form::where('status', 'Published')->count(),
            'draft' => Form::where('status', 'Unpublished')->count(),
            'total_templates' => $templatesLinkedToForms,
        ];

        return view('super-admin.templates.index', compact(
            'templates', 
            'forms', 
            'campuses', 
            'statuses', 
            'sgCodes', 
            'templateStats', 
            'formStats',
            'activeTab',
            'strategicGoals',
            'kraTitles',
            'kpiTitles',
            'templateCodes',
            'formStrategicGoals',
            'formKraTitles',
            'planningCoordinators'
        ));
    }

    /**
     * Show the form for creating a new template
     */
    public function create(Request $request)
    {
        return $this->renderCreateTemplate($request);
    }

    /**
     * Render create template view data (extracted from create() to avoid editor type-inference limits).
     */
    private function renderCreateTemplate(Request $request)
    {
        $campuses = Campus::where('is_active', true)->get();
        
        $strategicGoals = [
            'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
            'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
            'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
            'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
            'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
        ];

        // Get unique KRA titles from existing templates
        $kraTitles = Template::distinct()->pluck('kra_title')->filter()->values()->toArray();
        $kpiTitles = Template::distinct()->pluck('kpi_title')->filter()->values()->toArray();
        $templateCodes = Template::distinct()->pluck('template_code')->filter()->values()->toArray();
        
        // Get all planning coordinators for assignment dropdown
        // Include: Planning Coordinator position/role OR Creator/Editor (they act as Planning Coordinators)
        $planningCoordinators = \App\Models\User::where(function($query) {
                $query->where('position', 'Planning Coordinator')
                      ->orWhere('position', 'planning_coordinator')
                      ->orWhere('position', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                      ->orWhere('role', 'planning_coordinator')
                      ->orWhere('role', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
            })
            ->where('is_active', true)
            ->where('is_approved', true) // Only show approved Planning Coordinators
            ->with('campusInfo')
            ->orderBy('name')
            ->get();
        
        // If no approved ones found, check if there are any Planning Coordinators at all (for debugging)
        if ($planningCoordinators->count() == 0) {
            $allPlanningCoords = \App\Models\User::where(function($query) {
                    $query->where('position', 'Planning Coordinator')
                          ->orWhere('position', 'planning_coordinator')
                          ->orWhere('position', 'planning-coordinator')
                          ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                          ->orWhere('role', 'planning_coordinator')
                          ->orWhere('role', 'planning-coordinator')
                          ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
                })->get();
            
            \Log::warning('No approved Planning Coordinators found for template creation', [
                'role_constant' => \App\Models\User::ROLE_PLANNING_COORDINATOR,
                'total_planning_coords' => $allPlanningCoords->count(),
                'all_planning_coords' => $allPlanningCoords->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'position' => $user->position,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                        'is_approved' => $user->is_approved,
                    ];
                })->toArray(),
            ]);
            
            // Fallback: Show active Planning Coordinators even if not approved (with warning in UI)
            $planningCoordinators = \App\Models\User::where(function($query) {
                    $query->where('position', 'Planning Coordinator')
                          ->orWhere('position', 'planning_coordinator')
                          ->orWhere('position', 'planning-coordinator')
                          ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                          ->orWhere('role', 'planning_coordinator')
                          ->orWhere('role', 'planning-coordinator')
                          ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
                })
                ->where('is_active', true)
                ->with('campusInfo')
                ->orderBy('name')
                ->get();
        }
        
        // Debug: Log Planning Coordinators found
        \Log::info('Planning Coordinators query for template creation', [
            'role_constant' => \App\Models\User::ROLE_PLANNING_COORDINATOR,
            'count' => $planningCoordinators->count(),
            'user_ids' => $planningCoordinators->pluck('id')->toArray(),
            'user_names' => $planningCoordinators->pluck('name')->toArray(),
            'user_positions' => $planningCoordinators->pluck('position')->toArray(),
        ]);

        // If form_id is provided, pre-populate with form data
        $form = null;
        $parsedKpis = [];
        $parsedKras = [];
        $availableTemplateCodes = [];
        $defaultTemplateCode = null;
        
        if ($request->has('form_id')) {
            $form = Form::find($request->form_id);
            
            // Validate form exists
            if (!$form) {
                return redirect()->route('super-admin.templates.index', ['tab' => 'forms'])
                    ->with('error', 'Form not found. Please select a valid form.');
            }
            
            // Check if form has KRA/KPI data
            if (empty($form->kra_title) || empty($form->kpi_title)) {
                return redirect()->route('forms.show', $form->id)
                    ->with('error', 'No KRA/KPI titles found for this Form. Please edit the Form first to add KRA and KPI titles.');
            }
            
            // Parse KRA titles from form
            // First, try to get from structured kra_kpi_data (new format)
            if ($form->kra_kpi_data && is_array($form->kra_kpi_data) && !empty($form->kra_kpi_data)) {
                foreach ($form->kra_kpi_data as $kraData) {
                    if (isset($kraData['kra_title'])) {
                        $kraTitle = trim($kraData['kra_title']);
                        if (!empty($kraTitle) && !in_array($kraTitle, $parsedKras)) {
                            $parsedKras[] = $kraTitle;
                        }
                    }
                }
            }
            
            // If no KRAs found in structured data, parse from kra_title field (old format: "KRA1; KRA2")
            if (empty($parsedKras) && $form->kra_title) {
                $kraParts = explode('; ', $form->kra_title);
                foreach ($kraParts as $kraPart) {
                    $kraPart = trim($kraPart);
                    if (!empty($kraPart) && !in_array($kraPart, $parsedKras)) {
                        $parsedKras[] = $kraPart;
                    }
                }
            }
            
            // Parse KPI titles from form (format: "1 - Title 1; 2 - Title 2")
            if ($form->kpi_title) {
                $kpiParts = explode('; ', $form->kpi_title);
                foreach ($kpiParts as $kpiPart) {
                    $kpiPart = trim($kpiPart);
                    if (!empty($kpiPart)) {
                        // Parse "NUMBER - TITLE" format
                        if (preg_match('/^(.+?)\s*-\s*(.+)$/', $kpiPart, $matches)) {
                            $parsedKpis[] = [
                                'number' => trim($matches[1]),
                                'title' => trim($matches[2]),
                                'full' => $kpiPart
                            ];
                        } else {
                            // If no " - " separator, treat entire string as title
                            $parsedKpis[] = [
                                'number' => '',
                                'title' => $kpiPart,
                                'full' => $kpiPart
                            ];
                        }
                    }
                }
            }
            
            // Sequential T1, T2, … (extends past T5 when more templates exist on this form)
            $usedCodes = Template::where('form_id', $form->id)
                ->pluck('template_code')
                ->map(fn ($c) => trim((string) $c))
                ->filter()
                ->values()
                ->all();
            [$availableTemplateCodes, $defaultTemplateCode] = $this->availableSequentialTemplateCodesFromUsed($usedCodes);
        } else {
            // No form context yet: offer the standard T1–T5 pool (same as legacy create flow).
            [$availableTemplateCodes, $defaultTemplateCode] = $this->availableSequentialTemplateCodesFromUsed([]);
        }

        // Pass full nested KRA-KPI structure for direct access
        $kraKpiData = null;
        if ($form && $form->kra_kpi_data) {
            // Handle both JSON string and array formats
            if (is_string($form->kra_kpi_data)) {
                $decoded = json_decode($form->kra_kpi_data, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $kraKpiData = $decoded;
                }
            } elseif (is_array($form->kra_kpi_data) && !empty($form->kra_kpi_data)) {
                $kraKpiData = $form->kra_kpi_data;
            }
        }
        
        // Planning coordinators with campus for JS (id, name, campus_code, campus_name)
        $planningCoordinatorsWithCampus = $planningCoordinators->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'campus_code' => $u->campus_code ?? '',
            'campus_name' => optional($u->campusInfo)->name ?? $u->campus ?? $u->campus_code ?? '—',
        ]);
        $overallTargets = ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0];

        // Templates available for "Imitate/Copy" (prefiltered by current form when provided)
        $sourceTemplatesQuery = Template::query();
        if ($form && $form->id) {
            $sourceTemplatesQuery->where('form_id', $form->id);
        }
        $sourceTemplates = $sourceTemplatesQuery
            ->orderBy('created_at', 'desc')
            ->with('assignedUsers:id,name,campus_code')
            ->get(['id', 'template_code', 'sg_code', 'kra_title', 'kpi_title', 'status', 'fields_json', 'created_at']);
        
        // When arriving via "Copy" button, pre-select source template in the UI
        $copyFrom = (int) $request->input('copy_from', 0);

        return view('super-admin.templates.create', compact(
            'campuses',
            'strategicGoals',
            'kraTitles',
            'kpiTitles',
            'templateCodes',
            'form',
            'parsedKpis',
            'parsedKras',
            'kraKpiData',
            'availableTemplateCodes',
            'defaultTemplateCode',
            'planningCoordinators',
            'planningCoordinatorsWithCampus',
            'overallTargets',
            'sourceTemplates',
            'copyFrom'
        ));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $resolved = $this->resolveStoreFormIdAndTemplateCode($request);
        if ($resolved['redirect'] !== null) {
            return $resolved['redirect'];
        }
        $formId = $resolved['form_id'];
        $finalTemplateCode = $resolved['final_template_code'];

        $fieldsData = json_decode($request->fields_json, true);
        if (!is_array($fieldsData) || !isset($fieldsData['fields']) || empty($fieldsData['fields'])) {
            return redirect()->back()
                ->with('error', 'Invalid field structure. Please add at least one field to the template.')
                ->withInput();
        }
        $fieldsRedirect = $this->validateStoreFields($request, $fieldsData);
        if ($fieldsRedirect !== null) {
            return $fieldsRedirect;
        }

        // Accept both multi-assign and legacy single input names (before duplicate check — campus scope)
        $assignedUserIds = $request->input('assigned_user_ids', []);
        if (!is_array($assignedUserIds)) {
            $assignedUserIds = $assignedUserIds ? [$assignedUserIds] : [];
        }
        if (empty($assignedUserIds) && $request->filled('assigned_user_id')) {
            $assignedUserIds = [$request->input('assigned_user_id')];
        }
        $assignedUserIds = array_values(array_filter(array_unique(array_map('intval', $assignedUserIds))));
        foreach ($assignedUserIds as $uid) {
            $u = \App\Models\User::find($uid);
            if (!$u || !$u->isPlanningCoordinator()) {
                return redirect()->back()
                    ->withErrors(['assigned_user_ids' => 'All selected users must be Planning Coordinators.'])
                    ->withInput();
            }
        }
        $assignedUserId = $assignedUserIds[0] ?? null;

        $campusCodesNormalized = $this->normalizeTemplateCampusCodesFromRequest($request);
        if ($campusCodesNormalized === null && !empty($assignedUserIds)) {
            $campusCodesNormalized = \App\Models\User::query()
                ->whereIn('id', $assignedUserIds)
                ->whereNotNull('campus_code')
                ->pluck('campus_code')
                ->map(fn ($c) => strtoupper(trim((string) $c)))
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all();
            if (empty($campusCodesNormalized)) {
                $campusCodesNormalized = null;
            }
        }
        $dupRedirect = $this->checkStoreDuplicateAndForm($formId, $finalTemplateCode, $campusCodesNormalized);
        if ($dupRedirect !== null) {
            return $dupRedirect;
        }

        // Campus targets: if the input is present, treat it as source of truth (allows clearing)
        $hasCampusTargets = $request->has('campus_targets');
        $campusTargetsInput = $request->input('campus_targets', []);
        $campusTargets = $this->normalizeCampusTargetsInput(is_array($campusTargetsInput) ? $campusTargetsInput : []);
        if ($hasCampusTargets) {
            if (!empty($campusTargets)) {
                $fieldsData['campus_targets'] = $campusTargets;
            } else {
                unset($fieldsData['campus_targets']);
            }
        } elseif (!empty($campusTargets)) {
            // Backward compatibility: keep behavior when no campus_targets payload exists
            $fieldsData['campus_targets'] = $campusTargets;
        }
        $accomplishmentMode = $fieldsData['accomplishment_mode'] ?? 'overall';
        $fieldsData['accomplishment_mode'] = in_array($accomplishmentMode, ['overall', 'per_campus'], true)
            ? $accomplishmentMode
            : 'overall';
        $fieldsData['campus_targets_model'] = $this->buildCampusTargetsModel($campusTargets, $fieldsData['fields'] ?? []);

        try {
            DB::beginTransaction();
            $template = $this->createTemplateFromStoreRequest($request, $formId, $finalTemplateCode, $campusCodesNormalized, $fieldsData, $assignedUserId);
            $template->assignedUsers()->sync($assignedUserIds);
            DB::commit();

            if ($formId !== null) {
                return redirect()->route('forms.show', $formId)
                    ->with('success', "Template '{$template->template_code}' created successfully! It is now in {$template->status} status.");
            }
            return redirect()->route('super-admin.templates.index', ['tab' => 'templates', 'highlight' => $template->id])
                ->with('success', "Template '{$template->template_code}' created successfully! It is now in {$template->status} status.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create template: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified template
     */
    public function show(Template $template)
    {
        return $this->renderShowTemplate($template);
    }

    /**
     * Render show template view (extracted from show() to avoid editor type-inference limits).
     * Extra view data (e.g. readOnly) can be merged via $mergeData for view-only reuse.
     */
    public function renderShowTemplate(Template $template, array $mergeData = [])
    {
        $template->refresh();
        $template->load(['form', 'creator', 'assignedUser', 'assignedUsers.campusInfo']);
        $submissionsCount = Submission::where('template_code', $template->template_code)->count();

        // One block per assigned coordinator; resolve submission so super admin data is not lost.
        $coordinatorSubmissions = [];
        $assignedUsers = $template->assignedUsers->sortBy('name')->values();
        if ($assignedUsers->isNotEmpty()) {
            $allSubmissions = Submission::where('template_id', $template->id)
                ->with('submitter')
                ->orderBy('updated_at', 'desc')
                ->get();
            $usedSubmissionIds = [];

            foreach ($assignedUsers as $coordinator) {
                $coordinatorCampusCode = strtoupper(trim((string) ($coordinator->campus_code ?? '')));
                $coordinatorCampusName = optional($coordinator->campusInfo)->name ?? $coordinator->campus ?? $coordinatorCampusCode;

                // Use same submission selection as Planning Coordinator: prefer coordinator's submission with table_data
                $coordinatorSubs = $allSubmissions->filter(function ($s) use ($coordinator, $usedSubmissionIds) {
                    return (int) $s->submitted_by === (int) $coordinator->id && !in_array($s->id, $usedSubmissionIds, true);
                });
                $sub = $coordinatorSubs->first(function (Submission $s) {
                    return $this->submissionHasTableData($s);
                }) ?? $coordinatorSubs->first();
                // Only when coordinator has NO submission, use campus-matched submission (e.g. orphan data)
                if (!$sub) {
                    $subByCampus = $allSubmissions->first(function ($s) use ($coordinatorCampusCode, $coordinatorCampusName, $usedSubmissionIds) {
                        if (in_array($s->id, $usedSubmissionIds, true)) {
                            return false;
                        }
                        if (!$coordinatorCampusCode && !$coordinatorCampusName) {
                            return false;
                        }
                        $subCampus = trim((string) ($s->campus ?? $s->submitter->campus_code ?? ''));
                        if ($subCampus === '') {
                            return false;
                        }
                        $subUpper = strtoupper($subCampus);
                        $nameUpper = strtoupper($coordinatorCampusName);
                        return $subUpper === $coordinatorCampusCode
                            || str_contains($subUpper, $coordinatorCampusCode)
                            || str_contains($coordinatorCampusCode, $subUpper)
                            || str_contains($nameUpper, $subUpper)
                            || str_contains($subUpper, $nameUpper);
                    });
                    if ($subByCampus && $this->submissionHasTableData($subByCampus)) {
                        $sub = $subByCampus;
                    }
                }
                if ($sub) {
                    $usedSubmissionIds[] = $sub->id;
                }

                $tableData = [];
                $submissionId = null;
                $submitterName = $coordinator->name;
                $submitterEmail = $coordinator->email ?? '';
                $campus = $coordinatorCampusCode ?: $coordinatorCampusName;
                $updatedAt = $coordinator->updated_at ?? now();

                if ($sub) {
                    $raw = $sub->table_data;
                    if (is_string($raw)) {
                        $raw = json_decode($raw, true);
                    }
                    $tableData = is_array($raw) ? $raw : [];
                    $submissionId = $sub->id;
                    $submitterName = $sub->submitter->name ?? $coordinator->name;
                    $submitterEmail = $sub->submitter->email ?? $coordinator->email ?? '';
                    $campus = $sub->submitter->campus_code ?? $sub->campus ?? $campus;
                    $updatedAt = $sub->updated_at;
                }

                $coordinatorSubmissions[] = [
                    'submission_id' => $submissionId,
                    'user_id' => $coordinator->id,
                    'submitter_name' => $submitterName,
                    'submitter_email' => $submitterEmail,
                    'campus' => $campus,
                    'display_label' => $this->displayLabelForCampus(
                        $coordinatorCampusCode ?: null,
                        $coordinatorCampusName ?: null,
                        $coordinator->name
                    ),
                    'updated_at' => $updatedAt,
                    'table_data' => $tableData,
                ];
            }

            // Include any submission for this template not already shown (e.g. Alaminos if not in assigned users)
            foreach ($allSubmissions as $sub) {
                if (in_array($sub->id, $usedSubmissionIds, true)) {
                    continue;
                }
                $raw = $sub->table_data;
                if (is_string($raw)) {
                    $raw = json_decode($raw, true);
                }
                $tableData = is_array($raw) ? $raw : [];
                $campusLabel = trim((string) ($sub->campus ?? $sub->submitter->campus_code ?? ''));
                $campusNameForLabel = $campusLabel !== '' ? null : (optional($sub->submitter->campusInfo)->name ?? null);
                $displayLabel = $this->displayLabelForCampus(
                    $campusLabel !== '' ? $campusLabel : null,
                    $campusNameForLabel,
                    $sub->submitter->name ?? null
                );
                $orphanBlock = [
                    'submission_id' => $sub->id,
                    'user_id' => $sub->submitted_by,
                    'submitter_name' => $sub->submitter->name ?? 'Planning Coordinator',
                    'submitter_email' => $sub->submitter->email ?? '',
                    'campus' => $campusLabel,
                    'display_label' => $displayLabel,
                    'updated_at' => $sub->updated_at,
                    'table_data' => $tableData,
                ];
                $orphanKey = $this->canonicalCampusKey($orphanBlock);
                $alreadyHasCampus = false;
                foreach ($coordinatorSubmissions as $existing) {
                    if ($this->canonicalCampusKey($existing) === $orphanKey) {
                        $alreadyHasCampus = true;
                        break;
                    }
                }
                if (!$alreadyHasCampus) {
                    $coordinatorSubmissions[] = $orphanBlock;
                }
            }
        }

        // Latest submission (for backward compatibility / single-coordinator edit)
        $latestSubmission = Submission::where('template_id', $template->id)
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($latestSubmission) {
            $tableData = $latestSubmission->table_data;
            if (is_string($tableData)) {
                $tableData = json_decode($tableData, true);
            }
            if (is_array($tableData) && !empty($tableData)) {
                $latestSubmission->table_data = $tableData;
            }
        }

        // If no coordinator blocks but we have latest submission data, show it as one block
        if (empty($coordinatorSubmissions) && $latestSubmission && is_array($latestSubmission->table_data) && count($latestSubmission->table_data) > 0) {
            $latestSubmission->load('submitter');
            $campusCode = $latestSubmission->submitter->campus_code ?? null;
            $campusName = trim((string) ($latestSubmission->campus ?? ''));
            $coordinatorSubmissions[] = [
                'submission_id' => $latestSubmission->id,
                'user_id' => $latestSubmission->submitted_by,
                'submitter_name' => $latestSubmission->submitter->name ?? 'Planning Coordinator',
                'submitter_email' => $latestSubmission->submitter->email ?? '',
                'campus' => $latestSubmission->campus ?? '',
                'display_label' => $this->displayLabelForCampus(
                    $campusCode,
                    $campusName !== '' ? $campusName : null,
                    $latestSubmission->submitter->name ?? null
                ),
                'updated_at' => $latestSubmission->updated_at,
                'table_data' => $latestSubmission->table_data,
            ];
        }

        // Deduplicate: one block per campus (canonical key so "PSU BINMALEY CAMPUS" and "BINMALEY" merge).
        // Prefer blocks with real data rows over empty/new placeholders, even when placeholder is newer.
        $blockHasRealData = function (array $block): bool {
            $rows = $block['table_data'] ?? [];
            if (!is_array($rows) || empty($rows)) {
                return false;
            }
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $meta = $row['_meta'] ?? null;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $meta = is_array($decoded) ? $decoded : [];
                }
                $isSummary = is_array($meta) && (($meta['row_type'] ?? '') === 'summary');
                if ($isSummary) {
                    continue;
                }
                foreach ($row as $k => $v) {
                    if ($k === '_meta' || $k === '_after_separator') {
                        continue;
                    }
                    $vv = trim((string) $v);
                    if ($vv !== '' && $vv !== '—' && stripos($vv, 'select') !== 0 && stripos($vv, 'no data yet') !== 0) {
                        return true;
                    }
                }
            }
            return false;
        };

        $byCampus = [];
        $campusLabelReadabilityScore = function (array $block): int {
            $label = trim((string) ($block['display_label'] ?? ''));
            $campusPart = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $label);
            $campusPart = trim((string) $campusPart);
            if ($campusPart === '') {
                return 0;
            }
            // Prefer labels with word boundaries (e.g. "San Carlos") over compressed forms (e.g. "SANCARLOS").
            return preg_match('/\s+/', $campusPart) ? 2 : 1;
        };
        foreach ($coordinatorSubmissions as $block) {
            $key = $this->canonicalCampusKey($block);
            $existing = $byCampus[$key] ?? null;
            $blockTime = $block['updated_at'] ?? now();
            $existingTime = $existing['updated_at'] ?? null;
            $blockReal = $blockHasRealData($block);
            $existingReal = $existing ? $blockHasRealData($existing) : false;
            $blockLabelScore = $campusLabelReadabilityScore($block);
            $existingLabelScore = $existing ? $campusLabelReadabilityScore($existing) : 0;
            $replace = !$existing
                || ($blockReal && !$existingReal)
                || ($blockReal === $existingReal && (
                    ($blockTime > $existingTime)
                    || ($blockTime == $existingTime && $blockLabelScore > $existingLabelScore)
                    || ($blockTime == $existingTime && $blockLabelScore === $existingLabelScore
                        && strlen(trim((string) ($block['display_label'] ?? ''))) < strlen(trim((string) ($existing['display_label'] ?? ''))))
                ));
            if ($replace) {
                $byCampus[$key] = $block;
            }
        }
        $coordinatorSubmissions = array_values($byCampus);

        // Sort blocks by display label (e.g. "ALAMINOS PLANNING COORDINATOR") so order is predictable (alphabetical by campus)
        usort($coordinatorSubmissions, function ($a, $b) {
            $labelA = (string) ($a['display_label'] ?? '');
            $labelB = (string) ($b['display_label'] ?? '');
            return strcasecmp($labelA, $labelB);
        });

        // Use the SAME compute pipeline as Planning Coordinator so both views show identical
        // results: computeCalculatedFields + mergeStoredSummaryClearedCells + mergeStoredExtraSummaryRows.
        // This ensures formulas (Sum, Average, Count, Compare to Campus Target, etc.) align everywhere.
        $schemaFields = $template->getSchemaFields();
        $summaryRules = $template->getSummaryRules();
        $summaryCellMappings = $template->getSummaryCellMappings();
        if (!empty($schemaFields)) {
            foreach ($coordinatorSubmissions as &$block) {
                $tableData = $block['table_data'] ?? [];
                if (!is_array($tableData) || count($tableData) === 0) {
                    continue;
                }
                $block['table_data'] = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                $block['table_data'] = $this->computeService->mergeStoredSummaryClearedCells($block['table_data'], $tableData);
                $block['table_data'] = $this->computeService->mergeStoredExtraSummaryRows($block['table_data'], $tableData);
                $block['table_data'] = $this->computeService->applyPersistedSummaryRowsFromSource($block['table_data'], $tableData);
                $block['table_data'] = $this->computeService->finalizeTableDataAfterComputeMerges($block['table_data'], $tableData);
            }
            unset($block);
        }

        // Normalize each block: _meta as array, data rows first, then all summary rows (preserves Add Blue Row results)
        foreach ($coordinatorSubmissions as &$block) {
            $tableData = $block['table_data'] ?? [];
            if (!is_array($tableData) || count($tableData) === 0) {
                continue;
            }
            $dataRows = [];
            $summaryRows = [];
            foreach ($tableData as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $meta = $row['_meta'] ?? null;
                if (is_string($meta)) {
                    $decoded = json_decode($meta, true);
                    $row['_meta'] = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($meta)) {
                    $row['_meta'] = [];
                }
                $isSummary = is_array($row['_meta']) && (($row['_meta']['row_type'] ?? 'data') === 'summary');
                if ($isSummary) {
                    $summaryRows[] = $row;
                } else {
                    $dataRows[] = $row;
                }
            }
            if (count($dataRows) === 0 && count($summaryRows) === 0) {
                $block['table_data'] = [];
            } else {
                $block['table_data'] = array_merge($dataRows, $summaryRows);
            }
        }
        unset($block);

        // Summary target fields are passed to the view for optional UI logic.
        $summaryTargetFields = $this->summaryTargetFieldsFromRules($summaryRules);

        $quickAccessTemplates = collect([]);
        if ($template->form_id) {
            $quickAccessTemplates = Template::where('form_id', $template->form_id)
                ->with('campus')
                ->orderBy('template_code', 'asc')
                ->get();
        }

        $planningCoordinators = \App\Models\User::where(function ($query) {
            $query->where('position', 'Planning Coordinator')
                ->orWhere('position', 'planning_coordinator')
                ->orWhere('position', 'planning-coordinator')
                ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                ->orWhere('role', 'planning_coordinator')
                ->orWhere('role', 'planning-coordinator')
                ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
        })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $campusTargetsModel = is_array($template->fields_json ?? null)
            ? ($template->fields_json['campus_targets_model'] ?? [])
            : [];

        $overallTargetsArr = app(\App\Services\FormTargetsService::class)->getForTemplate($template);
        $targetIsPercentage = $overallTargetsArr && !empty($overallTargetsArr['is_percentage'] ?? false);

        $this->migrateManualTotalRowToTemplateFieldsJson($template, $coordinatorSubmissions);

        return view('super-admin.templates.show', array_merge(compact(
            'template',
            'submissionsCount',
            'planningCoordinators',
            'quickAccessTemplates',
            'latestSubmission',
            'coordinatorSubmissions',
            'summaryTargetFields',
            'campusTargetsModel',
            'targetIsPercentage'
        ), $mergeData));
    }

    /**
     * Green manual total row belongs on the template (like grand_total_rows), not inside a campus submission.
     * Migrate legacy rows saved inside submission table_data and strip them from blocks shown in the view.
     *
     * @param  array<int, array<string, mixed>>  $coordinatorSubmissions
     */
    private function migrateManualTotalRowToTemplateFieldsJson(Template $template, array &$coordinatorSubmissions): void
    {
        $fieldsJson = $template->fields_json ?? [];
        if (! is_array($fieldsJson)) {
            $fieldsJson = [];
        }
        if (! empty($fieldsJson['manual_total_row']) && is_array($fieldsJson['manual_total_row'])) {
            foreach ($coordinatorSubmissions as &$block) {
                $block['table_data'] = $this->stripManualTotalRowsFromTableData($block['table_data'] ?? []);
            }
            unset($block);

            return;
        }

        $migrated = null;
        foreach ($coordinatorSubmissions as &$block) {
            $tableData = $block['table_data'] ?? [];
            if (! is_array($tableData)) {
                continue;
            }
            foreach ($tableData as $idx => $row) {
                if (! is_array($row)) {
                    continue;
                }
                $meta = $row['_meta'] ?? [];
                if (is_string($meta)) {
                    $meta = json_decode($meta, true) ?? [];
                }
                if (! is_array($meta) || empty($meta['manual_total_row'])) {
                    continue;
                }
                $rowPayload = $row;
                unset($rowPayload['_meta']);
                $cellMappings = is_array($meta['summary_cell_mappings'] ?? null) ? $meta['summary_cell_mappings'] : [];
                $migrated = [
                    'row' => $rowPayload,
                    'cell_mappings' => $cellMappings,
                ];
                unset($tableData[$idx]);
                $block['table_data'] = array_values($tableData);
                break 2;
            }
        }
        unset($block);

        if ($migrated !== null) {
            $fieldsJson['manual_total_row'] = $migrated;
            $template->fields_json = $fieldsJson;
            $template->save();
        }

        foreach ($coordinatorSubmissions as &$block) {
            $block['table_data'] = $this->stripManualTotalRowsFromTableData($block['table_data'] ?? []);
        }
        unset($block);
    }

    /**
     * @param  array<int, mixed>  $tableData
     * @return array<int, array<string, mixed>>
     */
    private function stripManualTotalRowsFromTableData(array $tableData): array
    {
        $out = [];
        foreach ($tableData as $row) {
            if (! is_array($row)) {
                $out[] = $row;
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (is_array($meta) && ! empty($meta['manual_total_row'])) {
                continue;
            }
            $out[] = $row;
        }

        return array_values($out);
    }

    /**
     * Return a canonical key for deduplicating campus blocks (same campus = same key).
     */
    private function canonicalCampusKey(array $block): string
    {
        $campus = trim((string) ($block['campus'] ?? ''));
        $label = trim((string) ($block['display_label'] ?? ''));
        $raw = $campus !== '' ? $campus : $label;
        if ($raw === '') {
            return 'sub_' . ($block['submission_id'] ?? 'u' . ($block['user_id'] ?? ''));
        }
        $key = $raw;
        $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
        $key = preg_replace('/\s*campus\s*$/i', '', $key);
        $key = preg_replace('/^psu\s+/i', '', $key);   // PSU LINGAYEN -> LINGAYEN (so PSU X and X merge)
        $key = preg_replace('/^pel\.?\s*/i', '', $key);
        $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
        $key = strtoupper(trim(preg_replace('/\s+/', ' ', $key)));
        $key = preg_replace('/\./', '', $key); // STA. MARIA -> STA MARIA
        // Merge variants like "SANCARLOS" and "SAN CARLOS" into one canonical campus key.
        // Keep only alphanumeric chars so spacing/punctuation differences don't split the same campus.
        $key = preg_replace('/[^A-Z0-9]/', '', $key);
        $key = trim($key);
        return $key !== '' ? $key : ('sub_' . ($block['submission_id'] ?? 'u' . ($block['user_id'] ?? '')));
    }

    /**
     * Build a clean display label for a campus (no "PSU" prefix, no trailing "CAMPUS") for Super Admin template show.
     * e.g. "PSU San Carlos Campus" -> "San Carlos Planning Coordinator", "STA. MARIA" -> "Sta. Maria Planning Coordinator".
     */
    private function displayLabelForCampus(?string $campusCode, ?string $campusName, ?string $fallbackName = null): string
    {
        $raw = trim((string) ($campusCode ?: $campusName));
        if ($raw === '') {
            return trim($fallbackName ?? '') !== '' ? trim($fallbackName) . ' Planning Coordinator' : 'Planning Coordinator';
        }
        $key = $raw;
        $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
        $key = preg_replace('/\s*campus\s*$/i', '', $key);
        $key = preg_replace('/^psu\s+/i', '', $key);
        $key = preg_replace('/^pel\.?\s*/i', '', $key);
        $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
        $key = trim(preg_replace('/\s+/', ' ', $key));
        if ($key === '') {
            return trim($fallbackName ?? '') !== '' ? trim($fallbackName) . ' Planning Coordinator' : 'Planning Coordinator';
        }
        // Title-case but preserve "Sta." (e.g. Sta. Maria)
        if (preg_match('/^STA\.?\s+/i', $key)) {
            $rest = preg_replace('/^STA\.?\s+/i', '', $key);
            $key = 'Sta. ' . ucwords(strtolower(trim($rest)));
        } else {
            $key = ucwords(strtolower($key));
        }
        return $key . ' Planning Coordinator';
    }

    /**
     * Return list of field keys that are targets of summary rule outputs (for showing values in blue row; others show "—").
     */
    private function summaryTargetFieldsFromRules(array $summaryRules): array
    {
        $targets = [];
        foreach ($summaryRules as $rule) {
            if (($rule['enabled'] ?? false) !== true) {
                continue;
            }
            foreach ($rule['outputs'] ?? [] as $output) {
                $tf = $output['target_field'] ?? '';
                if ($tf !== '') {
                    $targets[$tf] = true;
                }
            }
        }
        return array_keys($targets);
    }

    /**
     * Return true if submission has at least one non-summary, non-empty row in table_data.
     */
    private function submissionHasTableData(Submission $sub): bool
    {
        $data = $sub->table_data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data) || empty($data)) {
            return false;
        }
        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? null;
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = is_array($decoded) ? $decoded : [];
            }
            if (is_array($meta) && (($meta['row_type'] ?? '') === 'summary')) {
                continue;
            }
            foreach ($row as $k => $v) {
                if ($k === '_meta') {
                    continue;
                }
                if (trim((string) $v) !== '' && trim((string) $v) !== '—') {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Save table data from the template show page (Super Admin). Creates or updates
     * submission(s) and logs to Audit Trailing.
     * Supports: (1) single table_data → update latest submission or create one;
     * (2) by_submission → array of { submission_id, table_data } to update multiple submissions.
     */
    public function saveTableData(Request $request, Template $template)
    {
        $bySubmission = $request->input('by_submission');
        if (is_string($bySubmission)) {
            $decoded = json_decode($bySubmission, true);
            $bySubmission = is_array($decoded) ? $decoded : null;
        }
        if (is_array($bySubmission) && count($bySubmission) > 0) {
            $grandTotals = $request->input('grand_totals');
            if (is_string($grandTotals)) {
                $grandTotals = json_decode($grandTotals, true);
            }
            $kpiFinalizeParam = false;
            $allIn = $request->all();
            if (array_key_exists('kpi_finalize_total_row', $allIn)) {
                $kpiFinalizeRaw = $allIn['kpi_finalize_total_row'];
                if (is_string($kpiFinalizeRaw)) {
                    $kpiFinalizeRaw = json_decode($kpiFinalizeRaw, true);
                }
                $kpiFinalizeParam = is_array($kpiFinalizeRaw) ? $kpiFinalizeRaw : null;
            }

            $manualTotalParam = false;
            if (array_key_exists('manual_total_row', $request->all())) {
                $manualTotalRaw = $request->input('manual_total_row');
                if (is_string($manualTotalRaw)) {
                    $manualTotalRaw = json_decode($manualTotalRaw, true);
                }
                $manualTotalParam = is_array($manualTotalRaw) ? $manualTotalRaw : null;
            }

            return $this->saveTableDataBySubmission(
                $request,
                $template,
                $bySubmission,
                is_array($grandTotals) ? $grandTotals : [],
                $kpiFinalizeParam,
                $manualTotalParam
            );
        }

        // When template has multiple assigned coordinators, require by_submission format
        // to avoid overwriting the wrong coordinator's data with single table_data
        try {
            $assignedCount = $template->assignedUsers()->count();
            if ($assignedCount > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'This template has multiple coordinators. Please save each block separately using the multi-block save format.',
                ], 422);
            }
        } catch (\Throwable $e) {
            // If pivot table or relation fails, allow single mode (legacy templates)
        }

        $request->validate([
            'table_data' => 'required|array|min:1',
            'table_data.*' => 'array',
        ]);

        $raw = $request->table_data;
        $tableData = [];
        foreach ($raw as $row) {
            if (is_object($row)) {
                $row = (array) $row;
            }
            if (!is_array($row)) {
                continue;
            }
            $normalized = [];
            foreach ($row as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $normalized[$k] = is_array($v) ? json_encode($v) : (string) $v;
                } else {
                    $normalized[$k] = $v;
                }
            }
            $tableData[] = $normalized;
        }

        // Keep full client payload (including summary row with "—" cleared cells) for merge BEFORE stripping.
        // mergeStoredSummaryClearedCells needs the client's summary row to persist Super Admin's "Remove formula" / cleared cells.
        $clientPayloadWithSummary = $tableData;

        // Strip summary rows for compute input (summary is regenerated by buildSummaryRows).
        $tableData = array_values(array_filter($tableData, function ($row) {
            if (!is_array($row)) {
                return false;
            }
            $meta = $row['_meta'] ?? null;
            if (is_array($meta) && (($meta['row_type'] ?? '') === 'summary')) {
                return false;
            }
            $values = array_values($row);
            if (empty($values)) {
                return false;
            }
            $first = trim((string) $values[0]);
            return strcasecmp($first, 'Summary') !== 0;
        }));

        if (empty($tableData)) {
            return response()->json(['success' => false, 'message' => 'At least one row of data is required.'], 422);
        }

        try {
            DB::beginTransaction();
            $template->refresh(); // Ensure latest summary_rules (e.g. after Remove formula) before compute

            $latestSubmission = Submission::where('template_id', $template->id)
                ->orderBy('updated_at', 'desc')
                ->first();

        if ($latestSubmission) {
            $oldTableData = is_array($latestSubmission->table_data) ? $latestSubmission->table_data : [];
            // Run formulas so the saved table_data includes the blue summary row (reflects on Planning Coordinator load)
            $schemaFields = $template->getSchemaFields();
            $summaryRules = $template->getSummaryRules();
            $summaryCellMappings = $template->getSummaryCellMappings();
            if (!empty($schemaFields)) {
                $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                // Pass full client payload (with summary row) so cleared "—" cells persist for Planning Coordinator
                $tableData = $this->computeService->mergeStoredSummaryClearedCells($tableData, $clientPayloadWithSummary);
                $tableData = $this->computeService->mergeStoredExtraSummaryRows($tableData, $clientPayloadWithSummary);
                $tableData = $this->computeService->applyPersistedSummaryRowsFromSource($tableData, $clientPayloadWithSummary);
                $tableData = $this->computeService->finalizeTableDataAfterComputeMerges($tableData, $clientPayloadWithSummary);
            }
            $latestSubmission->update(['table_data' => $tableData]);
                $this->syncTemplateFinalizedAccompFromTableData($template, $tableData);
                if ($this->isDraftAutosaveRequest($request)) {
                    $this->logTemplateEdit($template, 'Super Admin: autosaved table data.');
                } else {
                    $columnLabels = TableDataAuditHelper::getColumnLabelsFromTemplate($template);
                    $changes = TableDataAuditHelper::describeTableDataChanges($oldTableData, $tableData, $columnLabels);
                    $message = TableDataAuditHelper::buildAuditMessage('Super Admin: ', $changes);
                    $this->logTemplateEdit($template, $message);
                }
            } else {
                $campusName = $template->campus_code
                    ? (optional($template->campus)->name ?? $template->campus_code)
                    : 'All Campuses';
                $campusName = is_array($campusName) ? json_encode($campusName) : (string) $campusName;
                $quarter = $tableData[0]['quarter'] ?? $tableData[0]['Quarter'] ?? '1';
                $quarter = is_array($quarter) ? (string) reset($quarter) : (string) $quarter;
                $formTitle = ($template->sg_code ?? '') . ' - ' . $template->template_code;
                // Run formulas so the saved table_data includes the blue summary row
                $schemaFields = $template->getSchemaFields();
                $summaryRules = $template->getSummaryRules();
                $summaryCellMappings = $template->getSummaryCellMappings();
                if (!empty($schemaFields)) {
                    $tableData = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                    $tableData = $this->computeService->mergeStoredSummaryClearedCells($tableData, $clientPayloadWithSummary);
                    $tableData = $this->computeService->mergeStoredExtraSummaryRows($tableData, $clientPayloadWithSummary);
                    $tableData = $this->computeService->applyPersistedSummaryRowsFromSource($tableData, $clientPayloadWithSummary);
                    $tableData = $this->computeService->finalizeTableDataAfterComputeMerges($tableData, $clientPayloadWithSummary);
                }
                Submission::create([
                    'template_id' => $template->id,
                    'form_id' => $template->form_id,
                    'template_code' => $template->template_code,
                    'form_title' => $formTitle,
                    'sg_code' => $template->sg_code,
                    'kra_title' => $template->kra_title,
                    'kpi_title' => $template->kpi_title,
                    'campus' => $campusName,
                    'quarter' => $quarter,
                    'table_data' => $tableData,
                    'status' => 'Unpublished',
                    'submitted_by' => Auth::id(),
                    'is_draft' => true,
                ]);
                $this->syncTemplateFinalizedAccompFromTableData($template, $tableData);
                if ($this->isDraftAutosaveRequest($request)) {
                    $this->logTemplateEdit($template, 'Super Admin: autosaved table data.');
                } else {
                    $columnLabels = TableDataAuditHelper::getColumnLabelsFromTemplate($template);
                    $changes = TableDataAuditHelper::describeTableDataChanges([], $tableData, $columnLabels);
                    $message = TableDataAuditHelper::buildAuditMessage('Super Admin: ', $changes);
                    $this->logTemplateEdit($template, $message);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Table data saved. It will appear in Audit Trailing.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update template summary_rules (e.g. when Super Admin applies formula from view template selection).
     * Accepts { output: { target_field, operation, sourceA [, sourceB] [, suffix] }, group_by?: [string] }.
     */
    public function updateSummaryRules(Request $request, Template $template)
    {
        // Mode-only update: configure how accomplishment is presented/aggregated.
        if ($request->has('accomplishment_mode') && !$request->has('output')) {
            $request->validate([
                'accomplishment_mode' => 'required|in:overall,per_campus',
            ]);
            $fieldsJson = $template->fields_json ?? [];
            if (!is_array($fieldsJson)) {
                $fieldsJson = [];
            }
            $mode = $request->input('accomplishment_mode', 'overall');
            $fieldsJson['accomplishment_mode'] = $mode;
            // Keep per-campus option available for future scorecard drilldown.
            if (!isset($fieldsJson['campus_breakdown_enabled'])) {
                $fieldsJson['campus_breakdown_enabled'] = true;
            }
            $template->update(['fields_json' => $fieldsJson]);
            return response()->json([
                'success' => true,
                'message' => 'Accomplishment mode updated.',
                'accomplishment_mode' => $mode,
            ]);
        }

        $request->validate([
            'output' => 'required|array',
            'output.target_field' => 'required|string',
            'output.operation' => 'nullable|string',
            'output.ui_calc_type' => 'nullable|string',
            'output.ui_formula_operation' => 'nullable|string',
            'output.sourceA' => 'nullable|string',
            'output.sourceB' => 'nullable|string',
            'output.source_columns' => 'nullable|array',
            'output.source_columns.*' => 'string',
            'output.section_ref' => 'nullable|string',
            'output.suffix' => 'nullable|string',
            'output.remove_only' => 'nullable|boolean',
            'output.row_uids' => 'nullable|array',
            'output.row_uids.*' => 'string',
            'output.count_adjust' => 'nullable|integer',
            'output.custom_expr' => 'nullable|string',
            'output.source_keys' => 'nullable|array',
            'output.source_keys.*' => 'string',
            'output.base_source' => 'nullable|string',
            'output.base_aggregate' => 'nullable|string',
            'output.base_row_uids' => 'nullable|array',
            'output.base_row_uids.*' => 'string',
            'output.base_row_indices' => 'nullable|array',
            'output.base_row_indices.*' => 'integer|min:0',
            'output.chain' => 'nullable|array',
            'output.chain.*.op' => 'nullable|string',
            'output.chain.*.row_uid' => 'nullable|string',
            'output.chain.*.source' => 'nullable|string',
            'output.chain.*.row_index' => 'nullable|integer|min:0',
            'group_by' => 'nullable|array',
            'group_by.*' => 'string',
        ]);
        $output = $request->input('output');
        $targetField = $output['target_field'] ?? '';
        if ($targetField === '') {
            return response()->json(['success' => false, 'message' => 'target_field is required.'], 422);
        }
        $removeOnly = !empty($output['remove_only']);

        if ($removeOnly) {
            $fieldsJson = $template->fields_json ?? [];
            $summaryRules = $fieldsJson['summary_rules'] ?? [];
            $summaryCellMappings = $fieldsJson['summary_cell_mappings'] ?? [];
            $summaryRules = array_values($summaryRules);
            foreach ($summaryRules as $i => $rule) {
                $outputs = $rule['outputs'] ?? [];
                $outputs = array_values(array_filter($outputs, fn ($o) => ($o['target_field'] ?? '') !== $targetField));
                $summaryRules[$i]['outputs'] = $outputs;
            }
            $fieldsJson['summary_rules'] = $summaryRules;
            if (is_array($summaryCellMappings)) {
                $sectionRef = trim((string) ($output['section_ref'] ?? ''));
                $summaryCellMappings = array_values(array_filter($summaryCellMappings, function ($mapping) use ($targetField, $sectionRef) {
                    if (($mapping['target_field'] ?? '') !== $targetField) {
                        return true;
                    }
                    if ($sectionRef === '') {
                        return false;
                    }
                    return trim((string) ($mapping['section_ref'] ?? '')) !== $sectionRef;
                }));
                $fieldsJson['summary_cell_mappings'] = $summaryCellMappings;
            }
            $template->update(['fields_json' => $fieldsJson]);
            return response()->json([
                'success' => true,
                'message' => 'Formula removed from summary rules. Save table data to persist cleared cells.',
                'summary_rules' => $summaryRules,
                'summary_cell_mappings' => $fieldsJson['summary_cell_mappings'] ?? [],
            ]);
        }

        $operation = $output['operation'] ?? 'sum';
        $sourceA = $output['sourceA'] ?? $targetField;
        // For NO. column with count_total, use count_rows so we show total data rows (e.g. 5) not non-empty cells (e.g. 2)
        $noFieldNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($targetField)));
        if (($noFieldNorm === 'no' || $noFieldNorm === 'no_') && $operation === 'count_total') {
            $operation = 'count_rows';
        }
        $out = [
            'target_field' => $targetField,
            'operation' => $operation,
            'sourceA' => $sourceA,
        ];
        if (!empty($output['ui_calc_type'])) {
            $out['ui_calc_type'] = trim((string) $output['ui_calc_type']);
        }
        if (!empty($output['ui_formula_operation'])) {
            $out['ui_formula_operation'] = trim((string) $output['ui_formula_operation']);
        }
        if (!empty($output['sourceB'])) {
            $out['sourceB'] = $output['sourceB'];
        }
        if (!empty($output['source_columns']) && is_array($output['source_columns'])) {
            $sourceColumns = array_values(array_filter(array_map(static function ($key) {
                return trim((string) $key);
            }, $output['source_columns']), static function ($key) {
                return $key !== '';
            }));
            if (!empty($sourceColumns)) {
                $out['source_columns'] = $sourceColumns;
            }
        }
        if (!empty($output['section_ref'])) {
            $out['section_ref'] = trim((string) $output['section_ref']);
        }
        if (isset($output['suffix'])) {
            $out['suffix'] = $output['suffix'];
        }
        if (!empty($output['row_indices']) && is_array($output['row_indices'])) {
            $out['row_indices'] = array_values(array_map('intval', $output['row_indices']));
        }
        if (!empty($output['row_uids']) && is_array($output['row_uids'])) {
            $uids = array_values(array_filter(array_map(static function ($uid) {
                return trim((string)$uid);
            }, $output['row_uids']), static function ($uid) {
                return $uid !== '';
            }));
            if (!empty($uids)) {
                $out['row_uids'] = $uids;
            }
        }
        if (array_key_exists('count_adjust', $output)) {
            $out['count_adjust'] = (int) $output['count_adjust'];
        }
        if (! empty($output['custom_expr'])) {
            $out['custom_expr'] = trim((string) $output['custom_expr']);
        }
        if (! empty($output['source_keys']) && is_array($output['source_keys'])) {
            $sk = array_values(array_filter(array_map(static function ($key) {
                return trim((string) $key);
            }, $output['source_keys']), static function ($key) {
                return $key !== '';
            }));
            if (! empty($sk)) {
                $out['source_keys'] = $sk;
            }
        }
        if (! empty($output['base_source'])) {
            $out['base_source'] = trim((string) $output['base_source']);
        }
        if (! empty($output['base_aggregate'])) {
            $out['base_aggregate'] = trim((string) $output['base_aggregate']);
        }
        if (! empty($output['base_row_uids']) && is_array($output['base_row_uids'])) {
            $bru = array_values(array_filter(array_map(static function ($uid) {
                return trim((string) $uid);
            }, $output['base_row_uids']), static function ($uid) {
                return $uid !== '';
            }));
            if ($bru !== []) {
                $out['base_row_uids'] = $bru;
            }
        }
        if (! empty($output['base_row_indices']) && is_array($output['base_row_indices'])) {
            $bri = array_values(array_filter(array_map(static fn ($x) => (int) $x, $output['base_row_indices']), static fn ($n) => $n >= 0));
            if ($bri !== []) {
                $out['base_row_indices'] = $bri;
            }
        }
        if (array_key_exists('chain', $output) && is_array($output['chain'])) {
            $chainOut = [];
            foreach ($output['chain'] as $step) {
                if (! is_array($step)) {
                    continue;
                }
                $op = trim((string) ($step['op'] ?? ''));
                if ($op === '÷') {
                    $op = '/';
                }
                if ($op === '×') {
                    $op = '*';
                }
                if (! in_array($op, ['+', '-', '*', '/'], true)) {
                    continue;
                }
                $ru = trim((string) ($step['row_uid'] ?? ''));
                if ($ru === '') {
                    continue;
                }
                $src = trim((string) ($step['source'] ?? ''));
                $one = ['op' => $op, 'row_uid' => $ru, 'source' => $src];
                if (isset($step['row_index'])) {
                    $rix = (int) $step['row_index'];
                    if ($rix >= 0) {
                        $one['row_index'] = $rix;
                    }
                }
                $chainOut[] = $one;
            }
            $out['chain'] = $chainOut;
        }
        $schemaFields = $template->getSchemaFields();
        $groupBy = $request->input('group_by');
        if (empty($groupBy) && !empty($schemaFields)) {
            $keys = array_filter(array_map(fn ($f) => $f['key'] ?? $f['name'] ?? null, $schemaFields));
            $candidates = ['responsible_work_units', 'responsible_work_unit', 'campus', 'campus_code'];
            foreach ($candidates as $c) {
                if (in_array($c, $keys, true)) {
                    $groupBy = [$c];
                    break;
                }
            }
        }
        if (empty($groupBy)) {
            $groupBy = ['campus'];
        }
        $fieldsJson = $template->fields_json ?? [];
        $summaryRules = $fieldsJson['summary_rules'] ?? [];
        $summaryCellMappings = $fieldsJson['summary_cell_mappings'] ?? [];
        $summaryRules = array_values($summaryRules);
        $primaryRule = null;
        $primaryIndex = -1;
        foreach ($summaryRules as $i => $rule) {
            if (($rule['enabled'] ?? true) !== true || ($rule['placement'] ?? '') !== 'after_group') {
                continue;
            }
            $gb = $rule['group_by'] ?? [];
            if ($gb === $groupBy || (is_array($gb) && !empty($gb) && ($gb[0] ?? '') === ($groupBy[0] ?? ''))) {
                $primaryRule = $rule;
                $primaryIndex = $i;
                break;
            }
        }
        if ($primaryRule === null) {
            $primaryRule = [
                'id' => 'sr_' . uniqid(),
                'enabled' => true,
                'label' => 'Summary',
                'placement' => 'after_group',
                'group_by' => $groupBy,
                'outputs' => [],
            ];
            $summaryRules[] = $primaryRule;
            $primaryIndex = count($summaryRules) - 1;
        }
        $outputs = $primaryRule['outputs'] ?? [];
        $outputs = array_values(array_filter($outputs, fn ($o) => ($o['target_field'] ?? '') !== $targetField));
        $outputs[] = $out;
        $summaryRules[$primaryIndex]['outputs'] = $outputs;
        if (!isset($summaryRules[$primaryIndex]['id'])) {
            $summaryRules[$primaryIndex]['id'] = $primaryRule['id'] ?? 'sr_default';
        }
        $fieldsJson['summary_rules'] = $summaryRules;
        if (!is_array($summaryCellMappings)) {
            $summaryCellMappings = [];
        }
        $sectionRef = trim((string) ($out['section_ref'] ?? ''));
        $mappingKey = $targetField . '|' . $sectionRef;
        $summaryCellMappings = array_values(array_filter($summaryCellMappings, function ($mapping) use ($mappingKey) {
            $existingKey = trim((string) ($mapping['target_field'] ?? '')) . '|' . trim((string) ($mapping['section_ref'] ?? ''));
            return $existingKey !== $mappingKey;
        }));
        $summaryCellMappings[] = $out;
        $fieldsJson['summary_cell_mappings'] = $summaryCellMappings;
        $fieldsJson = $this->syncRollupRulesForAccomplishment($fieldsJson, $schemaFields, $out);
        $template->update(['fields_json' => $fieldsJson]);
        return response()->json([
            'success' => true,
            'message' => 'Summary rule updated.',
            'summary_rules' => $summaryRules,
            'summary_cell_mappings' => $summaryCellMappings,
        ]);
    }

    /**
     * Keep template rollup_rules aligned when formula output targets accomplishment columns.
     * This allows approval flows to automatically pick up accomplishment totals from blue-row results.
     */
    private function syncRollupRulesForAccomplishment(array $fieldsJson, array $schemaFields, array $output): array
    {
        $targetField = trim((string)($output['target_field'] ?? ''));
        if ($targetField === '') {
            return $fieldsJson;
        }
        if (!$this->looksLikeAccomplishmentField($targetField, $schemaFields)) {
            return $fieldsJson;
        }

        $operation = strtolower(trim((string)($output['operation'] ?? 'sum')));
        $aggregation = ($operation === 'avg') ? 'avg' : 'sum';
        $targetSourceField = $this->findBestTargetRollupField($schemaFields);

        $rollupRules = $fieldsJson['rollup_rules'] ?? [];
        if (!is_array($rollupRules)) {
            $rollupRules = [];
        }
        $rollupRules['accomp'] = [
            'source_field' => $targetField,
            'aggregation' => $aggregation,
        ];
        if ($targetSourceField !== '') {
            $existingTarget = $rollupRules['target'] ?? [];
            if (!is_array($existingTarget)) {
                $existingTarget = [];
            }
            if (empty($existingTarget['source_field'])) {
                $existingTarget['source_field'] = $targetSourceField;
            }
            if (empty($existingTarget['aggregation'])) {
                $existingTarget['aggregation'] = 'sum';
            }
            $rollupRules['target'] = $existingTarget;
        }
        $fieldsJson['rollup_rules'] = $rollupRules;
        return $fieldsJson;
    }

    private function looksLikeAccomplishmentField(string $fieldKey, array $schemaFields): bool
    {
        $normalized = $this->normalizeMetricKey($fieldKey);
        if (str_contains($normalized, 'accomp') || str_contains($normalized, 'accomplishment') || str_contains($normalized, 'actual')) {
            return true;
        }
        foreach ($schemaFields as $field) {
            $key = (string)($field['key'] ?? $field['name'] ?? '');
            if ($key !== $fieldKey) {
                continue;
            }
            $label = (string)($field['label'] ?? '');
            $labelNorm = $this->normalizeMetricKey($label);
            if (str_contains($labelNorm, 'accomp') || str_contains($labelNorm, 'accomplishment') || str_contains($labelNorm, 'actual')) {
                return true;
            }
        }
        return false;
    }

    private function findBestTargetRollupField(array $schemaFields): string
    {
        $best = '';
        foreach ($schemaFields as $field) {
            $key = (string)($field['key'] ?? $field['name'] ?? '');
            if ($key === '') {
                continue;
            }
            $label = (string)($field['label'] ?? '');
            $norm = $this->normalizeMetricKey($key . '_' . $label);
            if (str_contains($norm, 'target') && str_contains($norm, 'total')) {
                return $key;
            }
            if ($best === '' && str_contains($norm, 'target')) {
                $best = $key;
            }
        }
        return $best;
    }

    private function normalizeMetricKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
        return trim((string)$value, '_');
    }

    /**
     * Infer Q1–Q4 from saved grand_total_rows when Finalize ran but finalized_accomp was not POSTed.
     *
     * @param  array<int, array<string, mixed>>  $grandTotals
     */
    private function deriveFinalizedAccompFromGrandTotals(array $grandTotals): ?array
    {
        if ($grandTotals === []) {
            return null;
        }
        $values = [1 => null, 2 => null, 3 => null, 4 => null];
        $rowOrdinal = 0;
        foreach ($grandTotals as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $row = $item['row'] ?? null;
            if (! is_array($row)) {
                continue;
            }
            $q = null;
            if ($label !== '' && preg_match('/\(Q\s*([1-4])\s*\)/i', $label, $m)) {
                $q = (int) $m[1];
            }
            if ($q === null) {
                $rowOrdinal++;
                if ($rowOrdinal >= 1 && $rowOrdinal <= 4) {
                    $q = $rowOrdinal;
                }
            }
            if ($q === null || $q < 1 || $q > 4) {
                continue;
            }
            $bestNum = null;
            foreach ($row as $key => $raw) {
                if ($key === '_meta' || is_array($raw) || is_object($raw)) {
                    continue;
                }
                $s = trim((string) $raw);
                if ($s === '' || $s === '—' || $s === '-' || strcasecmp($s, 'select...') === 0) {
                    continue;
                }
                if (preg_match('/^\d{1,2}(st|nd|rd|th)\s+Q/i', $s)) {
                    continue;
                }
                $clean = preg_replace('/[^0-9.\-]/', '', $s);
                if ($clean === '' || ! is_numeric($clean)) {
                    continue;
                }
                $n = (float) $clean;
                if ($bestNum === null || $n > $bestNum) {
                    $bestNum = $n;
                }
            }
            if ($bestNum !== null) {
                $values[$q] = $bestNum;
            }
        }
        if ($values[1] === null && $values[2] === null && $values[3] === null && $values[4] === null) {
            return null;
        }

        return [
            'q1' => (string) ($values[1] ?? '0'),
            'q2' => (string) ($values[2] ?? '0'),
            'q3' => (string) ($values[3] ?? '0'),
            'q4' => (string) ($values[4] ?? '0'),
        ];
    }

    /**
     * Copy finalized Q1–Q4 from a saved submission summary row into template.fields_json
     * so Form Details VPASS can use finalized_accomp on the legacy single-block save path.
     */
    private function syncTemplateFinalizedAccompFromTableData(Template $template, array $tableData): void
    {
        foreach ($tableData as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($meta) || ($meta['row_type'] ?? '') !== 'summary') {
                continue;
            }
            $fa = $meta['finalized_accomp'] ?? null;
            if (! is_array($fa)) {
                continue;
            }
            if (! isset($fa['q1']) && ! isset($fa['q2']) && ! isset($fa['q3']) && ! isset($fa['q4'])) {
                continue;
            }
            $fieldsJson = $template->fields_json ?? [];
            if (! is_array($fieldsJson)) {
                $fieldsJson = [];
            }
            $fieldsJson['finalized_accomp'] = [
                'q1' => (string) ($fa['q1'] ?? '0'),
                'q2' => (string) ($fa['q2'] ?? '0'),
                'q3' => (string) ($fa['q3'] ?? '0'),
                'q4' => (string) ($fa['q4'] ?? '0'),
            ];
            $template->update(['fields_json' => $fieldsJson]);
            return;
        }
    }

    /**
     * Update multiple submissions' table_data (Super Admin editing all coordinators' data).
     * When submission_id is null/0 and user_id is set, creates a new submission for that coordinator.
     */
    private function saveTableDataBySubmission(Request $request, Template $template, array $bySubmission, array $grandTotals = [], mixed $kpiFinalizeTotalRow = false, mixed $manualTotalRow = false): \Illuminate\Http\JsonResponse
    {
        $templateId = $template->id;
        $validIds = Submission::where('template_id', $templateId)->pluck('id')->toArray();
        $template->refresh();

        try {
            DB::beginTransaction();
            foreach ($bySubmission as $item) {
                $subId = isset($item['submission_id']) ? (int) $item['submission_id'] : 0;
                $userId = isset($item['user_id']) ? (int) $item['user_id'] : null;
                $rows = $item['table_data'] ?? [];
                // Each item is one campus block (one submission). Data + blue row are saved per campus so formula results reflect on Planning Coordinator for that campus.
                if (!is_array($rows)) {
                    $rows = [];
                }
                $numberFieldKeys = [];
                foreach ($template->getSchemaFields() as $f) {
                    $key = $f['key'] ?? $f['name'] ?? null;
                    if ($key && ($f['type'] ?? '') === 'number') {
                        $numberFieldKeys[$key] = true;
                    }
                }
                $orderedRows = [];
                foreach ($rows as $row) {
                    if (is_object($row)) {
                        $row = (array) $row;
                    }
                    if (!is_array($row)) {
                        continue;
                    }
                    $meta = $row['_meta'] ?? null;
                    if (is_object($meta)) {
                        $meta = (array) $meta;
                    }
                    $isSummary = is_array($meta) && (($meta['row_type'] ?? '') === 'summary');
                    // Fallback: treat as summary row if first non-_meta column is "Summary" or "—" (client may send without _meta)
                    if (!$isSummary && !empty($row)) {
                        $firstVal = '';
                        foreach ($row as $k => $v) {
                            if ($k === '_meta' || is_array($v) || is_object($v)) {
                                continue;
                            }
                            $firstVal = trim((string) $v);
                            break;
                        }
                        $dash = "\u{2014}";
                        if (strcasecmp($firstVal, 'Summary') === 0 || $firstVal === '—' || $firstVal === $dash) {
                            $isSummary = true;
                        }
                    }
                    if ($isSummary) {
                        $normalizedSummary = [];
                        foreach ($row as $k => $v) {
                            if ($k === '_meta') {
                                $normalizedSummary['_meta'] = is_array($v) ? $v : (is_object($v) ? (array) $v : ['row_type' => 'summary']);
                            } elseif (is_array($v) || is_object($v)) {
                                $normalizedSummary[$k] = is_array($v) ? json_encode($v) : (string) $v;
                            } else {
                                $normalizedSummary[$k] = $v;
                            }
                        }
                        if (!isset($normalizedSummary['_meta']) || !is_array($normalizedSummary['_meta'])) {
                            $normalizedSummary['_meta'] = ['row_type' => 'summary'];
                        }
                        // Green "Add Total Row" is template-level (fields_json.manual_total_row), not per-submission.
                        if (!empty($normalizedSummary['_meta']['manual_total_row'])) {
                            continue;
                        }
                        $orderedRows[] = $normalizedSummary;
                        continue;
                    }
                    // Ensure no array values are passed to DB string columns; normalize for table_data
                    $normalizedRow = [];
                    foreach ($row as $k => $v) {
                        if (is_array($v) || is_object($v)) {
                            $normalizedRow[$k] = is_array($v) ? json_encode($v) : (string) $v;
                        } else {
                            $vTrim = trim((string) $v);
                            // Store empty string instead of "0" for number fields so placeholder zeros don't persist
                            if (isset($numberFieldKeys[$k]) && $vTrim === '0') {
                                $normalizedRow[$k] = '';
                            } else {
                                $normalizedRow[$k] = $v;
                            }
                        }
                    }
                    $first = trim((string) (array_values($normalizedRow)[0] ?? ''));
                    if (strcasecmp($first, 'Summary') === 0) {
                        continue;
                    }
                    $orderedRows[] = $normalizedRow;
                }

                $tableData = array_values(array_filter($orderedRows, function ($r) {
                    $m = $r['_meta'] ?? null;
                    if (is_array($m) && (($m['row_type'] ?? '') === 'summary')) {
                        return false;
                    }
                    return true;
                }));
                $clientSummaryRows = array_values(array_filter($orderedRows, function ($r) {
                    $m = $r['_meta'] ?? null;
                    return is_array($m) && (($m['row_type'] ?? '') === 'summary');
                }));

                if (empty($orderedRows)) {
                    continue;
                }

                if ($subId > 0 && in_array($subId, $validIds, true)) {
                    $sub = Submission::where('id', $subId)->where('template_id', $templateId)->first();
                        if ($sub) {
                        $schemaFields = $template->getSchemaFields();
                        $summaryRules = $template->getSummaryRules();
                        $existingRows = $sub->table_data;
                        if (is_string($existingRows)) {
                            $existingRows = json_decode($existingRows, true);
                        }
                        $existingRows = is_array($existingRows) ? $existingRows : [];
                        $existingDataRows = [];
                        foreach ($existingRows as $existingRow) {
                            if (!is_array($existingRow)) {
                                continue;
                            }
                            $existingMeta = $existingRow['_meta'] ?? null;
                            if (is_string($existingMeta)) {
                                $decodedExistingMeta = json_decode($existingMeta, true);
                                $existingMeta = is_array($decodedExistingMeta) ? $decodedExistingMeta : [];
                            }
                            $isExistingSummary = is_array($existingMeta) && (($existingMeta['row_type'] ?? '') === 'summary');
                            if ($isExistingSummary) {
                                continue;
                            }
                            $existingDataRows[] = $existingRow;
                        }
                        if (!empty($schemaFields)) {
                            $summaryCellMappings = $template->getSummaryCellMappings();
                            $computed = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                            $finalTableData = $this->computeService->mergeStoredSummaryClearedCells($computed, $orderedRows);
                            $finalTableData = $this->computeService->mergeStoredExtraSummaryRows($finalTableData, $orderedRows);
                            $finalTableData = $this->computeService->applyPersistedSummaryRowsFromSource($finalTableData, $orderedRows);
                            $finalTableData = $this->computeService->finalizeTableDataAfterComputeMerges($finalTableData, $orderedRows);
                        } else {
                            $finalTableData = $tableData;
                        }
                        $sub->update(['table_data' => $finalTableData]);
                    }
                    continue;
                }

                if (($subId === 0 || $subId === null) && $userId > 0 && !empty($orderedRows)) {
                    $user = \App\Models\User::find($userId);
                    if (!$user) {
                        continue;
                    }
                    $schemaFields = $template->getSchemaFields();
                    $summaryRules = $template->getSummaryRules();
                    $summaryCellMappings = $template->getSummaryCellMappings();
                    if (!empty($schemaFields)) {
                        $computed = $this->computeService->computeCalculatedFields($tableData, $schemaFields, $summaryRules, $summaryCellMappings);
                        $finalTableData = $this->computeService->mergeStoredSummaryClearedCells($computed, $orderedRows);
                        $finalTableData = $this->computeService->mergeStoredExtraSummaryRows($finalTableData, $orderedRows);
                        $finalTableData = $this->computeService->applyPersistedSummaryRowsFromSource($finalTableData, $orderedRows);
                        $finalTableData = $this->computeService->finalizeTableDataAfterComputeMerges($finalTableData, $orderedRows);
                    } else {
                        $finalTableData = $tableData;
                    }
                    $campusName = optional($user->campusInfo)->name ?? $user->campus ?? $user->campus_code ?? 'All Campuses';
                    $campusName = is_array($campusName) ? json_encode($campusName) : (string) $campusName;
                    // Field Structure autosave can POST without submission_id — update existing draft instead of creating duplicates.
                    $existingDraft = Submission::query()
                        ->where('template_id', $templateId)
                        ->where('submitted_by', $userId)
                        ->where('is_draft', true)
                        ->orderByDesc('id')
                        ->first();
                    if ($existingDraft) {
                        $existingDraft->update(['table_data' => $finalTableData]);
                    } else {
                        $formTitle = ($template->sg_code ?? '') . ' - ' . $template->template_code;
                        $firstRowForQuarter = $orderedRows[0] ?? [];
                        $quarter = $firstRowForQuarter['quarter'] ?? $firstRowForQuarter['Quarter'] ?? '1';
                        $quarter = is_array($quarter) ? (string) reset($quarter) : (string) $quarter;
                        Submission::create([
                            'template_id' => $templateId,
                            'form_id' => $template->form_id,
                            'template_code' => $template->template_code,
                            'form_title' => $formTitle,
                            'sg_code' => $template->sg_code,
                            'kra_title' => $template->kra_title,
                            'kpi_title' => $template->kpi_title,
                            'campus' => $campusName,
                            'quarter' => $quarter,
                            'table_data' => $finalTableData,
                            'status' => 'Unpublished',
                            'submitted_by' => $userId,
                            'is_draft' => true,
                        ]);
                    }
                }
            }
            // Save grand total rows to template fields_json (always update when key present, including empty to clear)
            $fieldsJson = $template->fields_json ?? [];
            if (! is_array($fieldsJson)) {
                $fieldsJson = [];
            }
            $fieldsJson['grand_total_rows'] = $grandTotals;
            if ($kpiFinalizeTotalRow !== false) {
                $fieldsJson['kpi_finalize_total_row'] = is_array($kpiFinalizeTotalRow) ? $kpiFinalizeTotalRow : null;
            }
            if ($manualTotalRow !== false) {
                $fieldsJson['manual_total_row'] = is_array($manualTotalRow) ? $manualTotalRow : null;
            }
            $faIn = $request->input('finalized_accomp');
            if (is_string($faIn)) {
                $faDecoded = json_decode($faIn, true);
                $faIn = is_array($faDecoded) ? $faDecoded : null;
            }
            $gtForDerive = is_array($grandTotals) ? $grandTotals : [];
            if (! is_array($faIn) || ! (isset($faIn['q1']) || isset($faIn['q2']) || isset($faIn['q3']) || isset($faIn['q4']))) {
                $derivedFa = $this->deriveFinalizedAccompFromGrandTotals($gtForDerive);
                if (is_array($derivedFa)) {
                    $faIn = $derivedFa;
                }
            }
            if (is_array($faIn) && (isset($faIn['q1']) || isset($faIn['q2']) || isset($faIn['q3']) || isset($faIn['q4']))) {
                $fieldsJson['finalized_accomp'] = [
                    'q1' => (string) ($faIn['q1'] ?? '0'),
                    'q2' => (string) ($faIn['q2'] ?? '0'),
                    'q3' => (string) ($faIn['q3'] ?? '0'),
                    'q4' => (string) ($faIn['q4'] ?? '0'),
                ];
            }
            $template->update(['fields_json' => $fieldsJson]);
            if ($this->isDraftAutosaveRequest($request)) {
                $this->logTemplateEdit($template, 'Super Admin: autosaved table data.');
            } else {
                $this->logTemplateEdit($template, 'Updated submission table data for multiple Planning Coordinators.');
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Table data saved. It will appear in Audit Trailing.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Return edit history for the template (JSON, for Audit Trailing modal).
     */
    public function editHistory(Template $template)
    {
        $history = TemplateEditHistory::where('template_id', $template->id)
            ->with(['user:id,name,role,campus_code', 'user.campusInfo:id,code,name'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $roleLabels = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'creator_editor' => 'Planning Coordinator',
            'view_only' => 'View Only',
            'planning_coordinator' => 'Planning Coordinator',
            'campus_admin' => 'Campus Admin',
        ];

        return response()->json([
            'template_code' => $template->template_code ?? '',
            'count' => $history->count(),
            'history' => $history->map(function ($h) use ($roleLabels) {
                $whoEdited = $h->user ? $h->user->name : 'Unknown';
                if ($h->user && isset($h->user->role, $roleLabels[$h->user->role])) {
                    $roleLabel = $roleLabels[$h->user->role];
                    if (in_array($h->user->role, ['planning_coordinator', 'creator_editor'], true)) {
                        $campusName = optional($h->user->campusInfo)->name ?? $h->user->campus ?? $h->user->campus_code ?? null;
                        $roleLabel = $campusName ? $campusName . ' Planning Coordinator' : $roleLabel;
                    }
                    $whoEdited .= ' (' . $roleLabel . ')';
                }
                return [
                    'who_edited' => $whoEdited,
                    'role' => $h->user && isset($roleLabels[$h->user->role]) ? $roleLabels[$h->user->role] : null,
                    'what_edited' => str_replace('Campus User / Planning Coordinator: ', 'Planning Coordinator: ', $h->what_edited),
                    'created_at' => $h->created_at->format('M j, Y g:i A'),
                    'created_at_relative' => $h->created_at->diffForHumans(),
                ];
            }),
        ]);
    }

    /**
     * Clear all edit history for the template (Super Admin only).
     */
    public function clearEditHistory(Template $template)
    {
        $deleted = TemplateEditHistory::where('template_id', $template->id)->delete();
        return response()->json([
            'success' => true,
            'message' => $deleted > 0
                ? 'Editing history cleared successfully.'
                : 'No history to clear.',
            'deleted' => $deleted,
        ]);
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(Template $template)
    {
        // Load the form relationship if it exists
        $template->load('form');
        
        $campuses = Campus::where('is_active', true)->get();
        
        $strategicGoals = [
            'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
            'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
            'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
            'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
            'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
        ];

        // Get unique KRA titles from existing templates
        $kraTitles = Template::distinct()->pluck('kra_title')->filter()->values()->toArray();
        $kpiTitles = Template::distinct()->pluck('kpi_title')->filter()->values()->toArray();
        $templateCodes = Template::distinct()->pluck('template_code')->filter()->values()->toArray();
        
        // Parse KRAs from associated form if it exists (similar to create)
        $parsedKras = [];
        if ($template->form) {
            $form = $template->form;
            
            // Parse KRA titles from form
            // First, try to get from structured kra_kpi_data (new format)
            if ($form->kra_kpi_data && is_array($form->kra_kpi_data) && !empty($form->kra_kpi_data)) {
                foreach ($form->kra_kpi_data as $kraData) {
                    if (isset($kraData['kra_title'])) {
                        $kraTitle = trim($kraData['kra_title']);
                        if (!empty($kraTitle) && !in_array($kraTitle, $parsedKras)) {
                            $parsedKras[] = $kraTitle;
                        }
                    }
                }
            }
            
            // If no KRAs found in structured data, parse from kra_title field (old format: "KRA1; KRA2")
            if (empty($parsedKras) && $form->kra_title) {
                $kraParts = explode('; ', $form->kra_title);
                foreach ($kraParts as $kraPart) {
                    $kraPart = trim($kraPart);
                    if (!empty($kraPart) && !in_array($kraPart, $parsedKras)) {
                        $parsedKras[] = $kraPart;
                    }
                }
            }
        }
        
        // Get all planning coordinators for assignment dropdown
        // Include: Planning Coordinator position/role OR Creator/Editor (they act as Planning Coordinators)
        $planningCoordinators = \App\Models\User::where(function($query) {
                $query->where('position', 'Planning Coordinator')
                      ->orWhere('position', 'planning_coordinator')
                      ->orWhere('position', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                      ->orWhere('role', 'planning_coordinator')
                      ->orWhere('role', 'planning-coordinator')
                      ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Pass full nested KRA-KPI structure for direct access
        $kraKpiData = null;
        if ($template->form && $template->form->kra_kpi_data) {
            if (is_string($template->form->kra_kpi_data)) {
                $decoded = json_decode($template->form->kra_kpi_data, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $kraKpiData = $decoded;
                }
            } elseif (is_array($template->form->kra_kpi_data) && !empty($template->form->kra_kpi_data)) {
                $kraKpiData = $template->form->kra_kpi_data;
            }
        }

        // Use FormTargetsService to resolve per-KPI targets for this template (Q1–Q4 + total)
        $overallTargetsArr = app(\App\Services\FormTargetsService::class)->getForTemplate($template);
        $overallTargets = $overallTargetsArr ? [
            'q1' => $overallTargetsArr['target_q1'] ?? 0,
            'q2' => $overallTargetsArr['target_q2'] ?? 0,
            'q3' => $overallTargetsArr['target_q3'] ?? 0,
            'q4' => $overallTargetsArr['target_q4'] ?? 0,
            'total' => $overallTargetsArr['target_total'] ?? 0,
            'is_percentage' => !empty($overallTargetsArr['is_percentage'] ?? false),
        ] : ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'is_percentage' => false];

        // Campuses for Campus Targets: only campuses of assigned Planning Coordinators. No row appears unless that coordinator is selected.
        $template->load(['assignedUsers.campusInfo']);
        $campusesForTargets = $template->assignedUsers
            ->filter(fn ($u) => $u->campusInfo && $u->campus_code)
            ->map(fn ($u) => $u->campusInfo)
            ->unique('code')
            ->values();

        // Planning coordinators with campus for JS (id, name, campus_code, campus_name)
        $planningCoordinatorsWithCampus = \App\Models\User::where(function ($query) {
            $query->where('position', 'Planning Coordinator')
                ->orWhere('position', 'planning_coordinator')
                ->orWhere('position', 'planning-coordinator')
                ->orWhere('role', \App\Models\User::ROLE_PLANNING_COORDINATOR)
                ->orWhere('role', 'planning_coordinator')
                ->orWhere('role', 'planning-coordinator')
                ->orWhere('role', \App\Models\User::ROLE_CREATOR_EDITOR);
        })
            ->where('is_active', true)
            ->with('campusInfo')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'campus_code' => $u->campus_code ?? '',
                'campus_name' => optional($u->campusInfo)->name ?? $u->campus ?? $u->campus_code ?? '—',
            ]);

        $template->load('assignedUsers');
        return view('super-admin.templates.edit', compact('template', 'campuses', 'strategicGoals', 'kraTitles', 'kpiTitles', 'templateCodes', 'planningCoordinators', 'parsedKras', 'kraKpiData', 'overallTargets', 'campusesForTargets', 'planningCoordinatorsWithCampus'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, Template $template)
    {
        $request->validate([
            'sg_code' => 'required|string|in:SG1,SG2,SG3,SG4,SG5',
            'template_code' => 'required|string|max:255',
            'kra_title' => 'required|string|max:255',
            'kpi_title' => 'required|string|max:20000',
            'fields_json' => 'required|string',
            'campus_code' => 'nullable|string',
            'campus_codes' => 'nullable|array',
            'campus_codes.*' => 'string|max:50',
            'status' => 'required|in:Unpublished,Published',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            // Decode the JSON string to array
            $fieldsData = json_decode($request->fields_json, true);
            
            if (!$fieldsData || !isset($fieldsData['fields'])) {
                return redirect()->back()
                    ->with('error', 'Invalid field structure.')
                    ->withInput();
            }

            foreach (($fieldsData['fields'] ?? []) as $field) {
                $calcType = $field['meta']['calc'] ?? null;
                if (in_array($calcType, ['unique', 'countif', 'sum', 'avg_percentage'], true) && empty($field['meta']['sourceA'])) {
                    return redirect()->back()
                        ->with('error', "Please set Source A for calculated column \"{$field['label']}\".")
                        ->withInput();
                }
            }
            $summaryRuleError = $this->validateSummaryRules($fieldsData);
            if ($summaryRuleError !== null) {
                return redirect()->back()
                    ->with('error', $summaryRuleError)
                    ->withInput();
            }

            $campusCodesNormalized = $this->normalizeTemplateCampusCodesFromRequest($request);

            // Check for duplicate template_code + same campus_codes (excluding current template).
            // Important: templates are scoped by form_id in the rest of the system (same code can exist for other forms),
            // so we only treat it as a duplicate when it matches the same form_id (or both are standalone).
            $existingTemplatesQuery = Template::where('template_code', $request->template_code)
                ->where('id', '!=', $template->id);
            if ($template->form_id !== null) {
                $existingTemplatesQuery->where('form_id', $template->form_id);
            } else {
                $existingTemplatesQuery->whereNull('form_id');
            }
            $existingTemplates = $existingTemplatesQuery->get();
            foreach ($existingTemplates as $existing) {
                $existingCodes = $existing->campus_codes;
                if (is_array($existingCodes)) {
                    $existingCodes = array_values(array_map('strtoupper', $existingCodes));
                    sort($existingCodes);
                }
                $same = $campusCodesNormalized === null && $existingCodes === null
                    || is_array($campusCodesNormalized) && is_array($existingCodes)
                    && $campusCodesNormalized === $existingCodes;
                if ($same) {
                    $label = $campusCodesNormalized ? implode(', ', $campusCodesNormalized) : 'All Campuses';
                    return redirect()->back()
                        ->with('error', "A template with code '{$request->template_code}' already exists for campus(es) '{$label}'. Please use a different template code or select different campuses.")
                        ->withInput();
                }
            }

            // Accept multi-select from standard array, JS mirror array, JSON mirror, and legacy single input.
            $assignedUserIdsRaw = $request->input('assigned_user_ids', []);
            if (!is_array($assignedUserIdsRaw)) {
                $assignedUserIdsRaw = $assignedUserIdsRaw ? [$assignedUserIdsRaw] : [];
            }

            $assignedUserIdsMirror = $request->input('assigned_user_ids_mirror', []);
            if (!is_array($assignedUserIdsMirror)) {
                $assignedUserIdsMirror = $assignedUserIdsMirror ? [$assignedUserIdsMirror] : [];
            }

            $assignedUserIdsFromJson = [];
            $assignedUserIdsJson = $request->input('assigned_user_ids_json');
            if (is_string($assignedUserIdsJson) && trim($assignedUserIdsJson) !== '') {
                $decodedAssignedUserIds = json_decode($assignedUserIdsJson, true);
                if (is_array($decodedAssignedUserIds)) {
                    $assignedUserIdsFromJson = $decodedAssignedUserIds;
                }
            }

            $assignedUserIds = array_merge($assignedUserIdsRaw, $assignedUserIdsMirror, $assignedUserIdsFromJson);
            if (empty($assignedUserIds) && $request->filled('assigned_user_id')) {
                $assignedUserIds = [$request->input('assigned_user_id')];
            }
            $assignedUserIds = array_values(array_filter(array_unique(array_map('intval', $assignedUserIds))));
            foreach ($assignedUserIds as $uid) {
                $u = \App\Models\User::find($uid);
                if (!$u || !$u->isPlanningCoordinator()) {
                    return redirect()->back()
                        ->withErrors(['assigned_user_ids' => 'All selected users must be Planning Coordinators.'])
                        ->withInput();
                }
            }
            $assignedUserId = $assignedUserIds[0] ?? null;

            // Campus targets: if the input is present, treat it as source of truth (allows clearing)
            $hasCampusTargets = $request->has('campus_targets');
            $campusTargetsInput = $request->input('campus_targets', []);
            if (!is_array($campusTargetsInput)) {
                $campusTargetsInput = [];
            }
            $campusTargets = $this->normalizeCampusTargetsInput($campusTargetsInput);
            if ($hasCampusTargets) {
                // Explicitly overwrite: set or clear
                $fieldsData['campus_targets'] = $campusTargets;
            } else {
                // If form doesn't send campus_targets (older UI), preserve existing if present
                if (isset($template->fields_json['campus_targets']) && is_array($template->fields_json['campus_targets'])) {
                    $fieldsData['campus_targets'] = $template->fields_json['campus_targets'];
                } elseif (!empty($campusTargets)) {
                    $fieldsData['campus_targets'] = $campusTargets;
                }
            }
            $fieldsData['accomplishment_mode'] = in_array(($fieldsData['accomplishment_mode'] ?? ($template->fields_json['accomplishment_mode'] ?? 'overall')), ['overall', 'per_campus'], true)
                ? ($fieldsData['accomplishment_mode'] ?? ($template->fields_json['accomplishment_mode'] ?? 'overall'))
                : 'overall';
            $fieldsData['campus_targets_model'] = $this->buildCampusTargetsModel(($fieldsData['campus_targets'] ?? []), $fieldsData['fields'] ?? []);

            // Preserve grand total rows and finalize total row — these are added on the Show page,
            // not in the editor form, so they must be carried over to avoid being wiped on every save.
            if (!isset($fieldsData['grand_total_rows']) && isset($template->fields_json['grand_total_rows'])) {
                $fieldsData['grand_total_rows'] = $template->fields_json['grand_total_rows'];
            }
            if (!isset($fieldsData['kpi_finalize_total_row']) && isset($template->fields_json['kpi_finalize_total_row'])) {
                $fieldsData['kpi_finalize_total_row'] = $template->fields_json['kpi_finalize_total_row'];
            }
            if (!isset($fieldsData['manual_total_row']) && isset($template->fields_json['manual_total_row'])) {
                $fieldsData['manual_total_row'] = $template->fields_json['manual_total_row'];
            }

            $firstCode = $campusCodesNormalized && count($campusCodesNormalized) > 0 ? $campusCodesNormalized[0] : null;
            $template->update([
                'sg_code' => $request->sg_code,
                'template_code' => $request->template_code,
                'kra_title' => $request->kra_title,
                'kpi_title' => $request->kpi_title,
                'fields_json' => $fieldsData,
                'status' => $request->status,
                'campus_code' => $firstCode,
                'campus_codes' => $campusCodesNormalized,
                'assigned_user_id' => $assignedUserId,
            ]);
            $template->assignedUsers()->sync($assignedUserIds);

            $this->logTemplateEdit($template, 'Updated template: SG, code, KRA/KPI, fields, status, campus, or assignment.');

            DB::commit();

            return redirect()->route('super-admin.templates.show', $template)
                ->with('success', 'Template updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update template: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified template and all related data (submissions, approvals, edit history).
     */
    public function destroy(Template $template)
    {
        try {
            $templateName = $template->template_code;
            $templateId = $template->id;

            DB::beginTransaction();

            // Submissions linked by template_id or template_code
            $submissionIds = Submission::where('template_id', $templateId)
                ->orWhere('template_code', $template->template_code)
                ->pluck('id');

            $submissionsCount = $submissionIds->count();

            // Delete approvals for those submissions (QA Coordinator approvals)
            \App\Models\Approval::whereIn('submission_id', $submissionIds)->delete();

            // Delete all submissions using this template
            Submission::where('template_id', $templateId)
                ->orWhere('template_code', $template->template_code)
                ->delete();

            // Delete template edit history
            TemplateEditHistory::where('template_id', $templateId)->delete();

            // Delete the template
            $template->delete();

            DB::commit();

            $message = "Template '{$templateName}' deleted successfully.";
            if ($submissionsCount > 0) {
                $message .= " {$submissionsCount} submission(s) and their approval data have been removed.";
            }

            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            // If deleted from settings page, redirect back to settings
            if (request()->headers->get('referer') && str_contains(request()->headers->get('referer'), '/settings')) {
                return redirect()->route('super-admin.settings.index')
                    ->with('success', $message);
            }

            return redirect()->route('super-admin.templates.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete template: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Toggle template publish status
     */
    public function toggleStatus(Template $template)
    {
        try {
            $template->update([
                'status' => $template->status === 'Unpublished' ? 'Published' : 'Unpublished'
            ]);

            $this->logTemplateEdit($template, 'Toggled status to ' . $template->status . '.');

            $message = $template->status === 'Published' 
                ? 'Template published successfully!'
                : 'Template moved to draft.';

            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $template->status
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            // Return JSON for AJAX requests
            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle status: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Clone template to another campus
     */
    public function clone(Request $request, Template $template)
    {
        $request->validate([
            'campus_code' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $newTemplate = $template->replicate();
            $code = $request->campus_code === 'ALL' ? null : strtoupper(trim($request->campus_code));
            $newTemplate->campus_code = $code;
            $newTemplate->campus_codes = $code ? [$code] : null;
            $newTemplate->status = 'Unpublished';
            $newTemplate->created_by = Auth::id();

            // Auto-assign the next available template code for the same form.
            $allowedCodes = ['T1', 'T2', 'T3', 'T4', 'T5'];
            $usedCodes = Template::where('form_id', $template->form_id)
                ->pluck('template_code')
                ->toArray();
            $nextCode = collect($allowedCodes)->first(fn($c) => !in_array($c, $usedCodes));
            if ($nextCode) {
                $newTemplate->template_code = $nextCode;
            } else {
                // All standard codes used — append a numeric suffix (T6, T7, …)
                $newTemplate->template_code = 'T' . (count($usedCodes) + 1);
            }

            $newTemplate->save();

            // Copy assigned Planning Coordinators from the source template.
            $assignedUserIds = $template->assignedUsers()->pluck('users.id')->toArray();
            if (!empty($assignedUserIds)) {
                $newTemplate->assignedUsers()->sync($assignedUserIds);
            }

            DB::commit();

            return redirect()->route('super-admin.templates.index')
                ->with('success', "Template cloned successfully as {$newTemplate->template_code}!");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to clone template: ' . $e->getMessage());
        }
    }

    /**
     * Imitate/copy an existing template into a new Template record.
     * - Copies fields_json (schema + formulas/rules)
     * - Overrides sg_code/kra_title/kpi_title/template_code + form_id
     * - Preserves source `campus_targets` by default
     *   (only overwrites when the request provides non-empty `campus_targets`)
     * - Syncs assigned Planning Coordinators from the current request
     */
    public function imitate(Request $request)
    {
        $request->validate([
            'source_template_id' => 'required|integer|exists:templates,id',
            'status' => 'required|in:Unpublished,Published',
            'sg_code' => 'required|string|in:SG1,SG2,SG3,SG4,SG5',
            'template_code' => 'required|string|max:255',
            'custom_template_code' => 'nullable|string|max:255',
            'kra_title' => 'required|string|max:255',
            'kpi_title' => 'required|string|max:20000|min:1',
            'form_id' => 'nullable|integer|exists:forms,id',
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'nullable|integer|exists:users,id',
        ]);

        $sourceTemplate = Template::findOrFail((int) $request->input('source_template_id'));

        $formId = $request->input('form_id');
        $formId = ($formId === '' || $formId === null) ? null : (int) $formId;

        $finalTemplateCode = $request->template_code;
        if ($request->template_code === 'Custom') {
            $finalTemplateCode = trim((string) ($request->input('custom_template_code') ?? ''));
            if ($finalTemplateCode === '') {
                return redirect()->back()
                    ->withErrors(['custom_template_code' => 'Please enter a custom template code.'])
                    ->withInput();
            }
        }

        // campus_code/campus_codes comes from the Create Template page (usually ALL)
        $campusCodesNormalized = $this->normalizeTemplateCampusCodesFromRequest($request);

        // Duplicate check: same template_code + same form_id + same campus scope
        $dupRedirect = $this->checkStoreDuplicateAndForm($formId, $finalTemplateCode, $campusCodesNormalized);
        if ($dupRedirect !== null) {
            return $dupRedirect;
        }

        // Accept both multi-assign and legacy single input names
        $assignedUserIds = $request->input('assigned_user_ids', []);
        if (!is_array($assignedUserIds)) {
            $assignedUserIds = $assignedUserIds ? [$assignedUserIds] : [];
        }
        if ($request->filled('assigned_user_id') && empty($assignedUserIds)) {
            $assignedUserIds = [$request->input('assigned_user_id')];
        }
        $assignedUserIds = array_values(array_filter(array_unique(array_map('intval', $assignedUserIds))));

        foreach ($assignedUserIds as $uid) {
            $u = \App\Models\User::find($uid);
            if (!$u || !$u->isPlanningCoordinator()) {
                return redirect()->back()
                    ->withErrors(['assigned_user_ids' => 'All selected users must be Planning Coordinators.'])
                    ->withInput();
            }
        }
        $assignedUserId = $assignedUserIds[0] ?? null;

        // Copy schema/rules from the source template.
        //
        // Important: during imitation we must preserve the source template's existing
        // values (e.g., `campus_targets`) unless the request provides a real override.
        $fieldsData = is_array($sourceTemplate->fields_json ?? null) ? $sourceTemplate->fields_json : [];

        // KPI finalize total row should NOT be present in a fresh copy.
        // It is only supposed to appear after the user clicks "Finalize" on the new template.
        unset($fieldsData['kpi_finalize_total_row']);
        unset($fieldsData['finalized_accomp']);

        // Campus targets: normalize request payload; if the request contains only empty
        // values (common when the UI doesn't render them for "imitate"), preserve
        // whatever already exists in the source template.
        $hasCampusTargetsPayload = $request->has('campus_targets');
        $campusTargetsInput = $request->input('campus_targets', []);
        $campusTargets = $this->normalizeCampusTargetsInput(is_array($campusTargetsInput) ? $campusTargetsInput : []);

        if ($hasCampusTargetsPayload && !empty($campusTargets)) {
            // Explicit override with actual data.
            $fieldsData['campus_targets'] = $campusTargets;
            $fieldsData['campus_targets_model'] = $this->buildCampusTargetsModel(
                $campusTargets,
                $fieldsData['fields'] ?? []
            );
        } else {
            // Preserve copied values. Rebuild model only if missing.
            $hasExistingTargets = isset($fieldsData['campus_targets']) && is_array($fieldsData['campus_targets']);
            $needsModelRebuild = empty($fieldsData['campus_targets_model']) && $hasExistingTargets;

            if ($needsModelRebuild) {
                $fieldsData['campus_targets_model'] = $this->buildCampusTargetsModel(
                    $fieldsData['campus_targets'],
                    $fieldsData['fields'] ?? []
                );
            }
        }

        // Normalize accomplishment mode if present
        $accomplishmentMode = $fieldsData['accomplishment_mode'] ?? 'overall';
        $fieldsData['accomplishment_mode'] = in_array($accomplishmentMode, ['overall', 'per_campus'], true)
            ? $accomplishmentMode
            : 'overall';

        try {
            DB::beginTransaction();

            $newTemplate = $this->createTemplateFromStoreRequest(
                $request,
                $formId,
                $finalTemplateCode,
                $campusCodesNormalized,
                $fieldsData,
                $assignedUserId
            );
            $newTemplate->assignedUsers()->sync($assignedUserIds);

            // Copy existing submission data (table evidence) from the source template.
            //
            // Without this, the new template will have the same schema/targets but
            // no Planning Coordinator submissions, so the UI shows "No data yet".
            $sourceSubmissions = Submission::where('template_id', $sourceTemplate->id)->get();
            foreach ($sourceSubmissions as $sourceSub) {
                if (!($sourceSub instanceof Submission)) {
                    continue;
                }
                // UI expects `table_data` to be non-empty (even summary-only) to avoid
                // rendering placeholder "No data yet" rows.
                $rawTableData = $sourceSub->table_data;
                if (is_string($rawTableData)) {
                    $rawTableData = json_decode($rawTableData, true);
                }
                if (!is_array($rawTableData) || empty($rawTableData)) {
                    continue;
                }

                $newSub = $sourceSub->replicate();
                $newSub->template_id = $newTemplate->id;
                $newSub->template_code = $newTemplate->template_code;

                // Keep data aligned with the destination form/template identity.
                $newSub->form_id = $formId;
                $newSub->form_title = $newTemplate->form?->form_title ?? $sourceSub->form_title;
                $newSub->sg_code = $newTemplate->sg_code;
                $newSub->kra_title = $newTemplate->kra_title;
                $newSub->kpi_title = $newTemplate->kpi_title;

                // Ensure submission_id uniqueness for the new record.
                $newSub->submission_id = null;
                $newSub->submitted_at = null;
                $newSub->last_updated = null;

                // Keep it editable-like for a copy. Super admin view doesn't rely heavily on this,
                // but it helps keep draft-like behavior.
                $newSub->status = 'Unpublished';
                $newSub->is_draft = true;

                $newSub->save();
            }

            DB::commit();

            return redirect()
                ->route('super-admin.templates.show', $newTemplate)
                ->with('success', 'Template copied successfully! You can now edit/update the new template details.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to copy template: ' . $e->getMessage());
        }
    }

    /**
     * Assign template to Planning Coordinator(s) - supports multiple.
     */
    public function assignTemplate(Request $request, Template $template)
    {
        $request->validate([
            'assigned_user_ids' => 'nullable|array',
            'assigned_user_ids.*' => 'exists:users,id',
        ]);

        try {
            $assignedUserIds = $request->input('assigned_user_ids', []);
            if (!is_array($assignedUserIds)) {
                $assignedUserIds = $assignedUserIds ? [$assignedUserIds] : [];
            }
            if (empty($assignedUserIds) && $request->filled('assigned_user_id')) {
                $assignedUserIds = [$request->input('assigned_user_id')];
            }
            $assignedUserIds = array_values(array_filter(array_unique(array_map('intval', $assignedUserIds))));
            foreach ($assignedUserIds as $uid) {
                $user = \App\Models\User::find($uid);
                if (!$user || !$user->isPlanningCoordinator()) {
                    return redirect()->back()
                        ->with('error', 'All selected users must be Planning Coordinators.');
                }
            }
            $template->assignedUsers()->sync($assignedUserIds);
            $template->update(['assigned_user_id' => $assignedUserIds[0] ?? null]);

            $this->logTemplateEdit($template, count($assignedUserIds) ? 'Assigned template to ' . count($assignedUserIds) . ' Planning Coordinator(s).' : 'Removed all template assignments.');

            $message = count($assignedUserIds)
                ? 'Template assigned to ' . count($assignedUserIds) . ' Planning Coordinator(s) successfully!'
                : 'Template assignment removed successfully!';

            return redirect()->back()
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to assign template: ' . $e->getMessage());
        }
    }

    /**
     * Resolve form_id and final template code; run initial store validation. Returns redirect if error.
     * @return array{form_id: int|null, final_template_code: string|null, redirect: RedirectResponse|null}
     */
    private function resolveStoreFormIdAndTemplateCode(Request $request): array
    {
        $formId = $request->input('form_id');
        if ($formId === '' || $formId === null) {
            $formId = null;
        } else {
            $formId = (int) $formId;
        }

        $kpiTitle = trim($request->input('kpi_title', ''));
        if (empty($kpiTitle)) {
            return ['form_id' => null, 'final_template_code' => null, 'redirect' => redirect()->back()
                ->with('error', 'Please select exactly one KPI title.')
                ->withInput()];
        }

        $finalTemplateCode = $request->template_code;
        if ($request->template_code === 'Custom') {
            $finalTemplateCode = trim($request->custom_template_code ?? '');
            if (empty($finalTemplateCode)) {
                return ['form_id' => $formId, 'final_template_code' => null, 'redirect' => redirect()->back()
                    ->withErrors(['custom_template_code' => 'Please enter a custom template code.'])
                    ->withInput()];
            }
        }

        if ($formId !== null) {
            $existing = Template::where('form_id', $formId)->where('template_code', $finalTemplateCode)->first();
            if ($existing) {
                return ['form_id' => $formId, 'final_template_code' => null, 'redirect' => redirect()->back()
                    ->withErrors(['template_code' => "Template code '{$finalTemplateCode}' is already used for this form. Please select a different code."])
                    ->withInput()];
            }
        }

        $rules = [
            'form_id' => 'nullable|exists:forms,id',
            'sg_code' => 'required|string|in:SG1,SG2,SG3,SG4,SG5',
            'template_code' => 'required|string|max:255',
            'kra_title' => 'required|string|max:20000',
            'kpi_title' => 'required|string|max:20000|min:1',
            'fields_json' => 'required|string',
            'campus_code' => 'nullable|string',
            'campus_codes' => 'nullable|array',
            'campus_codes.*' => 'string|max:50',
            'status' => 'required|in:Unpublished,Published',
        ];
        if ($request->template_code === 'Custom') {
            $rules['custom_template_code'] = 'required|string|max:255';
        }
        $messages = [
            'fields_json.required' => 'Field structure is required. Please add at least one field.',
            'kpi_title.required' => 'KPI Title is required. Please select exactly one KPI.',
            'kpi_title.min' => 'Please select exactly one KPI title.',
            'kra_title.max' => 'The KRA title is too long.',
        ];
        try {
            $request->validate($rules, $messages);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['form_id' => $formId, 'final_template_code' => $finalTemplateCode, 'redirect' => redirect()->back()->withErrors($e->errors())->withInput()];
        }

        return ['form_id' => $formId, 'final_template_code' => $finalTemplateCode, 'redirect' => null];
    }

    /**
     * Validate fields_json structure and calculated column sources. Returns redirect on error.
     */
    private function validateStoreFields(Request $request, array $fieldsData): ?RedirectResponse
    {
        if (empty($fieldsData['fields'])) {
            return redirect()->back()
                ->with('error', 'Invalid field structure. Please add at least one field to the template.')
                ->withInput();
        }
        foreach ($fieldsData['fields'] as $field) {
            $calcType = $field['meta']['calc'] ?? null;
            if (in_array($calcType, ['unique', 'countif', 'sum', 'avg_percentage'], true) && empty($field['meta']['sourceA'] ?? null)) {
                return redirect()->back()
                    ->with('error', "Please set Source A for calculated column \"{$field['label']}\".")
                    ->withInput();
            }
        }
        $summaryError = $this->validateSummaryRules($fieldsData);
        if ($summaryError !== null) {
            return redirect()->back()->with('error', $summaryError)->withInput();
        }
        return null;
    }

    /**
     * Normalize campus_codes from request: null = all campuses, or sorted array of uppercase codes.
     */
    private function normalizeTemplateCampusCodesFromRequest(Request $request): ?array
    {
        if ($request->has('campus_codes') && is_array($request->campus_codes)) {
            $codes = array_values(array_unique(array_filter(array_map(function ($c) {
                return strtoupper(trim((string) $c));
            }, $request->campus_codes))));
            sort($codes);
            return empty($codes) ? null : $codes;
        }
        $single = $request->input('campus_code');
        if ($single === null || $single === '' || $single === 'ALL') {
            return null;
        }
        return [strtoupper(trim($single))];
    }

    /**
     * Check duplicate template (same code + same campus_codes set for same form) and form existence. Returns redirect on error.
     */
    private function checkStoreDuplicateAndForm(?int $formId, string $finalTemplateCode, ?array $campusCodesNormalized): ?RedirectResponse
    {
        $existingTemplates = Template::where('template_code', $finalTemplateCode)->get();
        foreach ($existingTemplates as $existing) {
            if ($formId !== null && (int) $existing->form_id !== $formId) {
                continue;
            }
            $existingCodes = $existing->campus_codes;
            if (is_array($existingCodes)) {
                $existingCodes = array_values(array_map('strtoupper', $existingCodes));
                sort($existingCodes);
            }
            $same = $campusCodesNormalized === null && $existingCodes === null
                || is_array($campusCodesNormalized) && is_array($existingCodes)
                && $campusCodesNormalized === $existingCodes;
            if ($same) {
                $label = $campusCodesNormalized ? implode(', ', $campusCodesNormalized) : 'All Campuses';
                return redirect()->back()
                    ->with('error', "A template with code '{$finalTemplateCode}' already exists for this form and campus(es) '{$label}'. Please use a different template code or edit the existing template.")
                    ->withInput();
            }
        }
        if ($formId !== null && !Form::find($formId)) {
            return redirect()->back()->with('error', 'The selected form does not exist. Please try again.')->withInput();
        }
        return null;
    }

    /**
     * Create template record and commit. Returns the created Template.
     */
    private function createTemplateFromStoreRequest(Request $request, ?int $formId, string $finalTemplateCode, ?array $campusCodesNormalized, array $fieldsData, $assignedUserId): Template
    {
        $firstCode = $campusCodesNormalized && count($campusCodesNormalized) > 0 ? $campusCodesNormalized[0] : null;
        $template = Template::create([
            'form_id' => $formId,
            'sg_code' => $request->sg_code,
            'template_code' => $finalTemplateCode,
            'kra_title' => $request->kra_title,
            'kpi_title' => $request->kpi_title,
            'fields_json' => $fieldsData,
            'status' => $request->status,
            'created_by' => (string) Auth::id(),
            'campus_code' => $firstCode,
            'campus_codes' => $campusCodesNormalized,
            'assigned_user_id' => $assignedUserId ?: null,
        ]);
        $template->refresh();
        return $template;
    }

    /**
     * Validate optional summary row rules in template schema.
     */
    private function validateSummaryRules(array $fieldsData): ?string
    {
        $summaryRules = $fieldsData['summary_rules'] ?? [];
        if (empty($summaryRules)) {
            return null;
        }

        $fieldKeys = collect($fieldsData['fields'] ?? [])
            ->map(function ($field) {
                return $field['key'] ?? null;
            })
            ->filter()
            ->values()
            ->all();
        $fieldsByKey = collect($fieldsData['fields'] ?? [])
            ->filter(function ($field) {
                return !empty($field['key']);
            })
            ->keyBy('key')
            ->all();

        foreach ($summaryRules as $rule) {
            if (($rule['enabled'] ?? false) !== true) {
                continue;
            }

            $groupBy = $rule['group_by'][0] ?? '';
            if ($groupBy === '' || !in_array($groupBy, $fieldKeys, true)) {
                return 'Summary rule has an invalid Group By column.';
            }
            $groupField = $fieldsByKey[$groupBy] ?? null;
            if (!$groupField) {
                return 'Summary rule has an invalid Group By column.';
            }
            if (($groupField['type'] ?? '') === 'number' || isset($groupField['meta']['calc'])) {
                return 'Group By column must be a non-calculated, non-number column.';
            }

            $outputs = $rule['outputs'] ?? [];
            if (empty($outputs)) {
                return 'Summary rule must define at least one output.';
            }

            foreach ($outputs as $output) {
                $targetField = $output['target_field'] ?? '';
                $sourceA = $output['sourceA'] ?? '';
                if (!in_array($targetField, $fieldKeys, true) || !in_array($sourceA, $fieldKeys, true)) {
                    return 'Summary rule output references invalid columns.';
                }
            }
        }

        return null;
    }

    /**
     * Store new form (from super admin)
     */
    public function storeForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'division' => 'required|string|in:OP,OVPAFM,OVPASS,OVPREI,OVPQA,OVPLIA',
            'campus_code' => 'nullable|string|exists:campuses,code',
            'sg_code' => 'required|string|in:SG1,SG2,SG3,SG4,SG5',
            'kra_titles' => 'required|array|min:1',
            'kra_titles.*' => 'required|string|max:255',
            'kpi_numbers' => 'required|array|min:1',
            'kpi_numbers.*' => 'required|array|min:1',
            'kpi_numbers.*.*' => 'required|string|max:50',
            'kpi_titles' => 'required|array|min:1',
            'kpi_titles.*' => 'required|array|min:1',
            'kpi_titles.*.*' => 'required|string|max:2000',
            'responsible_units' => 'required|array|min:1',
            'responsible_units.*' => 'required|array|min:1',
            'responsible_units.*.*' => 'required|string|max:255',
            'template_code' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->redirectToCreateFormTabWithFailure($validator);
        }

        // Derive form_title from division selection
        $divisionTitles = [
            'OP' => 'Office of the President (OP)',
            'OVPAFM' => 'Office of the Vice President for Administration and Finance Management (OVPAFM)',
            'OVPASS' => 'Office of the Vice President for Academic and Student Services (OVPASS)',
            'OVPREI' => 'Office of the Vice President for Research, Extension & Innovation (OVPREI)',
            'OVPQA' => 'Office of the Vice President for Quality Assurance (OVPQA)',
            'OVPLIA' => 'Office of the Vice President for Local & International Affairs (OVPLIA)',
        ];
        $formTitle = $divisionTitles[$request->division] ?? $request->division;

        // Check for duplicate KPI numbers within each KRA
        $kpiNumbers = $request->kpi_numbers;
        foreach ($kpiNumbers as $kraIndex => $kraKpiNumbers) {
            if (count($kraKpiNumbers) !== count(array_unique($kraKpiNumbers))) {
                return $this->redirectToCreateFormTabWithFailure(null, "KPI numbers must be unique within each KRA. Duplicate found in KRA #" . ($kraIndex + 1), ['kpi_numbers' => "KPI numbers must be unique within each KRA. Duplicate found in KRA #" . ($kraIndex + 1)]);
            }
        }
        
        // Validate CL/UL levels for each KPI (supports CL, UL, and combined CL_UL)
        $kpiLevels = $request->kpi_levels ?? [];
        foreach ($kpiNumbers as $kraIndex => $kraKpiNumbers) {
            foreach ($kraKpiNumbers as $kpiIndex => $kpiNumber) {
                $levels = $this->normalizeKpiLevelsFromRequest($kpiLevels[$kraIndex][$kpiIndex] ?? null);
                if (empty($levels)) {
                    $levelMessage = "Please select at least one level (CL or UL) for KPI #{$kpiNumber} in KRA #" . ($kraIndex + 1);
                    return $this->redirectToCreateFormTabWithFailure(null, $levelMessage, ['kpi_levels' => $levelMessage]);
                }
            }
        }

        try {
            DB::beginTransaction();

            // Campus is assigned when creating/editing a template and selecting Planning Coordinator(s).
            // Forms are created without campus; template assignment determines campus.
            $campusCode = $request->campus_code ?: null;

            // Check for duplicate template code if template_code is provided (only when campus is set)
            if ($request->template_code && $campusCode) {
                $existingForm = Form::where('template_code', $request->template_code)
                    ->where('campus_code', $campusCode)
                    ->first();
                
                if ($existingForm) {
                    throw new \Exception("A form using template code '{$request->template_code}' already exists for this campus.");
                }
            }

            // Get the strategic goal title from the sg_code
            $strategicGoals = [
                'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
                'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
                'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
                'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
                'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
            ];

            // Get template_id from template_code if provided
            $templateId = null;
            if ($request->template_code) {
                $template = Template::where('template_code', $request->template_code)->first();
                $templateId = $template ? $template->id : null;
            }

            // Process multiple KRAs with nested KPIs
            $kraTitles = $request->kra_titles;
            $kpiNumbers = $request->kpi_numbers;
            $kpiTitles = $request->kpi_titles;
            $responsibleUnits = $request->responsible_units;
            $kpiLevels = $request->kpi_levels ?? [];
            
            // Build structured data for storage
            $kraKpiData = [];
            $allKpiEntries = [];
            $allKraTitles = [];
            
            // Process each KRA
            $globalKpiIndex = 0; // Track global KPI index for target values
            foreach ($kraTitles as $kraIndex => $kraTitle) {
                $allKraTitles[] = $kraTitle;
                $kraKpis = [];
                
                // Process KPIs for this KRA
                if (isset($kpiNumbers[$kraIndex]) && isset($kpiTitles[$kraIndex])) {
                    foreach ($kpiNumbers[$kraIndex] as $kpiIndex => $kpiNumber) {
                        $kpiTitle = $kpiTitles[$kraIndex][$kpiIndex] ?? '';
                        $responsibleUnit = $responsibleUnits[$kraIndex][$kpiIndex] ?? '';
                        
                        // Get CL/UL levels for this KPI
                        $levels = $this->normalizeKpiLevelsFromRequest($kpiLevels[$kraIndex][$kpiIndex] ?? null);
                        // Format level display: "CL", "UL", or "CL / UL"
                        $levelDisplay = '';
                        if (in_array('CL', $levels) && in_array('UL', $levels)) {
                            $levelDisplay = 'CL / UL';
                        } elseif (in_array('CL', $levels)) {
                            $levelDisplay = 'CL';
                        } elseif (in_array('UL', $levels)) {
                            $levelDisplay = 'UL';
                        }
                        
                        // Get target values for this specific KPI
                        $targetQ1 = $request->input("target_q1_{$globalKpiIndex}");
                        $targetQ2 = $request->input("target_q2_{$globalKpiIndex}");
                        $targetQ3 = $request->input("target_q3_{$globalKpiIndex}");
                        $targetQ4 = $request->input("target_q4_{$globalKpiIndex}");
                        $targetQ1 = is_numeric($targetQ1) ? (float) $targetQ1 : null;
                        $targetQ2 = is_numeric($targetQ2) ? (float) $targetQ2 : null;
                        $targetQ3 = is_numeric($targetQ3) ? (float) $targetQ3 : null;
                        $targetQ4 = is_numeric($targetQ4) ? (float) $targetQ4 : null;
                        // Only count quarters with actual data (non-zero); 0 = no target for that quarter
                        $values = array_filter([$targetQ1, $targetQ2, $targetQ3, $targetQ4], fn ($v) => $v !== null && $v !== '' && (float) $v > 0);
                        $targetQ1 = $targetQ1 ?? 0;
                        $targetQ2 = $targetQ2 ?? 0;
                        $targetQ3 = $targetQ3 ?? 0;
                        $targetQ4 = $targetQ4 ?? 0;
                        $isPercentage = $request->boolean("is_percentage_{$globalKpiIndex}");
                        $totalMode = $request->input("target_total_mode_{$globalKpiIndex}", 'average');
                        if (!in_array($totalMode, ['sum', 'average'], true)) {
                            $totalMode = 'average';
                        }
                        // Use selected total mode: sum or average
                        if ($totalMode === 'sum') {
                            if ($isPercentage && count($values) > 0) {
                                $targetTotal = array_sum($values);
                            } else {
                                $targetTotal = $targetQ1 + $targetQ2 + $targetQ3 + $targetQ4;
                            }
                        } else {
                            if ($isPercentage && count($values) > 0) {
                                $targetTotal = array_sum($values) / count($values);
                            } else {
                                $sum = $targetQ1 + $targetQ2 + $targetQ3 + $targetQ4;
                                $targetTotal = $sum > 0 ? $sum / 4 : 0;
                            }
                        }
                        
                        $kpiEntry = $kpiNumber . ' - ' . $kpiTitle;
                        $allKpiEntries[] = $kpiEntry;
                        
                        $kraKpis[] = [
                            'number' => $kpiNumber,
                            'title' => $kpiTitle,
                            'responsible_unit' => $responsibleUnit,
                            'level' => $levels, // Store as array: ['CL'], ['UL'], or ['CL', 'UL']
                            'level_display' => $levelDisplay, // Store formatted display: "CL", "UL", or "CL / UL"
                            'is_percentage' => $isPercentage,
                            'total_mode' => $totalMode,
                            'target_q1' => $targetQ1,
                            'target_q2' => $targetQ2,
                            'target_q3' => $targetQ3,
                            'target_q4' => $targetQ4,
                            'target_total' => $targetTotal,
                        ];
                        
                        $globalKpiIndex++; // Increment for next KPI
                    }
                }
                
                $kraKpiData[] = [
                    'kra_title' => $kraTitle,
                    'kpis' => $kraKpis,
                ];
            }
            
            // Combine KRA titles with semicolon (for backward compatibility)
            $kraTitle = implode('; ', $allKraTitles);
            
            // Combine all KPI entries with semicolon (for backward compatibility)
            $kpiTitle = implode('; ', $allKpiEntries);
            
            // Combine all responsible units (for backward compatibility - use first one or combine)
            $responsibleUnit = !empty($responsibleUnits) && !empty($responsibleUnits[0]) 
                ? implode('; ', array_filter(array_column($responsibleUnits[0], null))) 
                : '';

            // Calculate total target values from all KPIs across all KRAs
            $totalQ1 = 0;
            $totalQ2 = 0;
            $totalQ3 = 0;
            $totalQ4 = 0;
            
            // Sum up target values from all KPIs in the structured data
            foreach ($kraKpiData as $kraData) {
                if (isset($kraData['kpis'])) {
                    foreach ($kraData['kpis'] as $kpi) {
                        $totalQ1 += $kpi['target_q1'] ?? 0;
                        $totalQ2 += $kpi['target_q2'] ?? 0;
                        $totalQ3 += $kpi['target_q3'] ?? 0;
                        $totalQ4 += $kpi['target_q4'] ?? 0;
                    }
                }
            }

            // Campus is assigned when creating/editing a template and selecting Planning Coordinator(s)
            $campusCode = $request->filled('campus_code') ? $request->campus_code : null;

            $form = Form::create([
                'form_title' => $formTitle,
                'division' => $request->division,
                'sg_code' => $request->sg_code,
                'strategic_goal' => $strategicGoals[$request->sg_code] ?? $request->sg_code,
                'kra_title' => $kraTitle, // Combined KRA titles
                'kpi_title' => $kpiTitle, // Combined KPI entries
                'responsible_unit' => $responsibleUnit, // Combined responsible units
                'kra_kpi_data' => $kraKpiData, // Form model casts to JSON
                'target_q1' => $totalQ1,
                'target_q2' => $totalQ2,
                'target_q3' => $totalQ3,
                'target_q4' => $totalQ4,
                'target_total' => $totalQ1 + $totalQ2 + $totalQ3 + $totalQ4,
                'template_id' => $templateId,
                'template_code' => $request->template_code,
                'status' => 'Unpublished',
                'created_by' => Auth::id(),
                'campus_code' => $campusCode,
            ]);

            DB::commit();

            return redirect()->route('super-admin.templates.index', ['tab' => 'forms'])
                ->with('success', 'Form created successfully. Assign campus by selecting Planning Coordinator(s) when creating or editing a template.');

        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return $this->redirectToCreateFormTabWithFailure(
                null,
                'Failed to create form: ' . $e->getMessage()
            );
        }
    }

    /**
     * Redirect back to the Create Form tab (not Forms Management).
     */
    private function redirectToCreateFormTab(): RedirectResponse
    {
        return redirect()->route('super-admin.templates.index', ['tab' => 'create']);
    }

    /**
     * Redirect to Create Form after a failed save (keeps input + shows error).
     */
    private function redirectToCreateFormTabWithFailure(
        $validator = null,
        ?string $errorMessage = null,
        array $errorBag = []
    ): RedirectResponse {
        $redirect = $this->redirectToCreateFormTab()
            ->with('form_create_failed', true)
            ->withInput();

        if ($validator !== null) {
            $redirect->withErrors($validator);
            $errorMessage = $errorMessage ?: $validator->errors()->first();
        } elseif ($errorBag !== []) {
            $redirect->withErrors($errorBag);
        }

        if ($errorMessage) {
            $redirect->with('error', $errorMessage);
        }

        return $redirect;
    }

    /**
     * Normalize KPI level input (CL, UL, or CL_UL) to ['CL'], ['UL'], or ['CL', 'UL'].
     *
     * @return array<int, string>
     */
    private function normalizeKpiLevelsFromRequest(mixed $levels): array
    {
        if (is_array($levels)) {
            $normalized = [];
            foreach ($levels as $level) {
                if ($level === 'CL_UL') {
                    $normalized = array_merge($normalized, ['CL', 'UL']);
                } elseif (in_array($level, ['CL', 'UL'], true)) {
                    $normalized[] = $level;
                }
            }

            return array_values(array_unique($normalized));
        }

        if ($levels === 'CL_UL') {
            return ['CL', 'UL'];
        }

        if (in_array($levels, ['CL', 'UL'], true)) {
            return [$levels];
        }

        return [];
    }

    /**
     * Log a template edit for Audit Trailing (who, what, when).
     */
    private function isDraftAutosaveRequest(Request $request): bool
    {
        return $request->header('X-Draft-Autosave') === '1';
    }

    private function logTemplateEdit(Template $template, string $whatEdited): void
    {
        if (! Schema::hasTable('template_edit_history')) {
            Log::warning('template_edit_history table missing; skipping audit log', [
                'template_id' => $template->id,
            ]);

            return;
        }

        $text = $whatEdited;
        TemplateEditHistory::create([
            'template_id' => $template->id,
            'user_id' => Auth::id(),
            'what_edited' => strlen($text) > 500 ? substr($text, 0, 497) . '...' : $text,
        ]);
    }

    /**
     * Normalize campus targets payload from request.
     */
    private function normalizeCampusTargetsInput(array $campusTargetsInput): array
    {
        $toFloat = static function ($v): ?float {
            if ($v === null || $v === '') {
                return null;
            }
            return is_numeric($v) ? (float) $v : null;
        };

        $campusTargets = [];
        foreach ($campusTargetsInput as $code => $data) {
            $code = strtoupper(trim((string) $code));
            if ($code === '' || !is_array($data)) {
                continue;
            }
            $q1 = $toFloat($data['q1'] ?? null);
            $q2 = $toFloat($data['q2'] ?? null);
            $q3 = $toFloat($data['q3'] ?? null);
            $q4 = $toFloat($data['q4'] ?? null);
            $totalTarget = $toFloat($data['total_target'] ?? null);
            if ($q1 === null && $q2 === null && $q3 === null && $q4 === null && $totalTarget === null) {
                continue;
            }
            if ($totalTarget === null) {
                $totalTarget = (float)(($q1 ?? 0) + ($q2 ?? 0) + ($q3 ?? 0) + ($q4 ?? 0));
            }
            $campusTargets[$code] = [
                'q1' => $q1 ?? 0.0,
                'q2' => $q2 ?? 0.0,
                'q3' => $q3 ?? 0.0,
                'q4' => $q4 ?? 0.0,
                'total_target' => $totalTarget,
            ];
        }
        return $campusTargets;
    }

    /**
     * Build campus target model mapped to actual schema field keys.
     * This is future-ready for per-campus scorecard and direct accomplishment comparison.
     */
    private function buildCampusTargetsModel(array $campusTargets, array $schemaFields): array
    {
        $fieldMap = $this->resolveCampusMetricFieldMap($schemaFields);
        $campuses = [];
        foreach ($campusTargets as $code => $vals) {
            $campuses[$code] = [
                'target' => [
                    'q1' => (float) ($vals['q1'] ?? 0),
                    'q2' => (float) ($vals['q2'] ?? 0),
                    'q3' => (float) ($vals['q3'] ?? 0),
                    'q4' => (float) ($vals['q4'] ?? 0),
                    'total' => (float) ($vals['total_target'] ?? 0),
                    'type' => 'number',
                ],
                'accomplishment' => [
                    'q1' => 0.0,
                    'q2' => 0.0,
                    'q3' => 0.0,
                    'q4' => 0.0,
                    'total' => 0.0,
                ],
                'variance' => [
                    'q1' => 0.0,
                    'q2' => 0.0,
                    'q3' => 0.0,
                    'q4' => 0.0,
                    'total' => 0.0,
                ],
                'rate' => [
                    'q1' => 0.0,
                    'q2' => 0.0,
                    'q3' => 0.0,
                    'q4' => 0.0,
                    'total' => 0.0,
                ],
                'rating' => 'NO TARGET',
            ];
        }

        return [
            'field_map' => $fieldMap,
            'campuses' => $campuses,
            'version' => 1,
        ];
    }

    private function resolveCampusMetricFieldMap(array $schemaFields): array
    {
        $map = [
            'target_q1' => null,
            'target_q2' => null,
            'target_q3' => null,
            'target_q4' => null,
            'target_total' => null,
            'accomp_q1' => null,
            'accomp_q2' => null,
            'accomp_q3' => null,
            'accomp_q4' => null,
            'accomp_total' => null,
            'variance' => null,
            'rate' => null,
            'rating' => null,
        ];
        foreach ($schemaFields as $field) {
            $key = (string)($field['key'] ?? $field['name'] ?? '');
            $label = (string)($field['label'] ?? '');
            if ($key === '' && $label === '') {
                continue;
            }
            $norm = $this->normalizeMetricToken($key . '_' . $label);
            $isTarget = str_contains($norm, 'target');
            $isAccomp = str_contains($norm, 'accomp') || str_contains($norm, 'accomplishment') || str_contains($norm, 'actual');
            $isTotal = str_contains($norm, 'total');
            $quarter = $this->extractQuarterFromToken($norm);

            if ($isTarget && $quarter !== null && $map['target_q' . $quarter] === null) $map['target_q' . $quarter] = $key;
            if ($isAccomp && $quarter !== null && $map['accomp_q' . $quarter] === null) $map['accomp_q' . $quarter] = $key;
            if ($isTarget && $isTotal && $map['target_total'] === null) $map['target_total'] = $key;
            if ($isAccomp && $isTotal && $map['accomp_total'] === null) $map['accomp_total'] = $key;
            if (str_contains($norm, 'variance') && $map['variance'] === null) $map['variance'] = $key;
            if ((str_contains($norm, 'rate') && str_contains($norm, 'accomp')) && $map['rate'] === null) $map['rate'] = $key;
            if ((str_contains($norm, 'descriptive') || str_contains($norm, 'rating')) && $map['rating'] === null) $map['rating'] = $key;
        }
        return $map;
    }

    private function normalizeMetricToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
        return trim((string)$value, '_');
    }

    private function extractQuarterFromToken(string $token): ?int
    {
        if (preg_match('/(^|_)q1($|_)|1st|first/', $token)) return 1;
        if (preg_match('/(^|_)q2($|_)|2nd|second/', $token)) return 2;
        if (preg_match('/(^|_)q3($|_)|3rd|third/', $token)) return 3;
        if (preg_match('/(^|_)q4($|_)|4th|fourth/', $token)) return 4;
        return null;
    }

    // ─── LOCK / UNLOCK ────────────────────────────────────────────────────────

    public function lockTemplate(Request $request, Template $template): RedirectResponse
    {
        $request->validate([
            'lock_reason' => 'nullable|string|max:500',
        ]);

        if ($template->isLocked()) {
            return back()->with('info', 'Template "' . $template->template_code . '" is already locked.');
        }

        $template->lock(Auth::id(), $request->input('lock_reason', ''));

        // Resolve all assigned planning coordinators to notify
        $assignedUsers = $template->assignedUsers;
        if ($assignedUsers->isEmpty() && $template->assigned_user_id) {
            $assignedUsers = User::where('id', $template->assigned_user_id)->get();
        }

        foreach ($assignedUsers as $user) {
            $user->notify(new DeadlineReminderNotification(
                title: 'Template Locked: ' . $template->template_code,
                message: 'The template "' . $template->template_code . '" has been locked by the Super Admin. No further submissions or edits are allowed until it is unlocked.',
                deadline: now()->toDateString(),
                priority: 'warning',
                templateId: $template->id,
                templateCode: $template->template_code,
            ));
        }

        return back()->with('success', 'Template "' . $template->template_code . '" has been locked.');
    }

    public function unlockTemplate(Template $template): RedirectResponse
    {
        if (!$template->isLocked()) {
            return back()->with('info', 'Template "' . $template->template_code . '" is not currently locked.');
        }

        $assignedUsers = $template->assignedUsers;
        if ($assignedUsers->isEmpty() && $template->assigned_user_id) {
            $assignedUsers = User::where('id', $template->assigned_user_id)->get();
        }

        $template->unlock();

        foreach ($assignedUsers as $user) {
            $user->notify(new DeadlineReminderNotification(
                title: 'Template Unlocked: ' . $template->template_code,
                message: 'The template "' . $template->template_code . '" has been unlocked by the Super Admin. Submissions and edits are allowed again.',
                deadline: now()->toDateString(),
                priority: 'info',
                templateId: $template->id,
                templateCode: $template->template_code,
            ));
        }

        return back()->with('success', 'Template "' . $template->template_code . '" has been unlocked.');
    }

    // ─── NOTIFY ───────────────────────────────────────────────────────────────

    public function notifyForm(Template $template)
    {
        $recipients = User::where(function ($q) {
                $q->where('position', 'Planning Coordinator')
                  ->orWhere('position', 'planning_coordinator')
                  ->orWhere('position', 'planning-coordinator')
                  ->orWhere('role', User::ROLE_PLANNING_COORDINATOR)
                  ->orWhere('role', 'planning_coordinator')
                  ->orWhere('role', 'planning-coordinator')
                  ->orWhere('role', User::ROLE_CREATOR_EDITOR);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('super-admin.notifications.compose', compact('template', 'recipients'));
    }

    public function sendNotification(Request $request, Template $template): RedirectResponse
    {
        $data = $request->validate([
            'notif_message'  => 'required|string',
            'notif_deadline' => 'required|date|after_or_equal:today',
            'notif_priority' => 'required|in:normal,urgent',
            'user_ids'       => 'required|array|min:1',
            'user_ids.*'     => 'exists:users,id',
        ]);

        $fixedTitle = $template->fixedDeadlineNotificationTitle();

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::whereIn('id', $data['user_ids'])->get();

        foreach ($users as $user) {
            /** @var User $user */
            $user->notify(new DeadlineReminderNotification(
                title: $fixedTitle,
                message: $data['notif_message'],
                deadline: $data['notif_deadline'],
                priority: $data['notif_priority'],
                templateId: $template->id,
                templateCode: $template->template_code,
            ));
        }

        return back()->with('success', 'Notification sent to ' . $users->count() . ' user(s).');
    }
}

