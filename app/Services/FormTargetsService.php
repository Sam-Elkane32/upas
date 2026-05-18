<?php

namespace App\Services;

use App\Models\Form;
use App\Models\Submission;
use App\Models\Template;
use Illuminate\Support\Facades\Log;

/**
 * Resolves target values (Q1–Q4, total) from the Form for a submission.
 * Used so Performance Validation always shows the targets set by Super Admin in Form create/edit.
 */
class FormTargetsService
{
    /**
     * Get target values from the Form for this submission (matching KPI in kra_kpi_data).
     * Returns array with target_q1, target_q2, target_q3, target_q4, target_total; or null if form cannot be resolved.
     */
    public function getForSubmission(Submission $submission): ?array
    {
        if (!$submission->relationLoaded('form')) {
            $submission->load('form');
        }
        if (!$submission->relationLoaded('template')) {
            $submission->load('template');
        }

        $form = $this->resolveForm($submission);
        if (!$form) {
            Log::debug('FormTargetsService: No form resolved', [
                'submission_id' => $submission->id,
                'template_code' => $submission->template_code,
                'template_id' => $submission->template_id,
                'form_id' => $submission->form_id,
            ]);
            return null;
        }

        $tq1 = null;
        $tq2 = null;
        $tq3 = null;
        $tq4 = null;

        $matchingKpi = $this->findMatchingKpiFromForm($submission, $form);

        if (config('app.debug')) {
            $debugData = $this->getKraKpiDataArray($form);
            $firstKra = is_array($debugData) ? ($debugData[0] ?? null) : null;
            $firstKpi = $firstKra && !empty($firstKra['kpis']) ? $firstKra['kpis'][0] : null;
            Log::debug('FormTargetsService: KPI match result', [
                'submission_id' => $submission->id,
                'form_id' => $form->id,
                'submission_kpi_title' => $submission->kpi_title,
                'submission_kra_title' => $submission->kra_title,
                'extracted_kpi_number' => $this->extractKpiNumberFromText($submission->kpi_title ?? ''),
                'first_kra_title' => $firstKra['kra_title'] ?? null,
                'first_kpi_number' => $firstKpi['number'] ?? null,
                'first_kpi_title_preview' => $firstKpi ? mb_substr($firstKpi['title'] ?? '', 0, 80) : null,
                'match_found' => $matchingKpi !== null,
                'matched_targets' => $matchingKpi !== null ? [
                    'target_q1' => $matchingKpi['target_q1'] ?? null,
                    'target_q2' => $matchingKpi['target_q2'] ?? null,
                    'target_q3' => $matchingKpi['target_q3'] ?? null,
                    'target_q4' => $matchingKpi['target_q4'] ?? null,
                ] : null,
            ]);
        }

        if ($matchingKpi !== null) {
            $tq1 = (float) ($matchingKpi['target_q1'] ?? 0);
            $tq2 = (float) ($matchingKpi['target_q2'] ?? 0);
            $tq3 = (float) ($matchingKpi['target_q3'] ?? 0);
            $tq4 = (float) ($matchingKpi['target_q4'] ?? 0);
        }

        $submissionHasKpi = !empty(trim((string) ($submission->kpi_title ?? '')));
        $submissionHasKra = !empty(trim((string) ($submission->kra_title ?? '')));

        // No matching KPI found: never use form-level or sum when submission is for a specific KPI/KRA.
        // Those aggregates (e.g. 342, 66, 332, 137) are wrong for per-KPI validation.
        if ($tq1 === null && $tq2 === null && $tq3 === null && $tq4 === null) {
            if ($submissionHasKpi || $submissionHasKra) {
                Log::debug('FormTargetsService: No KPI match for submission with KPI/KRA – not using form-level or sum', [
                    'submission_id' => $submission->id,
                    'submission_kpi_title' => $submission->kpi_title,
                    'submission_kra_title' => $submission->kra_title,
                ]);
                return null;
            }
            $tq1 = $form->target_q1 !== null ? (float) $form->target_q1 : null;
            $tq2 = $form->target_q2 !== null ? (float) $form->target_q2 : null;
            $tq3 = $form->target_q3 !== null ? (float) $form->target_q3 : null;
            $tq4 = $form->target_q4 !== null ? (float) $form->target_q4 : null;
        }
        $kraKpiDataForSum = $this->getKraKpiDataArray($form);
        if ($tq1 === null && $tq2 === null && $tq3 === null && $tq4 === null && is_array($kraKpiDataForSum)) {
            $totalKpis = $this->countKpisInForm($form);
            if ($totalKpis <= 1 && !$submissionHasKpi && !$submissionHasKra) {
                $tq1 = 0; $tq2 = 0; $tq3 = 0; $tq4 = 0;
                foreach ($kraKpiDataForSum as $kra) {
                    foreach ($kra['kpis'] ?? [] as $kpi) {
                        $tq1 += (float) ($kpi['target_q1'] ?? 0);
                        $tq2 += (float) ($kpi['target_q2'] ?? 0);
                        $tq3 += (float) ($kpi['target_q3'] ?? 0);
                        $tq4 += (float) ($kpi['target_q4'] ?? 0);
                    }
                }
            }
        }

        $tq1 = $tq1 ?? 0;
        $tq2 = $tq2 ?? 0;
        $tq3 = $tq3 ?? 0;
        $tq4 = $tq4 ?? 0;
        $total = $tq1 + $tq2 + $tq3 + $tq4;

        return [
            'target_q1' => $tq1,
            'target_q2' => $tq2,
            'target_q3' => $tq3,
            'target_q4' => $tq4,
            'target_total' => $total,
        ];
    }

