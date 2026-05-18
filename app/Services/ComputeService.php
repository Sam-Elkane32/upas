<?php

namespace App\Services;

/**
 * Centralized computation service for template calculated fields.
 * Handles: unique, sum, countif, avg_percentage, formula operations, and summary rows.
 */
class ComputeService
{
    /**
     * Compute calculated fields based on template schema.
     */
    public function computeCalculatedFields(array $tableData, array $schemaFields, array $summaryRules = [], array $summaryCellMappings = []): array
    {
        if (empty($tableData) || empty($schemaFields)) {
            return $tableData;
        }

        // Work on data rows only; summary rows are regenerated authoritatively.
        $dataRows = array_values(array_filter($tableData, function ($row) {
            if (!is_array($row)) {
                return false;
            }
            $meta = $row['_meta'] ?? [];
            return (($meta['row_type'] ?? 'data') !== 'summary');
        }));

        // Find all calculated fields
        $calculatedFields = [];
        foreach ($schemaFields as $field) {
            if (isset($field['meta']['calc'])) {
                $fieldKey = $field['key'] ?? $field['name'] ?? null;
                if ($fieldKey) {
                    $calculatedFields[$fieldKey] = $field;
                }
            }
        }

        if (empty($calculatedFields)) {
            return $this->buildSummaryRows($dataRows, $schemaFields, $summaryRules, $summaryCellMappings);
        }

        // Process each calculated field
        foreach ($calculatedFields as $fieldKey => $field) {
            $calcType = $field['meta']['calc'];
            // Sum and Average are fillable — preserve user-entered values, skip auto-calculation
            if (in_array($calcType, ['sum', 'avg_percentage'], true)) {
                continue;
            }
            $applyAllRows = $field['meta']['applyAllRows'] ?? false;
            $scope = $field['meta']['scope'] ?? 'row';

            if ($calcType === 'formula') {
                foreach ($dataRows as &$row) {
                    $row[$fieldKey] = $this->calculateValue('formula', [], $field['meta'], $dataRows, $row);
                }
                unset($row);
                continue;
            }

            if ($calcType === 'avg_percentage' && $scope === 'all_rows') {
                $sourceKey = $field['meta']['sourceA'] ?? $fieldKey;
                $values = [];
                foreach ($dataRows as $row) {
                    $rawValue = (string)($row[$sourceKey] ?? '');
                    $clean = preg_replace('/[^0-9.\-]/', '', $rawValue);
                    if ($clean !== '' && is_numeric($clean)) {
                        $values[] = (float)$clean;
                    }
                }
                $calculatedValue = $this->calculateValue($calcType, $values, $field['meta'], $dataRows);
                foreach ($dataRows as &$row) {
                    $row[$fieldKey] = $calculatedValue;
                }
                unset($row);
                continue;
            }

            // For unique/countif: when result goes to summary row only, do not overwrite data rows (user fills values)
            $outputMode = $field['meta']['outputMode'] ?? '';
            $sourceKey = $field['meta']['sourceA'] ?? $fieldKey;
            $isSourceSelf = ($sourceKey === '' || $sourceKey === $fieldKey);
            if (in_array($calcType, ['unique', 'countif'], true) && ($outputMode === 'count' || $isSourceSelf)) {
                continue;
            }

            $applyAllRows = false;

            if ($applyAllRows) {
                $allValues = [];
                foreach ($dataRows as $row) {
                    if (isset($row[$fieldKey]) && $row[$fieldKey] !== '') {
                        $allValues[] = $row[$fieldKey];
                    }
                }
                $calculatedValue = $this->calculateValue($calcType, $allValues, $field['meta'], $dataRows);
                foreach ($dataRows as &$row) {
                    $row[$fieldKey] = $calculatedValue;
                }
                unset($row);
            } else {
                foreach ($dataRows as &$row) {
                    $sourceKey = $field['meta']['sourceA'] ?? $fieldKey;
                    $inputValue = (string)($row[$sourceKey] ?? '');
                    $values = preg_split('/[,;\n|]/', $inputValue);
                    $values = array_map('trim', $values);
                    $values = array_values(array_filter($values, fn($v) => $v !== ''));
                    $row[$fieldKey] = $this->calculateValue($calcType, $values, $field['meta'], $dataRows, $row);
                }
                unset($row);
            }
        }

        return $this->buildSummaryRows($dataRows, $schemaFields, $summaryRules, $summaryCellMappings);
    }

    /**
     * Build summary rows from template summary rules.
     *
     * @param  array<int, array<string, mixed>>  $summaryCellMappings  Same as template fields_json.summary_cell_mappings (Super Admin canonical per target_field).
     */
    public function buildSummaryRows(array $dataRows, array $schemaFields, array $summaryRules, array $summaryCellMappings = []): array
    {
        if (empty($dataRows)) {
            return $dataRows;
        }

        // Ensure we only process array rows (skip strings or other types from malformed data)
        $dataRows = array_values(array_filter($dataRows, fn($row) => is_array($row)));
        if (empty($dataRows)) {
            return $dataRows;
        }

        if (empty($summaryRules)) {
            $summaryRules = $this->buildFallbackSummaryRules($schemaFields);
        }
        if (empty($summaryRules)) {
            return $dataRows;
        }

        $fieldKeys = array_values(array_filter(array_map(function ($f) {
            $k = $this->getSchemaFieldKey($f);
            return $k !== '' ? $k : null;
        }, $schemaFields)));

        $enabledRules = array_values(array_filter($summaryRules, function ($rule) {
            return ($rule['enabled'] ?? false) === true
                && (($rule['placement'] ?? 'after_group') === 'after_group')
                && !empty($rule['group_by'][0] ?? null)
                && !empty($rule['outputs'] ?? []);
        }));

        if (empty($enabledRules)) {
            return $dataRows;
        }

        $primaryRule = $enabledRules[0];
        $groupKeyField = $primaryRule['group_by'][0];
        // Build groups by SECTION (separator): match Planning Coordinator – one blue row per section.
        // _after_separator marks start of new section; no separator = 1 section = 1 blue row.
        $grouped = [];
        $currentGroup = [];
        foreach ($dataRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $afterSeparator = !empty($row['_after_separator']);
            if (!empty($currentGroup) && $afterSeparator) {
                $grouped['section_' . count($grouped)] = $currentGroup;
                $currentGroup = [];
            }
            $currentGroup[] = $row;
        }
        if (!empty($currentGroup)) {
            $grouped['section_' . count($grouped)] = $currentGroup;
        }

        $resultRows = [];
        $sectionOrdinal = 0;
        $totalSections = count($grouped);
        foreach ($grouped as $groupValue => $rows) {
            $sectionOrdinal++;
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $row['_meta'] = is_array($row['_meta'] ?? null) ? ($row['_meta'] ?? []) : [];
                $row['_meta']['row_type'] = 'data';
                // _after_separator preserved in row for section structure (any data type, with or without auto-calc)
                $resultRows[] = $row;
            }

            // Skip summary row for unfilled groups (e.g. rows with empty RWU/campus) – matches Planning Coordinator behavior
            $groupHasFilledData = false;
            foreach ($rows as $r) {
                if (is_array($r) && trim((string)($r[$groupKeyField] ?? '')) !== '') {
                    $groupHasFilledData = true;
                    break;
                }
            }
            if (!$groupHasFilledData) {
                continue;
            }

            $summaryRow = ['_meta' => [
                'row_type' => 'summary',
                'group_key' => $groupValue,
                'summary_rule_id' => $primaryRule['id'] ?? 'sr_default',
                'is_readonly' => true,
            ]];

            foreach ($fieldKeys as $fieldKey) {
                $summaryRow[$fieldKey] = '';
            }
            $summaryRow[$groupKeyField] = '—';

            $outputsForRule = $primaryRule['outputs'] ?? [];
            $mergedOutputsForRule = [];
            foreach ($outputsForRule as $oRaw) {
                if (!is_array($oRaw)) {
                    continue;
                }
                $mergedOutputsForRule[] = $this->mergeSummaryOutputWithCellMapping($oRaw, $summaryCellMappings, $sectionOrdinal, $totalSections, $rows);
            }
            $scopedOutputsForRule = array_values(array_filter($mergedOutputsForRule, function ($output) use ($sectionOrdinal, $rows, $totalSections) {
                return is_array($output) && $this->summaryOutputMatchesSectionOrdinal($output, $sectionOrdinal, $rows, $totalSections);
            }));
            $inheritedRowIndices = null;
            foreach ($scopedOutputsForRule as $oScan) {
                if (!is_array($oScan)) {
                    continue;
                }
                if (!empty($oScan['row_indices']) && is_array($oScan['row_indices'])) {
                    $inheritedRowIndices = array_values(array_map(static fn ($x) => (int) $x, $oScan['row_indices']));
                    break;
                }
            }
            $inheritedRowUids = null;
            if ($inheritedRowIndices === null || $inheritedRowIndices === []) {
                foreach ($scopedOutputsForRule as $oScan) {
                    if (!is_array($oScan)) {
                        continue;
                    }
                    if (!empty($oScan['row_uids']) && is_array($oScan['row_uids'])) {
                        $inheritedRowUids = array_values(array_map(static fn ($x) => trim((string) $x), $oScan['row_uids']));
                        break;
                    }
                }
            }

            foreach ($scopedOutputsForRule as $output) {
                $targetField = $output['target_field'] ?? '';
                if ($targetField !== '') {
                    $effectiveOutput = $this->summaryOutputWithInheritedRowScope($output, $inheritedRowIndices, $inheritedRowUids);
                    $summaryRow[$targetField] = $this->calculateSummaryOperation($rows, $effectiveOutput);
                }
            }

            // NO. is left blank unless a summary rule explicitly targets it (no automatic row-count injection).

            $resultRows[] = $summaryRow;
        }

