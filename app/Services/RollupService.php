<?php

namespace App\Services;

use App\Models\Submission;
use App\Models\Template;

/**
 * Extracts target and accomplishment values from submission table_data
 * for use in approval forms (QA Coordinator review).
 */
class RollupService
{
    /** Common field names for target values (in order of preference) */
    protected array $targetFieldCandidates = [
        'target_value', 'target_output', 'target', 'no', 'quantity', 'count',
    ];

    /** Common field names for accomplishment values */
    protected array $accompFieldCandidates = [
        'actual_value', 'actual_output', 'actual', 'accomplishment', 'accomp',
    ];

    /**
     * Extract suggested target and accomplishment values from submission for approval form.
     * Returns array with target_q1..q4, accomp_q1..q4 (others zeroed based on submission quarter).
     */
    public function extractFromSubmission(Submission $submission): array
    {
        $tableData = $submission->table_data;
        if (!is_array($tableData) || empty($tableData)) {
            return $this->defaults();
        }
        // Some legacy submissions have null/empty quarter. Keep extractor resilient.
        $quarter = trim((string) ($submission->quarter ?? ''));
        if ($quarter === '') {
            $quarter = 'Q1';
        }

        // Prefer explicit quarterly accomplishment values from summary rows when available.
        // This supports Super Admin "Finalize" flows where Q1-Q4 are filled at once.
        $quarterlyFromSummary = $this->extractQuarterlyAccomplishmentFromSummary($tableData);
        if ($quarterlyFromSummary !== null) {
            return array_merge($this->defaults(), $quarterlyFromSummary);
        }

        $template = $submission->template;
        $rollupRules = $template ? $this->getRollupRules($template) : null;

        if ($rollupRules) {
            return $this->applyRollupRules($tableData, $quarter, $rollupRules);
        }

        // Use form's total_mode (Sum/Average) so accomplishment rollup matches target
        $aggregation = 'sum';
        $form = $submission->relationLoaded('form') ? $submission->form : $submission->load('form')->form;
        if ($form && is_array($form->kra_kpi_data ?? null)) {
            foreach ($form->kra_kpi_data as $kra) {
                foreach ($kra['kpis'] ?? [] as $kpi) {
                    if (!empty($kpi['total_mode'])) {
                        $aggregation = $kpi['total_mode'] === 'average' ? 'avg' : 'sum';
                        break 2;
                    }
                }
            }
        }

        return $this->autoExtract($tableData, $quarter, $aggregation);
    }

    /**
     * Detect accomplishment_q1..q4 values from summary row fields.
     * Returns null when no usable quarterly accomplishment fields are found.
     */
    protected function extractQuarterlyAccomplishmentFromSummary(array $tableData): ?array
    {
        $summaryRows = array_values(array_filter($tableData, function ($row) {
            if (!is_array($row)) return false;
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            return is_array($meta) && (($meta['row_type'] ?? 'data') === 'summary');
        }));
        if (empty($summaryRows)) return null;

        // Last summary row with Finalize metadata wins when multiple blue rows exist.
        for ($si = count($summaryRows) - 1; $si >= 0; $si--) {
            $tryRow = $summaryRows[$si];
            $tryMeta = $tryRow['_meta'] ?? [];
            if (is_string($tryMeta)) {
                $tryMeta = json_decode($tryMeta, true);
            }
            if (! is_array($tryMeta) || ! isset($tryMeta['finalized_accomp']) || ! is_array($tryMeta['finalized_accomp'])) {
                continue;
            }
            $fa = $tryMeta['finalized_accomp'];
            $result = [
                'accomp_q1' => $this->toFloat($fa['q1'] ?? 0),
                'accomp_q2' => $this->toFloat($fa['q2'] ?? 0),
                'accomp_q3' => $this->toFloat($fa['q3'] ?? 0),
                'accomp_q4' => $this->toFloat($fa['q4'] ?? 0),
            ];
            $hasAny = ($result['accomp_q1'] != 0.0) || ($result['accomp_q2'] != 0.0) || ($result['accomp_q3'] != 0.0) || ($result['accomp_q4'] != 0.0);
            if ($hasAny) {
                return $result;
            }
        }

        // Use last summary row (latest result row in section) for column-based detection.
        $row = $summaryRows[count($summaryRows) - 1];
        $accomp = [1 => null, 2 => null, 3 => null, 4 => null];
        foreach ($row as $key => $value) {
            if ($key === '_meta') continue;
            $norm = $this->normalizeToken((string) $key);
            if (!$this->looksLikeAccomplishmentToken($norm)) continue;
            $q = $this->detectQuarterIndex($norm);
            if ($q < 1 || $q > 4) continue;
            $accomp[$q] = $this->toFloat($value);
        }

        $hasAny = false;
        $result = [];
        for ($q = 1; $q <= 4; $q++) {
            $v = $accomp[$q];
            if ($v !== null) {
                $result["accomp_q{$q}"] = (float) $v;
                $hasAny = true;
            }
        }
        return $hasAny ? $result : null;
    }

    protected function normalizeToken(string $value): string
    {
        $v = strtolower(trim($value));
        $v = preg_replace('/[^a-z0-9]+/i', '_', $v);
        return trim((string) $v, '_');
    }

    protected function looksLikeAccomplishmentToken(string $token): bool
    {
        return str_contains($token, 'accomp') || str_contains($token, 'accomplishment') || str_contains($token, 'actual');
    }