    /**
     * Get target values from the Form for a Template (matching KPI/KRA in kra_kpi_data).
     * This mirrors getForSubmission but uses the template's KPI/KRA text.
     */
    public function getForTemplate(Template $template): ?array
    {
        if (!$template->relationLoaded('form')) {
            $template->load('form');
        }
        $form = $template->form;
        if (!$form) {
            return null;
        }

        // Build a fake Submission object so we can reuse the same matching logic
        $submission = new Submission();
        $submission->kpi_title = $template->kpi_title;
        $submission->kra_title = $template->kra_title;
        $submission->template_code = $template->template_code;
        $submission->template_id = $template->id;
        $submission->setRelation('form', $form);
        $submission->setRelation('template', $template);

        $matchingKpi = $this->findMatchingKpiFromForm($submission, $form);
        if ($matchingKpi === null) {
            return null;
        }

        $tq1 = (float) ($matchingKpi['target_q1'] ?? 0);
        $tq2 = (float) ($matchingKpi['target_q2'] ?? 0);
        $tq3 = (float) ($matchingKpi['target_q3'] ?? 0);
        $tq4 = (float) ($matchingKpi['target_q4'] ?? 0);
        $total = $tq1 + $tq2 + $tq3 + $tq4;
        $isPercentage = !empty($matchingKpi['is_percentage'] ?? false);

        return [
            'target_q1' => $tq1,
            'target_q2' => $tq2,
            'target_q3' => $tq3,
            'target_q4' => $tq4,
            'target_total' => $total,
            'is_percentage' => $isPercentage,
        ];
    }

    protected function resolveForm(Submission $submission): ?Form
    {
        if ($submission->form) {
            return $submission->form;
        }
        $template = $submission->template;
        if (!$template && $submission->template_code) {
            $template = Template::where('template_code', $submission->template_code)->first();
            if ($template) {
                $submission->setRelation('template', $template);
            }
        }
        if ($template?->form) {
            return $template->form;
        }
        if ($submission->template_id) {
            return Form::where('template_id', $submission->template_id)->first();
        }
        if ($submission->template_code) {
            return Form::where('template_code', $submission->template_code)->first();
        }
        return null;
    }

    protected function formHasMultipleKpis(Form $form): bool
    {
        return $this->countKpisInForm($form) > 1;
    }

    protected function countKpisInForm(Form $form): int
    {
        $data = $this->getKraKpiDataArray($form);
        if (!is_array($data)) {
            return 0;
        }
        $n = 0;
        foreach ($data as $kra) {
            $n += count($kra['kpis'] ?? []);
        }
        return $n;
    }