        return $resultRows;
    }

    /**
     * Merge per-campus "cleared" summary cells from stored table_data into computed result.
     * If the stored summary row had "—" or empty for a column, keep "—" in the computed summary row
     * so that "Remove formula" for a specific campus persists after reload and for Planning Coordinator view.
     * Merge with ALL computed summary rows (by position) – the old logic only merged with the LAST row,
     * which fails when the last row is a data row (e.g. [data, data, summary, data, data, data]).
     */
    public function mergeStoredSummaryClearedCells(array $computedTableData, array $storedTableData): array
    {
        if (empty($computedTableData) || empty($storedTableData)) {
            return $computedTableData;
        }
        $storedSummaryRows = [];
        foreach ($storedTableData as $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            if (is_array($meta) && ($meta['row_type'] ?? '') === 'summary') {
                $storedSummaryRows[] = $row;
            }
        }
        // Fallback: if no _meta summary, treat last row as summary (e.g. stored without _meta)
        if (empty($storedSummaryRows) && count($storedTableData) > 0) {
            $lastStored = $storedTableData[count($storedTableData) - 1];
            if (is_array($lastStored)) {
                $firstVal = '';
                foreach ($lastStored as $k => $v) {
                    if ($k === '_meta' || is_array($v) || is_object($v)) {
                        continue;
                    }
                    $firstVal = trim((string) $v);
                    break;
                }
                $dash = "\u{2014}";
                if (strcasecmp($firstVal, 'Summary') === 0 || $firstVal === '—' || $firstVal === $dash || $firstVal === '') {
                    $storedSummaryRows[] = $lastStored;
                }
            }
        }
        if (empty($storedSummaryRows)) {
            return $computedTableData;
        }
        $dashVariants = ['—', "\u{2014}", '-', '–', "\xE2\x80\x94"]; // em dash, en dash, hyphen
        $isClearedValue = function ($v) use ($dashVariants) {
            $v = trim((string) $v);
            if ($v === '') {
                return true;
            }
            foreach ($dashVariants as $d) {
                if ($v === $d) {
                    return true;
                }
            }
            return false;
        };
        $computedSummaryIndices = [];
        foreach ($computedTableData as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            if (is_array($meta) && ($meta['row_type'] ?? '') === 'summary') {
                $computedSummaryIndices[] = $idx;
            }
        }
        foreach ($computedSummaryIndices as $pos => $computedIdx) {
            $storedSummaryRow = $storedSummaryRows[$pos] ?? null;
            if (!is_array($storedSummaryRow)) {
                // No matching stored summary row for this computed position:
                // never borrow another summary's values (prevents random totals leaking across sections).
                continue;
            }
            $storedMetaForRow = $storedSummaryRow['_meta'] ?? [];
            if (is_string($storedMetaForRow)) {
                $storedMetaForRow = json_decode($storedMetaForRow, true) ?? [];
            }
            $storedMetaForRow = is_array($storedMetaForRow) ? $storedMetaForRow : [];
            // If a stored summary row is entirely cleared (all scalar columns are dash/empty),
            // preserve that intentional "no calculation yet" state across refresh/recompute.
            $storedRowAllCleared = true;
            foreach ($storedSummaryRow as $k => $v) {
                if ($k === '_meta' || is_array($v) || is_object($v)) {
                    continue;
                }
                if (!$isClearedValue($v)) {
                    $storedRowAllCleared = false;
                    break;
                }
            }
            if ($storedRowAllCleared) {
                foreach ($computedTableData[$computedIdx] as $ck => $cv) {
                    if ($ck === '_meta' || is_array($cv) || is_object($cv)) {
                        continue;
                    }
                    $computedTableData[$computedIdx][$ck] = '—';
                }
                // Keep stored summary metadata (mapping/manual flags) so editor tools still work.
                $computedMeta = $computedTableData[$computedIdx]['_meta'] ?? [];
                $computedMeta = is_array($computedMeta) ? $computedMeta : (is_string($computedMeta) ? (json_decode($computedMeta, true) ?? []) : []);
                if (!empty($storedMetaForRow['summary_cell_mappings'])) {
                    $computedMeta['summary_cell_mappings'] = $storedMetaForRow['summary_cell_mappings'];
                }
                if (!empty($storedMetaForRow['manual_override_fields'])) {
                    $computedMeta['manual_override_fields'] = $storedMetaForRow['manual_override_fields'];
                }
                if (isset($storedMetaForRow['finalized_accomp']) && is_array($storedMetaForRow['finalized_accomp'])) {
                    $computedMeta['finalized_accomp'] = $storedMetaForRow['finalized_accomp'];
                }
                $computedTableData[$computedIdx]['_meta'] = $computedMeta;
                continue;
            }
            $mappedFormulaNormKeys = [];
            $storedSummaryMappings = is_array($storedMetaForRow['summary_cell_mappings'] ?? null) ? $storedMetaForRow['summary_cell_mappings'] : [];
            foreach ($storedSummaryMappings as $mk => $mMeta) {
                $mkNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $mk)));
                $mkNorm = trim($mkNorm, '_');
                if ($mkNorm !== '') {
                    $mappedFormulaNormKeys[$mkNorm] = true;
                }
            }
            foreach ($storedSummaryRow as $colKey => $storedVal) {
                if ($colKey === '_meta') {
                    // Preserve summary_cell_mappings so Super Admin sees source cells when clicking blue row
                    $storedMeta = is_array($storedVal) ? $storedVal : (is_string($storedVal) ? (json_decode($storedVal, true) ?? []) : []);
                    $computedMeta = $computedTableData[$computedIdx]['_meta'] ?? [];
                    $computedMeta = is_array($computedMeta) ? $computedMeta : (is_string($computedMeta) ? (json_decode($computedMeta, true) ?? []) : []);
                    if (!empty($storedMeta['summary_cell_mappings'])) {
                        $computedMeta = array_merge($computedMeta, ['summary_cell_mappings' => $storedMeta['summary_cell_mappings']]);
                    }
                    if (!empty($storedMeta['manual_override_fields'])) {
                        $computedMeta = array_merge($computedMeta, ['manual_override_fields' => $storedMeta['manual_override_fields']]);
                    }
                    // Finalize (KPI Q1–Q4): must survive compute merge or Form KRA view shows 0 accomplishment
                    if (isset($storedMeta['finalized_accomp']) && is_array($storedMeta['finalized_accomp'])) {
                        $computedMeta = array_merge($computedMeta, ['finalized_accomp' => $storedMeta['finalized_accomp']]);
                    }
                    $computedTableData[$computedIdx]['_meta'] = $computedMeta;
                    continue;
                }
                if (is_array($storedVal) || is_object($storedVal)) {
                    continue;
                }
                $colNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $colKey)));
                $colNorm = trim($colNorm, '_');
                $isFormulaMappedCell = $colNorm !== '' && isset($mappedFormulaNormKeys[$colNorm]);
                if ($isClearedValue($storedVal)) {
                    // Do not wipe formula-mapped cells to "—"; keep freshly computed value.
                    if ($isFormulaMappedCell) {
                        continue;
                    }
                    $computedTableData[$computedIdx][$colKey] = '—';
                    continue;
                }
                // Safety net: if compute produced blank/dash but stored has a concrete value, keep stored.
                // This prevents disappearing blue-row results on refresh when a mapping/rule is missed.
                $computedNowRaw = $computedTableData[$computedIdx][$colKey] ?? '';
                if ($isClearedValue($computedNowRaw) && !$isClearedValue($storedVal)) {
                    $computedTableData[$computedIdx][$colKey] = (string) $storedVal;
                    continue;
                }
                // Persist formula-mapped summary values from stored payload (DB or incoming save).
                // Server rebuild uses section-local rows / indices; browser "Count unique" can span a wider
                // row scope — recomputed values may legitimately differ (e.g. 10 vs 4). Stored is authoritative.
                if ($isFormulaMappedCell) {
                    if (!$isClearedValue($storedVal)) {
                        $computedTableData[$computedIdx][$colKey] = (string) $storedVal;
                    }
                    continue;
                }
                // Only preserve stored values for manual override cells (e.g. Compare to Campus Target).
                // Formula-calculated cells (sum, average, etc.) must use the freshly computed value
                // so blue row updates when Planning Coordinator changes source cells.
                $storedMeta = $storedMetaForRow;
                if (is_string($storedMeta)) {
                    $storedMeta = json_decode($storedMeta, true) ?? [];
                }
                $manualOverrideFields = is_array($storedMeta['manual_override_fields'] ?? null) ? $storedMeta['manual_override_fields'] : [];
                $isManualOverride = false;
                foreach ($manualOverrideFields as $mof) {
                    $mofNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $mof)));
                    if ($colNorm !== '' && ($colNorm === $mofNorm || $colKey === $mof)) {
                        $isManualOverride = true;
                        break;
                    }
                }
                if ($isManualOverride) {
                    $computedTableData[$computedIdx][$colKey] = (string) $storedVal;
                }
                // else: keep computed value (do not overwrite formula-calculated cells)
            }
            // Reverse pass: for each column in computed row, check stored (handles key mismatch).
            // Super Admin is source of truth – if stored has "—" for any matching column, use "—".
            $storedNorm = [];
            foreach ($storedSummaryRow as $sk => $sv) {
                if ($sk === '_meta' || is_array($sv) || is_object($sv)) {
                    continue;
                }
                $norm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $sk)));
                $norm = trim($norm, '_');
                if ($norm !== '' && $isClearedValue($sv) && !isset($mappedFormulaNormKeys[$norm])) {
                    $storedNorm[$norm] = true;
                }
            }
            foreach (array_keys($computedTableData[$computedIdx]) as $ck) {
                if ($ck === '_meta') {
                    continue;
                }
                $cv = $computedTableData[$computedIdx][$ck] ?? null;
                if (is_array($cv) || is_object($cv)) {
                    continue;
                }
                $cnorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $ck)));
                $cnorm = trim($cnorm, '_');
                if ($cnorm !== '' && isset($storedNorm[$cnorm])) {
                    $computedTableData[$computedIdx][$ck] = '—';
                }
            }
        }
        return $computedTableData;
    }

    /**
     * Overlay scalar cells from persisted summary rows (DB or incoming POST) onto server-recomputed table_data.
     * computeCalculatedFields() rebuilds summaries in PHP; that can disagree with Super Admin / browser JS
     * (row scope, field-key resolution). Stored summary rows are the source of truth for display and saves.
     *
     * @param  array<int, array<string, mixed>>  $computedTableData  Output of computeCalculatedFields + merge* helpers
     * @param  array<int, array<string, mixed>>  $sourceTableData    Raw payload: same shape before recompute (DB row or request body)
     * @return array<int, array<string, mixed>>
     */
    public function applyPersistedSummaryRowsFromSource(array $computedTableData, array $sourceTableData): array
    {
        if ($computedTableData === [] || $sourceTableData === []) {
            return $computedTableData;
        }
        $sourceSummaries = [];
        foreach ($sourceTableData as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($this->rowIsSummary($row)) {
                $sourceSummaries[] = $row;
            }
        }
        if ($sourceSummaries === []) {
            return $computedTableData;
        }
        $ord = 0;
        foreach ($computedTableData as $idx => $row) {
            if (! is_array($row) || ! $this->rowIsSummary($row)) {
                continue;
            }
            if (! isset($sourceSummaries[$ord])) {
                $ord++;

                continue;
            }
            $src = $sourceSummaries[$ord];
            $ord++;
            foreach ($src as $k => $v) {
                if ($k === '_meta' || is_array($v) || is_object($v)) {
                    continue;
                }
                $computedTableData[$idx][$k] = $v;
            }
            $cm = $computedTableData[$idx]['_meta'] ?? [];
            if (is_string($cm)) {
                $cm = json_decode($cm, true) ?? [];
            }
            $cm = is_array($cm) ? $cm : [];
            $sm = $src['_meta'] ?? [];
            if (is_string($sm)) {
                $sm = json_decode($sm, true) ?? [];
            }
            $sm = is_array($sm) ? $sm : [];
            if ($sm !== []) {
                $computedTableData[$idx]['_meta'] = array_merge($cm, $sm);
            }
        }

        return $computedTableData;
    }

    /**
     * When compute drops all or some blue summary rows (e.g. no enabled summary_rules outputs),
     * re-weave rows from the stored payload so interleaved [data…, summary, …] matches the DB again.
     * Data rows are taken from $computedTableData in order; summary rows are copied from $storedTableData.
     */
    public function reinterlaceStoredSummaryRowsIfMissing(array $computedTableData, array $storedTableData): array
    {
        if ($computedTableData === [] || $storedTableData === []) {
            return $computedTableData;
        }
        $nStoredS = $this->countSummaryRowsInPayload($storedTableData);
        $nComputedS = $this->countSummaryRowsInPayload($computedTableData);
        if ($nStoredS === 0 || $nComputedS >= $nStoredS) {
            return $computedTableData;
        }
        if ($this->countDataRowsInPayload($computedTableData) !== $this->countDataRowsInPayload($storedTableData)) {
            return $computedTableData;
        }
        $dataQueue = [];
        foreach ($computedTableData as $row) {
            if (! is_array($row) || $this->rowIsSummaryOrSummaryLike($row)) {
                continue;
            }
            $dataQueue[] = $row;
        }
        $qi = 0;
        $out = [];
        foreach ($storedTableData as $storedRow) {
            if (! is_array($storedRow)) {
                continue;
            }
            if ($this->rowIsSummaryOrSummaryLike($storedRow)) {
                $out[] = $storedRow;
            } elseif ($qi < count($dataQueue)) {
                $out[] = $dataQueue[$qi++];
            } else {
                $out[] = $storedRow;
            }
        }
        while ($qi < count($dataQueue)) {
            $out[] = $dataQueue[$qi++];
        }

        return $out;
    }

    /**
     * Reinsert stored blue rows when compute dropped them, then re-apply cleared-cell + scalar overlays.
     */
    public function finalizeTableDataAfterComputeMerges(array $computedTableData, array $storedTableData): array
    {
        $beforeSummaryCount = $this->countSummaryRowsInPayload($computedTableData);
        $next = $this->reinterlaceStoredSummaryRowsIfMissing($computedTableData, $storedTableData);
        if ($this->countSummaryRowsInPayload($next) > $beforeSummaryCount) {
            $next = $this->mergeStoredSummaryClearedCells($next, $storedTableData);
            $next = $this->applyPersistedSummaryRowsFromSource($next, $storedTableData);
        }

        return $next;
    }

    /**
     * Count blue summary rows in a client or DB payload (aligns with save normalization).
     */
    public function countSummaryRowsInPayload(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            if ($this->rowIsSummaryOrSummaryLike($row)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * Non-summary rows (strict _meta + legacy heuristic excluded).
     */
    private function countDataRowsInPayload(array $rows): int
    {
        $n = 0;
        foreach ($rows as $row) {
            if (is_array($row) && ! $this->rowIsSummaryOrSummaryLike($row)) {
                $n++;
            }
        }

        return $n;
    }

    private function rowIsSummary(array $row): bool
    {
        $meta = $row['_meta'] ?? [];
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        }
        return is_array($meta) && (($meta['row_type'] ?? '') === 'summary');
    }

    /** Summary by _meta or legacy first-column marker (Summary / em dash). */
    private function rowIsSummaryOrSummaryLike(array $row): bool
    {
        if ($this->rowIsSummary($row)) {
            return true;
        }
        if ($row === []) {
            return false;
        }
        $firstVal = '';
        foreach ($row as $k => $v) {
            if ($k === '_meta' || is_array($v) || is_object($v)) {
                continue;
            }
            $firstVal = trim((string) $v);
            break;
        }
        $dash = "\u{2014}";

        return strcasecmp($firstVal, 'Summary') === 0 || $firstVal === '—' || $firstVal === $dash;
    }

    /**
     * Drop trailing summary rows when compute rebuilt more blues than the user kept (e.g. deleted bottom blue).
     */
    private function trimSummaryRowsToTargetCount(array $rows, int $targetCount): array
    {
        if ($targetCount < 0) {
            $targetCount = 0;
        }
        $summaryIndices = [];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            if ($this->rowIsSummary($row)) {
                $summaryIndices[] = $i;
            }
        }
        $current = count($summaryIndices);
        if ($current <= $targetCount) {
            return $rows;
        }
        $removeCount = $current - $targetCount;
        $toRemove = array_flip(array_slice($summaryIndices, -$removeCount));
        $out = [];
        foreach ($rows as $i => $row) {
            if (!isset($toRemove[$i])) {
                $out[] = $row;
            }
        }
        return $out;
    }

    /**
     * Preserve extra summary rows (2nd, 3rd, etc.) from stored table_data so Super Admin–added blue rows
     * appear in Planning Coordinator view. Do NOT re-add trailing legacy summary rows (summary at end
     * with no data rows after) – those persist incorrectly and should be removed on save.
     *
     * @param  int|null  $summaryRowTargetCount  When set (e.g. Planning Coordinator save), trim compute-rebuilt
     *                                           summaries to this count so deletes beat merge source row count.
     */
    public function mergeStoredExtraSummaryRows(array $computedTableData, array $storedTableData, ?int $summaryRowTargetCount = null): array
    {
        if (empty($storedTableData)) {
            if ($summaryRowTargetCount !== null) {
                return $this->trimSummaryRowsToTargetCount($computedTableData, $summaryRowTargetCount);
            }
            return $computedTableData;
        }
        $storedSummaryRowsWithContext = [];
        foreach ($storedTableData as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true);
            }
            if (is_array($meta) && ($meta['row_type'] ?? '') === 'summary') {
                $hasDataRowAfter = false;
                for ($j = $idx + 1; $j < count($storedTableData); $j++) {
                    $next = $storedTableData[$j] ?? null;
                    if (is_array($next)) {
                        $nextMeta = $next['_meta'] ?? [];
                        if (is_string($nextMeta)) {
                            $nextMeta = json_decode($nextMeta, true);
                        }
                        if (!is_array($nextMeta) || ($nextMeta['row_type'] ?? 'data') !== 'summary') {
                            $hasDataRowAfter = true;
                            break;
                        }
                    }
                }
                $storedSummaryRowsWithContext[] = ['row' => $row, 'index' => $idx, 'trailing' => !$hasDataRowAfter];
            }
        }
        $targetCount = $summaryRowTargetCount ?? $this->countSummaryRowsInPayload($storedTableData);

        if (count($storedSummaryRowsWithContext) <= 1) {
            return $this->trimSummaryRowsToTargetCount($computedTableData, $targetCount);
        }
        // Count sections (separator-based): max 1 blue row per section – don't add extras from old RWU grouping
        $sectionCount = 1;
        foreach ($storedTableData as $row) {
            if (is_array($row) && !empty($row['_after_separator'])) {
                $sectionCount++;
            }
        }
        $computedSummaryCount = 0;
        foreach ($computedTableData as $row) {
            if (is_array($row) && $this->rowIsSummary($row)) {
                $computedSummaryCount++;
            }
        }
        if ($computedSummaryCount < $sectionCount) {
            $toAdd = min($sectionCount - $computedSummaryCount, count($storedSummaryRowsWithContext) - 1);
            $added = 0;
            foreach (array_slice($storedSummaryRowsWithContext, 1) as $item) {
                if ($item['trailing'] || $added >= $toAdd) {
                    continue;
                }
                $computedTableData[] = $item['row'];
                $added++;
            }
        }

        return $this->trimSummaryRowsToTargetCount($computedTableData, $targetCount);
    }

    private function buildFallbackSummaryRules(array $schemaFields): array
    {
        $fieldByKey = [];
        foreach ($schemaFields as $field) {
            $key = $this->getSchemaFieldKey($field);
            if ($key !== '') {
                $fieldByKey[$key] = $field;
            }
        }

        $groupCandidates = ['responsible_work_units', 'responsible_work_unit', 'campus', 'campus_code'];
        $groupBy = null;
        foreach ($groupCandidates as $candidate) {
            if (isset($fieldByKey[$candidate])) {
                $groupBy = $candidate;
                break;
            }
        }
        if ($groupBy === null) {
            return [];
        }

        $outputs = [];
        foreach ($schemaFields as $field) {
            $key = $this->getSchemaFieldKey($field);
            $calcType = $field['meta']['calc'] ?? null;
            if ($key === '' || !$calcType) {
                continue;
            }
            // Formula (e.g. Percentage = A/B): summary = average of percentage column + "%"
            if ($calcType === 'formula') {
                $operation = $field['meta']['operation'] ?? '';
                if (in_array($operation, ['divide', 'percent_of'], true)) {
                    $outputs[] = [
                        'target_field' => $key,
                        'operation' => 'avg_with_suffix',
                        'sourceA' => $key,
                        'suffix' => '%',
                    ];
                }
                continue;
            }
            $operation = match ($calcType) {
                'sum' => 'sum',
                'avg_percentage' => 'avg',
                'unique' => 'count_unique',
                'countif' => 'count_total',
                default => null,
            };
            if ($operation) {
                $outputs[] = [
                    'target_field' => $key,
                    'operation' => $operation,
                    'sourceA' => $field['meta']['sourceA'] ?? $key,
                ];
            }
        }

        if (empty($outputs)) {
            return [];
        }

        return [[
            'id' => 'sr_default',
            'enabled' => true,
            'label' => 'Summary',
            'placement' => 'after_group',
            'group_by' => [$groupBy],
            'outputs' => $outputs,
        ]];
    }

    /**
     * Canonical field key from schema (matches view getFieldKey: key|name|normalized label).
     */
    private function getSchemaFieldKey(array $field): string
    {
        $key = $field['key'] ?? $field['name'] ?? null;
        if ($key !== null && $key !== '') {
            return (string) $key;
        }
        $label = $field['label'] ?? '';
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $label)));
        return trim($normalized, '_');
    }

    /**
     * Get value from row by key, with normalized key fallback (Planning Coordinator may send different casing).
     */
    private function getRowValueByKey(array $row, string $key): string
    {
        $v = (string)($row[$key] ?? '');
        if ($v !== '') {
            return $v;
        }
        $keyNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($key)));
        $keyNorm = trim($keyNorm, '_');
        if ($keyNorm === '') {
            return '';
        }
        foreach ($row as $k => $val) {
            if ($k === '_meta' || is_array($val) || is_object($val)) {
                continue;
            }
            $kNorm = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $k)));
            $kNorm = trim($kNorm, '_');
            if ($kNorm === $keyNorm) {
                return (string) $val;
            }
        }
        return '';
    }

    /**
     * Super Admin stores per-column formula metadata in summary_cell_mappings; summary_rules outputs can omit sourceA / row scope.
     * Last mapping with matching target_field wins (same as SA upsert order).
     *
     * @param  array<string, mixed>  $output
     * @param  array<int, array<string, mixed>>  $summaryCellMappings
     * @return array<string, mixed>
     */
    /**
     * Pick the summary_cell_mappings entry for this output and section (not "last target_field wins" globally).
     *
     * @param  array<int, array<string, mixed>>  $sectionRows  Data rows in the current section (for row_uid overlap).
     */
    private function pickSummaryCellMappingForSection(
        string $targetField,
        array $summaryCellMappings,
        int $sectionOrdinal,
        int $totalSections,
        array $sectionRows
    ): ?array {
        if ($targetField === '' || $summaryCellMappings === []) {
            return null;
        }
        $sectionUidSet = [];
        foreach ($sectionRows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (! is_array($meta)) {
                $meta = [];
            }
            $uid = trim((string) ($meta['row_uid'] ?? ''));
            if ($uid !== '') {
                $sectionUidSet[$uid] = true;
            }
        }
        $candidates = [];
        foreach ($summaryCellMappings as $index => $m) {
            if (! is_array($m)) {
                continue;
            }
            if (trim((string) ($m['target_field'] ?? '')) !== $targetField) {
                continue;
            }
            if (! $this->summaryCellMappingMatchesSection($m, $sectionOrdinal, $totalSections, $sectionUidSet)) {
                continue;
            }
            $candidates[] = ['mapping' => $m, 'index' => $index];
        }
        if ($candidates === []) {
            return null;
        }
        usort($candidates, function ($a, $b) {
            $aCols = is_array($a['mapping']['source_columns'] ?? null) ? count($a['mapping']['source_columns']) : 0;
            $bCols = is_array($b['mapping']['source_columns'] ?? null) ? count($b['mapping']['source_columns']) : 0;
            if ($aCols !== $bCols) {
                return $bCols <=> $aCols;
            }
            $aHasB = trim((string) ($a['mapping']['sourceB'] ?? '')) !== '' ? 1 : 0;
            $bHasB = trim((string) ($b['mapping']['sourceB'] ?? '')) !== '' ? 1 : 0;
            if ($aHasB !== $bHasB) {
                return $bHasB <=> $aHasB;
            }

            return $b['index'] <=> $a['index'];
        });

        return $candidates[0]['mapping'];
    }

    /**
     * @param  array<string, mixed>  $mapping
     * @param  array<string, true>  $sectionUidSet
     */
    private function summaryCellMappingMatchesSection(array $mapping, int $sectionOrdinal, int $totalSections, array $sectionUidSet): bool
    {
        $ref = trim((string) ($mapping['section_ref'] ?? ''));
        if ($ref === 'grand_total') {
            return false;
        }
        $rowUids = is_array($mapping['row_uids'] ?? null) ? $mapping['row_uids'] : [];
        if ($rowUids !== []) {
            foreach ($rowUids as $uid) {
                $uid = trim((string) $uid);
                if ($uid !== '' && isset($sectionUidSet[$uid])) {
                    return true;
                }
            }
        }
        $baseRowUids = is_array($mapping['base_row_uids'] ?? null) ? $mapping['base_row_uids'] : [];
        foreach ($baseRowUids as $uid) {
            $uid = trim((string) $uid);
            if ($uid !== '' && isset($sectionUidSet[$uid])) {
                return true;
            }
        }
        if ($ref === '') {
            return $totalSections <= 1 || $sectionOrdinal === 1;
        }
        if (preg_match('/(?:^|\|)sec:(\d+)(?:\||$)/', $ref, $m) === 1) {
            return ((int) ($m[1] ?? 0)) === $sectionOrdinal;
        }

        return $totalSections <= 1 || $sectionOrdinal === 1;
    }

    private function mergeSummaryOutputWithCellMapping(
        array $output,
        array $summaryCellMappings,
        int $sectionOrdinal = 1,
        int $totalSections = 1,
        array $sectionRows = []
    ): array {
        $tf = trim((string) ($output['target_field'] ?? ''));
        if ($tf === '' || $summaryCellMappings === []) {
            return $output;
        }
        $match = $this->pickSummaryCellMappingForSection($tf, $summaryCellMappings, $sectionOrdinal, $totalSections, $sectionRows);
        if ($match === null) {
            return $output;
        }
        $out = $output;
        foreach ($match as $k => $mv) {
            if ($k === 'target_field') {
                continue;
            }
            if ($mv === null || $mv === '' || $mv === []) {
                continue;
            }
            if ($k === 'row_indices' && is_array($mv)) {
                $out['row_indices'] = array_values(array_map(static fn ($x) => (int) $x, $mv));
            } elseif ($k === 'row_uids' && is_array($mv)) {
                $out['row_uids'] = array_values(array_filter(array_map(static fn ($u) => trim((string) $u), $mv)));
            } elseif ($k === 'source_columns' && is_array($mv)) {
                $out['source_columns'] = array_values(array_filter(array_map(static fn ($x) => trim((string) $x), $mv)));
            } elseif ($k === 'count_adjust') {
                $out['count_adjust'] = (int) $mv;
            } else {
                $out[$k] = $mv;
            }
        }

        return $out;
    }

    /**
     * Super Admin often persists row_indices / row_uids on only one summary output (the column used when Apply was clicked).
     * Other outputs in the same rule omit scope and used to sum the whole section — inherit sibling scope so all blue cells match user intent.
     *
     * @param array<string, mixed> $output
     * @param list<int>|null $inheritedRowIndices
     * @param list<string>|null $inheritedRowUids
     * @return array<string, mixed>
     */
    private function summaryOutputWithInheritedRowScope(array $output, ?array $inheritedRowIndices, ?array $inheritedRowUids): array
    {
        $out = $output;
        $hasIdx = !empty($out['row_indices']) && is_array($out['row_indices']) && count($out['row_indices']) > 0;
        $hasUid = !empty($out['row_uids']) && is_array($out['row_uids']) && count($out['row_uids']) > 0;
        if (!$hasIdx && $inheritedRowIndices !== null && count($inheritedRowIndices) > 0) {
            $out['row_indices'] = $inheritedRowIndices;
        }
        $effHasIdx = $hasIdx || (!empty($out['row_indices']) && is_array($out['row_indices']) && count($out['row_indices']) > 0);
        if (!$hasUid && !$effHasIdx && $inheritedRowUids !== null && count($inheritedRowUids) > 0) {
            $out['row_uids'] = $inheritedRowUids;
        }

        return $out;
    }

    /**
     * section_ref can be "sub:...|user:...|sec:N". Scope summary output to its section ordinal when present.
     *
     * @param array<string, mixed> $output
     */
    private function summaryOutputMatchesSectionOrdinal(array $output, int $sectionOrdinal, array $rows, int $totalSections): bool
    {
        $ref = trim((string) ($output['section_ref'] ?? ''));
        if ($ref === 'grand_total') {
            return false;
        }
        $hasUidOverlap = function (array $uids) use ($rows): bool {
            if ($uids === []) {
                return false;
            }
            $uidSet = [];
            foreach ($uids as $u) {
                $s = trim((string) $u);
                if ($s !== '') {
                    $uidSet[$s] = true;
                }
            }
            if ($uidSet === []) {
                return false;
            }
            foreach ($rows as $row) {
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
                $ru = trim((string) ($meta['row_uid'] ?? ''));
                if ($ru !== '' && isset($uidSet[$ru])) {
                    return true;
                }
            }

            return false;
        };
        if (!empty($output['row_uids']) && is_array($output['row_uids']) && $hasUidOverlap($output['row_uids'])) {
            return true;
        }
        if (!empty($output['base_row_uids']) && is_array($output['base_row_uids']) && $hasUidOverlap($output['base_row_uids'])) {
            return true;
        }
        if ($ref === '') {
            // Unscoped outputs are ambiguous once separators create multiple sections.
            // Keep compatibility by applying them only to section 1.
            return $totalSections <= 1 || $sectionOrdinal === 1;
        }
        if (preg_match('/(?:^|\|)sec:(\d+)(?:\||$)/', $ref, $m) === 1) {
            return ((int) ($m[1] ?? 0)) === $sectionOrdinal;
        }

        // Legacy refs without "|sec:N" are ambiguous when multiple sections exist — only apply to section 1.
        return $totalSections <= 1 || $sectionOrdinal === 1;
    }

    /**
     * Base sum or average over selected row uids in one column, then apply a chain of + − × ÷ with other cells.
     *
     * @param  array<int, array<string, mixed>>  $rows  Section data rows (not pre-filtered by row_indices for this type).
     * @param  array<string, mixed>  $output
     */
    private function calculateAggregateChain(array $rows, array $output): string
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = array_values($rows);
        $baseSource = trim((string) ($output['base_source'] ?? ''));
        if ($baseSource === '') {
            $baseSource = trim((string) ($output['sourceA'] ?? ''));
        }
        if ($baseSource === '') {
            $cols = $output['source_columns'] ?? [];
            if (is_array($cols) && count($cols) > 0) {
                $baseSource = trim((string) $cols[0]);
            }
        }
        if ($baseSource === '') {
            return '';
        }
        $baseAgg = strtolower(trim((string) ($output['base_aggregate'] ?? 'sum')));
        if (! in_array($baseAgg, ['sum', 'avg'], true)) {
            $baseAgg = 'sum';
        }
        $baseRowUids = [];
        if (! empty($output['base_row_uids']) && is_array($output['base_row_uids'])) {
            foreach ($output['base_row_uids'] as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $baseRowUids[] = $u;
                }
            }
        }
        $chain = $output['chain'] ?? [];
        if (! is_array($chain)) {
            $chain = [];
        }
        $baseRowIndices = [];
        if (! empty($output['base_row_indices']) && is_array($output['base_row_indices'])) {
            foreach ($output['base_row_indices'] as $ix) {
                $baseRowIndices[] = (int) $ix;
            }
        }

        $uidToRow = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $meta = $row['_meta'] ?? [];
            if (is_string($meta)) {
                $meta = json_decode($meta, true) ?? [];
            }
            if (! is_array($meta)) {
                $meta = [];
            }
            $uid = trim((string) ($meta['row_uid'] ?? ''));
            if ($uid !== '') {
                $uidToRow[$uid] = $row;
            }
        }

        $toFloat = static function (string $raw): float {
            $clean = preg_replace('/[^0-9.\-]/', '', $raw);

            return ($clean !== '' && is_numeric($clean)) ? (float) $clean : 0.0;
        };

        $uidMissing = false;
        foreach ($baseRowUids as $uid) {
            if ($uid === '' || ! isset($uidToRow[$uid])) {
                $uidMissing = true;
                break;
            }
        }
        $useIndexFallback = $uidMissing
            && $baseRowUids !== []
            && count($baseRowIndices) === count($baseRowUids);
        $anyBaseUidResolved = false;
        foreach ($baseRowUids as $uid) {
            $u = trim((string) $uid);
            if ($u !== '' && isset($uidToRow[$u])) {
                $anyBaseUidResolved = true;
                break;
            }
        }
        $allBaseUidsAbsent = $baseRowUids !== [] && ! $anyBaseUidResolved;
        $usePositionalFallback = ! $useIndexFallback && $allBaseUidsAbsent
            && count($baseRowUids) === count($rows) && count($rows) > 0;

        $baseVals = [];
        if ($useIndexFallback) {
            foreach ($baseRowIndices as $rowIdx) {
                if ($rowIdx < 0 || ! array_key_exists($rowIdx, $rows) || ! is_array($rows[$rowIdx])) {
                    continue;
                }
                $raw = $this->getRowValueByKey($rows[$rowIdx], $baseSource);
                $baseVals[] = $toFloat($raw);
            }
        } elseif ($usePositionalFallback) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $raw = $this->getRowValueByKey($row, $baseSource);
                $baseVals[] = $toFloat($raw);
            }
        } else {
            foreach ($baseRowUids as $uid) {
                if (! isset($uidToRow[$uid])) {
                    continue;
                }
                $raw = $this->getRowValueByKey($uidToRow[$uid], $baseSource);
                $baseVals[] = $toFloat($raw);
            }
        }
        if ($baseVals === []) {
            return '';
        }
        $base = $baseAgg === 'avg'
            ? array_sum($baseVals) / count($baseVals)
            : array_sum($baseVals);

        $result = $base;
        foreach ($chain as $step) {
            if (! is_array($step)) {
                continue;
            }
            $op = trim((string) ($step['op'] ?? '-'));
            if ($op === '÷') {
                $op = '/';
            }
            if ($op === '×') {
                $op = '*';
            }
            if (! in_array($op, ['+', '-', '*', '/'], true)) {
                continue;
            }
            $cu = trim((string) ($step['row_uid'] ?? ''));
            $chainRow = null;
            if ($cu !== '' && isset($uidToRow[$cu])) {
                $chainRow = $uidToRow[$cu];
            } elseif (isset($step['row_index'])) {
                $ri = (int) $step['row_index'];
                if ($ri >= 0 && array_key_exists($ri, $rows) && is_array($rows[$ri])) {
                    $chainRow = $rows[$ri];
                }
            }
            if ($chainRow === null) {
                continue;
            }
            $srcKey = trim((string) ($step['source'] ?? ''));
            if ($srcKey === '') {
                $srcKey = $baseSource;
            }
            $raw = $this->getRowValueByKey($chainRow, $srcKey);
            $v = $toFloat($raw);
            $result = match ($op) {
                '+' => $result + $v,
                '-' => $result - $v,
                '*' => $result * $v,
                '/' => $v != 0.0 ? ($result / $v) : $result,
                default => $result,
            };
        }

        return number_format($result, 2, '.', '');
    }

    private function calculateSummaryOperation(array $rows, array $output): string
    {
        $uiCalcType = trim((string) ($output['ui_calc_type'] ?? ''));
        // Same-row blue formulas (A÷B×100, etc.) are evaluated in the browser from the summary row;
        // do not aggregate data rows here or the server overwrites the stored % with a wrong sum.
        if (in_array($uiCalcType, ['blue-row-formula', 'blue-row-formula-multi', 'blue-row-formula-custom'], true)) {
            return '';
        }

        // Sum/avg over a chosen set of rows, then left-fold + − × ÷ with more row values (matches Super Admin aggregate-chain UI).
        if ($uiCalcType === 'aggregate_chain') {
            return $this->calculateAggregateChain($rows, $output);
        }

        $operation = trim((string) ($output['ui_formula_operation'] ?? ''));
        if ($operation === '') {
            $operation = trim((string) ($output['operation'] ?? ''));
        }
        if ($operation === '') {
            $operation = 'sum';
        }
        $sourceA = $output['sourceA'] ?? '';
        $sourceB = $output['sourceB'] ?? '';
        $sourceColumns = [];
        if (!empty($output['source_columns']) && is_array($output['source_columns'])) {
            $sourceColumns = array_values(array_filter(array_map(static function ($key) {
                return trim((string) $key);
            }, $output['source_columns']), static function ($key) {
                return $key !== '';
            }));
        }
        $suffix = $output['suffix'] ?? '';

        // When row_uids is set, compute over selected rows by stable row identity.
        // This survives row insert/delete/reorder better than index-based filtering.
        if (!empty($output['row_uids']) && is_array($output['row_uids'])) {
            $uidSet = [];
            foreach ($output['row_uids'] as $uid) {
                $uid = trim((string)$uid);
                if ($uid !== '') {
                    $uidSet[$uid] = true;
                }
            }
            if (!empty($uidSet)) {
                $filtered = [];
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $meta = $row['_meta'] ?? [];
                    if (is_string($meta)) {
                        $meta = json_decode($meta, true);
                    }
                    if (!is_array($meta)) {
                        $meta = [];
                    }
                    $rowUid = trim((string)($meta['row_uid'] ?? ''));
                    if ($rowUid !== '' && isset($uidSet[$rowUid])) {
                        $filtered[] = $row;
                    }
                }
                if (!empty($filtered)) {
                    $rows = $filtered;
                } elseif (!empty($output['row_indices']) && is_array($output['row_indices'])) {
                    // Stale row_uids from another save must not widen scope to all rows (wrong avg/sum).
                    $filtered = [];
                    foreach ($output['row_indices'] as $idx) {
                        if (array_key_exists((int) $idx, $rows)) {
                            $filtered[] = $rows[(int) $idx];
                        }
                    }
                    if (!empty($filtered)) {
                        $rows = $filtered;
                    }
                }
            }
        }

        // When row_indices is set, compute over selected rows only (legacy fallback).
        if (!empty($output['row_indices']) && is_array($output['row_indices'])) {
            $filtered = [];
            foreach ($output['row_indices'] as $idx) {
                if (array_key_exists($idx, $rows)) {
                    $filtered[] = $rows[$idx];
                }
            }
            $rows = $filtered;
        }

        // sum_with_suffix: sum source column and append suffix (e.g. "0.00%")
        if ($operation === 'sum_with_suffix' && $sourceA !== '') {
            $sum = 0.0;
            foreach ($rows as $row) {
                $value = $this->getRowValueByKey($row, $sourceA);
                $clean = preg_replace('/[^0-9.\-]/', '', $value);
                if ($clean !== '' && is_numeric($clean)) {
                    $sum += (float)$clean;
                }
            }
            return number_format($sum, 2, '.', '') . $suffix;
        }

        // avg_with_suffix: average of source column and append suffix (e.g. "36.91%")
        if ($operation === 'avg_with_suffix' && $sourceA !== '') {
            $values = [];
            foreach ($rows as $row) {
                $value = $this->getRowValueByKey($row, $sourceA);
                $clean = preg_replace('/[^0-9.\-]/', '', $value);
                if ($clean !== '' && is_numeric($clean)) {
                    $values[] = (float)$clean;
                }
            }
            $avg = count($values) > 0 ? array_sum($values) / count($values) : 0.0;
            return number_format($avg, 2, '.', '') . $suffix;
        }

        // ratio / ratio_percent: sum(column A) / sum(column B) for summary (e.g. total promoted / total graduates)
        if (in_array($operation, ['ratio', 'ratio_percent'], true) && $sourceA !== '' && $sourceB !== '') {
            $sumA = 0.0;
            $sumB = 0.0;
            foreach ($rows as $row) {
                $valA = $this->getRowValueByKey($row, $sourceA);
                $valB = $this->getRowValueByKey($row, $sourceB);
                $cleanA = preg_replace('/[^0-9.\-]/', '', $valA);
                $cleanB = preg_replace('/[^0-9.\-]/', '', $valB);
                if ($cleanA !== '' && is_numeric($cleanA)) {
                    $sumA += (float)$cleanA;
                }
                if ($cleanB !== '' && is_numeric($cleanB)) {
                    $sumB += (float)$cleanB;
                }
            }
            if ($sumB == 0.0) {
                return '0.00';
            }
            $ratio = $sumA / $sumB;
            if ($operation === 'ratio_percent') {
                $ratio *= 100;
            }
            return number_format($ratio, 2, '.', '');
        }

        // Row count summaries do not need a source column (Planning Coordinator / NO. column).
        if ($operation === 'count_rows') {
            return (string) count($rows);
        }

        // For count_total (Count All Values): use ALL source_columns when user selected multiple columns (e.g. 8 cells from 2 cols).
        // For count_unique/sum/avg: use one column (sourceA or source_columns[0]) to avoid wrong aggregation.
        $effectiveSourceKeys = [];
        if ($operation === 'count_total' && !empty($sourceColumns)) {
            $effectiveSourceKeys = $sourceColumns;
        } elseif ($sourceA !== '') {
            $effectiveSourceKeys = [$sourceA];
        } elseif (!empty($sourceColumns)) {
            $effectiveSourceKeys = [reset($sourceColumns)];
        }
        if (empty($effectiveSourceKeys)) {
            return '';
        }

        $numericValues = [];
        $textValues = [];
        foreach ($rows as $row) {
            foreach ($effectiveSourceKeys as $sourceKey) {
                $value = $this->getRowValueByKey($row, $sourceKey);
                $textValues[] = trim($value);
                $clean = preg_replace('/[^0-9.\-]/', '', $value);
                if ($clean !== '' && is_numeric($clean)) {
                    $numericValues[] = (float)$clean;
                }
            }
        }

        switch ($operation) {
            case 'avg':
                return count($numericValues) > 0 ? number_format(array_sum($numericValues) / count($numericValues), 2, '.', '') : '0.00';
            case 'avg_percentage':
                return count($numericValues) > 0
                    ? number_format(array_sum($numericValues) / count($numericValues), 2, '.', '') . '%'
                    : '0.00%';
            case 'count_unique':
                $set = [];
                foreach ($textValues as $v) {
                    if ($v === '') continue;
                    $set[strtolower(preg_replace('/\s+/', ' ', $v))] = true;
                }
                $base = count($set);
                $adjust = (int) ($output['count_adjust'] ?? 0);
                $result = $base + $adjust;

                return (string) max(0, $result);
            case 'count_duplicates':
                $norm = array_map(fn($v) => strtolower(preg_replace('/\s+/', ' ', $v)), array_filter($textValues, fn($v) => $v !== ''));
                $freq = array_count_values($norm);
                $dup = 0;
                foreach ($freq as $count) {
                    if ($count > 1) $dup += $count;
                }
                return (string)$dup;
            case 'count_total':
                // Don't count "0" as data so placeholder zeros don't inflate the summary
                return (string)count(array_filter($textValues, fn($v) => $v !== '' && $v !== '0'));
            case 'sum':
            default:
                return number_format(array_sum($numericValues), 2, '.', '');
        }
    }

    /**
     * Calculate value based on calculation type.
     */
    public function calculateValue(string $calcType, array $values, array $meta, array $allRowData = [], array $currentRow = []): string
    {
        switch ($calcType) {
            case 'unique':
                $uniqueMap = [];
                foreach ($values as $val) {
                    $displayValue = trim((string)preg_replace('/\s+/', ' ', (string)$val));
                    $normalized = strtolower($displayValue);
                    if ($normalized !== '' && !array_key_exists($normalized, $uniqueMap)) {
                        $uniqueMap[$normalized] = $displayValue;
                    }
                }
                return (string)count($uniqueMap);

            case 'sum':
                $sum = 0.0;
                foreach ($values as $val) {
                    $sum += (float)$val;
                }
                return (string)$sum;

            case 'countif':
                return (string)count(array_filter($values, fn($v) => trim((string)$v) !== ''));

            case 'avg_percentage':
                $nums = [];
                foreach ($values as $val) {
                    $clean = preg_replace('/[^0-9.\-]/', '', (string)$val);
                    if ($clean !== '' && is_numeric($clean)) {
                        $nums[] = (float)$clean;
                    }
                }
                return count($nums) > 0 ? number_format(array_sum($nums) / count($nums), 2, '.', '') : '0.00';

            case 'formula':
                $operation = $meta['operation'] ?? 'sum';
                $scope = $meta['scope'] ?? 'row';
                $sourceA = $meta['sourceA'] ?? '';
                $sourceB = $meta['sourceB'] ?? '';

                $toFloat = static function ($value): float {
                    if (is_numeric($value)) {
                        return (float)$value;
                    }
                    $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
                    return is_numeric($clean) ? (float)$clean : 0.0;
                };

                $getValue = function (string $fieldKey) use ($scope, $allRowData, $currentRow, $toFloat): float {
                    if ($fieldKey === '') return 0.0;
                    if ($scope === 'all_rows') {
                        $sum = 0.0;
                        foreach ($allRowData as $row) {
                            $sum += $toFloat($row[$fieldKey] ?? 0);
                        }
                        return $sum;
                    }
                    return $toFloat($currentRow[$fieldKey] ?? 0);
                };

                $a = $getValue($sourceA);
                $b = $getValue($sourceB);
                $result = match ($operation) {
                    'sum' => $a + $b,
                    'subtract' => $a - $b,
                    'multiply' => $a * $b,
                    'divide' => $b != 0.0 ? ($a / $b) : 0.0,
                    'percent_of' => $b != 0.0 ? (($a / $b) * 100) : 0.0,
                    'sum_over_b_percent' => $b != 0.0 ? ((($a + $b) / $b) * 100) : 0.0,
                    'diff_over_b_percent' => $b != 0.0 ? ((($a - $b) / $b) * 100) : 0.0,
                    default => 0.0,
                };
                return number_format($result, 2, '.', '');

            default:
                return '';
        }
    }

    /**
     * Ensure schema matches Super Admin: add Variance, Rate of Accomplishment, Descriptive Rating if missing.
     * Used by Planning Coordinator edit view so table structure aligns with Super Admin.
     */
    public static function ensureSchemaMatchesSuperAdmin(array $fields): array
    {
        $normalizeToken = function (array $f): string {
            $key = strtolower(trim((string) ($f['key'] ?? $f['name'] ?? '')));
            $label = strtolower(trim((string) ($f['label'] ?? '')));
            $combined = trim(preg_replace('/[^a-z0-9]+/i', '_', $key . '_' . $label), '_');
            return trim($combined, '_');
        };

        $hasVariance = false;
        $hasRate = false;
        $hasRating = false;
        foreach ($fields as $f) {
            $token = $normalizeToken(is_array($f) ? $f : []);
            if (str_contains($token, 'variance')) {
                $hasVariance = true;
            }
            if (str_contains($token, 'rate') && (str_contains($token, 'accomp') || str_contains($token, 'accomplishment'))) {
                $hasRate = true;
            }
            if (str_contains($token, 'descriptive') || str_contains($token, 'rating')) {
                $hasRating = true;
            }
        }

        $out = $fields;
        if (!$hasVariance) {
            $out[] = ['label' => 'Variance', 'type' => 'number', 'key' => 'variance'];
        }
        if (!$hasRate) {
            $out[] = ['label' => 'Rate of Accomplishment (%)', 'type' => 'number', 'key' => 'rate_of_accomplishment'];
        }
        if (!$hasRating) {
            $out[] = ['label' => 'Descriptive Rating', 'type' => 'text', 'key' => 'descriptive_rating'];
        }

        return $out;
    }

    /**
     * Remove Variance, Rate of Accomplishment, and Descriptive Rating columns from the schema for the
     * Planning Coordinator edit view only (those columns are not maintained there; mirrors JS isPerformanceMetricField).
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    public static function excludePerformanceMetricFieldsForPlanningCoordinator(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            if (! is_array($f)) {
                continue;
            }
            if (self::fieldLooksLikePerformanceMetricColumn($f)) {
                continue;
            }
            $out[] = $f;
        }

        return array_values($out);
    }

    /** @param  array<string, mixed>  $f */
    public static function fieldLooksLikePerformanceMetricColumn(array $f): bool
    {
        $t = strtolower(trim((string) ($f['key'] ?? '')).'_'.trim((string) ($f['label'] ?? '')));
        $t = preg_replace('/[^a-z0-9]+/', '_', $t);

        return str_contains($t, 'variance')
            || (str_contains($t, 'rate') && (str_contains($t, 'accomp') || str_contains($t, 'accomplishment')))
            || str_contains($t, 'descriptive')
            || str_contains($t, 'rating');
    }
}