    protected function detectQuarterIndex(string $token): int
    {
        if (preg_match('/(?:^|_)q1(?:_|$)|(?:^|_)1st(?:_|$)|(?:^|_)first(?:_|$)|(?:^|_)quarter_?1(?:_|$)/', $token)) return 1;
        if (preg_match('/(?:^|_)q2(?:_|$)|(?:^|_)2nd(?:_|$)|(?:^|_)second(?:_|$)|(?:^|_)quarter_?2(?:_|$)/', $token)) return 2;
        if (preg_match('/(?:^|_)q3(?:_|$)|(?:^|_)3rd(?:_|$)|(?:^|_)third(?:_|$)|(?:^|_)quarter_?3(?:_|$)/', $token)) return 3;
        if (preg_match('/(?:^|_)q4(?:_|$)|(?:^|_)4th(?:_|$)|(?:^|_)fourth(?:_|$)|(?:^|_)quarter_?4(?:_|$)/', $token)) return 4;
        return 0;
    }

    /**
     * Get rollup rules from template fields_json (optional config).
     */
    protected function getRollupRules(Template $template): ?array
    {
        $fieldsJson = $template->fields_json;
        if (!is_array($fieldsJson)) {
            return null;
        }
        return $fieldsJson['rollup_rules'] ?? null;
    }

    /**
     * Apply explicit rollup rules from template.
     * Accomplishment prefers the summary (blue) row value when present, so formula results
     * in blue rows align with per-campus and university-wide accomplishment per quarter.
     */
    protected function applyRollupRules(array $tableData, string $quarter, array $rules): array
    {
        $targetField = $rules['target']['source_field'] ?? 'target_value';
        $accompField = $rules['accomp']['source_field'] ?? 'actual_value';
        $agg = $rules['target']['aggregation'] ?? 'sum';

        $targetSum = $this->aggregateRows($tableData, $targetField, $agg);
        $accompSum = $this->getAccomplishmentFromTableData($tableData, $accompField, $agg);

        return $this->mapToQuarterly($targetSum, $accompSum, $quarter);
    }

    /**
     * Get accomplishment value: prefer summary (blue) row when it has a formula result.
     * Falls back to aggregating data rows when no summary row or no value exists.
     */
    protected function getAccomplishmentFromTableData(array $tableData, string $field, string $agg): float
    {
        foreach ($tableData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            if (!is_array($meta) || ($meta['row_type'] ?? 'data') !== 'summary') {
                continue;
            }
            $val = $row[$field] ?? null;
            $valStr = trim((string) $val);
            $dash = "\u{2014}";
            if ($valStr === '' || $valStr === '—' || $valStr === $dash) {
                continue;
            }
            return $this->toFloat($val);
        }

        return $this->aggregateRows($tableData, $field, $agg);
    }

    /**
     * Auto-extract using common field names. Uses aggregation (sum or avg) from form total_mode when set.
     */
    protected function autoExtract(array $tableData, string $quarter, string $aggregation = 'sum'): array
    {
        $targetKey = $this->findFirstMatchingKey($tableData, $this->targetFieldCandidates);
        $accompKey = $this->findFirstMatchingKey($tableData, $this->accompFieldCandidates);

        $targetValue = $this->aggregateRows($tableData, $targetKey ?? '', $aggregation);
        $accompValue = $this->aggregateRows($tableData, $accompKey ?? '', $aggregation);

        return $this->mapToQuarterly($targetValue, $accompValue, $quarter);
    }

    protected function findFirstMatchingKey(array $tableData, array $candidates): ?string
    {
        $firstRow = $tableData[0] ?? [];
        if (!is_array($firstRow)) return null;

        $keys = array_keys($firstRow);
        foreach ($candidates as $candidate) {
            foreach ($keys as $k) {
                if (strtolower(str_replace(['-', ' '], '_', $k)) === strtolower($candidate)) {
                    return $k;
                }
            }
        }
        return null;
    }

    protected function aggregateRows(array $rows, string $field, string $agg): float
    {
        $values = [];
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            if (($row['_meta']['row_type'] ?? 'data') === 'summary') continue;
            $values[] = $this->toFloat($row[$field] ?? 0);
        }
        return match ($agg) {
            'avg' => count($values) > 0 ? array_sum($values) / count($values) : 0.0,
            default => array_sum($values),
        };
    }

    protected function mapToQuarterly(float $targetSum, float $accompSum, string $quarter): array
    {
        $q = $this->quarterToIndex($quarter);
        $result = [
            'target_q1' => 0.0, 'target_q2' => 0.0, 'target_q3' => 0.0, 'target_q4' => 0.0,
            'accomp_q1' => 0.0, 'accomp_q2' => 0.0, 'accomp_q3' => 0.0, 'accomp_q4' => 0.0,
        ];
        if ($q >= 1 && $q <= 4) {
            $result["target_q{$q}"] = $targetSum;
            $result["accomp_q{$q}"] = $accompSum;
        }
        return $result;
    }

    protected function quarterToIndex(string $quarter): int
    {
        $q = strtolower(trim($quarter));
        if (str_contains($q, '1') || $q === '1st q' || $q === 'q1') return 1;
        if (str_contains($q, '2') || $q === '2nd q' || $q === 'q2') return 2;
        if (str_contains($q, '3') || $q === '3rd q' || $q === 'q3') return 3;
        if (str_contains($q, '4') || $q === '4th q' || $q === 'q4') return 4;
        return 1;
    }

    protected function toFloat($value): float
    {
        if (is_numeric($value)) return (float) $value;
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    protected function defaults(): array
    {
        return [
            'target_q1' => 0.0, 'target_q2' => 0.0, 'target_q3' => 0.0, 'target_q4' => 0.0,
            'accomp_q1' => 0.0, 'accomp_q2' => 0.0, 'accomp_q3' => 0.0, 'accomp_q4' => 0.0,
        ];
    }
}