    protected function findMatchingKpiFromForm(Submission $submission, Form $form): ?array
    {
        $kraKpiData = $this->getKraKpiDataArray($form);
        if (!is_array($kraKpiData) || empty($kraKpiData)) {
            return null;
        }
        $subKra = $this->normalizeForMatch($submission->kra_title ?? '');
        $subKpi = $this->normalizeForMatch($this->normalizeSubmissionKpiTitle($submission->kpi_title ?? ''));
        $subKpiNumber = $this->extractKpiNumberFromText($submission->kpi_title ?? '');
        if ($subKra === '' && $subKpi === '' && $subKpiNumber === null) {
            return null;
        }

        $subKraStripped = $this->stripKraPrefix($subKra);
        $subKpiStripped = $this->stripKpiPrefix($subKpi);

        foreach ($kraKpiData as $kraIndex => $kra) {
            $kraTitle = $this->normalizeForMatch($kra['kra_title'] ?? '');
            $kraTitleStripped = $this->stripKraPrefix($kraTitle);
            $kraMatches = $subKra === ''
                || $kraTitle === $subKra
                || $kraTitleStripped === $subKraStripped
                || str_contains($kraTitle, $subKra)
                || str_contains($subKra, $kraTitle)
                || str_contains($kraTitleStripped, $subKraStripped)
                || str_contains($subKraStripped, $kraTitleStripped);
            if (!$kraMatches) {
                continue;
            }
            $kpis = $kra['kpis'] ?? [];
            $singleKpi = count($kpis) === 1 ? $kpis[0] : null;

            // If only one KPI in this KRA and submission has any KPI text, use it
            if ($singleKpi !== null && $subKpi !== '') {
                $oneTitle = $this->normalizeForMatch(trim((string) ($singleKpi['title'] ?? '')));
                if ($oneTitle !== '' && $this->titleMatchByPrefix($subKpiStripped, $oneTitle, 25)) {
                    return $singleKpi;
                }
            }

            foreach ($kpis as $kpiIndex => $kpi) {
                $num = trim((string) ($kpi['number'] ?? ''));
                $title = trim((string) ($kpi['title'] ?? ''));
                $full = $num !== '' ? $num . ' - ' . $title : $title;
                $normalizedFull = $this->normalizeForMatch($full);
                $normalizedTitle = $this->normalizeForMatch($title);

                if ($subKpi === '') {
                    return $singleKpi ?? $kpi;
                }
                // Match by number first (form may store number as "1" or 1; submission has "1" from "1 - Title")
                if ($subKpiNumber !== null && $num !== '' && $this->kpiNumbersMatch($subKpiNumber, $num)) {
                    return $kpi;
                }
                if ($subKpiNumber !== null && $num !== '' && $this->kpiNumbersMatchRelaxed($subKpiNumber, $num, $kpiIndex)) {
                    return $kpi;
                }
                if ($subKpiNumber !== null && $num !== '' && (int) $subKpiNumber === (int) $num) {
                    return $kpi;
                }
                // Match by title prefix when number matches or when KRA has one KPI (first 40 chars overlap)
                if ($this->titleMatchByPrefix($subKpiStripped, $normalizedTitle, 40)) {
                    return $kpi;
                }
                if ($normalizedFull === $subKpi || $normalizedTitle === $subKpi) {
                    return $kpi;
                }
                if ($normalizedFull === $subKpiStripped || $normalizedTitle === $subKpiStripped) {
                    return $kpi;
                }
                if ($this->titleMatchByFirstChars($subKpiStripped, $normalizedTitle) || $this->titleMatchByFirstChars($normalizedTitle, $subKpiStripped)) {
                    return $kpi;
                }
                if ($this->meaningfulOverlap($subKpi, $normalizedTitle) || $this->meaningfulOverlap($normalizedTitle, $subKpi)) {
                    return $kpi;
                }
                if ($this->meaningfulOverlap($subKpiStripped, $normalizedTitle) || $this->meaningfulOverlap($normalizedTitle, $subKpiStripped)) {
                    return $kpi;
                }
                if (str_contains($subKpi, $normalizedTitle) || str_contains($normalizedTitle, $subKpi)) {
                    return $kpi;
                }
                if (str_contains($subKpi, $normalizedFull) || str_contains($normalizedFull, $subKpi)) {
                    return $kpi;
                }
                if (str_starts_with($subKpi, $normalizedTitle) || str_starts_with($normalizedFull, $subKpi)) {
                    return $kpi;
                }
                if (str_starts_with($normalizedTitle, $subKpi) || str_starts_with($normalizedFull, $subKpiStripped)) {
                    return $kpi;
                }
                if (strlen($normalizedTitle) >= 10 && strlen($subKpiStripped) >= 10 && str_starts_with($subKpiStripped, substr($normalizedTitle, 0, 20))) {
                    return $kpi;
                }
                if (strlen($normalizedTitle) >= 10 && strlen($subKpiStripped) >= 10 && str_starts_with($normalizedTitle, substr($subKpiStripped, 0, 20))) {
                    return $kpi;
                }
            }
            if ($singleKpi !== null) {
                return $singleKpi;
            }
        }

        // Last resort: KRA matched but no KPI matched – use first KPI of first matching KRA when submission is clearly for KPI 1
        $isLikelyKpi1 = ($subKpiNumber !== null && (int) $subKpiNumber === 1)
            || (strlen($subKpiStripped) >= 15 && str_contains($subKpiStripped, 'number of reviewed'));
        if ($isLikelyKpi1) {
            foreach ($kraKpiData as $kra) {
                $kraTitle = $this->normalizeForMatch($kra['kra_title'] ?? '');
                $kraTitleStripped = $this->stripKraPrefix($kraTitle);
                $kraMatches = $subKra === ''
                    || $kraTitle === $subKra
                    || $kraTitleStripped === $subKraStripped
                    || str_contains($kraTitle, $subKra)
                    || str_contains($subKra, $kraTitle)
                    || str_contains($kraTitleStripped, $subKraStripped)
                    || str_contains($subKraStripped, $kraTitleStripped);
                if ($kraMatches) {
                    $kpis = $kra['kpis'] ?? [];
                    if (!empty($kpis)) {
                        return $kpis[0];
                    }
                }
            }
        }

        return null;
    }

