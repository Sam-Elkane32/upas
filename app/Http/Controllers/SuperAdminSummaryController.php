<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Submission;
use App\Models\Campus;
use App\Models\Template;
use App\Services\RollupService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SuperAdminSummaryExport;

/**
 * SuperAdminSummaryController
 * 
 * Handles Summary of Accomplishments preview and exports
 * NEW CONTROLLER - Does not modify existing export functionality
 */
class SuperAdminSummaryController extends Controller
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
     * Preview Summary of Accomplishments
     * Shows formatted preview before export
     */
    public function preview(Request $request)
    {
        $data = $this->getSummaryData($request);
        
        return view('super-admin.summary.preview', $data);
    }

    /**
     * Export Summary as PDF
     * Matches the sample PDF format exactly
     */
    public function exportPdf(Request $request)
    {
        $data = $this->getSummaryData($request);
        
        $pdf = Pdf::loadView('super-admin.summary.pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('uaps-summary-accomplishments-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Export Summary as Excel
     * Mimics PDF layout in spreadsheet form
     */
    public function exportExcel(Request $request)
    {
        $data = $this->getSummaryData($request);
        
        return Excel::download(
            new SuperAdminSummaryExport($data),
            'uaps-summary-accomplishments-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Get Summary Data
     * Compiles all approved accomplishments with SG/KRA/KPI statistics
     * 
     * @param Request $request
     * @return array
     */
    private function getSummaryData(Request $request)
    {
        // Forms with kra_kpi_data use the same VPASS engine as /forms/{id} (all submission statuses + template finalize).
        // Legacy submission-only forms stay on Approved / Pending Review rows only.
        $vpassFormIds = $this->formIdsWithStructuredKraKpi();
        $approvedSubmissions = $this->getApprovedSubmissions($vpassFormIds);
        $structuredData = $this->buildStructuredData($approvedSubmissions);
        $breakdownLegacy = $this->calculateBreakdownStatistics($structuredData);
        $breakdownVpass = $this->buildVpassFormBreakdown($vpassFormIds);

        $overallBreakdown = $breakdownLegacy['overall_breakdown'];
        $this->mergeOverallBreakdown($overallBreakdown, $breakdownVpass['overall_breakdown']);
        $kraSummary = $this->mergeKraSummaries($breakdownLegacy['kra_summary'], $breakdownVpass['kra_summary']);
        $workUnitSummary = $this->mergeWorkUnitSummaries($breakdownLegacy['work_unit_summary'], $breakdownVpass['work_unit_summary']);

        $universityStats = $this->calculateUniversityStatsMerged(
            $approvedSubmissions,
            $overallBreakdown,
            $kraSummary,
            $vpassFormIds,
            (int) ($breakdownVpass['vpass_kpi_rows'] ?? 0)
        );
        $campusStats = $this->calculateCampusStats($approvedSubmissions);

        $this->addStatusMapsToStructuredData($structuredData, $breakdownLegacy['kpi_status_map']);

        $extendedMerged = $this->emptyExtendedOverall();
        $legacyExt = $this->accumulateLegacyExtendedStatistics($structuredData);
        $this->mergeExtendedOverall($extendedMerged, $legacyExt['extended_overall']);
        $this->mergeExtendedOverall($extendedMerged, $breakdownVpass['extended_overall'] ?? $this->emptyExtendedOverall());

        $officeMap = $breakdownVpass['extended_office_sg_map'] ?? [];
        $this->mergeExtendedOfficeSgMaps($officeMap, $legacyExt['extended_office_sg_map'] ?? []);

        $officeSummaryBySg = $this->buildOfficeSummaryBySgFromMap($officeMap);
        $scorecardMatrix = $this->buildScorecardPerformanceMatrix($officeMap);
        $contributingFormTitles = $this->distinctContributingFormTitles($vpassFormIds, $approvedSubmissions);

        return [
            'structured_data' => $structuredData,
            'university_stats' => $universityStats,
            'campus_stats' => $campusStats,
            'overall_breakdown' => $overallBreakdown,
            'kra_summary' => $kraSummary,
            'work_unit_summary' => $workUnitSummary,
            'kpi_status_map' => $breakdownLegacy['kpi_status_map'],
            'user' => Auth::user(),
            'extended_overall' => $extendedMerged,
            'office_summary_by_sg' => $officeSummaryBySg,
            'scorecard_performance_matrix' => $scorecardMatrix,
            'contributing_form_titles' => $contributingFormTitles,
        ];
    }

    /**
     * Organizational form title(s) (e.g. OVPASS) — distinct from KPI responsible work units (CI, CED).
     *
     * @param  Collection<int, int>  $vpassFormIds
     * @param  \Illuminate\Database\Eloquent\Collection<int, Submission>  $approvedSubmissions
     * @return list<string>
     */
    private function distinctContributingFormTitles(Collection $vpassFormIds, $approvedSubmissions): array
    {
        $out = [];
        if ($vpassFormIds->isNotEmpty()) {
            $forms = Form::query()
                ->whereIn('id', $vpassFormIds->all())
                ->get(['id', 'form_title', 'division']);
            foreach ($forms as $f) {
                $s = $this->summaryFormOrganizationalLabel($f);
                if ($s !== '') {
                    $out[] = $s;
                }
            }
        }
        foreach ($approvedSubmissions as $sub) {
            if (! $sub instanceof Submission) {
                continue;
            }
            $form = $sub->relationLoaded('form') ? $sub->form : null;
            if ($form instanceof Form) {
                $s = $this->summaryFormOrganizationalLabel($form);
                if ($s !== '') {
                    $out[] = $s;
                }
            }
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /**
     * Organizational label for summary context (matches Form Details: form_title, optional division).
     */
    private function summaryFormOrganizationalLabel(Form $form): string
    {
        $title = $this->normalizeSummaryOrganizationalTitle(trim((string) ($form->form_title ?? '')));
        $division = $this->normalizeSummaryDivisionCode(trim((string) ($form->division ?? '')));

        $out = '';
        if ($title !== '' && $division !== '') {
            if (strcasecmp($title, $division) === 0) {
                $out = $title;
            } elseif (preg_match('/\(([^()]+)\)\s*$/u', $title, $m) && strcasecmp(trim($m[1]), $division) === 0) {
                $out = $title;
            } else {
                $out = $title.' — '.$division;
            }
        } elseif ($title !== '') {
            $out = $title;
        } elseif ($division !== '') {
            $out = $division;
        }

        return $this->expandOypassSummaryDisplayLabel($out);
    }

    /**
     * Ensure summary shows the full organizational name for OVPASS when the DB only has the acronym or title without (OVPASS).
     *
     * @param  string  $label  One contributing label; may contain " · " between multiple forms.
     */
    private function expandOypassSummaryDisplayLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }
        if (str_contains($label, ' · ')) {
            $parts = array_map(fn (string $p) => $this->expandOypassSummaryDisplayLabel(trim($p)), explode(' · ', $label));

            return implode(' · ', $parts);
        }

        if (preg_match('/^(OVPASS|VPASS)$/i', $label)) {
            return 'Office of the Vice President for Academic and Student Services (OVPASS)';
        }
        if (preg_match('/^Office of the Vice President for Academic and Student Services\s*$/i', $label)) {
            return 'Office of the Vice President for Academic and Student Services (OVPASS)';
        }

        return $label;
    }

    /**
     * Legacy data sometimes stores the acronym as "VPASS"; display uses "OVPASS" (Office of the Vice President …).
     */
    private function normalizeSummaryDivisionCode(string $division): string
    {
        if ($division === '') {
            return '';
        }
        if (strcasecmp($division, 'VPASS') === 0) {
            return 'OVPASS';
        }

        return $division;
    }

    /**
     * Fix "(VPASS)" in stored titles to "(OVPASS)" when the leading O was omitted in the acronym.
     */
    private function normalizeSummaryOrganizationalTitle(string $title): string
    {
        if ($title === '') {
            return '';
        }

        return (string) preg_replace('/\(VPASS\)/i', '(OVPASS)', $title);
    }

    /**
     * Get approved submissions
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    /**
     * @param  Collection<int, int>  $excludeFormIds  Forms aggregated via VPASS (avoid double-counting).
     */
    private function getApprovedSubmissions(Collection $excludeFormIds)
    {
        $q = Submission::with(['template', 'submitter', 'approval', 'form'])
            ->whereIn('status', ['Approved', 'Pending Review'])
            ->where(function ($q) {
                $q->where('is_draft', false)->orWhereNull('is_draft');
            });

        if ($excludeFormIds->isNotEmpty()) {
            $ids = $excludeFormIds->values()->all();
            $q->where(function ($q2) use ($ids) {
                $q2->whereNull('form_id')->orWhereNotIn('form_id', $ids);
            });
        }

        return $q->orderBy('sg_code')
            ->orderBy('kra_title')
            ->orderBy('kpi_title')
            ->get();
    }

    /**
     * @return Collection<int, int>
     */
    private function formIdsWithStructuredKraKpi(): Collection
    {
        return Form::query()
            ->whereNotNull('kra_kpi_data')
            ->get(['id', 'kra_kpi_data'])
            ->filter(function (Form $form) {
                $d = $form->kra_kpi_data;
                if (is_string($d)) {
                    $d = json_decode($d, true) ?? [];
                }

                return is_array($d) && count($d) > 0;
            })
            ->pluck('id');
    }

    /**
     * @param  Collection<int, int>  $formIds
     * @return array{overall_breakdown: array, kra_summary: Collection, work_unit_summary: Collection, vpass_kpi_rows: int, extended_overall: array, extended_office_sg_map: array<string, array<string, mixed>>}
     */
    private function buildVpassFormBreakdown(Collection $formIds): array
    {
        $emptyOverall = [
            'total_kpis' => 0,
            'no_target' => 0,
            'no_accomplishment' => 0,
            'below_target' => 0,
            'met_target' => 0,
            'above_target' => 0,
        ];

        if ($formIds->isEmpty()) {
            return [
                'overall_breakdown' => $emptyOverall,
                'kra_summary' => collect(),
                'work_unit_summary' => collect(),
                'vpass_kpi_rows' => 0,
                'extended_overall' => $this->emptyExtendedOverall(),
                'extended_office_sg_map' => [],
            ];
        }

        /** @var FormController $formController */
        $formController = app(FormController::class);
        $overall = $emptyOverall;
        $kraMap = [];
        $wuMap = [];
        $extendedOverall = $this->emptyExtendedOverall();
        $officeSgMap = [];

        $forms = Form::query()->whereIn('id', $formIds->all())->get();

        foreach ($forms as $form) {
            if (! $form instanceof Form) {
                continue;
            }
            $metrics = $formController->vpassKpiMetricsForSummary($form);
            if (! is_array($metrics) || $metrics === []) {
                continue;
            }

            $kraKpiData = $form->kra_kpi_data;
            if (is_string($kraKpiData)) {
                $kraKpiData = json_decode($kraKpiData, true) ?? [];
            }
            if (! is_array($kraKpiData)) {
                continue;
            }

            $sgCode = trim((string) ($form->sg_code ?? '')) ?: 'Unknown SG';

            foreach ($kraKpiData as $kraIndex => $kraData) {
                if (! is_array($kraData)) {
                    continue;
                }
                $kraTitle = (string) ($kraData['kra_title'] ?? '');
                $kpis = $kraData['kpis'] ?? [];
                foreach ($kpis as $kpiIndex => $kpi) {
                    if (! is_array($kpi)) {
                        continue;
                    }
                    $key = "{$kraIndex}_{$kpiIndex}";
                    if (! isset($metrics[$key])) {
                        continue;
                    }
                    $m = $metrics[$key];
                    $targetQ1 = (float) ($kpi['target_q1'] ?? 0);
                    $targetQ2 = (float) ($kpi['target_q2'] ?? 0);
                    $targetQ3 = (float) ($kpi['target_q3'] ?? 0);
                    $targetQ4 = (float) ($kpi['target_q4'] ?? 0);
                    $targetTotal = (float) ($kpi['target_total'] ?? ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4));
                    $accompTotal = (float) ($m['accomp_total'] ?? 0);

                    $category = $this->classifyTargetAccompCategory($targetTotal, $accompTotal);

                    $overall['total_kpis']++;
                    $overall[$category]++;

                    $kraKey = $sgCode.'|'.$kraTitle;
                    if (! isset($kraMap[$kraKey])) {
                        $kraMap[$kraKey] = [
                            'sg_code' => $sgCode,
                            'kra_title' => $kraTitle,
                            'total_kpis' => 0,
                            'no_target' => 0,
                            'no_accomplishment' => 0,
                            'below_target' => 0,
                            'met_target' => 0,
                            'above_target' => 0,
                        ];
                    }
                    $kraMap[$kraKey]['total_kpis']++;
                    $kraMap[$kraKey][$category]++;

                    $wu = isset($kpi['responsible_work_units']) ? trim((string) $kpi['responsible_work_units']) : '';
                    if ($wu === '') {
                        $wu = trim((string) ($form->responsible_unit ?? ''));
                    }
                    if ($wu === '') {
                        $wu = 'Unspecified';
                    }
                    if (! isset($wuMap[$wu])) {
                        $wuMap[$wu] = [
                            'work_unit' => $wu,
                            'total_kpis' => 0,
                            'no_target' => 0,
                            'no_accomplishment' => 0,
                            'below_target' => 0,
                            'met_target' => 0,
                            'above_target' => 0,
                        ];
                    }
                    $wuMap[$wu]['total_kpis']++;
                    $wuMap[$wu][$category]++;

                    $extBucket = $this->classifyExtendedScorecardBucket($targetQ1, $targetQ2, $targetQ3, $targetQ4, $targetTotal, $accompTotal);
                    $this->incrementExtendedOverall($extendedOverall, $extBucket);

                    foreach ($this->splitResponsibleWorkUnits($wu) as $wuPart) {
                        $this->incrementExtendedOfficeSgRow($officeSgMap, $sgCode, $wuPart, $extBucket);
                    }
                }
            }
        }

        return [
            'overall_breakdown' => $overall,
            'kra_summary' => collect(array_values($kraMap))->sortBy(['sg_code', 'kra_title'])->values(),
            'work_unit_summary' => collect(array_values($wuMap))->sortBy('work_unit')->values(),
            'vpass_kpi_rows' => $overall['total_kpis'],
            'extended_overall' => $extendedOverall,
            'extended_office_sg_map' => $officeSgMap,
        ];
    }

    private function mergeOverallBreakdown(array &$into, array $from): void
    {
        foreach (['total_kpis', 'no_target', 'no_accomplishment', 'below_target', 'met_target', 'above_target'] as $k) {
            $into[$k] = ($into[$k] ?? 0) + ($from[$k] ?? 0);
        }
    }

    private function mergeKraSummaries(Collection $a, Collection $b): Collection
    {
        $map = [];
        foreach ($a as $row) {
            $key = $row['sg_code'].'|'.$row['kra_title'];
            $map[$key] = $row;
        }
        foreach ($b as $row) {
            $key = $row['sg_code'].'|'.$row['kra_title'];
            if (! isset($map[$key])) {
                $map[$key] = $row;

                continue;
            }
            foreach (['total_kpis', 'no_target', 'no_accomplishment', 'below_target', 'met_target', 'above_target'] as $f) {
                $map[$key][$f] = ($map[$key][$f] ?? 0) + ($row[$f] ?? 0);
            }
        }

        return collect(array_values($map))->sortBy(['sg_code', 'kra_title'])->values();
    }

    private function mergeWorkUnitSummaries(Collection $a, Collection $b): Collection
    {
        $map = [];
        foreach ($a as $row) {
            $key = $row['work_unit'];
            $map[$key] = $row;
        }
        foreach ($b as $row) {
            $key = $row['work_unit'];
            if (! isset($map[$key])) {
                $map[$key] = $row;

                continue;
            }
            foreach (['total_kpis', 'no_target', 'no_accomplishment', 'below_target', 'met_target', 'above_target'] as $f) {
                $map[$key][$f] = ($map[$key][$f] ?? 0) + ($row[$f] ?? 0);
            }
        }

        return collect(array_values($map))->sortBy('work_unit')->values();
    }

    /**
     * VPASS-style extended buckets (aligned with official summary columns). Kept separate from legacy five-bucket totals.
     *
     * @return array<string, int|float>
     */
    private function emptyExtendedOverall(): array
    {
        return [
            'total_kpis' => 0,
            'total_with_targets' => 0,
            'above_target' => 0,
            'met_target' => 0,
            'below_target' => 0,
            'no_accomplishment_with_target' => 0,
            'accomplishment_no_target' => 0,
            'no_target_q12' => 0,
            'no_target_annual' => 0,
        ];
    }

    private function incrementExtendedOverall(array &$ext, string $bucket): void
    {
        $ext['total_kpis']++;
        if (in_array($bucket, ['above_target', 'met_target', 'below_target', 'no_accomplishment_with_target'], true)) {
            $ext['total_with_targets']++;
        }
        $ext[$bucket] = ($ext[$bucket] ?? 0) + 1;
    }

    /**
     * @param  array<string, int|float>  $into
     * @param  array<string, int|float>  $from
     */
    private function mergeExtendedOverall(array &$into, array $from): void
    {
        foreach (array_keys($this->emptyExtendedOverall()) as $k) {
            $into[$k] = ($into[$k] ?? 0) + ($from[$k] ?? 0);
        }
    }

    /**
     * Mutually exclusive extended bucket per counted row (submission or VPASS KPI).
     */
    private function classifyExtendedScorecardBucket(float $t1, float $t2, float $t3, float $t4, float $targetTotal, float $accompTotal): string
    {
        $eps = 0.02;
        $sumQ = $t1 + $t2 + $t3 + $t4;
        $tt = max($targetTotal, $sumQ);

        if ($tt > $eps) {
            if ($accompTotal <= $eps) {
                return 'no_accomplishment_with_target';
            }
            if ($accompTotal < $tt - $eps) {
                return 'below_target';
            }
            if (abs($accompTotal - $tt) <= max($eps, abs($tt) * 1e-6)) {
                return 'met_target';
            }

            return 'above_target';
        }
        if ($accompTotal > $eps) {
            return 'accomplishment_no_target';
        }
        $h1 = $t1 + $t2;
        $h2 = $t3 + $t4;
        if ($h1 <= $eps && $h2 > $eps) {
            return 'no_target_q12';
        }

        return 'no_target_annual';
    }

    /**
     * @param  array<string, array<string, mixed>>  $into
     * @param  array<string, array<string, mixed>>  $from
     */
    private function mergeExtendedOfficeSgMaps(array &$into, array $from): void
    {
        $bucketFields = [
            'above_target', 'met_target', 'below_target', 'no_accomplishment_with_target',
            'accomplishment_no_target', 'no_target_q12', 'no_target_annual',
        ];
        foreach ($from as $key => $row) {
            if (! isset($into[$key])) {
                $into[$key] = $row;

                continue;
            }
            $into[$key]['total_kpis'] = ($into[$key]['total_kpis'] ?? 0) + ($row['total_kpis'] ?? 0);
            $into[$key]['total_with_targets'] = ($into[$key]['total_with_targets'] ?? 0) + ($row['total_with_targets'] ?? 0);
            foreach ($bucketFields as $f) {
                $into[$key][$f] = ($into[$key][$f] ?? 0) + ($row[$f] ?? 0);
            }
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $officeMap
     * @return list<array<string, mixed>>
     */
    private function buildOfficeSummaryBySgFromMap(array $officeMap): array
    {
        $rows = array_values($officeMap);
        usort($rows, function (array $a, array $b): int {
            $c = strcmp((string) ($a['sg_code'] ?? ''), (string) ($b['sg_code'] ?? ''));
            if ($c !== 0) {
                return $c;
            }

            return strcmp((string) ($a['work_unit'] ?? ''), (string) ($b['work_unit'] ?? ''));
        });

        return $rows;
    }

    /**
     * Roll SG-level office rows into one row per work unit for a balance-scorecard style view.
     *
     * @param  array<string, array<string, mixed>>  $officeMap
     * @return list<array<string, mixed>>
     */
    private function buildScorecardPerformanceMatrix(array $officeMap): array
    {
        $byWu = [];
        foreach ($officeMap as $entry) {
            $wu = (string) ($entry['work_unit'] ?? 'Unspecified');
            if (! isset($byWu[$wu])) {
                $byWu[$wu] = [
                    'work_unit' => $wu,
                    'positive' => 0,
                    'negative' => 0,
                    'total_kpis' => 0,
                ];
            }
            $pos = (int) ($entry['above_target'] ?? 0) + (int) ($entry['met_target'] ?? 0) + (int) ($entry['accomplishment_no_target'] ?? 0);
            $neg = (int) ($entry['below_target'] ?? 0) + (int) ($entry['no_accomplishment_with_target'] ?? 0);
            $byWu[$wu]['positive'] += $pos;
            $byWu[$wu]['negative'] += $neg;
            $byWu[$wu]['total_kpis'] += (int) ($entry['total_kpis'] ?? 0);
        }
        $out = [];
        foreach ($byWu as $agg) {
            $t = max(0, (int) $agg['total_kpis']);
            $out[] = [
                'work_unit' => $agg['work_unit'],
                'positive_count' => $agg['positive'],
                'negative_count' => $agg['negative'],
                'total_kpis' => $t,
                'on_track_pct' => $t > 0 ? round(($agg['positive'] / $t) * 100, 2) : 0.0,
                'off_track_pct' => $t > 0 ? round(($agg['negative'] / $t) * 100, 2) : 0.0,
            ];
        }
        usort($out, fn (array $a, array $b): int => strcmp((string) $a['work_unit'], (string) $b['work_unit']));

        return $out;
    }

    /**
     * One scorecard row per office. Splits "CI, CED, Registrar" so each division gets the same KPI attributed (shared responsibility).
     *
     * @param  array<string, array<string, mixed>>  $officeMap
     */
    private function incrementExtendedOfficeSgRow(array &$officeMap, string $sgCode, string $workUnitSingle, string $extBucket): void
    {
        $sgWuKey = $sgCode."\x1e".$workUnitSingle;
        if (! isset($officeMap[$sgWuKey])) {
            $officeMap[$sgWuKey] = [
                'sg_code' => $sgCode,
                'work_unit' => $workUnitSingle,
                'total_kpis' => 0,
                'total_with_targets' => 0,
                'above_target' => 0,
                'met_target' => 0,
                'below_target' => 0,
                'no_accomplishment_with_target' => 0,
                'accomplishment_no_target' => 0,
                'no_target_q12' => 0,
                'no_target_annual' => 0,
            ];
        }
        $officeMap[$sgWuKey]['total_kpis']++;
        if (in_array($extBucket, ['above_target', 'met_target', 'below_target', 'no_accomplishment_with_target'], true)) {
            $officeMap[$sgWuKey]['total_with_targets']++;
        }
        $officeMap[$sgWuKey][$extBucket] = ($officeMap[$sgWuKey][$extBucket] ?? 0) + 1;
    }

    /**
     * @return list<string>
     */
    private function splitResponsibleWorkUnits(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return ['Unspecified'];
        }
        $parts = preg_split('/\s*[,;|]\s*|\s+and\s+/i', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p !== '') {
                $out[] = $p;
            }
        }
        $out = array_values(array_unique($out));

        return $out !== [] ? $out : ['Unspecified'];
    }

    /**
     * @return array{extended_overall: array<string, int|float>, extended_office_sg_map: array<string, array<string, mixed>>}
     */
    private function accumulateLegacyExtendedStatistics(array $structuredData): array
    {
        $ext = $this->emptyExtendedOverall();
        $officeMap = [];
        $rollup = app(RollupService::class);

        foreach ($structuredData as $sg) {
            $sgCode = (string) ($sg['sg_code'] ?? 'Unknown SG');
            foreach ($sg['kras'] ?? [] as $kra) {
                $kraTitle = (string) ($kra['kra_title'] ?? '');
                foreach ($kra['kpis'] ?? [] as $kpi) {
                    foreach ($kpi['submissions'] ?? [] as $submission) {
                        if (! $submission instanceof Submission) {
                            continue;
                        }
                        $targetTotal = $this->resolveTargetTotalForSummary($submission);
                        [$t1, $t2, $t3, $t4] = $this->resolveTargetQuartersForSummary($submission);
                        $accompTotal = $this->resolveAccompTotalForSummary($submission, $rollup);
                        $bucket = $this->classifyExtendedScorecardBucket($t1, $t2, $t3, $t4, $targetTotal, $accompTotal);
                        $this->incrementExtendedOverall($ext, $bucket);

                        $wu = $this->getWorkUnit($submission);
                        foreach ($this->splitResponsibleWorkUnits($wu) as $wuPart) {
                            $this->incrementExtendedOfficeSgRow($officeMap, $sgCode, $wuPart, $bucket);
                        }
                    }
                }
            }
        }

        return [
            'extended_overall' => $ext,
            'extended_office_sg_map' => $officeMap,
        ];
    }

    /**
     * Quarterly targets for extended VPASS-style classification (submission + form KPI definition).
     *
     * @return array{0: float, 1: float, 2: float, 3: float}
     */
    private function resolveTargetQuartersForSummary(Submission $submission): array
    {
        $z = 0.0;
        if ($submission->approval) {
            $a = $submission->approval;
            $t1 = (float) ($a->target_q1 ?? 0);
            $t2 = (float) ($a->target_q2 ?? 0);
            $t3 = (float) ($a->target_q3 ?? 0);
            $t4 = (float) ($a->target_q4 ?? 0);
            if (abs($t1 + $t2 + $t3 + $t4) > 1e-9) {
                return [$t1, $t2, $t3, $t4];
            }
        }

        $form = $submission->relationLoaded('form') ? $submission->form : $submission->form()->first();
        if (! $form instanceof Form) {
            return [$z, $z, $z, $z];
        }

        $kraKpi = $form->kra_kpi_data ?? [];
        if (is_string($kraKpi)) {
            $kraKpi = json_decode($kraKpi, true) ?? [];
        }
        if (! is_array($kraKpi)) {
            return [$z, $z, $z, $z];
        }

        $subKra = strtolower(preg_replace('/\s+/', ' ', trim((string) ($submission->kra_title ?? ''))));
        $subKpi = strtolower(preg_replace('/\s+/', ' ', trim((string) ($submission->kpi_title ?? ''))));

        foreach ($kraKpi as $kra) {
            if (! is_array($kra)) {
                continue;
            }
            $kraTitle = strtolower(preg_replace('/\s+/', ' ', trim((string) ($kra['kra_title'] ?? ''))));
            if ($subKra !== '' && $kraTitle !== '' && ! str_contains($kraTitle, $subKra) && ! str_contains($subKra, $kraTitle)) {
                continue;
            }
            foreach ($kra['kpis'] ?? [] as $kpi) {
                if (! is_array($kpi) || ! $this->kpiDefinitionMatchesSubmissionKpiTitle($kpi, $subKpi)) {
                    continue;
                }

                return [
                    (float) ($kpi['target_q1'] ?? 0),
                    (float) ($kpi['target_q2'] ?? 0),
                    (float) ($kpi['target_q3'] ?? 0),
                    (float) ($kpi['target_q4'] ?? 0),
                ];
            }
        }

        return [$z, $z, $z, $z];
    }

    /**
     * @param  Collection<int, int>  $vpassFormIds
     */
    private function calculateUniversityStatsMerged($approvedSubmissions, array $overallBreakdown, Collection $kraSummary, Collection $vpassFormIds, int $vpassKpiRows): array
    {
        $vpassTemplatesWithFinalize = $this->countVpassTemplatesWithFinalizedAccomp($vpassFormIds);
        // KPI table can fill from campus submissions before template finalize; still show reporting activity in the header.
        $vpassReportingBump = $vpassKpiRows > 0 ? max($vpassTemplatesWithFinalize, 1) : $vpassTemplatesWithFinalize;

        return [
            'total_campuses' => Campus::where('is_active', true)->count(),
            'total_approved_submissions' => $approvedSubmissions->count() + $vpassReportingBump,
            'total_fully_approved_submissions' => $approvedSubmissions->where('status', 'Approved')->count(),
            'total_sgs' => $kraSummary->pluck('sg_code')->filter()->unique()->count(),
            'total_kras' => $kraSummary->count(),
            'total_kpis' => $overallBreakdown['total_kpis'],
            'date_generated' => now()->format('F d, Y h:i A'),
        ];
    }

    /**
     * Templates on VPASS forms that have Super Admin finalized Q1–Q4 in fields_json.
     *
     * @param  Collection<int, int>  $formIds
     */
    private function countVpassTemplatesWithFinalizedAccomp(Collection $formIds): int
    {
        if ($formIds->isEmpty()) {
            return 0;
        }

        $rows = DB::table('templates')
            ->whereIn('form_id', $formIds->all())
            ->whereNotNull('fields_json')
            ->pluck('fields_json');

        $n = 0;
        foreach ($rows as $raw) {
            if (! is_string($raw) || $raw === '') {
                continue;
            }
            $fj = json_decode($raw, true);
            if (! is_array($fj) || ! isset($fj['finalized_accomp']) || ! is_array($fj['finalized_accomp'])) {
                continue;
            }
            $fa = $fj['finalized_accomp'];
            $sum = abs((float) ($fa['q1'] ?? 0)) + abs((float) ($fa['q2'] ?? 0))
                + abs((float) ($fa['q3'] ?? 0)) + abs((float) ($fa['q4'] ?? 0));
            if ($sum > 1e-9) {
                $n++;
            }
        }

        return $n;
    }
    
    /**
     * Build structured data grouped by SG/KRA/KPI
     * 
     * @param \Illuminate\Database\Eloquent\Collection $approvedSubmissions
     * @return array
     */
    private function buildStructuredData($approvedSubmissions)
    {
        $groupedBySG = $approvedSubmissions->groupBy('sg_code');
        $structuredData = [];
        
        foreach ($groupedBySG as $sgCode => $sgSubmissions) {
            $kraGroups = $sgSubmissions->groupBy('kra_title');
            $kraData = [];
            
            foreach ($kraGroups as $kraTitle => $kraSubmissions) {
                $kpiGroups = $kraSubmissions->groupBy('kpi_title');
                $kpiData = [];
                
                foreach ($kpiGroups as $kpiTitle => $kpiSubmissions) {
                    $kpiData[] = [
                        'kpi_title' => $kpiTitle,
                        'submissions' => $kpiSubmissions,
                        'total_submissions' => $kpiSubmissions->count(),
                        'campuses' => $kpiSubmissions->pluck('campus')->unique()->count(),
                    ];
                }
                
                $kraData[] = [
                    'kra_title' => $kraTitle,
                    'kpis' => $kpiData,
                    'total_submissions' => $kraSubmissions->count(),
                ];
            }
            
            $structuredData[] = [
                'sg_code' => $sgCode,
                'kras' => $kraData,
                'total_submissions' => $sgSubmissions->count(),
            ];
        }
        
        return $structuredData;
    }
    
    /**
     * Calculate campus statistics
     * 
     * @param \Illuminate\Database\Eloquent\Collection $approvedSubmissions
     * @return array
     */
    private function calculateCampusStats($approvedSubmissions)
    {
        $campusStats = [];
        $campusGroups = $approvedSubmissions->groupBy('campus');
        
        foreach ($campusGroups as $campusName => $campusSubmissions) {
            $campusStats[] = [
                'campus_name' => $campusName,
                'total_submissions' => $campusSubmissions->count(),
                'unique_kpis' => $campusSubmissions->pluck('kpi_title')->unique()->count(),
            ];
        }
        
        return $campusStats;
    }
    
    /**
     * Calculate breakdown statistics
     * 
     * @param array $structuredData
     * @return array
     */
    private function calculateBreakdownStatistics(array $structuredData)
    {
        $overallBreakdown = [
            'total_kpis'        => 0,
            'no_target'         => 0,
            'no_accomplishment' => 0,
            'below_target'      => 0,
            'met_target'        => 0,
            'above_target'      => 0,
        ];

        $kraSummaryMap = [];
        $workUnitSummaryMap = [];
        $kpiStatusMap = [];

        foreach ($structuredData as $sg) {
            foreach ($sg['kras'] as $kra) {
                $kraKey = $sg['sg_code'] . '|' . $kra['kra_title'];

                if (!isset($kraSummaryMap[$kraKey])) {
                    $kraSummaryMap[$kraKey] = [
                        'sg_code'         => $sg['sg_code'],
                        'kra_title'       => $kra['kra_title'],
                        'total_kpis'      => 0,
                        'no_target'       => 0,
                        'no_accomplishment' => 0,
                        'below_target'    => 0,
                        'met_target'      => 0,
                        'above_target'    => 0,
                    ];
                }

                foreach ($kra['kpis'] as $kpi) {
                    $kpiKey = $sg['sg_code'] . '|' . $kra['kra_title'] . '|' . $kpi['kpi_title'];

                    if (!isset($kpiStatusMap[$kpiKey])) {
                        $kpiStatusMap[$kpiKey] = [
                            'no_target'         => 0,
                            'no_accomplishment' => 0,
                            'below_target'      => 0,
                            'met_target'        => 0,
                            'above_target'      => 0,
                        ];
                    }

                    foreach ($kpi['submissions'] as $submission) {
                        $category = $this->calculateSubmissionCategory($submission);
                        $unit = $this->getWorkUnit($submission);

                        $overallBreakdown['total_kpis']++;
                        $overallBreakdown[$category]++;

                        $kraSummaryMap[$kraKey]['total_kpis']++;
                        $kraSummaryMap[$kraKey][$category]++;

                        if (!isset($workUnitSummaryMap[$unit])) {
                            $workUnitSummaryMap[$unit] = [
                                'work_unit'       => $unit,
                                'total_kpis'      => 0,
                                'no_target'       => 0,
                                'no_accomplishment' => 0,
                                'below_target'    => 0,
                                'met_target'      => 0,
                                'above_target'    => 0,
                            ];
                        }
                        $workUnitSummaryMap[$unit]['total_kpis']++;
                        $workUnitSummaryMap[$unit][$category]++;

                        $kpiStatusMap[$kpiKey][$category]++;
                    }
                }
            }
        }

        return [
            'overall_breakdown' => $overallBreakdown,
            'kra_summary' => collect(array_values($kraSummaryMap)),
            'work_unit_summary' => collect(array_values($workUnitSummaryMap)),
            'kpi_status_map' => $kpiStatusMap,
        ];
    }
    
    /**
     * Calculate submission category based on target vs accomplishment
     * 
     * @param Submission $submission
     * @return string
     */
    private function calculateSubmissionCategory($submission)
    {
        $rollup = app(RollupService::class);
        $targetTotal = $this->resolveTargetTotalForSummary($submission);
        $accompTotal = $this->resolveAccompTotalForSummary($submission, $rollup);

        return $this->classifyTargetAccompCategory($targetTotal, $accompTotal);
    }

    /**
     * Same buckets as VPASS / Form Details (epsilon for float totals).
     */
    private function classifyTargetAccompCategory(float $targetTotal, float $accompTotal): string
    {
        $eps = 0.02;

        if ($targetTotal <= $eps && $accompTotal <= $eps) {
            return 'no_target';
        }
        if ($targetTotal > $eps && $accompTotal <= $eps) {
            return 'no_accomplishment';
        }
        if ($targetTotal > $eps && $accompTotal > $eps) {
            if ($accompTotal < $targetTotal - $eps) {
                return 'below_target';
            }
            if (abs($accompTotal - $targetTotal) <= max($eps, abs($targetTotal) * 1e-6)) {
                return 'met_target';
            }

            return 'above_target';
        }

        return 'no_target';
    }

    /**
     * Accomplishment total aligned with Form VPASS: finalized_accomp on summary row, then template.fields_json,
     * then approval, then RollupService.
     */
    private function resolveAccompTotalForSummary(Submission $submission, RollupService $rollup): float
    {
        if ($this->submissionTableDataHasFinalizedAccomp($submission)) {
            $ex = $rollup->extractFromSubmission($submission);

            return $this->sumQuarterlyAccomp($ex);
        }

        $fromTemplate = $this->finalizedAccompTotalsFromTemplate($submission->template);
        if ($fromTemplate !== null) {
            return $fromTemplate;
        }

        if ($submission->approval) {
            $a = $submission->approval;
            $t = (float) ($a->accomp_total ?? 0);
            if ($t > 0) {
                return $t;
            }

            return (float) $a->accomp_q1 + (float) $a->accomp_q2 + (float) $a->accomp_q3 + (float) $a->accomp_q4;
        }

        $ex = $rollup->extractFromSubmission($submission);

        return $this->sumQuarterlyAccomp($ex);
    }

    /**
     * @param  array<string, mixed>  $extracted
     */
    private function sumQuarterlyAccomp(array $extracted): float
    {
        return (float) ($extracted['accomp_q1'] ?? 0)
            + (float) ($extracted['accomp_q2'] ?? 0)
            + (float) ($extracted['accomp_q3'] ?? 0)
            + (float) ($extracted['accomp_q4'] ?? 0);
    }

    private function finalizedAccompTotalsFromTemplate(?Template $template): ?float
    {
        if (! $template) {
            return null;
        }
        $fj = $template->fields_json;
        if (! is_array($fj) || ! isset($fj['finalized_accomp']) || ! is_array($fj['finalized_accomp'])) {
            return null;
        }
        $fa = $fj['finalized_accomp'];
        $q1 = (float) ($fa['q1'] ?? 0);
        $q2 = (float) ($fa['q2'] ?? 0);
        $q3 = (float) ($fa['q3'] ?? 0);
        $q4 = (float) ($fa['q4'] ?? 0);
        $sum = $q1 + $q2 + $q3 + $q4;

        return $sum > 1e-9 ? $sum : null;
    }

    /**
     * Target total: prefer QA approval targets; else KPI definition on the parent form (Balance Scorecard / VPASS).
     */
    private function resolveTargetTotalForSummary(Submission $submission): float
    {
        if ($submission->approval) {
            $a = $submission->approval;
            $t = (float) ($a->target_total ?? 0);
            if ($t > 0) {
                return $t;
            }
            $t = (float) $a->target_q1 + (float) $a->target_q2 + (float) $a->target_q3 + (float) $a->target_q4;
            if ($t > 0) {
                return $t;
            }
        }

        $form = $submission->relationLoaded('form') ? $submission->form : $submission->form()->first();
        if (! $form instanceof Form) {
            return 0.0;
        }

        $kraKpi = $form->kra_kpi_data ?? [];
        if (is_string($kraKpi)) {
            $kraKpi = json_decode($kraKpi, true) ?? [];
        }
        if (! is_array($kraKpi)) {
            return 0.0;
        }

        $subKra = strtolower(preg_replace('/\s+/', ' ', trim((string) ($submission->kra_title ?? ''))));
        $subKpi = strtolower(preg_replace('/\s+/', ' ', trim((string) ($submission->kpi_title ?? ''))));

        foreach ($kraKpi as $kra) {
            if (! is_array($kra)) {
                continue;
            }
            $kraTitle = strtolower(preg_replace('/\s+/', ' ', trim((string) ($kra['kra_title'] ?? ''))));
            if ($subKra !== '' && $kraTitle !== '' && ! str_contains($kraTitle, $subKra) && ! str_contains($subKra, $kraTitle)) {
                continue;
            }
            foreach ($kra['kpis'] ?? [] as $kpi) {
                if (! is_array($kpi) || ! $this->kpiDefinitionMatchesSubmissionKpiTitle($kpi, $subKpi)) {
                    continue;
                }
                $tq1 = (float) ($kpi['target_q1'] ?? 0);
                $tq2 = (float) ($kpi['target_q2'] ?? 0);
                $tq3 = (float) ($kpi['target_q3'] ?? 0);
                $tq4 = (float) ($kpi['target_q4'] ?? 0);

                return (float) ($kpi['target_total'] ?? ($tq1 + $tq2 + $tq3 + $tq4));
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $kpi
     */
    private function kpiDefinitionMatchesSubmissionKpiTitle(array $kpi, string $subKpiNorm): bool
    {
        if ($subKpiNorm === '') {
            return false;
        }
        $num = trim((string) ($kpi['number'] ?? ''));
        $title = trim((string) ($kpi['title'] ?? ''));
        $full = strtolower($num !== '' ? $num.' - '.$title : $title);
        if ($full !== '' && (str_contains($subKpiNorm, $full) || str_contains($full, $subKpiNorm))) {
            return true;
        }
        if ($num !== '' && preg_match('/^'.preg_quote($num, '/').'(\s|-|–|\.|:|,|$)/u', $subKpiNorm)) {
            return true;
        }

        return false;
    }

    private function submissionTableDataHasFinalizedAccomp(Submission $submission): bool
    {
        $tableData = $submission->table_data;
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
     * Get work unit from submission
     * 
     * @param Submission $submission
     * @return string
     */
    private function getWorkUnit($submission)
    {
        // VPASS / balance scorecard CSV rolls up by office codes (e.g. CI, CED) from the KPI row.
        $fromTable = $this->responsibleWorkUnitsFromTableData($submission->table_data);
        if ($fromTable !== null) {
            return $fromTable;
        }

        if ($submission->campus) {
            return $submission->campus;
        }
        if (optional($submission->submitter)->campus) {
            return $submission->submitter->campus;
        }
        if (optional($submission->form)->responsible_unit) {
            $formUnit = $submission->form->responsible_unit;
            if (! empty(trim($formUnit))) {
                return $formUnit;
            }
        }

        return 'Unspecified';
    }

    /**
     * @param  mixed  $tableData
     */
    private function responsibleWorkUnitsFromTableData($tableData): ?string
    {
        if (! is_array($tableData)) {
            return null;
        }
        foreach ($tableData as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (is_array($meta) && ($meta['row_type'] ?? '') === 'summary') {
                continue;
            }
            if (isset($row['responsible_work_units'])) {
                $raw = $row['responsible_work_units'];
                if (is_array($raw)) {
                    $raw = implode(', ', array_filter(array_map('trim', $raw)));
                }
                $s = trim((string) $raw);
                if ($s !== '') {
                    return $s;
                }
            }
        }

        return null;
    }
    
    /**
     * Add status maps to structured data
     * 
     * @param array $structuredData
     * @param array $kpiStatusMap
     * @return void
     */
    private function addStatusMapsToStructuredData(array &$structuredData, array $kpiStatusMap)
    {
        foreach ($structuredData as &$sg) {
            foreach ($sg['kras'] as &$kra) {
                foreach ($kra['kpis'] as &$kpi) {
                    $kpiKey = $sg['sg_code'] . '|' . $kra['kra_title'] . '|' . $kpi['kpi_title'];
                    $kpi['status_map'] = $kpiStatusMap[$kpiKey] ?? [
                        'no_target' => 0,
                        'no_accomplishment' => 0,
                        'below_target' => 0,
                        'met_target' => 0,
                        'above_target' => 0,
                    ];
                }
            }
        }
    }
}

