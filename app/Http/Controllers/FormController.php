<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Form;
use App\Models\Submission;
use App\Models\Approval;
use App\Services\RollupService;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class FormController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of forms
     */
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isSuperAdmin()) {
            // Super Admin can see all forms
            $forms = collect([]); // Will be implemented with actual form system
        } elseif ($user->isAdmin()) {
            // QA Coordinator can see campus forms
            $forms = collect([]); // Will be implemented with actual form system
        } else {
            // Creator/Editor can see their own forms
            $forms = collect([]); // Will be implemented with actual form system
        }
        
        return view('forms.index', compact('forms'));
    }

    /**
     * Show the form for creating a new form
     */
    public function create()
    {
        $user = Auth::user();
        
        if (!$user->canCreateForms()) {
            abort(403, 'You do not have permission to create forms.');
        }
        
        return view('forms.create');
    }

    /**
     * Store a newly created form
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->canCreateForms()) {
            abort(403, 'You do not have permission to create forms.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:sg,kra,kpi,target,accomplishment',
            'description' => 'required|string',
            'target_date' => 'required|date|after:today',
        ]);

        // This will be implemented with actual form system
        // For now, return success message
        
        return redirect()->route('forms.index')
            ->with('success', 'Form created successfully. This feature will be fully implemented in the next phase.');
    }

    /**
     * Display the specified form
     */
    public function show(Form $form)
    {
        $user = Auth::user();

        $form->load(['creator', 'campus']);

        // Super Admin: all forms. View-only: published forms within scope. Others: campus or owner.
        if ($user->isSuperAdmin()) {
            // allowed
        } elseif ($user->isViewOnly()) {
            if (!$user->viewOnlyCanAccessForm($form)) {
                abort(403, 'You do not have permission to view this form.');
            }
        } elseif ($form->campus_code !== $user->campus_code && $form->created_by !== $user->id) {
            abort(403, 'You do not have permission to view this form.');
        }

        // Footer tabs only need metadata — omit fields_json (often huge) to avoid OOM on Form Details.
        $templates = \App\Models\Template::where('form_id', $form->id)
            ->select([
                'id', 'form_id', 'sg_code', 'template_code', 'kra_title', 'kpi_title',
                'status', 'campus_code', 'created_by', 'assigned_user_id',
                'is_locked', 'locked_at', 'locked_by', 'lock_reason',
                'created_at', 'updated_at',
            ])
            ->with('campus')
            ->orderBy('template_code')
            ->get();
        $form->setRelation('templates', $templates);

        // Build VPASS-style data per KPI: accomplishment, variance, rate, descriptive rating
        $vpassKpiData = $this->buildVpassDataForForm($form);

        return view('forms.show', compact('form', 'vpassKpiData'));
    }

    /**
     * Same VPASS KPI roll-ups as Form Details (/forms/{id}) for Super Admin Summary of Accomplishments.
     */
    public function vpassKpiMetricsForSummary(Form $form): array
    {
        return $this->buildVpassDataForForm($form);
    }

    /**
     * Build per-KPI VPASS data (accomplishment, variance, rate, descriptive rating) for Form Details.
     * Keys are "kraIndex_kpiIndex" for easy lookup in the view.
     */
    protected function buildVpassDataForForm(Form $form): array
    {
        $kraKpiData = $form->kra_kpi_data;
        if (is_string($kraKpiData)) {
            $decodedKra = json_decode($kraKpiData, true);
            $kraKpiData = is_array($decodedKra) ? $decodedKra : [];
        }
        if (! is_array($kraKpiData) || empty($kraKpiData)) {
            return [];
        }

        // One pass over templates.fields_json — do not call per KPI (was exhausting memory on large forms).
        $tplFaIndex = $this->buildTemplateFinalizedAccompIndex($form->id);

        // Never load all table_data at once (OOM on multi-campus forms). Match on slim rows, then hydrate per submission.
        $submissionLightRows = $this->submissionLightRowsForForm($form->id);
        $submissionMatchIndex = $this->buildSubmissionMatchIndex($submissionLightRows);
        $rollup = app(RollupService::class);
        $accomplishmentMode = $this->resolveAccomplishmentMode($form);
        $out = [];
        $kpiTemplateOrdinal = 0;
        // Never bulk-load every submission for the form (OOM on large forms). Fetch slim rows per id on demand.
        $submissionCache = [];
        $tableDataCache = [];
        $getSubmission = function ($id) use (&$submissionCache) {
            $id = (int) $id;
            if (array_key_exists($id, $submissionCache)) {
                return $submissionCache[$id];
            }
            $submissionCache[$id] = Submission::query()
                ->select(['id', 'campus', 'submitted_by'])
                ->with([
                    'approval:submission_id,accomp_q1,accomp_q2,accomp_q3,accomp_q4,accomp_total',
                    'submitter:id,campus_code',
                ])
                ->find($id);

            return $submissionCache[$id];
        };
        $ensureSubmissionTableData = function (Submission $sub) use (&$tableDataCache): void {
            $sid = (int) $sub->id;
            if (array_key_exists($sid, $tableDataCache)) {
                $sub->setAttribute('table_data', $tableDataCache[$sid]);
                return;
            }
            $raw = DB::table('submissions')->where('id', $sid)->value('table_data');
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $tableDataCache[$sid] = is_array($decoded) ? $decoded : [];
            } elseif (is_array($raw)) {
                $tableDataCache[$sid] = $raw;
            } else {
                $tableDataCache[$sid] = [];
            }
            $sub->setAttribute('table_data', $tableDataCache[$sid]);
        };

        foreach ($kraKpiData as $kraIndex => $kraData) {
            $kpis = $kraData['kpis'] ?? [];
            foreach ($kpis as $kpiIndex => $kpi) {
                $key = "{$kraIndex}_{$kpiIndex}";
                $targetQ1 = (float) ($kpi['target_q1'] ?? 0);
                $targetQ2 = (float) ($kpi['target_q2'] ?? 0);
                $targetQ3 = (float) ($kpi['target_q3'] ?? 0);
                $targetQ4 = (float) ($kpi['target_q4'] ?? 0);
                $targetTotal = (float) ($kpi['target_total'] ?? ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4));

                $accompQ1 = 0.0;
                $accompQ2 = 0.0;
                $accompQ3 = 0.0;
                $accompQ4 = 0.0;
                $accompTotal = 0.0;

                $matchedIds = $this->findSubmissionIdsForKpi($submissionMatchIndex, $kraData['kra_title'] ?? '', $kpi);
                $campusBreakdown = [];
                if ($accomplishmentMode === 'overall') {
                    // Finalize stores Q1–Q4 that are already the mixed / university roll-up (51,7,5,6). Each campus
                    // submission may carry a copy of that same row — summing N submissions multiplies by N (×9).
                    // If any match has finalized_accomp, use one canonical submission only, not a sum.
                    $canonicalId = null;
                    foreach ($matchedIds as $mid) {
                        $probe = $getSubmission($mid);
                        if ($probe) {
                            $ensureSubmissionTableData($probe);
                        }
                        if ($probe && $this->submissionTableDataHasFinalizedAccomp($probe)) {
                            $canonicalId = $mid;
                            unset($probe);
                            break;
                        }
                        unset($probe);
                    }
                    $useTemplateFinalizeRollup = $canonicalId !== null;

                    foreach ($matchedIds as $mid) {
                        $sub = $getSubmission($mid);
                        if (! $sub) {
                            continue;
                        }
                        if (! $sub->approval) {
                            $ensureSubmissionTableData($sub);
                        }
                        [$q1, $q2, $q3, $q4, $total] = $this->extractAccomplishmentFromSubmission($sub, $rollup);
                        if (! $useTemplateFinalizeRollup) {
                            $accompQ1 += $q1;
                            $accompQ2 += $q2;
                            $accompQ3 += $q3;
                            $accompQ4 += $q4;
                            $accompTotal += $total;
                        }
                        $campusKey = trim((string) ($sub->campus ?: ($sub->submitter?->campus_code ?? 'UNKNOWN')));
                        if ($campusKey === '') {
                            $campusKey = 'UNKNOWN';
                        }
                        if (! isset($campusBreakdown[$campusKey])) {
                            $campusBreakdown[$campusKey] = [
                                'accomp_q1' => 0.0,
                                'accomp_q2' => 0.0,
                                'accomp_q3' => 0.0,
                                'accomp_q4' => 0.0,
                                'accomp_total' => 0.0,
                            ];
                        }
                        $campusBreakdown[$campusKey]['accomp_q1'] += $q1;
                        $campusBreakdown[$campusKey]['accomp_q2'] += $q2;
                        $campusBreakdown[$campusKey]['accomp_q3'] += $q3;
                        $campusBreakdown[$campusKey]['accomp_q4'] += $q4;
                        $campusBreakdown[$campusKey]['accomp_total'] += $total;
                        unset($sub);
                    }

                    if ($useTemplateFinalizeRollup && $canonicalId !== null) {
                        $canonicalSub = $getSubmission($canonicalId);
                        if ($canonicalSub) {
                            $ensureSubmissionTableData($canonicalSub);
                            [$accompQ1, $accompQ2, $accompQ3, $accompQ4, $accompTotal] = $this->extractAccomplishmentFromSubmission(
                                $canonicalSub,
                                $rollup
                            );
                        }
                        unset($canonicalSub);
                    }
                } else {
                    $sub = null;
                    if ($matchedIds !== []) {
                        $sub = $getSubmission($matchedIds[0]);
                    }
                    if ($sub) {
                        if (! $sub->approval) {
                            $ensureSubmissionTableData($sub);
                        }
                        [$accompQ1, $accompQ2, $accompQ3, $accompQ4, $accompTotal] = $this->extractAccomplishmentFromSubmission($sub, $rollup);
                    }
                    unset($sub);
                }

                // Template.fields_json.finalized_accomp (Super Admin Finalize + save) is the roll-up the UI shows
                // on the template (e.g. 51, 7, 5, 6). Prefer it whenever it has non-zero data so Form Details
                // does not stay on stale submission rollups or heuristic column picks when submissions also carry
                // empty/partial _meta.finalized_accomp (which skips the old "fallback only if zeros" path).
                $preferredTemplateCode = 'T'.($kpiTemplateOrdinal + 1);
                $fromTpl = $this->findFinalizedAccompFromIndex(
                    $tplFaIndex,
                    $kraData['kra_title'] ?? '',
                    $kpi,
                    $preferredTemplateCode
                );
                if ($fromTpl !== null) {
                    $tplQuarterSum = abs($fromTpl['q1']) + abs($fromTpl['q2']) + abs($fromTpl['q3']) + abs($fromTpl['q4']);
                    if ($tplQuarterSum > 1e-9 || abs($fromTpl['total']) > 1e-9) {
                        $accompQ1 = $fromTpl['q1'];
                        $accompQ2 = $fromTpl['q2'];
                        $accompQ3 = $fromTpl['q3'];
                        $accompQ4 = $fromTpl['q4'];
                        $accompTotal = $fromTpl['total'];
                    }
                }

                $kpiTemplateOrdinal++;

                $variance = $targetTotal - $accompTotal;
                $rate = $targetTotal > 0 ? (($accompTotal / $targetTotal) * 100) : 0.0;
                $descriptiveRating = $this->getVpassDescriptiveRating($rate, $targetTotal, $accompTotal);

                $out[$key] = [
                    'accomp_q1' => $accompQ1,
                    'accomp_q2' => $accompQ2,
                    'accomp_q3' => $accompQ3,
                    'accomp_q4' => $accompQ4,
                    'accomp_total' => $accompTotal,
                    'variance' => $variance,
                    'rate_of_accomplishment' => $rate,
                    'descriptive_rating' => $descriptiveRating,
                    'accomplishment_mode' => $accomplishmentMode,
                    'campus_breakdown' => $campusBreakdown,
                ];
            }
        }

        return $out;
    }

    /**
     * KRA/KPI + approval presence for matching without loading submission.table_data.
     */
    protected function submissionLightRowsForForm(int $formId): Collection
    {
        $rows = DB::table('submissions as s')
            ->leftJoin('approvals as a', 'a.submission_id', '=', 's.id')
            ->where('s.form_id', $formId)
            ->orderByDesc('s.updated_at')
            ->select([
                's.id',
                's.kra_title',
                's.kpi_title',
                's.campus',
                's.submitted_by',
                'a.id as approval_pk',
            ])
            ->get();

        return collect($rows);
    }

    /**
     * Build lightweight lookup index so KPI matching doesn't scan all rows each time.
     *
     * @return array{rows: array<int, object>, by_kra: array<string, array<int, object>>}
     */
    protected function buildSubmissionMatchIndex(Collection $lightRows): array
    {
        $rows = [];
        $byKra = [];

        foreach ($lightRows as $row) {
            $row->norm_kra = $this->normalizeText((string) ($row->kra_title ?? ''));
            $row->norm_kpi = $this->normalizeText((string) ($row->kpi_title ?? ''));
            $rows[] = $row;

            if ($row->norm_kra !== '') {
                $byKra[$row->norm_kra] ??= [];
                $byKra[$row->norm_kra][] = $row;
            }
        }

        return [
            'rows' => $rows,
            'by_kra' => $byKra,
        ];
    }

    /**
     * Submission ids for this KRA/KPI (approval rows first, then others), same order as legacy findSubmissionsForKpi.
     *
     * @return list<int|string>
     */
    protected function findSubmissionIdsForKpi(array $matchIndex, string $kraTitle, array $kpi): array
    {
        $kpiNumber = trim((string) ($kpi['number'] ?? ''));
        $kpiTitle = trim((string) ($kpi['title'] ?? ''));
        $fullKpi = $kpiNumber !== '' ? $kpiNumber.' - '.$kpiTitle : $kpiTitle;
        $normKra = $this->normalizeText($kraTitle);
        $normKpi = $this->normalizeText($fullKpi);
        $withApproval = [];
        $withoutApproval = [];
        $candidateRows = $normKra !== ''
            ? ($matchIndex['by_kra'][$normKra] ?? $matchIndex['rows'])
            : $matchIndex['rows'];

        foreach ($candidateRows as $row) {
            $subKra = (string) ($row->norm_kra ?? '');
            $subKpi = (string) ($row->norm_kpi ?? '');
            if ($subKpi === '' && $subKra === '') {
                continue;
            }
            if ($normKra !== '' && $subKra !== '') {
                $kraMatch = str_contains($subKra, $normKra) || str_contains($normKra, $subKra);
                if (! $kraMatch) {
                    continue;
                }
            }
            $numPrefix = $kpiNumber !== '' && str_starts_with($subKpi, $kpiNumber);
            $match = ($normKpi !== '' && (str_contains($subKpi, $normKpi) || str_contains($normKpi, $subKpi) || $numPrefix))
                || $numPrefix;
            if (! $match) {
                continue;
            }
            $id = $row->id;
            if ($row->approval_pk !== null) {
                $withApproval[] = $id;
            } else {
                $withoutApproval[] = $id;
            }
        }

        return array_merge($withApproval, $withoutApproval);
    }

    protected function normalizeText(string $value): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    /**
     * Extract accomplishment quarterly + total from one submission.
     */
    protected function extractAccomplishmentFromSubmission(Submission $sub, RollupService $rollup): array
    {
        // Template Finalize stores _meta.finalized_accomp on the summary row; rollup reads it.
        // Prefer that over stale approval rows that still have zeros after Super Admin Finalize.
        if ($this->submissionTableDataHasFinalizedAccomp($sub)) {
            $extracted = $rollup->extractFromSubmission($sub);
            $q1 = (float) ($extracted['accomp_q1'] ?? 0);
            $q2 = (float) ($extracted['accomp_q2'] ?? 0);
            $q3 = (float) ($extracted['accomp_q3'] ?? 0);
            $q4 = (float) ($extracted['accomp_q4'] ?? 0);
            $total = $q1 + $q2 + $q3 + $q4;

            return [$q1, $q2, $q3, $q4, $total];
        }
        if ($sub->approval) {
            $q1 = (float) $sub->approval->accomp_q1;
            $q2 = (float) $sub->approval->accomp_q2;
            $q3 = (float) $sub->approval->accomp_q3;
            $q4 = (float) $sub->approval->accomp_q4;
            $total = (float) $sub->approval->accomp_total;
            return [$q1, $q2, $q3, $q4, $total];
        }
        $extracted = $rollup->extractFromSubmission($sub);
        $q1 = (float) ($extracted['accomp_q1'] ?? 0);
        $q2 = (float) ($extracted['accomp_q2'] ?? 0);
        $q3 = (float) ($extracted['accomp_q3'] ?? 0);
        $q4 = (float) ($extracted['accomp_q4'] ?? 0);
        $total = $q1 + $q2 + $q3 + $q4;
        return [$q1, $q2, $q3, $q4, $total];
    }

    /**
     * True when submission table_data has a summary row with finalized Q1–Q4 from Super Admin Finalize.
     */
    protected function submissionTableDataHasFinalizedAccomp(Submission $sub): bool
    {
        $tableData = $sub->table_data;
        if (! is_array($tableData)) {
            return false;
        }
        foreach ($tableData as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (! is_array($meta) || ($meta['row_type'] ?? '') !== 'summary') {
                continue;
            }
            if (isset($meta['finalized_accomp']) && is_array($meta['finalized_accomp'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a template's KRA/KPI labels match a row from form kra_kpi_data.
     * Uses the same loose KRA/KPI rules as findSubmissionIdsForKpi so Form Details VPASS
     * lines up with submission matching and finalized_accomp on the correct template (e.g. T2 → KPI 2).
     */
    protected function templateMatchesKpiForVpass(object $template, string $kraTitle, array $kpi): bool
    {
        $kpiNumber = trim((string) ($kpi['number'] ?? ''));
        $kpiTitle = trim((string) ($kpi['title'] ?? ''));
        $fullKpi = $kpiNumber !== '' ? $kpiNumber.' - '.$kpiTitle : $kpiTitle;
        $normKra = strtolower(preg_replace('/\s+/', ' ', $kraTitle));
        $normKpi = strtolower(preg_replace('/\s+/', ' ', $fullKpi));
        $subKra = strtolower(preg_replace('/\s+/', ' ', (string) ($template->kra_title ?? '')));
        $rawKpiTitle = trim((string) ($template->kpi_title ?? ''));
        $subKpi = strtolower(preg_replace('/\s+/', ' ', $rawKpiTitle));
        $subKpiHead = $subKpi;
        if ($rawKpiTitle !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $rawKpiTitle);
            $firstLine = trim((string) ($lines[0] ?? ''));
            if ($firstLine !== '') {
                $subKpiHead = strtolower(preg_replace('/\s+/', ' ', $firstLine));
            }
        }
        if ($subKpi === '' && $subKra === '') {
            return false;
        }
        if ($normKra !== '' && $subKra !== '') {
            $kraMatch = str_contains($subKra, $normKra) || str_contains($normKra, $subKra);
            if (! $kraMatch) {
                return false;
            }
        }

        // Avoid "1" matching "10 - …"; require delimiter after KPI number.
        $numPrefix = false;
        if ($kpiNumber !== '') {
            $kn = preg_quote($kpiNumber, '/');
            $numPrefix = (bool) preg_match('/^'.$kn.'(\s|-|–|\.|:|,|$)/u', $subKpi);
        }

        $headMatch = $normKpi !== '' && $subKpiHead !== ''
            && (str_contains($subKpiHead, $normKpi) || str_contains($normKpi, $subKpiHead));

        return ($normKpi !== '' && (str_contains($subKpi, $normKpi) || str_contains($normKpi, $subKpi) || $numPrefix || $headMatch))
            || $numPrefix;
    }

    /**
     * Parse each template's fields_json once; extract finalized_accomp only (small).
     *
     * @return list<object{template_code: string, kra_title: mixed, kpi_title: mixed, finalized: ?array}>
     */
    protected function buildTemplateFinalizedAccompIndex(int $formId): array
    {
        $out = [];
        foreach (DB::table('templates')
            ->where('form_id', $formId)
            ->orderBy('template_code')
            ->select(['template_code', 'kra_title', 'kpi_title', 'fields_json'])
            ->cursor() as $row) {
            $finalized = null;
            $raw = $row->fields_json ?? null;
            if (is_string($raw) && $raw !== '') {
                $fj = json_decode($raw, true);
                if (is_array($fj) && isset($fj['finalized_accomp']) && is_array($fj['finalized_accomp'])) {
                    $finalized = $this->normalizeFinalizedAccompArray($fj['finalized_accomp']);
                }
                unset($fj);
            }
            $out[] = (object) [
                'template_code' => (string) ($row->template_code ?? ''),
                'kra_title' => $row->kra_title,
                'kpi_title' => $row->kpi_title,
                'finalized' => $finalized,
            ];
        }

        return $out;
    }

    /**
     * @param  list<object{template_code: string, kra_title: mixed, kpi_title: mixed, finalized: ?array}>  $tplIndex
     * @return array{q1: float, q2: float, q3: float, q4: float, total: float}|null
     */
    protected function findFinalizedAccompFromIndex(array $tplIndex, string $kraTitle, array $kpi, ?string $preferTemplateCode = null): ?array
    {
        foreach ($tplIndex as $row) {
            if (! $this->templateMatchesKpiForVpass($row, $kraTitle, $kpi)) {
                continue;
            }
            if ($row->finalized !== null) {
                return $row->finalized;
            }
        }
        if ($preferTemplateCode !== null && trim($preferTemplateCode) !== '') {
            $want = strtolower(trim($preferTemplateCode));
            foreach ($tplIndex as $row) {
                if (strtolower(trim((string) $row->template_code)) !== $want) {
                    continue;
                }
                if ($row->finalized !== null) {
                    return $row->finalized;
                }
            }
        }

        return null;
    }

    /**
     * @return array{q1: float, q2: float, q3: float, q4: float, total: float}
     */
    protected function normalizeFinalizedAccompArray(array $fa): array
    {
        $q1 = (float) ($fa['q1'] ?? 0);
        $q2 = (float) ($fa['q2'] ?? 0);
        $q3 = (float) ($fa['q3'] ?? 0);
        $q4 = (float) ($fa['q4'] ?? 0);

        return [
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'total' => $q1 + $q2 + $q3 + $q4,
        ];
    }

    /**
     * Resolve accomplishment display mode from template config.
     * Defaults to "overall" (whole across campuses), with "per_campus" as opt-in.
     */
    protected function resolveAccomplishmentMode(Form $form): string
    {
        $raw = DB::table('templates')
            ->where('form_id', $form->id)
            ->orderBy('template_code')
            ->value('fields_json');
        if (! is_string($raw) || $raw === '') {
            return 'overall';
        }
        $fj = json_decode($raw, true);
        $mode = is_array($fj) ? ($fj['accomplishment_mode'] ?? 'overall') : 'overall';
        unset($fj);

        return in_array($mode, ['overall', 'per_campus'], true) ? $mode : 'overall';
    }

    /**
     * VPASS-style descriptive rating (NO TARGET, BELOW TARGET, MET TARGET, ABOVE TARGET, NO ACCOMPLISHMENT).
     */
    protected function getVpassDescriptiveRating(float $rate, float $targetTotal, float $accomplishmentTotal): string
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
        if ($rate == 100 || abs($rate - 100) < 0.01) {
            return 'MET TARGET';
        }
        if ($rate > 0 && $rate < 100) {
            return 'BELOW TARGET';
        }
        return 'NO ACCOMPLISHMENT';
    }

    /**
     * Show the form for editing the specified form
     */
    public function edit(Form $form)
    {
        $user = Auth::user();
        
        $form->load(['creator', 'campus', 'template']);
        
        // Check permissions - Super Admin can edit all forms
        if (!$user->isSuperAdmin()) {
            if (!$user->canCreateForms()) {
                abort(403, 'You do not have permission to edit forms.');
            }
            if ($form->campus_code !== $user->campus_code && $form->created_by !== $user->id) {
                abort(403, 'You do not have permission to edit this form.');
            }
        }
        
        // For super admin, use the proper edit form view
        if ($user->isSuperAdmin()) {
            $campuses = \App\Models\Campus::where('is_active', true)->get();
            $strategicGoals = [
                'SG1' => 'SG1 – Industry-Focused and Innovation-Based Student Learning and Development',
                'SG2' => 'SG2 – Responsive and Sustainable Research, Community Extension, and Innovative Programs',
                'SG3' => 'SG3 – Efficient and Effective Governance and Finance Management',
                'SG4' => 'SG4 – High-Performing and Engaged Human Resource',
                'SG5' => 'SG5 – Strategic and Functional Internationalization Program'
            ];
            
            // Load structured KRA/KPI data if available
            $kraKpiData = $form->kra_kpi_data ?? null;
            
            // If it's a JSON string, decode it
            if (is_string($kraKpiData)) {
                $kraKpiData = json_decode($kraKpiData, true);
            }
            
            // If no structured data, fallback to old format
            if (!$kraKpiData || !is_array($kraKpiData) || empty($kraKpiData)) {
                // Parse KPI title to extract KPI numbers and titles (backward compatibility)
                $kpiEntries = [];
                if ($form->kpi_title) {
                    $kpiParts = explode('; ', $form->kpi_title);
                    foreach ($kpiParts as $part) {
                        if (preg_match('/^(.+?)\s*-\s*(.+)$/', trim($part), $matches)) {
                            $kpiEntries[] = [
                                'number' => trim($matches[1]),
                                'title' => trim($matches[2])
                            ];
                        }
                    }
                }
                
                // If no KPI entries found, create a default one
                if (empty($kpiEntries)) {
                    $kpiEntries[] = [
                        'number' => '1',
                        'title' => ''
                    ];
                }
                
                // Create a single KRA entry from old format
                $kraKpiData = [
                    [
                        'kra_title' => $form->kra_title ?? 'KRA Title',
                        'kpis' => array_map(function($kpi) use ($form) {
                            return [
                                'number' => $kpi['number'],
                                'title' => $kpi['title'],
                                'responsible_unit' => $form->responsible_unit ?? ''
                            ];
                        }, $kpiEntries)
                    ]
                ];
            }
            
            return view('super-admin.forms.edit', compact('form', 'campuses', 'strategicGoals', 'kraKpiData'));
        }
        
        return view('forms.edit', compact('form'));
    }

    /**
     * Update the specified form
     */
    public function update(Request $request, Form $form)
    {
        $user = Auth::user();
        
        // Check permissions - Super Admin can edit all forms
        if (!$user->isSuperAdmin()) {
            if (!$user->canCreateForms()) {
                abort(403, 'You do not have permission to edit forms.');
            }
            if ($form->campus_code !== $user->campus_code && $form->created_by !== $user->id) {
                abort(403, 'You do not have permission to edit this form.');
            }
        }
        
        // For super admin, use the same validation as storeForm
        if ($user->isSuperAdmin()) {
            $request->validate([
                'division' => 'required|string|in:OP,OVPAFM,OVPASS,OVPREI,OVPQA,OVPLIA',
                'campus_code' => 'nullable|string',
                'sg_code' => 'required|string|in:SG1,SG2,SG3,SG4,SG5',
                'kra_titles' => 'required|array|min:1',
                'kra_titles.*' => 'required|string|max:255',
                'kpi_numbers' => 'required|array',
                'kpi_numbers.*' => 'required|array|min:1',
                'kpi_numbers.*.*' => 'required|string|max:50',
                'kpi_titles' => 'required|array',
                'kpi_titles.*' => 'required|array|min:1',
                'kpi_titles.*.*' => 'required|string|max:2000',
                'responsible_units' => 'required|array',
                'responsible_units.*' => 'required|array|min:1',
                'responsible_units.*.*' => 'required|string|max:255',
                'template_code' => 'nullable|string|max:255',
            ]);

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
                    return redirect()->back()
                        ->withErrors(['kpi_numbers' => "KPI numbers must be unique within each KRA. Duplicate found in KRA #" . ($kraIndex + 1)])
                        ->withInput();
                }
            }

            try {
                DB::beginTransaction();

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
                    $template = \App\Models\Template::where('template_code', $request->template_code)->first();
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
                            $levels = $kpiLevels[$kraIndex][$kpiIndex] ?? [];
                            if (!is_array($levels)) {
                                // Handle combined CL_UL value from dropdown
                                if ($levels === 'CL_UL') {
                                    $levels = ['CL', 'UL'];
                                } else {
                                    $levels = $levels ? [$levels] : [];
                                }
                            }
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
                
                // Collect unique responsible unit values across all KRAs/KPIs
                $allUnitValues = [];
                foreach ($responsibleUnits as $kraUnits) {
                    foreach ((array) $kraUnits as $unitString) {
                        foreach (array_map('trim', preg_split('/[,;\/]+/', (string) $unitString)) as $unit) {
                            if ($unit !== '' && !in_array($unit, $allUnitValues, true)) {
                                $allUnitValues[] = $unit;
                            }
                        }
                    }
                }
                $responsibleUnit = implode(', ', $allUnitValues);

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

                // Get campus_code: use request value, or keep existing, or fallback to user's campus_code, or default to 'LINGAYEN'
                $campusCode = $request->campus_code;
                if (empty($campusCode)) {
                    $campusCode = $form->campus_code; // Keep existing value
                    if (empty($campusCode)) {
                        $user = Auth::user();
                        $campusCode = $user->campus_code ?? 'LINGAYEN';
                    }
                }

                $form->update([
                    'form_title' => $formTitle,
                    'division' => $request->division,
                    'sg_code' => $request->sg_code,
                    'strategic_goal' => $strategicGoals[$request->sg_code] ?? $request->sg_code,
                    'kra_title' => $kraTitle, // Combined KRA titles
                    'kpi_title' => $kpiTitle, // Combined KPI entries
                    'responsible_unit' => $responsibleUnit, // Combined responsible units
                    'kra_kpi_data' => json_encode($kraKpiData), // Store structured data as JSON
                    'target_q1' => $totalQ1,
                    'target_q2' => $totalQ2,
                    'target_q3' => $totalQ3,
                    'target_q4' => $totalQ4,
                    'target_total' => $totalQ1 + $totalQ2 + $totalQ3 + $totalQ4,
                    'template_id' => $templateId,
                    'template_code' => $request->template_code,
                    'campus_code' => $campusCode,
                ]);

                DB::commit();

                return redirect()->route('forms.show', $form->id)
                    ->with('success', 'Form updated successfully!');

            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to update form: ' . $e->getMessage());
            }
        }
        
        // For non-super-admin, use old validation (placeholder)
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:sg,kra,kpi,target,accomplishment',
            'description' => 'required|string',
            'target_date' => 'required|date|after:today',
        ]);
        
        return redirect()->route('forms.index')
            ->with('success', 'Form updated successfully.');
    }

    /**
     * Toggle form status (Publish/Unpublish)
     */
    public function toggleStatus(Request $request, Form $form)
    {
        $user = Auth::user();
        
        // Check permissions - Super Admin can toggle all forms
        if (!$user->isSuperAdmin()) {
            if ($form->campus_code !== $user->campus_code && $form->created_by !== $user->id) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'You do not have permission to toggle this form status.'], 403);
                }
                abort(403, 'You do not have permission to toggle this form status.');
            }
        }
        
        try {
            $form->status = $form->status === 'Published' ? 'Unpublished' : 'Published';
            $form->save();
            
            $message = $form->status === 'Published' 
                ? 'Form published successfully!' 
                : 'Form moved to draft.';
            
            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'status' => $form->status
                ]);
            }
            
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to toggle status: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()
                ->with('error', 'Failed to toggle status: ' . $e->getMessage());
        }
    }

    /**
     * Lock all templates contained in the specified form.
     */
    public function lockForm(Request $request, Form $form)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'lock_reason' => 'nullable|string|max:500',
        ]);

        $templates = $form->templates;

        if ($templates->isEmpty()) {
            return back()->with('info', 'This form has no templates to lock.');
        }

        $lockReason    = (string) ($request->input('lock_reason') ?? '');
        $lockedCount   = 0;
        $lockedCodes   = [];
        $notifyUsers   = collect(); // keyed by user id to deduplicate

        foreach ($templates as $template) {
            if (!$template->isLocked()) {
                $template->lock(Auth::id(), $lockReason);
                $lockedCount++;
                $lockedCodes[] = $template->template_code;

                foreach ($template->assignedUsers as $user) {
                    $notifyUsers->put($user->id, $user);
                }
            }
        }

        if ($lockedCount > 0 && $notifyUsers->isNotEmpty()) {
            $formTitle    = $form->form_title ?? 'Form';
            $templateList = implode(', ', $lockedCodes);
            $reasonLine   = $lockReason !== ''
                ? "\nReason: {$lockReason}"
                : '';

            $message = "The form \"{$formTitle}\" has been locked by the administrator."
                . " The following template(s) are now inaccessible for submissions and edits: {$templateList}."
                . $reasonLine
                . " Please contact the administrator for more information.";

            foreach ($notifyUsers as $user) {
                $user->notify(new DeadlineReminderNotification(
                    title: "Form Locked — {$formTitle}",
                    message: $message,
                    deadline: now()->toDateString(),
                    priority: 'warning',
                ));
            }
        }

        return back()->with('success', "Form locked successfully. {$lockedCount} template(s) have been locked.");
    }

    /**
     * Unlock all templates contained in the specified form.
     */
    public function unlockForm(Form $form)
    {
        if (!Auth::user()->isSuperAdmin()) {
            abort(403);
        }

        $templates = $form->templates;

        if ($templates->isEmpty()) {
            return back()->with('info', 'This form has no templates to unlock.');
        }

        $unlockedCount = 0;
        $unlockedCodes = [];
        $notifyUsers   = collect();

        foreach ($templates as $template) {
            if (!$template->isLocked()) {
                continue;
            }
            $unlockedCodes[] = $template->template_code;
            foreach ($template->assignedUsers as $user) {
                $notifyUsers->put($user->id, $user);
            }
            $template->unlock();
            $unlockedCount++;
        }

        if ($unlockedCount > 0 && $notifyUsers->isNotEmpty()) {
            $formTitle     = $form->form_title ?? 'Form';
            $templateList  = implode(', ', $unlockedCodes);
            $message       = "The form \"{$formTitle}\" has been unlocked by the administrator."
                . " The following template(s) are available again for submissions and edits: {$templateList}."
                . ' You may resume work as normal.';

            foreach ($notifyUsers as $user) {
                $user->notify(new DeadlineReminderNotification(
                    title: "Form Unlocked — {$formTitle}",
                    message: $message,
                    deadline: now()->toDateString(),
                    priority: 'info',
                ));
            }
        }

        return back()->with('success', "Form unlocked successfully. {$unlockedCount} template(s) have been unlocked.");
    }

    /**
     * Remove the specified form
     */
    public function destroy(Form $form)
    {
        $user = Auth::user();
        
        // Check permissions - Super Admin can delete all forms
        if (!$user->isSuperAdmin()) {
            if (!$user->canCreateForms()) {
                abort(403, 'You do not have permission to delete forms.');
            }
            if ($form->campus_code !== $user->campus_code && $form->created_by !== $user->id) {
                abort(403, 'You do not have permission to delete this form.');
            }
        }
        
        try {
            DB::beginTransaction();
            $formTitle = $form->form_title;

            // Cascade delete: Form is root - delete all Templates and their Submissions (including under review)
            $templateIds = $form->templates()->pluck('id')->toArray();
            $submissionIds = Submission::where(function ($q) use ($form, $templateIds) {
                $q->where('form_id', $form->id);
                if (!empty($templateIds)) {
                    $q->orWhereIn('template_id', $templateIds);
                }
            })->pluck('id')->filter()->values()->all();

            if (!empty($submissionIds)) {
                Approval::whereIn('submission_id', $submissionIds)->delete();
                Submission::whereIn('id', $submissionIds)->delete();
            }

            $form->delete();
            DB::commit();

            // If deleted from settings page, redirect back to settings
            if (request()->headers->get('referer') && str_contains(request()->headers->get('referer'), '/settings')) {
                return redirect()->route('super-admin.settings.index')
                    ->with('success', "Form '{$formTitle}' and all its templates and submissions have been deleted.");
            }
            
            return redirect()->route('super-admin.templates.index', ['tab' => 'forms'])
                ->with('success', 'Form and all its templates and submissions have been deleted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to delete form: ' . $e->getMessage());
        }
    }
}