    /** Ensure we always have an array (Form may store JSON string in DB in some flows). */
    protected function getKraKpiDataArray(Form $form): ?array
    {
        $data = $form->kra_kpi_data;
        if (is_array($data)) {
            return $data;
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    protected function stripKraPrefix(string $s): string
    {
        $s = preg_replace('/^kra\s*\d+(\.\d+)*\s*[-–—:]\s*/i', '', $s);
        return $this->normalizeForMatch($s);
    }

    protected function titleMatchByFirstChars(string $a, string $b, int $len = 80): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        $a = mb_substr($a, 0, $len);
        $b = mb_substr($b, 0, $len);
        return strlen($a) >= 10 && strlen($b) >= 10 && (str_starts_with($a, $b) || str_starts_with($b, $a) || str_contains($a, $b) || str_contains($b, $a));
    }

    /** Match when either string's first $minLen chars appear in the other (handles truncated or wrapped titles). */
    protected function titleMatchByPrefix(string $a, string $b, int $minLen = 40): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        $prefixA = mb_substr($a, 0, $minLen);
        $prefixB = mb_substr($b, 0, $minLen);
        if (strlen($prefixA) < 15 || strlen($prefixB) < 15) {
            return false;
        }
        return str_contains($a, $prefixB) || str_contains($b, $prefixA)
            || str_starts_with($a, $prefixB) || str_starts_with($b, $prefixA);
    }

    protected function kpiNumbersMatchRelaxed(string $subNum, string $formNum, int $kpiIndex): bool
    {
        if ($this->kpiNumbersMatch($subNum, $formNum)) {
            return true;
        }
        $s = (float) $subNum;
        $f = (float) $formNum;
        if ($kpiIndex === 0 && (int) $s === (int) $f && $s >= 1 && $s < 2 && $f >= 1 && $f < 2) {
            return true;
        }
        if (abs($s - $f) < 0.01) {
            return true;
        }
        return false;
    }

    protected function extractKpiNumberFromText(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        if (preg_match('/^\s*(\d+(?:\.\d+)*)\s*[-–—:\s]/u', $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/\b(\d+(?:\.\d+)*)\s*[-–—:]/u', $text, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/kpi\s*(\d+(?:\.\d+)*)/i', $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    protected function kpiNumbersMatch(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);
        if ($a === '' || $b === '') {
            return false;
        }
        return $a === $b || (float) $a === (float) $b;
    }

    protected function meaningfulOverlap(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }
        $minLen = 15;
        if (strlen($a) < $minLen || strlen($b) < $minLen) {
            return str_contains($a, $b) || str_contains($b, $a);
        }
        return str_contains($a, $b) || str_contains($b, $a);
    }

    protected function stripKpiPrefix(string $s): string
    {
        $s = preg_replace('/^kpi\s*\d+(\.\d+)*\s*[-–—:]\s*/i', '', $s);
        $s = preg_replace('/^\d+(\.\d+)*\s*[-–—:]\s*/', '', $s);
        return $this->normalizeForMatch($s);
    }

    protected function normalizeForMatch(string $s): string
    {
        $s = preg_replace('/[\s\xC2\xA0]+/u', ' ', $s);
        return strtolower(trim($s));
    }

    /**
     * Normalize submission kpi_title for matching: collapse whitespace, remove surrounding quotes, BOM.
     * Submission may store e.g. "1 - \"Number of reviewed...\r\n" with quotes and newlines.
     */
    protected function normalizeSubmissionKpiTitle(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/u', '', $s); // UTF-8 BOM
        $s = preg_replace('/[\s\xC2\xA0]+/u', ' ', $s);
        $s = trim($s);
        $s = preg_replace('/^["\']+|["\']+$/u', '', $s);
        return trim($s);
    }
}
