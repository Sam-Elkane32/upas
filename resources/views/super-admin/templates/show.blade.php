<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    @php
        $readOnly = !empty($readOnly ?? false);
        $viewOnlyBackUrl = $viewOnlyBackUrl ?? null;
        $quickAccessTemplates = $quickAccessTemplates ?? collect([]);
        if (!($quickAccessTemplates instanceof \Illuminate\Support\Collection)) {
            $quickAccessTemplates = collect($quickAccessTemplates ?: []);
        }
        $hasQuickAccessFooter = $quickAccessTemplates->count() > 0;
    @endphp
    <div class="pt-2 {{ $hasQuickAccessFooter ? 'pb-16' : 'pb-4' }}">

        @include('super-admin.templates.partials.show-unsaved-changes-modal')

        <div class="max-w-full w-full sm:px-6 lg:px-8">

        @include('super-admin.templates.partials.show-page-header')
        @include('super-admin.templates.partials.show-template-information')

            <!-- Field Structure -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Field Structure</h3>
                    </div>
                    @include('super-admin.templates.partials.show-js-field-helpers')
                    
                    @if($template->fields_json && isset($template->fields_json['fields']) && count($template->fields_json['fields']) > 0)
                        @php
                            $fieldsAll = $template->fields_json['fields'] ?? [];
                            $coordinatorSubmissions = $coordinatorSubmissions ?? [];
                            $getFieldKey = function($f) {
                                $k = $f['key'] ?? $f['name'] ?? null;
                                if ($k !== null && $k !== '') return (string) $k;
                                $label = $f['label'] ?? '';
                                $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim((string) $label)));
                                return trim($normalized, '_');
                            };
                            $normalizeFieldMetricToken = function(array $f) use ($getFieldKey) {
                                $key = strtolower(trim((string) $getFieldKey($f)));
                                $label = strtolower(trim((string) ($f['label'] ?? '')));
                                $combined = trim($key . '_' . $label, '_');
                                $combined = preg_replace('/[^a-z0-9]+/i', '_', $combined);
                                return trim((string) $combined, '_');
                            };
                            // Exclude Variance, Rate of Accomplishment, Descriptive Rating from field structure.
                            // They appear only in Compare to Campus Target panel, not in the table.
                            $isPerfField = function($f) use ($normalizeFieldMetricToken) {
                                $t = $normalizeFieldMetricToken(is_array($f) ? $f : []);
                                return str_contains($t, 'variance') || (str_contains($t, 'rate') && (str_contains($t, 'accomp') || str_contains($t, 'accomplishment'))) || str_contains($t, 'descriptive') || str_contains($t, 'rating');
                            };
                            $fieldsRaw = array_values(array_filter($fieldsAll, fn($f) => !$isPerfField($f)));
                            [$fields, $tableHeaderPlan, $tableHeaderTwoRows] = \App\Support\TemplateTableGrid::expandFieldsWithSubheaderGroups($fieldsRaw, $getFieldKey);
                            $performanceStickySlotByIndex = [];
                            // Evidence column key in table_data may differ per campus block; scan all data rows (not only row 0 / first block).
                            $resolveEvidenceColumnKeyFromTableData = function (?array $tableData): ?string {
                                if (empty($tableData) || !is_array($tableData)) {
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
                                    foreach (array_keys($row) as $rk) {
                                        if ($rk === '_meta' || $rk === '_after_separator') {
                                            continue;
                                        }
                                        $n = strtolower(str_replace(['-', ' '], '_', (string) $rk));
                                        if (str_contains($n, 'evidence') && str_contains($n, 'verified')
                                            && (str_contains($n, 'qa') || str_contains($n, 'q_a') || str_contains($n, 'm_e'))) {
                                            return $rk;
                                        }
                                    }
                                }
                                return null;
                            };
                            $evidenceColumnKey = null;
                            if (count($coordinatorSubmissions) > 0) {
                                foreach ($coordinatorSubmissions as $_evBlock) {
                                    $evidenceColumnKey = $resolveEvidenceColumnKeyFromTableData($_evBlock['table_data'] ?? []);
                                    if ($evidenceColumnKey !== null) {
                                        break;
                                    }
                                }
                            }
                            if ($evidenceColumnKey === null && isset($latestSubmission) && $latestSubmission && is_array($latestSubmission->table_data)) {
                                $evidenceColumnKey = $resolveEvidenceColumnKeyFromTableData($latestSubmission->table_data);
                            }
                            $getCellValue = function ($row, $field, $explicitEvidenceKey = null) use ($getFieldKey, $evidenceColumnKey) {
                                if (!is_array($row)) {
                                    return '';
                                }
                                $key = $getFieldKey($field);
                                $labelLower = strtolower((string) ($field['label'] ?? ''));
                                $isEvidenceField = str_contains($labelLower, 'evidence') && str_contains($labelLower, 'verified')
                                    && (str_contains($labelLower, 'qa') || str_contains($labelLower, 'm&e') || str_contains($labelLower, 'm e'));
                                $evKey = $explicitEvidenceKey !== null && $explicitEvidenceKey !== '' ? $explicitEvidenceKey : $evidenceColumnKey;
                                if ($isEvidenceField && $evKey !== null) {
                                    $evVal = (string) ($row[$evKey] ?? '');
                                    if ($evVal !== '') {
                                        return $evVal;
                                    }
                                }
                                if (array_key_exists($key, $row)) {
                                    return $row[$key];
                                }
                                if ($evKey !== null && $isEvidenceField) {
                                    return (string) ($row[$evKey] ?? '');
                                }
                                foreach (array_keys($row) as $rk) {
                                    if ($rk === '_meta') {
                                        continue;
                                    }
                                    $normalizedRk = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $rk));
                                    if (strlen($normalizedRk) <= 3 && strlen($key) > 10) {
                                        continue;
                                    }
                                    if ($normalizedRk === $key) {
                                        return $row[$rk];
                                    }
                                }
                                if (! empty($field['_grid_is_subcolumn']) && (int) ($field['_grid_subcolumn_index'] ?? 0) === 0) {
                                    $pk = $field['_grid_parent_key'] ?? '';
                                    if ($pk !== '' && array_key_exists($pk, $row)) {
                                        $subVal = $row[$key] ?? null;
                                        if ($subVal === null || $subVal === '') {
                                            return $row[$pk];
                                        }
                                    }
                                }
                                return '';
                            };
                            $hasMultipleCoordinatorData = count($coordinatorSubmissions) > 0;
                            $latestSubmission = $latestSubmission ?? null;
                            $submissionRows = (isset($latestSubmission) && $latestSubmission && is_array($latestSubmission->table_data)) ? $latestSubmission->table_data : [];
                            $defaultRows = count($submissionRows) > 0 ? count($submissionRows) : 5;
                            $hasSubmissionData = count($submissionRows) > 0;
                            $coordinatorBlocksForJs = array_map(function($b) {
                                return [
                                    'submission_id' => $b['submission_id'],
                                    'user_id' => $b['user_id'] ?? null,
                                    'submitter_name' => $b['submitter_name'],
                                    'campus' => $b['campus'] ?? null,
                                    'display_label' => $b['display_label'] ?? null,
                                ];
                            }, $coordinatorSubmissions);
                        @endphp
                        @if($hasMultipleCoordinatorData)
                            {{-- Data from all assigned coordinators: Super Admin can create, edit, delete --}}
                            <p class="text-sm text-gray-600 mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $readOnly ? 'bg-slate-100 text-slate-700' : 'bg-green-100 text-green-800' }}">
                                    @if($readOnly)
                                        Data from all assigned Planning Coordinators. View only - editing is disabled.
                                    @else
                                        Data from all assigned Planning Coordinators. Edits auto-save and will reflect for them.
                                    @endif
                                </span>
                                @if(!$readOnly)
                                <span id="autosave-status" class="ml-2 text-xs text-gray-400" aria-live="polite">Draft autosave on</span>
                                @endif
                            </p>
                            @if(request()->boolean('debug_targets') && !empty($campusTargetsModel))
                                <details class="mb-3 border border-indigo-200 bg-indigo-50 rounded p-2">
                                    <summary class="text-xs font-semibold text-indigo-800 cursor-pointer">Campus targets model (debug)</summary>
                                    <pre class="mt-2 text-[11px] leading-4 whitespace-pre-wrap text-indigo-900 overflow-auto max-h-56">{{ json_encode($campusTargetsModel, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        @php
                            // Campus-level targets configured on the template (if any)
                            $campusTargetsConfig = $template->fields_json['campus_targets'] ?? [];
                            $campusTargetsModelCampuses = is_array($campusTargetsModel ?? null) && isset($campusTargetsModel['campuses']) && is_array($campusTargetsModel['campuses'])
                                ? $campusTargetsModel['campuses']
                                : [];
                            $normalizeCampusTokenForTargets = function (?string $value): string {
                                $key = trim((string) $value);
                                if ($key === '') return '';
                                $key = preg_replace('/\s*planning\s+coordinator\s*$/i', '', $key);
                                $key = preg_replace('/\s*campus\s*$/i', '', $key);
                                $key = preg_replace('/^psu\s+/i', '', $key);
                                $key = preg_replace('/^pel\.?\s*/i', '', $key);
                                $key = preg_replace('/^\s*(the\s+)?(campus\s+of\s+)/i', '', $key);
                                $key = strtoupper(trim((string) preg_replace('/\s+/', ' ', $key)));
                                $key = str_replace('.', '', $key);
                                return trim($key);
                            };
                            $campusTargetsByNormalizedKey = [];
                            foreach ($campusTargetsConfig as $campusCode => $vals) {
                                $n = $normalizeCampusTokenForTargets((string) $campusCode);
                                if ($n !== '' && is_array($vals)) {
                                    $campusTargetsByNormalizedKey[$n] = [
                                        'q1' => (float) ($vals['q1'] ?? 0),
                                        'q2' => (float) ($vals['q2'] ?? 0),
                                        'q3' => (float) ($vals['q3'] ?? 0),
                                        'q4' => (float) ($vals['q4'] ?? 0),
                                        'total' => (float) ($vals['total_target'] ?? 0),
                                    ];
                                }
                            }
                            foreach ($campusTargetsModelCampuses as $campusCode => $campusModelVals) {
                                $n = $normalizeCampusTokenForTargets((string) $campusCode);
                                if ($n === '' || !is_array($campusModelVals)) {
                                    continue;
                                }
                                $targetVals = is_array($campusModelVals['target'] ?? null) ? $campusModelVals['target'] : [];
                                $campusTargetsByNormalizedKey[$n] = [
                                    'q1' => (float) ($targetVals['q1'] ?? 0),
                                    'q2' => (float) ($targetVals['q2'] ?? 0),
                                    'q3' => (float) ($targetVals['q3'] ?? 0),
                                    'q4' => (float) ($targetVals['q4'] ?? 0),
                                    'total' => (float) ($targetVals['total'] ?? 0),
                                ];
                            }
                            $summaryTargetFields = $summaryTargetFields ?? [];

                            // Determine indices for campus target mapping in the blue summary row
                            $firstNumberFieldIndex = null; // Fallback if we cannot find a dedicated target column
                            $targetFieldIndex = null;      // Column whose label mentions "target"
                            $quarterFieldIndices = [
                                'q1' => null,
                                'q2' => null,
                                'q3' => null,
                                'q4' => null,
                            ];

                            foreach ($fieldsRaw as $i => $field) {
                                $type = $field['type'] ?? '';
                                $labelLower = strtolower($field['label'] ?? '');

                                if ($firstNumberFieldIndex === null && $type === 'number') {
                                    $firstNumberFieldIndex = $i;
                                }

                                // Prefer a dedicated "Target" column (e.g. "Target", "Total Target") for overall campus target
                                if ($targetFieldIndex === null && str_contains($labelLower, 'target') && $type === 'number') {
                                    $targetFieldIndex = $i;
                                }

                                // Optional: per-quarter target columns if your table has them
                                if ($quarterFieldIndices['q1'] === null &&
                                    (str_contains($labelLower, 'q1') || str_contains($labelLower, '1st q') || str_contains($labelLower, '1st quarter') || str_contains($labelLower, 'first quarter'))) {
                                    $quarterFieldIndices['q1'] = $i;
                                }
                                if ($quarterFieldIndices['q2'] === null &&
                                    (str_contains($labelLower, 'q2') || str_contains($labelLower, '2nd q') || str_contains($labelLower, '2nd quarter') || str_contains($labelLower, 'second quarter'))) {
                                    $quarterFieldIndices['q2'] = $i;
                                }
                                if ($quarterFieldIndices['q3'] === null &&
                                    (str_contains($labelLower, 'q3') || str_contains($labelLower, '3rd q') || str_contains($labelLower, '3rd quarter') || str_contains($labelLower, 'third quarter'))) {
                                    $quarterFieldIndices['q3'] = $i;
                                }
                                if ($quarterFieldIndices['q4'] === null &&
                                    (str_contains($labelLower, 'q4') || str_contains($labelLower, '4th q') || str_contains($labelLower, '4th quarter') || str_contains($labelLower, 'fourth quarter'))) {
                                    $quarterFieldIndices['q4'] = $i;
                                }
                            }
                        @endphp
                        <style>
                            /* Single-container cells: no inner box; selection applies to the whole cell */
                            #table-container-multi td input,
                            #table-container-multi td select,
                            #table-container-multi td textarea {
                                background: transparent !important;
                                border: none !important;
                                box-shadow: none !important;
                                outline: none;
                                border-radius: 0;
                            }
                            #table-container-multi td input:focus,
                            #table-container-multi td select:focus,
                            #table-container-multi td textarea:focus {
                                box-shadow: none !important;
                                outline: none;
                            }
                            #table-container-multi td.cell-selected {
                                outline: none;
                                position: relative;
                            }
                            /* Only the outer ring sits on top (e.g. above popover); cell content stays underneath */
                            #table-container-multi td.cell-selected::after {
                                content: '';
                                position: absolute;
                                inset: 0;
                                border: 3px solid rgb(79 70 229);
                                pointer-events: none;
                                z-index: 101;
                                box-sizing: border-box;
                            }
                            /* Blue row formula source cells: highlight when a blue result cell is selected */
                            #table-container-multi td.cell-source-for-blue {
                                outline: none;
                                position: relative;
                            }
                            #table-container-multi td.cell-source-for-blue::after {
                                content: '';
                                position: absolute;
                                inset: 0;
                                border: 2px dashed rgb(217 119 6);
                                /* Keep blue-row background visible; source cue is border-only. */
                                background: transparent;
                                pointer-events: none;
                                z-index: 99;
                                box-sizing: border-box;
                            }
                            /* Table readability: min column width so data is visible and not truncated */
                            #table-container-multi table {
                                table-layout: auto;
                                width: 100%;
                            }
                            #table-container-multi th,
                            #table-container-multi td {
                                min-width: 10rem;
                            }
                            #table-container-multi th {
                                white-space: normal;
                                word-wrap: break-word;
                                overflow-wrap: break-word;
                            }
                            #table-container-multi td {
                                white-space: normal;
                                word-wrap: break-word;
                                overflow-wrap: break-word;
                                vertical-align: top;
                            }
                            #table-container-multi td input[type="text"],
                            #table-container-multi td input[type="number"],
                            #table-container-multi td select,
                            #table-container-multi td textarea {
                                min-width: 0;
                                width: 100%;
                                max-width: 100%;
                            }
                            body.uaps-reselect-cells-pick {
                                cursor: copy;
                            }
                            body.uaps-reselect-cells-pick #table-container-multi tr.data-row.bg-blue-100 td {
                                cursor: pointer;
                            }
                            /* Selection popover: allow clicks to pass through to table so user can select cells behind it */
                            #selection-popover { pointer-events: none; }
                            /* Content column must receive clicks (flex gaps, padding, helper text); otherwise hits fall through to the table and clear selection */
                            #selection-popover #selection-popover-inner { pointer-events: auto; }
                            #selection-popover #selection-popover-drag,
                            #selection-popover #selection-calc-type,
                            #selection-popover #compare-campus-target-options,
                            #selection-popover #compare-campus-target-column,
                            #selection-popover #compare-campus-target-value-preview,
                            #selection-popover #grand-total-quarter-options,
                            #selection-popover #grand-total-quarter-select,
                            #selection-popover #unique-adjust-options,
                            #selection-popover #unique-adjust-operator,
                            #selection-popover #unique-adjust-amount,
                            #selection-popover #grand-total-cascade-wizard,
                            #selection-popover #grand-total-cascade-wizard select,
                            #selection-popover #grand-total-cascade-wizard label,
                            #selection-popover #selection-apply-calc-btn { pointer-events: auto; }
                            #selection-popover:has(#grand-total-cascade-wizard:not(.hidden)) #selection-calc-type {
                                display: none !important;
                            }
                        </style>
                        <div class="border border-gray-200 rounded-lg relative overflow-visible" id="table-container-multi">
                                @php
                                    $fieldMetaByIndex = [];
                                    $lastVisibleColIndexForDelete = -1;
                                    foreach ($fields as $fIdx => $f) {
                                        $fieldKeyMeta = $getFieldKey($f);
                                        $fieldLabelMeta = (string) ($f['label'] ?? '');
                                        $labelNormMeta = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $fieldLabelMeta));
                                        $metricTokenMeta = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', ((string) $fieldKeyMeta) . '_' . $fieldLabelMeta));
                                        $isPerformanceMetricMeta = str_contains($metricTokenMeta, 'variance')
                                            || (str_contains($metricTokenMeta, 'rate') && (str_contains($metricTokenMeta, 'accomp') || str_contains($metricTokenMeta, 'accomplishment')))
                                            || str_contains($metricTokenMeta, 'descriptive')
                                            || str_contains($metricTokenMeta, 'rating');
                                        if (!$isPerformanceMetricMeta) {
                                            $lastVisibleColIndexForDelete = $fIdx;
                                        }
                                        $stickySlotMeta = $performanceStickySlotByIndex[$fIdx] ?? null;
                                        $subheadersMeta = $f['subheaders'] ?? [];
                                        $subheadersMeta = is_array($subheadersMeta) ? array_values(array_filter(array_map('strval', $subheadersMeta))) : [];
                                        $isNoColumnMeta = $labelNormMeta === 'no';
                                        $isNumOrCalcMeta = ($f['type'] ?? '') === 'number' || isset($f['meta']['calc']);
                                        $fieldMetaByIndex[$fIdx] = [
                                            'field_key' => $fieldKeyMeta,
                                            'is_no_column' => $isNoColumnMeta,
                                            'is_num_or_calc' => $isNumOrCalcMeta,
                                            'is_performance_metric' => $isPerformanceMetricMeta,
                                            'sticky_class' => $stickySlotMeta !== null ? ('sticky-perf sticky-perf-' . $stickySlotMeta) : '',
                                            'col_width_class' => ($isNoColumnMeta || str_contains($labelNormMeta, 'quarter') || in_array($labelNormMeta, ['q1', 'q2', 'q3', 'q4'], true)) ? 'col-narrow' : 'col-default',
                                            'subheaders' => $subheadersMeta,
                                        ];
                                    }
                                    if ($lastVisibleColIndexForDelete < 0) {
                                        $lastVisibleColIndexForDelete = count($fields) - 1;
                                    }
                                @endphp
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200" id="field-structure-table-multi">
                                        <thead class="bg-gray-50">
                                            @if(!empty($tableHeaderTwoRows))
                                                <tr>
                                                    @php $hExpIdx = 0; @endphp
                                                    @foreach($tableHeaderPlan as $h)
                                                        @if(($h['kind'] ?? '') === 'single')
                                                            @php
                                                                $field = $fields[$hExpIdx];
                                                                $hExpIdx++;
                                                                $thIdx = $hExpIdx - 1;
                                                                $fieldMeta = $fieldMetaByIndex[$thIdx] ?? [];
                                                                $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                                $isNumOrCalc = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                                $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                                $alignCell = $isNumOrCalc && !$isNoColumn ? 'text-right' : 'text-center';
                                                                $shOne = $fieldMeta['subheaders'] ?? [];
                                                                $hasOneSub = count($shOne) === 1 && ($shOne[0] ?? '') !== '';
                                                            @endphp
                                                            @if($hasOneSub)
                                                                <th rowspan="2" class="p-0 align-top text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 {{ $alignCell }} {{ $stickyClass }}">
                                                                    <div class="border-b border-gray-300 px-4 py-2 {{ $alignCell }}">
                                                                        <span class="block">{{ $field['label'] ?? 'N/A' }}</span>
                                                                    </div>
                                                                    <div class="px-4 py-2 {{ $alignCell }}">
                                                                        <span class="block text-[10px] font-normal normal-case text-gray-600 leading-tight">{{ $shOne[0] }}</span>
                                                                    </div>
                                                                </th>
                                                            @else
                                                                <th rowspan="2" class="px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 {{ $alignCell }} {{ $stickyClass }} align-middle">
                                                                    <span class="block">{{ $field['label'] ?? 'N/A' }}</span>
                                                                </th>
                                                            @endif
                                                        @else
                                                            @php
                                                                $n = count($h['subs'] ?? []);
                                                                $hExpIdx += $n;
                                                            @endphp
                                                            <th colspan="{{ $n }}" class="px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-b border-gray-300 text-center align-middle">
                                                                <span class="block">{{ $h['parent_label'] ?? '' }}</span>
                                                            </th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                                <tr>
                                                    @php $hExpIdx = 0; @endphp
                                                    @foreach($tableHeaderPlan as $h)
                                                        @if(($h['kind'] ?? '') === 'single')
                                                            @php $hExpIdx++; @endphp
                                                        @else
                                                            @foreach(($h['subs'] ?? []) as $subLbl)
                                                                @php
                                                                    $field = $fields[$hExpIdx];
                                                                    $hExpIdx++;
                                                                    $thIdx = $hExpIdx - 1;
                                                                    $fieldMeta = $fieldMetaByIndex[$thIdx] ?? [];
                                                                    $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                                @endphp
                                                                {{-- Sub-label row (M/F): always centered; body cells keep number alignment --}}
                                                                <th class="px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 text-center {{ $stickyClass }}">
                                                                    <span class="block">{{ $subLbl }}</span>
                                                                </th>
                                                            @endforeach
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            @else
                                                <tr>
                                                    @foreach($fields as $idx => $field)
                                                        @php
                                                            $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                            $isNumOrCalc = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                            $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                            $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                        @endphp
                                                        <th class="px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 {{ $isNumOrCalc && !$isNoColumn ? 'text-right' : 'text-center' }} {{ $stickyClass }}">
                                                            <span class="block">{{ $field['label'] ?? 'N/A' }}</span>
                                                            @php
                                                                $shOne = $fieldMeta['subheaders'] ?? [];
                                                            @endphp
                                                            @if(count($shOne) === 1 && ($shOne[0] ?? '') !== '')
                                                                <span class="block text-[10px] font-normal normal-case text-gray-600 mt-0.5 leading-tight">{{ $shOne[0] }}</span>
                                                            @endif
                                                        </th>
                                                    @endforeach
                                                </tr>
                                            @endif
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="table-body-multi" data-last-submission-id="{{ count($coordinatorSubmissions) > 0 ? ($coordinatorSubmissions[count($coordinatorSubmissions)-1]['submission_id'] ?? '') : '' }}" data-last-visible-col-index="{{ $lastVisibleColIndexForDelete }}">
                                            @foreach($coordinatorSubmissions as $blockIndex => $block)
                                                @php
                                                    $campusTargetForBlock = null;
                                                    $candidateKeys = [
                                                        $normalizeCampusTokenForTargets((string) ($block['campus'] ?? '')),
                                                        $normalizeCampusTokenForTargets((string) ($block['display_label'] ?? '')),
                                                        $normalizeCampusTokenForTargets((string) ($block['submitter_name'] ?? '')),
                                                    ];
                                                    foreach ($candidateKeys as $candidateKey) {
                                                        if ($candidateKey !== '' && isset($campusTargetsByNormalizedKey[$candidateKey])) {
                                                            $campusTargetForBlock = $campusTargetsByNormalizedKey[$candidateKey];
                                                            break;
                                                        }
                                                    }
                                                @endphp
                                                <tr class="bg-gray-100 border-t-2 border-gray-300 section-header-row border-l-4 border-indigo-200" data-submission-id="{{ $block['submission_id'] ?? '' }}" data-user-id="{{ $block['user_id'] ?? '' }}">
                                                    <td colspan="{{ count($fields) }}" class="px-4 py-2.5">
                                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                                        <span class="text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ $block['display_label'] ?? $block['submitter_name'] }}</span>
                                                        <div class="flex items-center gap-2 flex-wrap justify-end">
                                                            @if(is_array($campusTargetForBlock))
                                                                @php $targetSuffix = !empty($targetIsPercentage ?? false) ? '%' : ''; @endphp
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-100 text-amber-800 border border-amber-200 normal-case">
                                                                    Target: Q1 {{ number_format((float) ($campusTargetForBlock['q1'] ?? 0), 2) }}{{ $targetSuffix }} |
                                                                    Q2 {{ number_format((float) ($campusTargetForBlock['q2'] ?? 0), 2) }}{{ $targetSuffix }} |
                                                                    Q3 {{ number_format((float) ($campusTargetForBlock['q3'] ?? 0), 2) }}{{ $targetSuffix }} |
                                                                    Q4 {{ number_format((float) ($campusTargetForBlock['q4'] ?? 0), 2) }}{{ $targetSuffix }} |
                                                                    Total {{ number_format((float) ($campusTargetForBlock['total'] ?? 0), 2) }}{{ $targetSuffix }}
                                                                </span>
                                                            @endif
                                                            <span class="text-xs text-gray-500 font-normal normal-case">{{ $block['updated_at']->format('M j, Y g:i A') }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                </tr>
                                                @php
                                                    $blockHasData = !empty($block['table_data']);
                                                    $blockEvidenceKey = $resolveEvidenceColumnKeyFromTableData($block['table_data'] ?? []);
                                                @endphp
                                                @if($blockHasData)
                                                @foreach($block['table_data'] as $row)
                                                    @php
                                                        $meta = $row['_meta'] ?? null;
                                                        if (is_string($meta)) {
                                                            $meta = json_decode($meta, true);
                                                        }
                                                        $meta = is_array($meta) ? $meta : [];
                                                        $manualOverrideFields = is_array($meta['manual_override_fields'] ?? null) ? $meta['manual_override_fields'] : [];
                                                        $summaryCellMeta = is_array($meta['summary_cell_mappings'] ?? null) ? $meta['summary_cell_mappings'] : [];
                                                        $isSummaryRow = is_array($row) && (($meta['row_type'] ?? 'data') === 'summary');
                                                        if (!$isSummaryRow && is_array($row)) {
                                                            foreach ($row as $k => $v) {
                                                                if ($k === '_meta') continue;
                                                                $v = trim((string)$v);
                                                                if (strtolower($v) === 'summary') {
                                                                    $isSummaryRow = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        if (!$isSummaryRow && is_array($row)) {
                                                            // Read-only fallback: some stored summary rows may miss _meta.row_type.
                                                            // Detect "blue-row-like" records by structure: mostly placeholders/text on descriptor columns
                                                            // plus at least one numeric/accomplishment value.
                                                            $nonMetricLooksSummary = true;
                                                            $hasMetricValue = false;
                                                            foreach ($fields as $fScan) {
                                                                $scanKey = $getFieldKey($fScan);
                                                                $scanVal = trim((string) ($getCellValue($row, $fScan, $blockEvidenceKey) ?? ''));
                                                                $scanType = strtolower((string) ($fScan['type'] ?? 'text'));
                                                                $scanLabel = strtolower((string) ($fScan['label'] ?? ''));
                                                                $isMetricField = $scanType === 'number'
                                                                    || str_contains($scanLabel, 'target')
                                                                    || str_contains($scanLabel, 'total')
                                                                    || str_contains($scanLabel, 'variance')
                                                                    || str_contains($scanLabel, 'rate')
                                                                    || str_contains($scanLabel, 'accomp')
                                                                    || str_contains($scanLabel, 'rating')
                                                                    || str_contains($scanLabel, 'descriptive');
                                                                if ($scanVal === '' || $scanVal === '-' || $scanVal === '-') {
                                                                    continue;
                                                                }
                                                                if ($isMetricField) {
                                                                    $hasMetricValue = true;
                                                                    continue;
                                                                }
                                                                if (!in_array(strtolower($scanVal), ['summary', 'total'], true)) {
                                                                    $nonMetricLooksSummary = false;
                                                                    break;
                                                                }
                                                            }
                                                            if ($nonMetricLooksSummary && $hasMetricValue) {
                                                                $isSummaryRow = true;
                                                            }
                                                        }
                                                        // Show summary row always so Super Admin sees same blue row result as Planning Coordinator.
                                                        // Previously skipped when first (avoids blue row at top) but that hid the blue row when block had only summary or ordering edge case.
                                                        $skipThisRow = false;
                                                        $rowUid = $isSummaryRow ? '' : (string)($meta['row_uid'] ?? '');
                                                        // Stable fallback so formula row_uids still match after refresh (uniqid() broke scoped sums).
                                                        if (!$isSummaryRow && $rowUid === '') {
                                                            $subPart = (string) ($block['submission_id'] ?? '');
                                                            $userPart = (string) ($block['user_id'] ?? '');
                                                            $rowUid = 'legacy_' . substr(md5($subPart . '|' . $userPart . '|' . (string) $blockIndex . '|' . (string) $loop->index), 0, 22);
                                                        }
                                                        $afterSeparator = !$isSummaryRow && !empty($row['_after_separator']);
                                                    @endphp
                                                    @if($afterSeparator)
                                                    <tr class="separator-row border-l-4 border-indigo-200" data-submission-id="{{ $block['submission_id'] ?? '' }}" data-user-id="{{ $block['user_id'] ?? '' }}">
                                                        <td colspan="{{ count($fields) }}" class="h-4 min-h-[1rem] px-4 py-2 bg-gray-200 border-t-2 border-b-2 border-gray-300"></td>
                                                    </tr>
                                                    @endif
                                                    @if(!$skipThisRow)
                                                    <tr class="data-row {{ $isSummaryRow ? 'bg-blue-100 group' : 'hover:bg-gray-50 group' }} border-l-4 border-indigo-200" data-submission-id="{{ $block['submission_id'] ?? '' }}" data-user-id="{{ $block['user_id'] ?? '' }}" data-row-uid="{{ $rowUid }}" {{ $isSummaryRow ? 'data-row-type="summary"' : '' }}>
                                                        @foreach($fields as $idx => $field)
                                                            @php
                                                                $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                                $fieldKey = $fieldMeta['field_key'] ?? $getFieldKey($field);
                                                                $val = $getCellValue($row, $field, $blockEvidenceKey);
                                                                if (is_array($val) || is_object($val)) $val = json_encode($val);
                                                                $val = (string) $val;
                                                                if (!$isSummaryRow && (trim($val) === '-' || trim($val) === '')) $val = '';
                                                                if (!$isSummaryRow && ($field['type'] ?? '') === 'number' && trim($val) === '0') $val = '';
                                                                if ($isSummaryRow && $idx === 0 && trim($val) === 'Summary') $val = '';
                                                                $labelLower = strtolower($field['label'] ?? '');
                                                                $isEvidenceVerifiedCol = !$isSummaryRow && (str_contains($labelLower, 'evidence') && str_contains($labelLower, 'verified') && str_contains($labelLower, 'qa'));
                                                                $isLastCol = ($idx === count($fields) - 1);
                                                                $showDeleteInCell = ($idx === $lastVisibleColIndexForDelete);

                                                                // Preserve saved summary values exactly as stored (Super Admin input/formula).
                                                                // Do not override blue-row values at render time.
                                                                // NO. is only used here for alignment styling.
                                                                $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                                $isNumOrCalcCol = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                                $isPerformanceMetricCol = (bool) ($fieldMeta['is_performance_metric'] ?? false);
                                                                $readOnlyPerfDataCell = !$isSummaryRow && $isPerformanceMetricCol;
                                                                $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                                $isManualOverrideSummaryCell = $isSummaryRow && in_array($fieldKey, $manualOverrideFields, true);
                                                                $manualOverrideClass = $isManualOverrideSummaryCell ? 'manual-override' : '';
                                                                $manualOverrideAttr = $isManualOverrideSummaryCell ? 'data-manual-override="1"' : '';
                                                                $cellMappingMeta = $isSummaryRow && is_array($summaryCellMeta[$fieldKey] ?? null) ? $summaryCellMeta[$fieldKey] : null;
                                                                $formulaSourceColumnsAttr = '';
                                                                $formulaRowUidsAttr = '';
                                                                $formulaRowIndicesAttr = '';
                                                                $formulaSourceAAttr = '';
                                                                $formulaSourceBAttr = '';
                                                                $formulaSourceKeysAttr = '';
                                                                $formulaSectionRefAttr = '';
                                                                $formulaUiCalcTypeAttr = '';
                                                                $formulaUiFormulaOperationAttr = '';
                                                                $formulaCountAdjustAttr = '';
                                                                if ($isSummaryRow && is_array($cellMappingMeta)) {
                                                                    $formulaSourceColumnsAttr = !empty($cellMappingMeta['source_columns']) ? 'data-formula-source-columns="' . e(json_encode(array_values($cellMappingMeta['source_columns']))) . '"' : '';
                                                                    $formulaRowUidsAttr = !empty($cellMappingMeta['row_uids']) ? 'data-formula-row-uids="' . e(json_encode(array_values($cellMappingMeta['row_uids']))) . '"' : '';
                                                                    $formulaRowIndicesAttr = !empty($cellMappingMeta['row_indices']) ? 'data-formula-row-indices="' . e(json_encode(array_values($cellMappingMeta['row_indices']))) . '"' : '';
                                                                    $formulaSourceAAttr = isset($cellMappingMeta['sourceA']) ? 'data-formula-source-a="' . e((string) $cellMappingMeta['sourceA']) . '"' : '';
                                                                    $formulaSourceBAttr = isset($cellMappingMeta['sourceB']) ? 'data-formula-source-b="' . e((string) $cellMappingMeta['sourceB']) . '"' : '';
                                                                    $formulaSourceKeysAttr = !empty($cellMappingMeta['source_keys']) ? 'data-formula-source-keys="' . e(json_encode(array_values($cellMappingMeta['source_keys']))) . '"' : '';
                                                                    $formulaSectionRefAttr = isset($cellMappingMeta['section_ref']) ? 'data-formula-section-ref="' . e((string) $cellMappingMeta['section_ref']) . '"' : '';
                                                                    $formulaUiCalcTypeAttr = isset($cellMappingMeta['ui_calc_type']) ? 'data-formula-ui-calc-type="' . e((string) $cellMappingMeta['ui_calc_type']) . '"' : '';
                                                                    $formulaUiFormulaOperationAttr = isset($cellMappingMeta['ui_formula_operation']) ? 'data-formula-ui-formula-operation="' . e((string) $cellMappingMeta['ui_formula_operation']) . '"' : '';
                                                                    if (array_key_exists('count_adjust', $cellMappingMeta)) {
                                                                        $formulaCountAdjustAttr = 'data-formula-count-adjust="' . e((string) (int) $cellMappingMeta['count_adjust']) . '"';
                                                                    }
                                                                }
                                                            @endphp
                                                            <td data-field-col="{{ $idx }}" class="px-4 py-1.5 border-r border-gray-200 {{ $isNumOrCalcCol && !$isNoColumn ? 'text-right' : ($isNoColumn ? 'text-center' : '') }} {{ $isSummaryRow ? 'bg-blue-100' : '' }} {{ $showDeleteInCell ? 'relative' : '' }} {{ $stickyClass }} {{ $manualOverrideClass }}" {!! $manualOverrideAttr !!} {!! $formulaSourceColumnsAttr !!} {!! $formulaRowUidsAttr !!} {!! $formulaRowIndicesAttr !!} {!! $formulaSourceAAttr !!} {!! $formulaSourceBAttr !!} {!! $formulaSourceKeysAttr ?? '' !!} {!! $formulaSectionRefAttr !!} {!! $formulaUiCalcTypeAttr !!} {!! $formulaUiFormulaOperationAttr !!} {!! $formulaCountAdjustAttr !!}>
                                                                @if($showDeleteInCell)
                                                                    <div class="flex items-center gap-2 relative pr-8 min-h-[28px]">
                                                                @endif
                                                                @if($isSummaryRow)
                                                                    @if($idx === 0)
                                                                        <span class="text-sm font-semibold text-gray-800">{{ $val }}</span>
                                                                    @elseif(($field['type'] ?? 'text') === 'number')
                                                                        <input
                                                                            type="text"
                                                                            inputmode="decimal"
                                                                            class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold"
                                                                            name="{{ $fieldKey }}"
                                                                            value="{{ $val }}"
                                                                        >
                                                                    @else
                                                                        <input
                                                                            type="text"
                                                                            class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold"
                                                                            name="{{ $fieldKey }}"
                                                                            value="{{ $val }}"
                                                                        >
                                                                    @endif
                                                                @elseif($readOnlyPerfDataCell)
                                                                    <span class="text-sm text-gray-500 select-none">{{ trim($val) !== '' ? $val : '' }}</span>
                                                                @elseif(($field['type'] ?? 'text') === 'dropdown' && isset($field['options']) && is_array($field['options']) && count($field['options']) > 0)
                                                                    <select class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent" name="{{ $fieldKey }}">
                                                                        <option value="">Select...</option>
                                                                        @foreach($field['options'] as $option)
                                                                            <option value="{{ $option }}" {{ strcasecmp(trim((string) $val), trim((string) $option)) === 0 ? 'selected' : '' }}>{{ $option }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @elseif(($field['type'] ?? 'text') === 'textarea')
                                                                    <textarea class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none resize-none" rows="2" name="{{ $fieldKey }}">{{ $val }}</textarea>
                                                                @elseif(($field['type'] ?? 'text') === 'number')
                                                                    <input type="number" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" name="{{ $fieldKey }}" value="{{ $val }}">
                                                                @else
                                                                    @php
                                                                        $label = $field['label'] ?? '';
                                                                        $isGoogleDriveLink = (stripos($label, 'google') !== false && stripos($label, 'drive') !== false) || (stripos($label, 'supporting') !== false && stripos($label, 'document') !== false);
                                                                        $isUrl = $val && (strpos($val, 'http') === 0 || strpos($val, 'https') === 0);
                                                                    @endphp
                                                                    @if($isGoogleDriveLink)
                                                                        @if($isUrl && $val !== '')
                                                                            {{-- Super Admin: centered "Open link"; URL in hidden input for save --}}
                                                                            <div class="flex items-center justify-center w-full min-h-[28px]">
                                                                                <input type="hidden" name="{{ $fieldKey }}" value="{{ $val }}">
                                                                                <a href="{{ $val }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 text-sm font-medium underline whitespace-nowrap">Open link</a>
                                                                            </div>
                                                                        @else
                                                                            <div class="flex items-center gap-2 flex-wrap w-full">
                                                                                <input type="text" class="flex-1 min-w-0 text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" name="{{ $fieldKey }}" value="{{ $val }}" placeholder="Paste or type link">
                                                                            </div>
                                                                        @endif
                                                                    @else
                                                                        <input type="text" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none {{ $isUrl ? 'text-blue-600' : '' }}" name="{{ $fieldKey }}" value="{{ $val }}">
                                                                    @endif
                                                                @endif
                                                                @if($showDeleteInCell)
                                                                        <button type="button" class="delete-row-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none" title="Delete row">A</button>
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                @else
                                                {{-- Placeholder row when this coordinator has no data yet --}}
                                                <tr class="data-row hover:bg-gray-50 group border-l-4 border-indigo-200" data-submission-id="{{ $block['submission_id'] ?? '' }}" data-user-id="{{ $block['user_id'] ?? '' }}">
                                                    @foreach($fields as $idx => $field)
                                                        @php
                                                            $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                            $fieldKey = $fieldMeta['field_key'] ?? $getFieldKey($field);
                                                            $colWidthClass = $fieldMeta['col_width_class'] ?? 'col-default';
                                                            $isLastColPlaceholder = ($idx === $lastVisibleColIndexForDelete);
                                                            $isPerformanceMetricCol = (bool) ($fieldMeta['is_performance_metric'] ?? false);
                                                            $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                        @endphp
                                                        <td data-field-col="{{ $idx }}" class="px-4 py-1.5 border-r border-gray-200 {{ $colWidthClass }} {{ $isLastColPlaceholder ? 'relative' : '' }} {{ $stickyClass }}">
                                                            @if($isLastColPlaceholder)<div class="flex items-center gap-2 relative pr-8 min-h-[28px]">@endif
                                                            @if($isPerformanceMetricCol)
                                                                <span class="text-sm text-gray-400 select-none">-</span>
                                                            @elseif(($field['type'] ?? 'text') === 'dropdown' && isset($field['options']) && is_array($field['options']))
                                                                <select class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent" name="{{ $fieldKey }}">
                                                                    <option value="">Select...</option>
                                                                    @foreach($field['options'] as $option)
                                                                        <option value="{{ $option }}">{{ $option }}</option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif(($field['type'] ?? 'text') === 'textarea')
                                                                <textarea class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none resize-none" rows="2" name="{{ $fieldKey }}" placeholder="{{ $idx === 0 ? 'No data yet' : '' }}"></textarea>
                                                            @elseif(($field['type'] ?? 'text') === 'number')
                                                                <input type="number" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" name="{{ $fieldKey }}" value="" placeholder="{{ $idx === 0 ? 'No data yet' : '' }}">
                                                            @else
                                                                <input type="text" class="w-full text-sm text-gray-400 italic border-0 focus:ring-0 focus:outline-none bg-transparent" name="{{ $fieldKey }}" value="" placeholder="{{ $idx === 0 ? 'No data yet' : '' }}">
                                                            @endif
                                                            @if($isLastColPlaceholder)<button type="button" class="delete-row-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none" title="Delete row">A</button></div>@endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                                @endif
                                            @endforeach
                                            @if(!$readOnly)
                                            {{-- Manual total row: green summary row; use … → Calculation to aggregate other campus blue summary rows --}}
                                            <tr id="manual-total-empty-row-template" class="hidden" aria-hidden="true" data-manual-total-row="1" data-submission-id="" data-user-id="" data-row-type="summary">
                                                @foreach($fields as $idx => $field)
                                                    @php
                                                        $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                        $fieldKey = $fieldMeta['field_key'] ?? $getFieldKey($field);
                                                        $isLastCol = ($idx === $lastVisibleColIndexForDelete);
                                                        $showDeleteInCell = $isLastCol;
                                                        $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                        $isNumOrCalcCol = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                        $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                    @endphp
                                                    <td data-field-col="{{ $idx }}" class="px-4 py-1.5 border-r border-emerald-200 {{ $isNumOrCalcCol && !$isNoColumn ? 'text-right' : ($isNoColumn ? 'text-center' : '') }} bg-emerald-100 {{ $showDeleteInCell ? 'relative' : '' }} {{ $stickyClass }}">
                                                        @if($showDeleteInCell)
                                                            <div class="flex items-center gap-2 relative pr-8 min-h-[28px]">
                                                        @endif
                                                        @if($idx === 0)
                                                            <span class="text-sm font-semibold text-gray-800"></span>
                                                        @elseif(($field['type'] ?? 'text') === 'number')
                                                            <input type="text" inputmode="decimal" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold" name="{{ $fieldKey }}" value="">
                                                        @else
                                                            <input type="text" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold" name="{{ $fieldKey }}" value="">
                                                        @endif
                                                        @if($showDeleteInCell)
                                                                <button type="button" class="delete-row-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none" title="Delete row">A</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                            {{-- Grand total row template (hidden, cloned when adding) --}}
                                            <tr id="grand-total-row-template" class="grand-total-row data-row group bg-amber-100 border-t-2 border-amber-300 border-l-4 border-amber-400 hidden" data-row-type="grand-total">
                                                @foreach($fields as $idx => $field)
                                                    @php
                                                        $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                        $fieldKey = $fieldMeta['field_key'] ?? $getFieldKey($field);
                                                        $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                        $isNumOrCalcCol = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                        $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                    @endphp
                                                    <td data-field-col="{{ $idx }}" class="px-4 py-1.5 border-r border-amber-200 {{ $isNumOrCalcCol && !$isNoColumn ? 'text-right' : ($isNoColumn ? 'text-center' : '') }} {{ $stickyClass }} grand-total-cell {{ $idx === $lastVisibleColIndexForDelete ? 'relative' : '' }}">
                                                        @if($idx === 0)
                                                            <span class="text-sm font-semibold leading-tight text-amber-900 uppercase tracking-wide">Grand total</span>
                                                        @elseif($idx === $lastVisibleColIndexForDelete)
                                                            <div class="relative pr-16 min-h-[28px] flex items-center justify-end">
                                                                @if(($field['type'] ?? 'text') === 'number')
                                                                    <input type="text" inputmode="decimal" class="w-full text-sm text-amber-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold text-right" name="grand_total_{{ $fieldKey }}" value="-">
                                                                @else
                                                                    <input type="text" class="w-full text-sm text-amber-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold" name="grand_total_{{ $fieldKey }}" value="-">
                                                                @endif
                                                                <button type="button" class="remove-grand-total-btn absolute right-10 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none shrink-0" title="Remove grand total row">A</button>
                                                            </div>
                                                        @elseif(($field['type'] ?? 'text') === 'number')
                                                            <input type="text" inputmode="decimal" class="w-full text-sm text-amber-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold" name="grand_total_{{ $fieldKey }}" value="-">
                                                        @else
                                                            <input type="text" class="w-full text-sm text-amber-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold" name="grand_total_{{ $fieldKey }}" value="-">
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                            {{-- KPI Finalize overall total row template (hidden; placed below quarter grand totals on Finalize) --}}
                                            <tr id="kpi-finalize-total-row-template" class="kpi-finalize-total-row data-row group bg-indigo-50 border-t-2 border-indigo-200 border-l-4 border-indigo-300 hidden" data-row-type="kpi-finalize-total">
                                                @foreach($fields as $idx => $field)
                                                    @php
                                                        $fieldMeta = $fieldMetaByIndex[$idx] ?? [];
                                                        $fieldKey = $fieldMeta['field_key'] ?? $getFieldKey($field);
                                                        $isNoColumn = (bool) ($fieldMeta['is_no_column'] ?? false);
                                                        $isNumOrCalcCol = (bool) ($fieldMeta['is_num_or_calc'] ?? false);
                                                        $stickyClass = $fieldMeta['sticky_class'] ?? '';
                                                    @endphp
                                                    <td data-field-col="{{ $idx }}" class="px-4 py-1.5 border-r border-gray-200 bg-indigo-50 {{ $isNumOrCalcCol && !$isNoColumn ? 'text-right' : ($isNoColumn ? 'text-center' : '') }} {{ $stickyClass }} kpi-finalize-total-cell {{ $idx === $lastVisibleColIndexForDelete ? 'relative' : '' }}">
                                                        @if($idx === 0)
                                                            <span class="text-sm font-semibold text-gray-800 whitespace-nowrap" title="Filled when you click Finalize and choose Sum or Average">Overall total</span>
                                                        @elseif($idx === $lastVisibleColIndexForDelete)
                                                            <div class="relative pr-8 min-h-[28px] flex items-center justify-end">
                                                                @if(($field['type'] ?? 'text') === 'number')
                                                                    <input type="text" inputmode="decimal" readonly aria-readonly="true" tabindex="-1" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold text-right cursor-default" name="kpi_finalize_total_{{ $fieldKey }}" value="-">
                                                                @else
                                                                    <input type="text" readonly aria-readonly="true" tabindex="-1" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold cursor-default" name="kpi_finalize_total_{{ $fieldKey }}" value="-">
                                                                @endif
                                                                <button type="button" class="remove-kpi-finalize-total-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none shrink-0" title="Remove overall total row">A</button>
                                                            </div>
                                                        @elseif(($field['type'] ?? 'text') === 'number')
                                                            <input type="text" inputmode="decimal" readonly aria-readonly="true" tabindex="-1" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold cursor-default" name="kpi_finalize_total_{{ $fieldKey }}" value="-">
                                                        @else
                                                            <input type="text" readonly aria-readonly="true" tabindex="-1" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold cursor-default" name="kpi_finalize_total_{{ $fieldKey }}" value="-">
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                            {{-- Control buttons last in tbody so green / grand-total rows insert directly under blue summaries --}}
                                            <tr id="add-grand-total-row" class="border-t-2 border-amber-200 bg-amber-50/50 hover:bg-amber-50">
                                                <td colspan="{{ count($fields) }}" class="px-4 py-3 text-center">
                                                    <div class="flex items-center justify-center gap-3 md:gap-4 flex-wrap">
                                                        <button type="button" id="add-grand-total-btn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-lg transition-colors shadow-sm">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                            Add grand total row
                                                        </button>
                                                        <button type="button" id="add-total-rows-btn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-800 bg-emerald-100 hover:bg-emerald-200 border border-emerald-300 rounded-lg transition-colors shadow-sm">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                                            Add Total Rows
                                                        </button>
                                                        <button type="button" id="finalize-kpi-btn" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-800 bg-indigo-100 hover:bg-indigo-200 border border-indigo-300 rounded-lg transition-colors shadow-sm">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Finalize
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                        @if(!$readOnly)
                                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                            <tr>
                                                <td colspan="{{ count($fields) }}" class="px-4 py-2 align-top"></td>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                    {{-- Finalize: floating popover anchored to Finalize button (Sum / Average) --}}
                                    <div id="finalize-choice-popover" class="hidden fixed z-[300] w-[min(calc(100vw-1rem),20rem)] pointer-events-auto" role="dialog" aria-modal="false" aria-labelledby="finalize-choice-title" aria-hidden="true" style="left: 0; top: 0;">
                                        <div class="bg-white rounded-xl shadow-2xl border border-slate-200/90 ring-1 ring-slate-900/5 overflow-hidden">
                                            <div class="px-3.5 pt-3 pb-2 border-b border-slate-100 bg-gradient-to-b from-slate-50 to-white">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div>
                                                        <h2 id="finalize-choice-title" class="text-sm font-semibold text-slate-900 leading-tight">Finalize overall total</h2>
                                                        <p class="text-[11px] text-slate-600 mt-0.5 leading-snug">Combine quarter grand totals into the <strong>Overall total</strong> row.</p>
                                                    </div>
                                                    <button type="button" id="finalize-choice-close-btn" class="shrink-0 rounded-md p-1 text-slate-400 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Close" aria-label="Close">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="p-2.5 space-y-2 max-h-[min(70vh,24rem)] overflow-y-auto">
                                                <button type="button" id="finalize-choice-sum-btn" class="w-full text-left rounded-lg border border-slate-200 bg-white px-3 py-2.5 hover:border-indigo-400 hover:bg-indigo-50/60 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors group">
                                                    <span class="flex items-start gap-2.5">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-indigo-700 group-hover:bg-indigo-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h10M4 18h16"/></svg>
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="block text-xs font-semibold text-slate-900">Sum</span>
                                                            <span class="block text-[11px] text-slate-600 mt-0.5 leading-snug">Add Q1 + Q2 + Q3 + Q4 grand total values.</span>
                                                        </span>
                                                    </span>
                                                </button>
                                                <button type="button" id="finalize-choice-avg-btn" class="w-full text-left rounded-lg border border-slate-200 bg-white px-3 py-2.5 hover:border-indigo-400 hover:bg-indigo-50/60 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors group">
                                                    <span class="flex items-start gap-2.5">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-violet-100 text-violet-700 group-hover:bg-violet-200">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h10M4 17h14"/></svg>
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="block text-xs font-semibold text-slate-900">Average</span>
                                                            <span class="block text-[11px] text-slate-600 mt-0.5 leading-snug">Mean of quarters that have a value (empty quarters skipped).</span>
                                                        </span>
                                                    </span>
                                                </button>
                                            </div>
                                            <div class="px-2.5 pb-2.5 pt-0 flex justify-end border-t border-slate-50">
                                                <button type="button" id="finalize-choice-cancel-btn" class="px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 rounded-md hover:bg-slate-100">Cancel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="delete-last-row-container-multi" class="absolute right-0 flex items-center justify-center opacity-0 transition-opacity duration-200" style="width: 48px;">
                                    <button type="button" id="delete-last-row-btn-multi" class="text-red-600 hover:text-red-800 font-bold text-xl leading-none" title="Delete last row">A</button>
                                </div>
                                <!-- Floating calculate popover (draggable) -->
                                <div id="selection-popover" class="hidden fixed z-[220] flex flex-col bg-white border border-gray-200 rounded-lg shadow-lg" style="min-width: 220px; max-width: 320px;">
                                    <div id="selection-popover-drag" class="flex items-center justify-between px-3 py-1.5 border-b border-gray-100 bg-gray-50 rounded-t-lg cursor-grab active:cursor-grabbing select-none">
                                        <label class="text-xs font-medium text-gray-700">Choose calculation</label>
                                        <span class="text-[10px] text-gray-400">drag</span>
                                    </div>
                                    <div id="selection-popover-inner" class="flex flex-col gap-2 py-2 px-3" style="overflow-y: auto; max-height: calc(100vh - 80px);">
                                    <div id="grand-total-cascade-wizard" class="hidden flex flex-col gap-2 border border-amber-100 rounded-md p-2 bg-amber-50/40">
                                        <p class="text-[10px] text-amber-900 font-medium">Grand total - choose in order:</p>
                                        <div class="flex flex-col gap-1">
                                            <label for="gt-wizard-type" class="text-[11px] font-medium text-gray-700">1. Type of Grand Total</label>
                                            <select id="gt-wizard-type" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer bg-white">
                                                <option value="">- Select -</option>
                                                <option value="quarter">Quarter</option>
                                                <option value="school_year">School Year</option>
                                                <option value="calculation">Calculation</option>
                                                <option value="average">Average</option>
                                            </select>
                                        </div>
                                        <div id="gt-wizard-step2-wrap" class="hidden flex flex-col gap-1">
                                            <label for="gt-wizard-step2" id="gt-wizard-step2-label" class="text-[11px] font-medium text-gray-700">2.</label>
                                            <select id="gt-wizard-step2" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer bg-white"></select>
                                        </div>
                                        <div id="gt-wizard-step3-wrap" class="hidden flex flex-col gap-1">
                                            <label for="gt-wizard-step3" id="gt-wizard-step3-label" class="text-[11px] font-medium text-gray-700">3.</label>
                                            <select id="gt-wizard-step3" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer bg-white"></select>
                                        </div>
                                    </div>
                                    <select id="selection-calc-type" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer">
                                        <option value="">- Select action -</option>
                                        <option value="blue-row-formula" data-hide-for-grand-total="1">Formula (A & B)</option>
                                        <option value="blue-row-formula-multi" data-hide-for-grand-total="1">Formula (A+B+C...)</option>
                                        <option value="sum">Sum</option>
                                        <option value="aggregate-chain">Sum / Avg then chain (A+/-AA-a)</option>
                                        <option value="avg" data-hide-for-grand-total="1" data-hide-for-blue-row="1">Average</option>
                                        <option value="avg_number">Average (Number)</option>
                                        <option value="avg_percentage">Average (Percentage)</option>
                                        <option value="unique">Count Unique Values</option>
                                        <option value="unique_adjust">Count unique (A+/- adjust)</option>
                                        <option value="countif">Count All Values</option>
                                        <option value="count_rows" data-hide-for-blue-row="1">Count Rows</option>
                                        <option value="compare-campus-target" data-hide-for-grand-total="1">Compare to Campus Target</option>
                                        <option value="clear-calculation">Clear calculation (recalculate)</option>
                                    </select>
                                    <div id="compare-campus-target-options" class="hidden flex flex-col gap-1.5">
                                        <label class="text-[11px] font-medium text-gray-700">Select column (value to compare)</label>
                                        <select id="compare-campus-target-column" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer bg-white">
                                            <option value="">- Select column -</option>
                                            @foreach($fields ?? [] as $field)
                                                @php $fk = $getFieldKey($field); @endphp
                                                <option value="{{ $fk }}">{{ $field['label'] ?? $fk }}</option>
                                            @endforeach
                                        </select>
                                        <p id="compare-campus-target-value-preview" class="text-[10px] text-indigo-700 bg-indigo-50 px-2 py-1.5 rounded font-medium hidden"></p>
                                        <p class="text-[10px] text-gray-500">Value from this column A- Campus Target a written to the selected blue cell.</p>
                                    </div>
                                    <div id="unique-adjust-options" class="hidden flex flex-col gap-1.5">
                                        <p class="text-[11px] font-medium text-gray-700">Then on that count:</p>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <select id="unique-adjust-operator" class="text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 px-2 cursor-pointer bg-white shrink-0 min-w-[9rem]" aria-label="Add to or subtract from unique count">
                                                <option value="add">Add to count</option>
                                                <option value="subtract">Subtract from count</option>
                                            </select>
                                            <input type="number" id="unique-adjust-amount" min="0" step="1" value="0" class="w-16 text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 px-2 text-center" inputmode="numeric" aria-label="Number to add or subtract">
                                            <span class="text-[11px] text-gray-600">(e.g. Count unique <span class="font-medium text-gray-800">+ 1</span> or <span class="font-medium text-gray-800">a 1</span>)</span>
                                        </div>
                                        <p id="unique-adjust-preview" class="text-[10px] text-indigo-800 bg-indigo-50 px-2 py-1.5 rounded font-medium">Preview: count unique, then no change</p>
                                        <p class="text-[10px] text-gray-500">Same as <strong>Count Unique Values</strong>, then add or subtract this whole number. Final result is not below 0.</p>
                                    </div>
                                    <div id="grand-total-quarter-options" class="hidden flex flex-col gap-1.5">
                                        <label class="text-[11px] font-medium text-gray-700">Select quarter (Grand Total sources)</label>
                                        <select id="grand-total-quarter-select" class="w-full text-xs border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer bg-white">
                                            <option value="">- Use quarter cell value -</option>
                                            <option value="1">Q1</option>
                                            <option value="2">Q2</option>
                                            <option value="3">Q3</option>
                                            <option value="4">Q4</option>
                                        </select>
                                    </div>
                                    <label class="inline-flex items-center gap-1.5 text-[11px] text-gray-700 select-none">
                                        <input id="preserve-blue-source-selection" type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                        Preserve current sources when picking first blue target
                                    </label>
                                    <p id="selection-live-hint" class="text-[11px] text-indigo-700 bg-indigo-50 px-2 py-1 rounded break-words">Source: 0 cells -> Target: 0 blue cells</p>
                                    <p id="selection-mode-hint" class="text-[11px] text-gray-500 px-1 hidden"></p>
                                    <div class="flex items-center gap-2">
                                        <button type="button" id="selection-apply-calc-btn" class="inline-flex items-center gap-1.5 py-1 px-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded cursor-pointer">Apply</button>
                                    </div>
                                    </div>
                                </div>
                                <!-- Floating Add Row / Separate on row hover (data rows); match Planning Coordinator: fixed so visible when table scrolls -->
                                <div id="row-actions-popover" class="hidden fixed z-[9999] flex flex-col divide-y divide-gray-100 py-1 px-1 bg-white border border-indigo-200 rounded-md shadow-md pointer-events-auto w-max max-w-[min(13.5rem,calc(100vw-1rem))]" role="toolbar" aria-label="Row actions">
                                    <button type="button" id="row-actions-add-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded border-0 shadow-sm whitespace-nowrap w-full justify-start" title="Add row below this row">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        Row below
                                    </button>
                                    <button type="button" id="row-actions-add-rows-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded border-0 whitespace-nowrap w-full justify-start" title="Add multiple rows (enter count)">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h10"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17v4m-2-2h4"></path></svg>
                                        Several rowsa
                                    </button>
                                    <button type="button" id="row-actions-calculate-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-emerald-50 hover:text-emerald-800 rounded border-0 whitespace-nowrap w-full justify-start" title="Sum year columns into Total M/F, set Grand Total (M+F), refresh summary &amp; grand-total rows">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Calculate
                                    </button>
                                    <button type="button" id="row-actions-separate-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded border-0 whitespace-nowrap w-full justify-start" title="Add a section divider and start a new group of rows">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <line x1="3" y1="12" x2="21" y2="12" stroke-width="2" stroke-linecap="round"></line>
                                            <line x1="3" y1="7" x2="9" y2="7" stroke-width="2" stroke-linecap="round"></line>
                                            <line x1="3" y1="17" x2="9" y2="17" stroke-width="2" stroke-linecap="round"></line>
                                        </svg>
                                        New section
                                    </button>
                                </div>
                                <div id="row-actions-popover-blue" class="hidden absolute z-[250] flex flex-col divide-y divide-gray-100 py-1 px-1 bg-white border border-indigo-200 rounded-md shadow-md pointer-events-auto w-max max-w-[min(13.5rem,calc(100vw-1rem))]" role="toolbar" aria-label="Blue row actions">
                                    <button type="button" id="row-actions-open-calc-btn-blue" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-emerald-50 hover:text-emerald-800 rounded border-0 whitespace-nowrap w-full justify-start" title="Open calculation popover for selected blue cell">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                        Calculation
                                    </button>
                                    <button type="button" id="row-actions-remove-formula-btn-blue" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-red-700 bg-white hover:bg-red-50 rounded border-0 whitespace-nowrap w-full justify-start" title="Remove formula from selected blue/grand-total cell">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6M5 7l1 12h12l1-12"></path></svg>
                                        Remove formula
                                    </button>
                                    <button type="button" id="row-actions-reselect-cells-blue-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded border-0 whitespace-nowrap w-full justify-start" title="Reuse which data rows feed this cell (same pattern as the other blue total). Your formula stays the same.">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h10"></path></svg>
                                        Reselect Cells
                                    </button>
                                    <button type="button" id="row-actions-add-btn-blue" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded border-0 shadow-sm whitespace-nowrap w-full justify-start" title="Add row below blue summary row">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        Blue row below
                                    </button>
                                </div>
                                <div id="campus-target-compare-panel" class="hidden absolute z-[300] right-2 top-2 w-[320px] sm:w-[380px] lg:w-[440px] max-w-[calc(100%-1rem)] bg-white border border-emerald-200 rounded-lg shadow-lg">
                                    <div class="px-3 py-2 border-b border-emerald-100 flex items-center justify-between">
                                        <p class="text-xs font-semibold text-emerald-800">Campus Target Comparison</p>
                                        <button type="button" id="campus-target-compare-close" class="text-xs px-2 py-0.5 rounded border border-gray-200 text-gray-600 hover:text-gray-800 hover:bg-gray-50">Close</button>
                                    </div>
                                    <div id="campus-target-compare-content" class="p-3 text-xs text-gray-700 max-h-[55vh] overflow-auto"></div>
                                </div>
                            </div>
                            <!-- Formula Modal -->
                            <div id="formula-modal-backdrop" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[10000] hidden">
                                <div class="min-h-screen flex items-center justify-center px-4">
                                    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-gray-200">
                                        <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-900">Apply Formula to Selected Rows</h4>
                                            <button type="button" id="formula-modal-close" class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <div class="px-5 py-4 space-y-4 text-sm">
                                            <p id="formula-modal-desc" class="text-xs text-gray-600">Choose a target column and one or two source columns. The formula will run for each selected row only.</p>
                                            <div id="formula-target-section" class="space-y-2">
                                                <p id="formula-selected-column-info" class="text-xs font-medium text-indigo-700 bg-indigo-50 px-3 py-2.5 rounded-lg hidden"></p>
                                                <div id="formula-target-select-wrap">
                                                    <label class="block text-xs font-semibold text-gray-800 mb-1.5">Target Column</label>
                                                    <select id="formula-target" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">
                                                        <option value="">- Select target column -</option>
                                                        @foreach($fields as $field)
                                                            @php $fk = $getFieldKey($field); @endphp
                                                            <option value="{{ $fk }}">{{ $field['label'] ?? $fk }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="rounded-lg border border-gray-200 bg-gray-50/50 px-4 py-3">
                                                <p class="text-xs font-semibold text-gray-700 mb-3">Source columns</p>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">A <span class="text-amber-600">(required)</span></label>
                                                        <select id="formula-source-a" class="formula-source-select w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                            <option value="">- Select -</option>
                                                            @foreach($fields as $field)
                                                                @php $fk = $getFieldKey($field); @endphp
                                                                <option value="{{ $fk }}">{{ $field['label'] ?? $fk }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">B <span class="text-gray-400">(optional)</span></label>
                                                        <select id="formula-source-b" class="formula-source-select w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                                            <option value="">- Select -</option>
                                                            @foreach($fields as $field)
                                                                @php $fk = $getFieldKey($field); @endphp
                                                                <option value="{{ $fk }}">{{ $field['label'] ?? $fk }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div id="formula-sources-extra" class="mt-3 space-y-2 hidden"></div>
                                                <button type="button" id="formula-add-source-btn" class="mt-3 w-full py-2 px-3 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg border border-dashed border-indigo-200 flex items-center justify-center gap-2 transition-colors hidden">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                    Add source C, D, E...
                                                </button>
                                                <p id="formula-preview" class="mt-3 text-xs font-mono text-indigo-700 bg-indigo-50 px-3 py-2 rounded-lg border border-indigo-100 hidden"></p>
                                            </div>
                                            <div id="formula-operation-wrap">
                                                <div class="flex items-center justify-between gap-2 mb-1.5">
                                                    <label class="block text-xs font-semibold text-gray-800">Operation</label>
                                                    <button type="button" id="formula-remove-operation-btn" class="shrink-0 inline-flex items-center gap-1 px-2 py-1 text-[11px] font-medium text-red-600 hover:text-red-700 hover:bg-red-50 border border-red-200 rounded transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent" title="Delete this operation from the saved list">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        Delete operation
                                                    </button>
                                                </div>
                                                <select id="formula-operation" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-shadow">
                                                    <option value="sum">A + B (Sum)</option>
                                                    <option value="subtract">A - B (Difference)</option>
                                                    <option value="multiply">A A B (Product)</option>
                                                    <option value="divide">A A- B (Quotient)</option>
                                                    <option value="percent_of">A A- B A 100 (Percent of)</option>
                                                    <option value="sum_over_b_percent">(A + B) A- B A 100</option>
                                                    <option value="diff_over_b_percent">(A - B) A- B A 100</option>
                                                    <option value="custom">Custom (enter your own expression)</option>
                                                </select>
                                            </div>
                                            <div id="formula-custom-wrap" class="hidden">
                                                <label class="block text-xs font-semibold text-gray-800 mb-1.5">Custom expression</label>
                                                <input type="text" id="formula-custom-expr" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g. A + B, A + B + C, A A B A- 100, (A - B) A- B A 100">
                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                    <span class="text-[10px] text-gray-500 self-center mr-1">Insert:</span>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="A">A</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="B">B</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="+">+</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="-">a</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="*">A</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="/">A-</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="(">(</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char=")">)</button>
                                                    <button type="button" class="formula-op-btn px-2.5 py-1.5 text-sm font-mono bg-gray-100 hover:bg-indigo-100 hover:text-indigo-700 border border-gray-200 rounded-md transition-colors" data-char="100">100</button>
                                                    <span id="formula-op-btns-extra" class="contents"></span>
                                                </div>
                                                <p id="formula-custom-expr-hint" class="mt-1 text-[11px] text-gray-500">Use A, B, C, D... for source values. Click buttons above or type operators.</p>
                                            </div>
                                            <p id="formula-error" class="text-xs text-red-600 hidden"></p>
                                        </div>
                                        <div class="px-5 py-3 border-t border-gray-200 flex justify-end space-x-2">
                                            <button type="button" id="formula-cancel" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>
                                            <button type="button" id="formula-apply-confirm" class="px-4 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-md">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Auto-calc modal (Count Unique / Count All / Sum / Average) -->
                            <div id="autocalc-modal-backdrop" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[10000] hidden">
                                <div class="min-h-screen flex items-center justify-center px-4">
                                    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-gray-200">
                                        <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-gray-900" id="autocalc-modal-title">Calculate</h4>
                                            <button type="button" id="autocalc-modal-close" class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <div class="px-5 py-4 space-y-4 text-sm">
                                            <p id="autocalc-selected-info" class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2 py-1.5 rounded"></p>
                                            <p id="autocalc-error" class="text-xs text-red-600 hidden"></p>
                                            <p id="autocalc-add-row-wrap" class="hidden">
                                                <button type="button" id="autocalc-add-result-row-btn" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 underline">Add result row & apply</button>
                                            </p>
                                        </div>
                                        <div class="px-5 py-3 border-t border-gray-200 flex justify-end space-x-2">
                                            <button type="button" id="autocalc-cancel" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>
                                            <button type="button" id="autocalc-apply" class="px-4 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-md">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Aggregate chain: base sum/avg on selected white cells, then + a A A- more cells -->
                            <div id="aggregate-chain-modal-backdrop" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[10000] hidden">
                                <div class="min-h-screen flex items-center justify-center px-4 py-4 sm:py-6">
                                    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 max-h-[min(88vh,26rem)] sm:max-h-[min(88vh,32rem)] flex flex-col min-h-0 overflow-hidden">
                                        <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between shrink-0">
                                            <h4 class="text-sm font-semibold text-gray-900">Sum / Avg then chain (A+/-AA-a)</h4>
                                            <button type="button" id="aggregate-chain-modal-close" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                        <div class="px-5 py-3 space-y-3 text-sm flex-1 min-h-0 overflow-y-auto overscroll-contain">
                                            <p class="text-xs text-gray-600 shrink-0">First, sum (or average) values from rows you mark as <strong>Base</strong>. Then apply each <strong>term</strong> in order using the operator (left-to-right with the running total).</p>
                                            <p id="aggregate-chain-error" class="text-xs text-red-600 hidden shrink-0"></p>
                                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                                <label class="text-xs font-medium text-gray-700">Base aggregate</label>
                                                <select id="aggregate-chain-base-agg" class="text-xs border border-gray-300 rounded py-1.5 px-2 bg-white">
                                                    <option value="sum">Sum of base cells</option>
                                                    <option value="avg">Average of base cells</option>
                                                </select>
                                            </div>
                                            <div class="flex flex-col gap-1 min-h-0">
                                                <p class="text-xs font-medium text-gray-700 shrink-0">Include in base (at least one)</p>
                                                {{-- Fixed-height scrollport: parent min-h-0 + explicit height so flex does not expand past modal --}}
                                                <div id="aggregate-chain-base-list" class="aggregate-chain-scroll-list border border-gray-200 rounded-md divide-y divide-gray-100 text-xs bg-gray-50/40"></div>
                                            </div>
                                            <div class="flex flex-col gap-1 pt-0.5 min-h-0">
                                                <div class="flex items-center justify-between shrink-0">
                                                    <span class="text-xs font-medium text-gray-700">Chain terms (optional)</span>
                                                    <button type="button" id="aggregate-chain-add-term" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">+ Add term</button>
                                                </div>
                                                <div id="aggregate-chain-terms-wrap" class="aggregate-chain-scroll-list space-y-2"></div>
                                            </div>
                                            <p id="aggregate-chain-preview" class="text-xs font-medium text-indigo-800 bg-indigo-50 px-2 py-1.5 rounded hidden shrink-0"></p>
                                        </div>
                                        <div class="px-5 py-3 border-t border-gray-200 flex justify-end space-x-2 shrink-0 bg-white">
                                            <button type="button" id="aggregate-chain-cancel" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>
                                            <button type="button" id="aggregate-chain-apply" class="px-4 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-md">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <style>
                                /* Aggregate chain modal: explicit height + min-h-0 so flex does not size these to content (which hides inner scrollbars) */
                                #aggregate-chain-modal-backdrop #aggregate-chain-base-list {
                                    height: min(42vh, 200px);
                                    max-height: min(42vh, 200px);
                                    min-height: 0;
                                    overflow-y: auto;
                                    overflow-x: hidden;
                                    overscroll-behavior: contain;
                                    -webkit-overflow-scrolling: touch;
                                }
                                #aggregate-chain-modal-backdrop #aggregate-chain-terms-wrap {
                                    max-height: min(30vh, 150px);
                                    min-height: 0;
                                    overflow-y: auto;
                                    overflow-x: hidden;
                                    overscroll-behavior: contain;
                                    -webkit-overflow-scrolling: touch;
                                }
                            </style>
                            <script>
                                (function() {
                                var READ_ONLY_TEMPLATE_VIEW = @json($readOnly);
                                window.TEMPLATE_SHOW_READ_ONLY = READ_ONLY_TEMPLATE_VIEW;
                                window.tableDataDirty = false;
                                @include('super-admin.templates.partials.template-autosave-queue')
                                if (READ_ONLY_TEMPLATE_VIEW) {
                                    window.performSaveTableData = function(opts) {
                                        opts = opts || {};
                                        if (typeof opts.onDone === 'function') opts.onDone();
                                        if (typeof opts.onSuccess === 'function') opts.onSuccess();
                                    };
                                    function normalizeReadOnlyBlueRows() {
                                        var tableBody = document.getElementById('table-body-multi');
                                        if (!tableBody) return;
                                        var keyFor = function(subId, userId) {
                                            return String(subId || '') + '|' + String(userId || '');
                                        };
                                        var isControlOrTemplateRow = function(tr) {
                                            if (!tr) return true;
                                            if (tr.id === 'add-grand-total-row' || tr.id === 'grand-total-row-template' || tr.id === 'kpi-finalize-total-row-template' || tr.id === 'manual-total-empty-row-template') return true;
                                            if (tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row')) return true;
                                            return false;
                                        };
                                        var createBlueRow = function(subId, userId, colCount) {
                                            var tr = document.createElement('tr');
                                            tr.className = 'data-row bg-blue-100 border-l-4 border-indigo-200';
                                            tr.setAttribute('data-submission-id', String(subId || ''));
                                            tr.setAttribute('data-user-id', String(userId || ''));
                                            tr.setAttribute('data-row-type', 'summary');
                                            for (var ci = 0; ci < colCount; ci++) {
                                                var td = document.createElement('td');
                                                td.setAttribute('data-field-col', String(ci));
                                                td.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100';
                                                if (ci === 0) {
                                                    var sp = document.createElement('span');
                                                    sp.className = 'text-sm font-semibold text-gray-800';
                                                    sp.textContent = '';
                                                    td.appendChild(sp);
                                                } else {
                                                    var inp = document.createElement('input');
                                                    inp.type = 'text';
                                                    inp.value = '';
                                                    inp.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                                    td.appendChild(inp);
                                                }
                                                tr.appendChild(td);
                                            }
                                            return tr;
                                        };

                                        // Keep existing blue rows as a reusable pool, then rebuild placement by chunk.
                                        var bluePoolByKey = {};
                                        Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row.bg-blue-100')).forEach(function(tr) {
                                            if (isControlOrTemplateRow(tr)) return;
                                            var k = keyFor(tr.getAttribute('data-submission-id'), tr.getAttribute('data-user-id'));
                                            if (!bluePoolByKey[k]) bluePoolByKey[k] = [];
                                            bluePoolByKey[k].push(tr);
                                            if (tr.parentNode) tr.remove();
                                        });

                                        var activeHeaderSubId = '';
                                        var activeHeaderUserId = '';
                                        var chunkDataRows = [];
                                        function flushChunk() {
                                            if (chunkDataRows.length === 0) return;
                                            var first = chunkDataRows[0];
                                            var last = chunkDataRows[chunkDataRows.length - 1];
                                            var subId = first.getAttribute('data-submission-id') || activeHeaderSubId || '';
                                            var userId = first.getAttribute('data-user-id') || activeHeaderUserId || '';
                                            var key = keyFor(subId, userId);
                                            var colCount = first.querySelectorAll('td').length || 1;
                                            var reuse = (bluePoolByKey[key] && bluePoolByKey[key].length > 0) ? bluePoolByKey[key].shift() : null;
                                            var blueRow = reuse || createBlueRow(subId, userId, colCount);
                                            blueRow.classList.add('bg-blue-100');
                                            blueRow.setAttribute('data-row-type', 'summary');
                                            blueRow.setAttribute('data-submission-id', String(subId || ''));
                                            blueRow.setAttribute('data-user-id', String(userId || ''));
                                            last.insertAdjacentElement('afterend', blueRow);
                                            chunkDataRows = [];
                                        }

                                        var rows = Array.prototype.slice.call(tableBody.children || []);
                                        rows.forEach(function(tr) {
                                            if (!tr || !tr.classList) return;
                                            if (isControlOrTemplateRow(tr)) {
                                                flushChunk();
                                                return;
                                            }
                                            if (tr.classList.contains('section-header-row')) {
                                                flushChunk();
                                                activeHeaderSubId = tr.getAttribute('data-submission-id') || '';
                                                activeHeaderUserId = tr.getAttribute('data-user-id') || '';
                                                return;
                                            }
                                            if (tr.classList.contains('separator-row')) {
                                                flushChunk();
                                                return;
                                            }
                                            if (tr.classList.contains('data-row') && !tr.classList.contains('bg-blue-100')) {
                                                chunkDataRows.push(tr);
                                            }
                                        });
                                        flushChunk();
                                    }
                                    function applyReadOnlyUiMulti() {
                                        normalizeReadOnlyBlueRows();
                                        var root = document.getElementById('table-container-multi');
                                        if (root) {
                                            root.classList.add('template-show-readonly');
                                            root.querySelectorAll('input, select, textarea').forEach(function(el) {
                                                el.readOnly = true;
                                                el.disabled = true;
                                            });
                                            root.querySelectorAll('button').forEach(function(btn) {
                                                btn.disabled = true;
                                                btn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                                            });
                                        }
                                        var dots = document.getElementById('row-actions-dots-btn');
                                        if (dots) dots.remove();
                                        var ast = document.getElementById('autosave-status');
                                        if (ast) ast.classList.add('hidden');
                                        ['selection-popover','row-actions-popover','row-actions-popover-blue','finalize-choice-popover','formula-modal-backdrop','autocalc-modal-backdrop','aggregate-chain-modal-backdrop'].forEach(function(id) {
                                            var el = document.getElementById(id);
                                            if (el) {
                                                el.classList.add('hidden');
                                                el.setAttribute('aria-hidden', 'true');
                                            }
                                        });
                                    }
                                    if (document.readyState === 'loading') {
                                        document.addEventListener('DOMContentLoaded', applyReadOnlyUiMulti);
                                    } else {
                                        applyReadOnlyUiMulti();
                                    }
                                    return;
                                }
                                var tableBody = document.getElementById('table-body-multi');
                                var fields = @json($fields);
                                window.performanceStickySlotByIndex = @json($performanceStickySlotByIndex ?? []);
                                var coordinatorBlocks = @json($coordinatorBlocksForJs);
                                var savedGrandTotalRows = @json($template->fields_json['grand_total_rows'] ?? []);
                                var savedKpiFinalizeTotalRow = @json($template->fields_json['kpi_finalize_total_row'] ?? null);
                                var savedManualTotalRow = @json($template->fields_json['manual_total_row'] ?? null);
                                var saveUrl = '{{ route("super-admin.templates.save-table-data", $template) }}';
                                var summaryRulesUrl = '{{ route("super-admin.templates.update-summary-rules", $template) }}';
                                var templateIdForFormulas = {{ $template->id }};
                                var customFormulasStorageKey = 'template_' + templateIdForFormulas + '_custom_formulas';
                                var selectedRowsMulti = [];
                                var lastClickedRowMulti = null;
                                var lastClickedCellMulti = null;
                                var cellPasteClipboard = null; // legacy: { fieldKey, value } | grid: { v: 3, rows: [[{v}]], primaryFieldKey }
                                const ROW_COPY_BUFFER_KEY = 'uaps_row_copy_v1';
                                
                                // Custom modal dialog to replace window.prompt for "add N rows"
                                function showRowCountDialog(defaultCount) {
                                    return new Promise(function(resolve) {
                                        var backdropId = 'sa-rowcount-dialog-backdrop';
                                        var existing = document.getElementById(backdropId);
                                        var backdrop = existing || document.createElement('div');
                                        if (!existing) {
                                            backdrop.id = backdropId;
                                            backdrop.className = 'fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[100000] flex items-center justify-center';
                                            backdrop.style.display = 'none';

                                            var modal = document.createElement('div');
                                            modal.id = 'sa-rowcount-dialog-modal';
                                            modal.className = 'bg-white rounded-xl shadow-2xl max-w-[90vw] w-[420px] border border-gray-200';
                                            modal.innerHTML = '' +
                                                '<div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">' +
                                                '  <h4 id="sa-rowcount-dialog-title" class="text-sm font-semibold text-gray-900">Add rows</h4>' +
                                                '  <button type="button" id="sa-rowcount-dialog-close" class="text-gray-400 hover:text-gray-600" aria-label="Close">' +
                                                '    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                                                '  </button>' +
                                                '</div>' +
                                                '<div class="px-5 py-4 space-y-3">' +
                                                '  <p class="text-xs text-gray-600">How many rows do you want to add?</p>' +
                                                '  <input id="sa-rowcount-dialog-input" type="number" min="1" max="100" step="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />' +
                                                '</div>' +
                                                '<div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">' +
                                                '  <button type="button" id="sa-rowcount-dialog-cancel" class="px-4 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>' +
                                                '  <button type="button" id="sa-rowcount-dialog-ok" class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">OK</button>' +
                                                '</div>';
                                            backdrop.appendChild(modal);
                                            document.body.appendChild(backdrop);
                                        }

                                        var input = document.getElementById('sa-rowcount-dialog-input');
                                        var okBtn = document.getElementById('sa-rowcount-dialog-ok');
                                        var cancelBtn = document.getElementById('sa-rowcount-dialog-cancel');
                                        var closeBtn = document.getElementById('sa-rowcount-dialog-close');

                                        var resolved = false;
                                        function cleanup() {
                                            if (resolved) return;
                                            resolved = true;
                                            backdrop.style.display = 'none';
                                            if (typeof document !== 'undefined') document.body.style.overflow = '';
                                        }
                                        function closeWith(val) {
                                            cleanup();
                                            resolve(val);
                                        }

                                        backdrop.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';
                                        if (input) {
                                            input.value = (defaultCount && Number.isFinite(defaultCount)) ? String(defaultCount) : '1';
                                            setTimeout(function() { try { input.focus(); input.select(); } catch (e) {} }, 0);
                                        }

                                        var escHandler = function(ev) {
                                            if (ev.key === 'Escape') {
                                                ev.preventDefault();
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                            }
                                        };
                                        document.addEventListener('keydown', escHandler, true);

                                        // Enter should trigger OK when focus is on the input.
                                        var enterHandler = function(ev) {
                                            if (ev.key !== 'Enter') return;
                                            var active = document.activeElement;
                                            var isOnInput = (active === input) || (active && input && active === input);
                                            if (!isOnInput) return;
                                            ev.preventDefault();
                                            if (okBtn && typeof okBtn.onclick === 'function') okBtn.onclick();
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        document.addEventListener('keydown', enterHandler, true);

                                        if (okBtn) okBtn.onclick = function() {
                                            var raw = input ? String(input.value || '').trim() : '';
                                            var count = parseInt(raw, 10);
                                            if (!Number.isFinite(count) || count <= 0) {
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                                document.removeEventListener('keydown', enterHandler, true);
                                                return;
                                            }
                                            count = Math.min(count, 100);
                                            closeWith(count);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        if (cancelBtn) cancelBtn.onclick = function() {
                                            closeWith(null);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        if (closeBtn) closeBtn.onclick = function() {
                                            closeWith(null);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        backdrop.onclick = function(e) {
                                            if (e.target === backdrop) {
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                                document.removeEventListener('keydown', enterHandler, true);
                                            }
                                        };
                                    });
                                }
                                
                                var selectionPopover = document.getElementById('selection-popover');
                                var tableContainerMulti = document.getElementById('table-container-multi');
                                var formulaModal = document.getElementById('formula-modal-backdrop');
                                var formulaTargetSelect = document.getElementById('formula-target');
                                var formulaSourceASelect = document.getElementById('formula-source-a');
                                var formulaSourceBSelect = document.getElementById('formula-source-b');
                                var formulaOperationSelect = document.getElementById('formula-operation');
                                var formulaError = document.getElementById('formula-error');
                                var formulaSelectedColumnInfo = document.getElementById('formula-selected-column-info');
                                var formulaClose = document.getElementById('formula-modal-close');
                                var formulaCancel = document.getElementById('formula-cancel');
                                var formulaApplyConfirm = document.getElementById('formula-apply-confirm');
                                var selectionCalcTypeSelect = document.getElementById('selection-calc-type');
                                var selectionApplyCalcBtn = document.getElementById('selection-apply-calc-btn');
                                var preserveBlueSourceSelectionCheckbox = document.getElementById('preserve-blue-source-selection');
                                var selectionLiveHint = document.getElementById('selection-live-hint');
                                var selectionModeHint = document.getElementById('selection-mode-hint');
                                var compareCampusTargetOptions = document.getElementById('compare-campus-target-options');
                                var compareCampusTargetColumn = document.getElementById('compare-campus-target-column');
                                var compareCampusTargetValuePreview = document.getElementById('compare-campus-target-value-preview');
                                var uniqueAdjustOptions = document.getElementById('unique-adjust-options');
                                var uniqueAdjustOperatorSelect = document.getElementById('unique-adjust-operator');
                                var uniqueAdjustAmountInput = document.getElementById('unique-adjust-amount');
                                var uniqueAdjustPreview = document.getElementById('unique-adjust-preview');
                                var grandTotalQuarterOptions = document.getElementById('grand-total-quarter-options');
                                var grandTotalQuarterSelect = document.getElementById('grand-total-quarter-select');
                                var campusTargetComparePanel = document.getElementById('campus-target-compare-panel');
                                var campusTargetCompareContent = document.getElementById('campus-target-compare-content');
                                var campusTargetCompareClose = document.getElementById('campus-target-compare-close');
                                var autocalcModal = document.getElementById('autocalc-modal-backdrop');
                                var autocalcModalTitle = document.getElementById('autocalc-modal-title');
                                var autocalcSelectedInfo = document.getElementById('autocalc-selected-info');
                                var autocalcSourceSelect = document.getElementById('autocalc-source');
                                var autocalcTargetSelect = document.getElementById('autocalc-target');
                                var autocalcError = document.getElementById('autocalc-error');
                                var autocalcModalClose = document.getElementById('autocalc-modal-close');
                                var autocalcCancel = document.getElementById('autocalc-cancel');
                                var autocalcApply = document.getElementById('autocalc-apply');
                                var aggregateChainModal = document.getElementById('aggregate-chain-modal-backdrop');
                                var aggregateChainModalClose = document.getElementById('aggregate-chain-modal-close');
                                var aggregateChainCancel = document.getElementById('aggregate-chain-cancel');
                                var aggregateChainApplyBtn = document.getElementById('aggregate-chain-apply');
                                var aggregateChainBaseAgg = document.getElementById('aggregate-chain-base-agg');
                                var aggregateChainBaseList = document.getElementById('aggregate-chain-base-list');
                                var aggregateChainTermsWrap = document.getElementById('aggregate-chain-terms-wrap');
                                var aggregateChainAddTerm = document.getElementById('aggregate-chain-add-term');
                                var aggregateChainError = document.getElementById('aggregate-chain-error');
                                var aggregateChainPreview = document.getElementById('aggregate-chain-preview');
                                var aggregateChainPending = null;
                                var aggregateChainCellMeta = [];
                                var formulaUseBlueRow = false;
                                var formulaBlueRowOnlyMode = false;
                                var formulaGrandTotalMode = false;
                                var formulaMultiSourceMode = false;
                                var formulaCustomMode = false;
                                function shouldPreserveBlueSourceSelection() {
                                    return !preserveBlueSourceSelectionCheckbox || !!preserveBlueSourceSelectionCheckbox.checked;
                                }
                                var rowActionsPopover = document.getElementById('row-actions-popover');
                                var rowActionsPopoverBlue = document.getElementById('row-actions-popover-blue');
                                // "3 dots" trigger to open row-actions popovers (prevents auto-opening)
                                var rowActionsDotsBtn = document.getElementById('row-actions-dots-btn');
                                if (!rowActionsDotsBtn) {
                                    rowActionsDotsBtn = document.createElement('button');
                                    rowActionsDotsBtn.id = 'row-actions-dots-btn';
                                    rowActionsDotsBtn.type = 'button';
                                    rowActionsDotsBtn.setAttribute('aria-label', 'Row actions');
                                    // Plain ellipsis trigger: no circle/background
                                    rowActionsDotsBtn.className = 'hidden fixed z-[100000] w-6 h-6 p-0 bg-transparent border-0 shadow-none flex items-center justify-center text-gray-600 hover:text-gray-900 cursor-pointer';
                                    rowActionsDotsBtn.style.cssText = 'position:fixed;z-index:100000;background:transparent;border:none;box-shadow:none;outline:none;';
                                    rowActionsDotsBtn.innerHTML =
                                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
                                        '  <circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none"></circle>' +
                                        '  <circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"></circle>' +
                                        '  <circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"></circle>' +
                                        '</svg>';
                                    document.body.appendChild(rowActionsDotsBtn);
                                }
                                var rowActionsDotsTargetRow = null;
                                var rowActionsDotsTargetRefCell = null;
                                var rowActionsBlueTargetRefCell = null;
                                var pendingReselectCellsPick = false;
                                var reselectCellsTargetTd = null;
                                /** After mousedown applies Reselect Cells, the following click still fires on the source cell and would re-run blue-row logic - suppress that one click. */
                                var suppressNextTableClickAfterReselectCellsPick = false;
                                var rowActionsDotsTargetIsBlue = false;
                                var hoveredRowForActions = null;
                                var rowActionsHideTimeout = null;
                                var forceOpenSelectionPopoverOnce = false;
                                var campusTargetsByNormalizedKey = @json($campusTargetsByNormalizedKey ?? []);
                                var latestCampusCompareEntries = [];
                                var pendingCompareCampusTargetResults = [];
                                var summaryRulesData = @json((is_array($template->fields_json ?? null) ? ($template->fields_json['summary_rules'] ?? []) : []));
                                var summaryCellMappingsData = @json((is_array($template->fields_json ?? null) ? ($template->fields_json['summary_cell_mappings'] ?? []) : []));
                                var summarySelectionMappingsByKey = {};
                                var selectionModeState = '';
                                var selectionCalcState = '';
                                var activeGrandTotalCell = null;
                                var grandTotalManualOverrideByKey = {};
                                var suppressSelectionClearUntil = 0;
                                var cellEditUndoStack = [];
                                var cellEditRedoStack = [];
                                var CELL_EDIT_UNDO_MAX = 40;
                                
                                function isCellEmptyValue(v) {
                                    var s = (v === null || v === undefined) ? '' : String(v);
                                    s = s.trim();
                                    return s === '' || s === '-' || s === '-' || s.toLowerCase() === 'select...';
                                }
                                
                                function getEditableInputElFromTd(td) {
                                    if (!td) return null;
                                    // Try common editable elements inside the td
                                    var el = td.querySelector('input[name], select[name], textarea[name]');
                                    if (!el) return null;
                                    // Ignore hidden fields if any
                                    if (el.tagName === 'INPUT' && (el.type || '').toLowerCase() === 'hidden') return null;
                                    return el;
                                }
                                
                                function readValueFromTd(td) {
                                    var inputEl = getEditableInputElFromTd(td);
                                    if (!inputEl) return null;
                                    var fieldKey = inputEl.getAttribute('name') || '';
                                    var value = (inputEl.tagName === 'SELECT') ? inputEl.value : (inputEl.value || '');
                                    if (!fieldKey) return null;
                                    return { fieldKey: String(fieldKey), value: String(value) };
                                }
                                
                                function writeValueToTd(td, fieldKey, value) {
                                    var inputEl = getEditableInputElFromTd(td);
                                    if (!inputEl) return false;
                                    var key = inputEl.getAttribute('name') || '';
                                    if (fieldKey && String(key) !== String(fieldKey)) return false;
                                    if (inputEl.disabled || inputEl.readOnly) return false;
                                    
                                    if (inputEl.tagName === 'SELECT') {
                                        inputEl.value = value || '';
                                        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                    } else {
                                        inputEl.value = value || '';
                                        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                                        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                    }
                                    return true;
                                }
                                function getGrandTotalCellKey(gtTd) {
                                    if (!gtTd) return null;
                                    var colIdx = getColIndex(gtTd);
                                    var tr = gtTd.closest('tr.grand-total-row');
                                    var rowIdx = tr && tableBody ? Array.prototype.indexOf.call(tableBody.querySelectorAll('tr.grand-total-row'), tr) : -1;
                                    return 'gt_' + colIdx + '_' + rowIdx;
                                }
                                function setGrandTotalManualOverride(gtTd, on) {
                                    var key = getGrandTotalCellKey(gtTd);
                                    if (!key) return;
                                    if (on) grandTotalManualOverrideByKey[key] = true;
                                    else delete grandTotalManualOverrideByKey[key];
                                }
                                function suppressSelectionClear(ms) {
                                    var ttl = typeof ms === 'number' ? ms : 1200;
                                    suppressSelectionClearUntil = Date.now() + Math.max(200, ttl);
                                }
                                var getFieldKey = function(field) {
                                    var k = field.key || field.name || null;
                                    if (k) return String(k);
                                    var label = field.label || '';
                                    return label.replace(/[^a-z0-9]+/gi, '_').toLowerCase().replace(/^_+|_+$/g, '');
                                };
                                function getSavedCustomFormulas() {
                                    try {
                                        var raw = localStorage.getItem(customFormulasStorageKey);
                                        if (!raw) return [];
                                        var arr = JSON.parse(raw);
                                        return Array.isArray(arr) ? arr.filter(function(x) { return typeof x === 'string' && x.trim().length > 0; }) : [];
                                    } catch (e) { return []; }
                                }
                                function normalizeCustomExpr(expr) {
                                    var s = String(expr || '').trim();
                                    if (!s) return '';
                                    s = s.replace(/\s+/g, ' ');
                                    s = s.replace(/[*x]/gi, '\u00D7').replace(/\//g, '\u00F7');
                                    s = s.replace(/\s*([+\-\u00D7\u00F7])\s*/g, ' $1 ');
                                    return s.replace(/\s+/g, ' ').trim();
                                }
                                function addSavedCustomFormula(expr) {
                                    var ex = normalizeCustomExpr(expr);
                                    if (!ex) return;
                                    var arr = getSavedCustomFormulas();
                                    var normalized = arr.map(normalizeCustomExpr);
                                    if (normalized.indexOf(ex) === -1) {
                                        arr.unshift(ex);
                                        if (arr.length > 20) arr = arr.slice(0, 20);
                                        try { localStorage.setItem(customFormulasStorageKey, JSON.stringify(arr)); } catch (e) {}
                                    }
                                }
                                function removeSavedCustomFormula(expr) {
                                    var ex = normalizeCustomExpr(expr);
                                    if (!ex) return;
                                    var arr = getSavedCustomFormulas().filter(function(x) { return normalizeCustomExpr(x) !== ex; });
                                    try { localStorage.setItem(customFormulasStorageKey, JSON.stringify(arr)); } catch (e) {}
                                }
                                function isPercentOperation(op, customExpr) {
                                    if (op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent') return true;
                                    if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                        var expr = (typeof op === 'string' && op.indexOf('custom:') === 0) ? op.substring(7) : (customExpr || '');
                                        var n = (normalizeCustomExpr(expr) || expr).toLowerCase();
                                        return /\u00D7\s*100\s*$|\*\s*100\s*$/.test(n) || /\u00D7\s*100\b|\*\s*100\b/.test(n);
                                    }
                                    return false;
                                }
                                function updateSummaryRulesCacheFromResponse(res) {
                                    if (!res) return;
                                    if (Array.isArray(res.summary_rules)) {
                                        summaryRulesData = res.summary_rules;
                                    }
                                    if (Array.isArray(res.summary_cell_mappings)) {
                                        summaryCellMappingsData = res.summary_cell_mappings;
                                        rebuildSelectionMappingsCache();
                                    }
                                }
                                function setCellFormulaMapping(cell, mapping) {
                                    if (!cell) return;
                                    mapping = mapping || {};
                                    try {
                                        cell._formulaMapping = JSON.parse(JSON.stringify(mapping));
                                    } catch (err) {
                                        cell._formulaMapping = mapping;
                                    }
                                    var sourceColumns = Array.isArray(mapping.source_columns) ? mapping.source_columns : [];
                                    var rowUids = Array.isArray(mapping.row_uids) ? mapping.row_uids : [];
                                    var rowIndices = Array.isArray(mapping.row_indices) ? mapping.row_indices : [];
                                    if (sourceColumns.length > 0) cell.setAttribute('data-formula-source-columns', JSON.stringify(sourceColumns));
                                    else cell.removeAttribute('data-formula-source-columns');
                                    if (rowUids.length > 0) cell.setAttribute('data-formula-row-uids', JSON.stringify(rowUids));
                                    else cell.removeAttribute('data-formula-row-uids');
                                    if (rowIndices.length > 0) cell.setAttribute('data-formula-row-indices', JSON.stringify(rowIndices));
                                    else cell.removeAttribute('data-formula-row-indices');
                                    if (mapping.sourceA) cell.setAttribute('data-formula-source-a', String(mapping.sourceA));
                                    else cell.removeAttribute('data-formula-source-a');
                                    if (mapping.sourceB) cell.setAttribute('data-formula-source-b', String(mapping.sourceB));
                                    else cell.removeAttribute('data-formula-source-b');
                                    var sourceKeys = Array.isArray(mapping.source_keys) ? mapping.source_keys : [];
                                    if (sourceKeys.length > 0) cell.setAttribute('data-formula-source-keys', JSON.stringify(sourceKeys));
                                    else cell.removeAttribute('data-formula-source-keys');
                                    if (mapping.section_ref) cell.setAttribute('data-formula-section-ref', String(mapping.section_ref));
                                    else cell.removeAttribute('data-formula-section-ref');
                                    if (mapping.ui_calc_type) cell.setAttribute('data-formula-ui-calc-type', String(mapping.ui_calc_type));
                                    else cell.removeAttribute('data-formula-ui-calc-type');
                                    if (mapping.ui_formula_operation) cell.setAttribute('data-formula-ui-formula-operation', String(mapping.ui_formula_operation));
                                    else cell.removeAttribute('data-formula-ui-formula-operation');
                                    if (mapping.source_quarter != null && String(mapping.source_quarter).trim() !== '') cell.setAttribute('data-formula-source-quarter', String(mapping.source_quarter));
                                    else cell.removeAttribute('data-formula-source-quarter');
                                    if (String(mapping.grand_total_ctc_aggregate || '') === '1') cell.setAttribute('data-formula-grand-total-ctc-aggregate', '1');
                                    else cell.removeAttribute('data-formula-grand-total-ctc-aggregate');
                                    if (mapping.manual_total_from_all_blues === true || String(mapping.manual_total_from_all_blues || '') === '1') cell.setAttribute('data-formula-manual-total-all-blues', '1');
                                    else cell.removeAttribute('data-formula-manual-total-all-blues');
                                    var uiCt = String(mapping.ui_calc_type || '').trim();
                                    var uiFo = String(mapping.ui_formula_operation || '').trim();
                                    if (mapping.count_adjust !== undefined && mapping.count_adjust !== null && (uiCt === 'unique_adjust' || uiFo === 'unique_adjust')) {
                                        var cai = parseInt(String(mapping.count_adjust), 10);
                                        cell.setAttribute('data-formula-count-adjust', String(isNaN(cai) ? 0 : cai));
                                    } else {
                                        cell.removeAttribute('data-formula-count-adjust');
                                    }
                                }
                                function clearCellFormulaMapping(cell) {
                                    if (!cell) return;
                                    try { delete cell._formulaMapping; } catch (err) {}
                                    cell.removeAttribute('data-formula-source-columns');
                                    cell.removeAttribute('data-formula-row-uids');
                                    cell.removeAttribute('data-formula-row-indices');
                                    cell.removeAttribute('data-formula-source-a');
                                    cell.removeAttribute('data-formula-source-b');
                                    cell.removeAttribute('data-formula-source-keys');
                                    cell.removeAttribute('data-formula-section-ref');
                                    cell.removeAttribute('data-formula-ui-calc-type');
                                    cell.removeAttribute('data-formula-ui-formula-operation');
                                    cell.removeAttribute('data-formula-source-quarter');
                                    cell.removeAttribute('data-formula-grand-total-ctc-aggregate');
                                    cell.removeAttribute('data-formula-manual-total-all-blues');
                                    cell.removeAttribute('data-formula-count-adjust');
                                }
                                function getCellFormulaMapping(cell) {
                                    if (!cell) return null;
                                    if (cell._formulaMapping && typeof cell._formulaMapping === 'object') {
                                        return cell._formulaMapping;
                                    }
                                    var sourceColumnsRaw = cell.getAttribute('data-formula-source-columns');
                                    var sourceA = cell.getAttribute('data-formula-source-a');
                                    var sourceB = cell.getAttribute('data-formula-source-b');
                                    var sourceKeysRaw = cell.getAttribute('data-formula-source-keys');
                                    var sectionRef = cell.getAttribute('data-formula-section-ref');
                                    var rowUidsRaw = cell.getAttribute('data-formula-row-uids');
                                    var rowIndicesRaw = cell.getAttribute('data-formula-row-indices');
                                    var uiCalcType = cell.getAttribute('data-formula-ui-calc-type');
                                    var uiFormulaOperation = cell.getAttribute('data-formula-ui-formula-operation');
                                    var sourceQuarter = cell.getAttribute('data-formula-source-quarter');
                                    var grandTotalCtcAggregate = cell.getAttribute('data-formula-grand-total-ctc-aggregate');
                                    var manualTotalAllBluesAttr = cell.getAttribute('data-formula-manual-total-all-blues');
                                    var countAdjustAttr = cell.getAttribute('data-formula-count-adjust');
                                    if (!sourceColumnsRaw && !sourceA && !sourceB && !sourceKeysRaw && !sectionRef && !rowUidsRaw && !rowIndicesRaw && !uiCalcType && !uiFormulaOperation && !sourceQuarter && !grandTotalCtcAggregate && !manualTotalAllBluesAttr && countAdjustAttr == null) return null;
                                    var mapping = {};
                                    try { mapping.source_columns = sourceColumnsRaw ? JSON.parse(sourceColumnsRaw) : []; } catch (err) { mapping.source_columns = []; }
                                    try { mapping.row_uids = rowUidsRaw ? JSON.parse(rowUidsRaw) : []; } catch (err2) { mapping.row_uids = []; }
                                    try { mapping.row_indices = rowIndicesRaw ? JSON.parse(rowIndicesRaw) : []; } catch (err3) { mapping.row_indices = []; }
                                    try { mapping.source_keys = sourceKeysRaw ? JSON.parse(sourceKeysRaw) : []; } catch (err4) { mapping.source_keys = []; }
                                    if (sourceA) mapping.sourceA = sourceA;
                                    if (sourceB) mapping.sourceB = sourceB;
                                    if (sectionRef) mapping.section_ref = sectionRef;
                                    if (uiCalcType) mapping.ui_calc_type = uiCalcType;
                                    if (uiFormulaOperation) mapping.ui_formula_operation = uiFormulaOperation;
                                    if (sourceQuarter) mapping.source_quarter = sourceQuarter;
                                    if (String(grandTotalCtcAggregate || '') === '1') mapping.grand_total_ctc_aggregate = '1';
                                    if (String(manualTotalAllBluesAttr || '') === '1') mapping.manual_total_from_all_blues = true;
                                    if (countAdjustAttr !== null && countAdjustAttr !== '') {
                                        var caParsed = parseInt(String(countAdjustAttr), 10);
                                        mapping.count_adjust = isNaN(caParsed) ? 0 : caParsed;
                                    }
                                    cell._formulaMapping = mapping;
                                    return mapping;
                                }
                                function parseUniqueCountAdjustFromUi() {
                                    var amtRaw = uniqueAdjustAmountInput ? String(uniqueAdjustAmountInput.value || '').trim() : '0';
                                    var amt = parseInt(amtRaw, 10);
                                    if (isNaN(amt) || amt < 0) amt = 0;
                                    var op = uniqueAdjustOperatorSelect ? String(uniqueAdjustOperatorSelect.value || 'add') : 'add';
                                    if (op === 'subtract') return -amt;
                                    return amt;
                                }
                                function updateUniqueAdjustPreview() {
                                    if (!uniqueAdjustPreview) return;
                                    var amtRaw = uniqueAdjustAmountInput ? String(uniqueAdjustAmountInput.value || '').trim() : '0';
                                    var amt = parseInt(amtRaw, 10);
                                    if (isNaN(amt) || amt < 0) amt = 0;
                                    var op = uniqueAdjustOperatorSelect ? String(uniqueAdjustOperatorSelect.value || 'add') : 'add';
                                    if (amt === 0) {
                                        uniqueAdjustPreview.textContent = 'Preview: count unique, then no change';
                                        return;
                                    }
                                    if (op === 'subtract') {
                                        uniqueAdjustPreview.textContent = 'Preview: (count unique) \u2212 ' + amt + '  \u2192  max(0, result)';
                                    } else {
                                        uniqueAdjustPreview.textContent = 'Preview: (count unique) + ' + amt;
                                    }
                                }
                                function syncUniqueAdjustPanelFromMapping(mapping) {
                                    if (!uniqueAdjustOperatorSelect || !uniqueAdjustAmountInput) return;
                                    var v = 0;
                                    if (mapping && mapping.count_adjust !== undefined && mapping.count_adjust !== null && String(mapping.count_adjust).trim() !== '') {
                                        var p = parseInt(String(mapping.count_adjust), 10);
                                        if (!isNaN(p)) v = p;
                                    }
                                    if (v < 0) {
                                        uniqueAdjustOperatorSelect.value = 'subtract';
                                        uniqueAdjustAmountInput.value = String(Math.abs(v));
                                    } else {
                                        uniqueAdjustOperatorSelect.value = 'add';
                                        uniqueAdjustAmountInput.value = String(v);
                                    }
                                    updateUniqueAdjustPreview();
                                }
                                function setSelectionCalcOption(value, label) {
                                    if (!selectionCalcTypeSelect) return;
                                    var val = String(value || '').trim();
                                    if (!val) {
                                        selectionCalcTypeSelect.value = '';
                                        var tempOptToRemove = selectionCalcTypeSelect.querySelector('option[data-temp-calc-option="1"]');
                                        if (tempOptToRemove) tempOptToRemove.remove();
                                        return;
                                    }
                                    var options = Array.prototype.slice.call(selectionCalcTypeSelect.options || []);
                                    var matched = options.find(function(opt) { return String(opt.value || '') === val; });
                                    if (!matched) {
                                        var tempOpt = selectionCalcTypeSelect.querySelector('option[data-temp-calc-option="1"]');
                                        if (!tempOpt) {
                                            tempOpt = document.createElement('option');
                                            tempOpt.setAttribute('data-temp-calc-option', '1');
                                            selectionCalcTypeSelect.appendChild(tempOpt);
                                        }
                                        tempOpt.value = val;
                                        tempOpt.textContent = label || val;
                                        matched = tempOpt;
                                    } else {
                                        var oldTemp = selectionCalcTypeSelect.querySelector('option[data-temp-calc-option="1"]');
                                        if (oldTemp) oldTemp.remove();
                                    }
                                    matched.selected = true;
                                    selectionCalcTypeSelect.value = val;
                                }
                                function clearSelectionCalcIfRestored() {
                                    if (selectionCalcState === 'restored') {
                                        setSelectionCalcOption('', '');
                                        selectionCalcState = '';
                                    }
                                }
                                function restoreCalcTypeFromMapping(mapping) {
                                    if (!selectionCalcTypeSelect || !mapping || typeof mapping !== 'object') return;
                                    var calcType = String(mapping.ui_calc_type || '').trim();
                                    var calcLabel = '';
                                    if (calcType === 'grand-total') {
                                        var gtOp = String(mapping.ui_formula_operation || mapping.operation || 'sum').trim();
                                        if (gtOp === 'count_unique') gtOp = 'unique';
                                        if (gtOp === 'count_total') gtOp = 'countif';
                                        if (gtOp === 'avg') gtOp = 'avg_number';
                                        if (['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows'].indexOf(gtOp) !== -1) {
                                            calcType = gtOp;
                                        } else {
                                            calcType = 'sum';
                                        }
                                    }
                                    if (calcType === 'formula') {
                                        var legacyFormulaOp = String(mapping.ui_formula_operation || '').trim();
                                        if (legacyFormulaOp === 'percent_of') {
                                            calcType = 'blue-row-formula';
                                        } else if (legacyFormulaOp === 'sum') {
                                            calcType = 'sum';
                                        } else {
                                            calcType = 'saved-summary-formula';
                                            calcLabel = 'Saved Formula';
                                        }
                                    } else if (calcType === 'summary-formula') {
                                        var legacySummaryOp = String(mapping.ui_formula_operation || '').trim();
                                        if (legacySummaryOp === 'percent_of') {
                                            calcType = 'blue-row-formula';
                                        } else if (legacySummaryOp === 'sum') {
                                            calcType = 'sum';
                                        } else {
                                            calcType = 'saved-summary-formula';
                                            calcLabel = 'Saved Summary Formula';
                                        }
                                    }
                                    if (!calcType) {
                                        var op = String(mapping.operation || '').trim();
                                        if (op === 'count_unique' && String(mapping.ui_calc_type || '').trim() === 'unique_adjust') calcType = 'unique_adjust';
                                        else if (op === 'count_unique' && mapping.count_adjust !== undefined && mapping.count_adjust !== null && String(mapping.count_adjust).trim() !== '') calcType = 'unique_adjust';
                                        else if (op === 'count_unique') calcType = 'unique';
                                        else if (op === 'count_total') calcType = 'countif';
                                        else if (op === 'avg') calcType = 'avg';
                                        else if (op === 'sum') {
                                            calcType = 'sum';
                                        } else if (op === 'ratio_percent') calcType = 'blue-row-formula';
                                        else if (op === 'ratio') {
                                            calcType = 'saved-summary-formula';
                                            calcLabel = 'Saved Summary Formula';
                                        }
                                    }
                                    if (!calcType) return;
                                    setSelectionCalcOption(calcType, calcLabel);
                                    selectionCalcState = 'restored';
                                    if (typeof syncUniqueAdjustPanelFromMapping === 'function') syncUniqueAdjustPanelFromMapping(mapping);
                                    if ((calcType === 'summary-formula' || calcType === 'saved-summary-formula' || calcType === 'blue-row-formula') && formulaOperationSelect) {
                                        var uiFormulaOperation = String(mapping.ui_formula_operation || mapping.operation || '').trim();
                                        if (uiFormulaOperation) {
                                            var hasFormulaOption = Array.prototype.slice.call(formulaOperationSelect.options || []).some(function(opt) {
                                                return String(opt.value || '') === uiFormulaOperation;
                                            });
                                            if (hasFormulaOption) formulaOperationSelect.value = uiFormulaOperation;
                                        }
                                    }
                                }
                                function hasMeaningfulBlueResultValue(cell) {
                                    if (!cell) return false;
                                    var raw = String(getCellRawValue(cell) || '').trim();
                                    return raw !== '' && raw !== '-';
                                }
                                function inferBlueRowFormulaSources(blueRow, targetTd, targetVal) {
                                    if (!blueRow || !targetTd || typeof targetVal !== 'number') return null;
                                    var targetCol = getColIndex(targetTd);
                                    if (targetCol < 0 || targetCol >= fields.length) return null;
                                    var tds = window.getRowTdCells(blueRow);
                                    var candidates = [];
                                    for (var c = 0; c < tds.length && c < fields.length; c++) {
                                        if (c === targetCol) continue;
                                        var rawC = String(getCellRawValue(tds[c]) || '').trim();
                                        var val = toNumeric(getCellRawValue(tds[c]));
                                        if (val === 0 && rawC === '') continue;
                                        candidates.push({ idx: c, val: val, key: getFieldKey(fields[c]) });
                                    }
                                    /** Prefer sources just left of the result cell: rightmost qualifying pair, then tightest error (avoids early columns that coincidentally match the same %). */
                                    function betterInferenceCandidate(prev, next) {
                                        if (!next) return prev;
                                        if (!prev) return next;
                                        if (next.maxIdx > prev.maxIdx) return next;
                                        if (next.maxIdx < prev.maxIdx) return prev;
                                        if (next.err < prev.err - 1e-9) return next;
                                        if (prev.err < next.err - 1e-9) return prev;
                                        if (next.minIdx > prev.minIdx) return next;
                                        return prev;
                                    }
                                    var bestSum = null;
                                    var bestPercent = null;
                                    for (var i = 0; i < candidates.length; i++) {
                                        for (var j = 0; j < candidates.length; j++) {
                                            if (i === j) continue;
                                            var ia = candidates[i].idx;
                                            var ib = candidates[j].idx;
                                            var maxIdx = Math.max(ia, ib);
                                            var minIdx = Math.min(ia, ib);
                                            var sum = candidates[i].val + candidates[j].val;
                                            var sumErr = Math.abs(sum - targetVal);
                                            if (sumErr < 0.02) {
                                                var sumCand = {
                                                    mapping: {
                                                        sourceA: candidates[i].key,
                                                        sourceB: candidates[j].key,
                                                        operation: 'sum',
                                                        ui_calc_type: 'blue-row-formula',
                                                        ui_formula_operation: 'sum'
                                                    },
                                                    idxA: ia,
                                                    idxB: ib,
                                                    err: sumErr,
                                                    maxIdx: maxIdx,
                                                    minIdx: minIdx
                                                };
                                                bestSum = betterInferenceCandidate(bestSum, sumCand);
                                            }
                                            var vb = candidates[j].val;
                                            if (vb > 0) {
                                                var pctErr = Math.abs((candidates[i].val / vb) * 100 - targetVal);
                                                if (pctErr < 0.5) {
                                                    var pctCand = {
                                                        mapping: {
                                                            sourceA: candidates[i].key,
                                                            sourceB: candidates[j].key,
                                                            operation: 'percent_of',
                                                            ui_calc_type: 'blue-row-formula',
                                                            ui_formula_operation: 'percent_of'
                                                        },
                                                        idxA: ia,
                                                        idxB: ib,
                                                        err: pctErr,
                                                        maxIdx: maxIdx,
                                                        minIdx: minIdx
                                                    };
                                                    bestPercent = betterInferenceCandidate(bestPercent, pctCand);
                                                }
                                            }
                                        }
                                    }
                                    var tf = fields[targetCol];
                                    var preferPercent = tf && typeof isFieldPercentage === 'function'
                                        && isFieldPercentage(tf, typeof normalizeMetricText === 'function' ? normalizeMetricText(getFieldKey(tf)) : '');
                                    var pick = null;
                                    if (preferPercent) {
                                        pick = bestPercent || bestSum;
                                    } else {
                                        pick = bestSum || bestPercent;
                                    }
                                    if (!pick) return null;
                                    return {
                                        mapping: pick.mapping,
                                        idxA: pick.idxA,
                                        idxB: pick.idxB
                                    };
                                }
                                function getBlueRowSiblingFormulaMapping(cell) {
                                    if (!cell) return null;
                                    var row = cell.closest('tr.data-row');
                                    if (!row || !row.classList.contains('bg-blue-100')) return null;
                                    var tds = window.getRowTdCells(row);
                                    var best = null;
                                    var bestScore = -1;
                                    tds.forEach(function(td) {
                                        if (!td || td === cell) return;
                                        var mapping = getCellFormulaMapping(td);
                                        if (!mapping) return;
                                        var score = 0;
                                        if (Array.isArray(mapping.source_columns)) score += mapping.source_columns.length * 10;
                                        if (String(mapping.sourceB || '').trim() !== '') score += 5;
                                        if (Array.isArray(mapping.row_uids) && mapping.row_uids.length > 0) score += 3;
                                        if (score > bestScore) {
                                            bestScore = score;
                                            best = mapping;
                                        }
                                    });
                                    return best;
                                }
                                function buildSelectionMappingKey(sectionRef, targetField) {
                                    return String(sectionRef || '') + '||' + String(targetField || '');
                                }
                                function rebuildSelectionMappingsCache() {
                                    summarySelectionMappingsByKey = {};
                                    if (!Array.isArray(summaryCellMappingsData)) return;
                                    summaryCellMappingsData.forEach(function(mapping) {
                                        mapping = mapping || {};
                                        var key = buildSelectionMappingKey(mapping.section_ref || '', mapping.target_field || '');
                                        if (!key || key === '||') return;
                                        summarySelectionMappingsByKey[key] = mapping;
                                    });
                                }
                                function upsertSelectionMapping(mapping) {
                                    mapping = mapping || {};
                                    var key = buildSelectionMappingKey(mapping.section_ref || '', mapping.target_field || '');
                                    if (!key || key === '||') return;
                                    summarySelectionMappingsByKey[key] = mapping;
                                }
                                /** Campus blue summary (bg-blue-100) or green manual cross-campus total row (formula UI). */
                                function rowIsBlueOrManualSummaryForFormula(tr) {
                                    if (!tr) return false;
                                    if (String(tr.getAttribute('data-manual-total-row') || '') === '1') return true;
                                    return tr.classList.contains('bg-blue-100');
                                }
                                /** When cross-campus manual mode is on, only the manual total row receives writes; otherwise campus blue rows only. */
                                function rowMatchesBlueFormulaApplyTarget(tr, crossCampusManualMode) {
                                    if (!tr) return false;
                                    if (crossCampusManualMode) return String(tr.getAttribute('data-manual-total-row') || '') === '1';
                                    return tr.classList.contains('bg-blue-100');
                                }
                                /** Persist Formula (A & B) / multi / custom blue-row mappings to template fields_json so new submissions and reloads hydrate the same %. */
                                function persistBlueRowSameRowFormulasToTemplate(selectedBlueCells, mappingBase) {
                                    if (typeof summaryRulesUrl === 'undefined' || !selectedBlueCells || selectedBlueCells.length === 0 || !mappingBase) return;
                                    var token = document.querySelector('meta[name="csrf-token"]');
                                    token = token ? token.getAttribute('content') : '';
                                    var seen = {};
                                    selectedBlueCells.forEach(function(td) {
                                        var br = td.closest('tr.data-row');
                                        if (!br || !rowIsBlueOrManualSummaryForFormula(br)) return;
                                        var cidx = getColIndex(td);
                                        var targetField = cidx >= 0 && cidx < fields.length ? getFieldKey(fields[cidx]) : '';
                                        if (!targetField) return;
                                        var sectionRef = buildSectionRefFromRow(br);
                                        var dedupeKey = targetField + '|' + String(sectionRef || '');
                                        if (seen[dedupeKey]) return;
                                        seen[dedupeKey] = true;
                                        var payload = Object.assign({}, mappingBase, {
                                            target_field: targetField,
                                            section_ref: sectionRef
                                        });
                                        upsertSelectionMapping(payload);
                                        fetch(summaryRulesUrl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                            body: JSON.stringify({ output: payload })
                                        }).then(function(r) { return r.json(); }).then(function(res) { updateSummaryRulesCacheFromResponse(res); }).catch(function() {});
                                    });
                                }
                                rebuildSelectionMappingsCache();
                                function setSelectionModeState(mode) {
                                    selectionModeState = String(mode || '');
                                    if (selectionModeState === 'manual'
                                        || selectionModeState.indexOf('Manual blue value entered') === 0
                                        || selectionModeState.indexOf('Empty blue result cell') === 0) {
                                        clearSelectionCalcIfRestored();
                                    }
                                    if (!selectionModeHint) return;
                                    if (!selectionModeState) {
                                        selectionModeHint.textContent = '';
                                        selectionModeHint.classList.add('hidden');
                                        return;
                                    }
                                    if (selectionModeState === 'retrieved') {
                                        selectionModeHint.textContent = 'Using saved formula mapping from this blue result.';
                                    } else if (selectionModeState === 'manual') {
                                        selectionModeHint.textContent = 'Using your current manual selection.';
                                    } else {
                                        selectionModeHint.textContent = selectionModeState;
                                    }
                                    selectionModeHint.classList.remove('hidden');
                                }
                                function makeRowUid() {
                                    return 'row_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 10);
                                }
                                function ensureDataRowUid(tr) {
                                    if (!tr || tr.getAttribute('data-row-type') === 'summary' || tr.classList.contains('bg-blue-100')) {
                                        return '';
                                    }
                                    var uid = (tr.getAttribute('data-row-uid') || '').trim();
                                    if (!uid) {
                                        uid = makeRowUid();
                                        tr.setAttribute('data-row-uid', uid);
                                    }
                                    return uid;
                                }
                                function findTdByRowUidCol(rowUid, colIdx) {
                                    if (!tableBody || !rowUid || colIdx < 0) return null;
                                    var rows = tableBody.querySelectorAll('tr.data-row');
                                    for (var ri = 0; ri < rows.length; ri++) {
                                        var tr = rows[ri];
                                        if ((tr.getAttribute('data-row-uid') || '') !== String(rowUid)) continue;
                                        var tds = window.getRowTdCells(tr);
                                        return tds[colIdx] || null;
                                    }
                                    return null;
                                }
                                function applyCellEditBatch(batch, useBefore) {
                                    if (!batch || !batch.length) return;
                                    for (var bi = 0; bi < batch.length; bi++) {
                                        var c = batch[bi];
                                        var td = findTdByRowUidCol(c.rowUid, c.colIdx);
                                        if (!td) continue;
                                        var v = useBefore ? c.before : c.after;
                                        writeValueToTd(td, c.fieldKey, v);
                                        var aLink = td.querySelector('a[href]');
                                        if (aLink && v) aLink.setAttribute('href', v);
                                        else if (aLink && !v) aLink.removeAttribute('href');
                                    }
                                    var td0 = findTdByRowUidCol(batch[0].rowUid, batch[0].colIdx);
                                    if (td0 && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(td0);
                                }
                                function pushCellEditUndoBatch(batch) {
                                    if (!batch || batch.length === 0) return;
                                    cellEditUndoStack.push(batch);
                                    if (cellEditUndoStack.length > CELL_EDIT_UNDO_MAX) cellEditUndoStack.shift();
                                    cellEditRedoStack.length = 0;
                                }
                                function performCellUndo() {
                                    if (!cellEditUndoStack.length) return false;
                                    var batch = cellEditUndoStack.pop();
                                    applyCellEditBatch(batch, true);
                                    cellEditRedoStack.push(batch);
                                    window.tableDataDirty = true;
                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                    if (typeof hideRowActionsPopover === 'function') hideRowActionsPopover();
                                    return true;
                                }
                                function performCellRedo() {
                                    if (!cellEditRedoStack.length) return false;
                                    var batch = cellEditRedoStack.pop();
                                    applyCellEditBatch(batch, false);
                                    cellEditUndoStack.push(batch);
                                    window.tableDataDirty = true;
                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                    if (typeof hideRowActionsPopover === 'function') hideRowActionsPopover();
                                    return true;
                                }
                                function activeElementIsTableCellTyping() {
                                    var a = document.activeElement;
                                    if (!a || !tableBody || !tableBody.contains(a)) return false;
                                    if (!a.closest || !a.closest('td')) return false;
                                    var tdAct = a.closest('td');
                                    var trAct = tdAct && tdAct.closest ? tdAct.closest('tr.data-row') : null;
                                    // Grand total / KPI overall row: allow document-level Ctrl+Z/Y to run our cell batch undo first.
                                    // If the stack is empty, performCellUndo is a no-op and the browser can still handle the key.
                                    if (trAct && (trAct.classList.contains('grand-total-row') || trAct.classList.contains('kpi-finalize-total-row'))) {
                                        return false;
                                    }
                                    if (a.tagName === 'TEXTAREA') return true;
                                    if (a.tagName === 'INPUT') {
                                        var t = (a.type || '').toLowerCase();
                                        if (t === 'button' || t === 'checkbox' || t === 'radio' || t === 'hidden' || t === 'submit') return false;
                                        return !a.readOnly && !a.disabled;
                                    }
                                    return false;
                                }
                                function getSelectedCellsStats() {
                                    var selected = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')) : [];
                                    var sourceCount = 0;
                                    var blueTargetCount = 0;
                                    var sourceCols = {};
                                    var blueCols = {};
                                    var hasGrandTotalSelected = selected.some(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && tr.classList.contains('grand-total-row');
                                    });
                                    selected.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return;
                                        var colIndex = getColIndex(td);
                                        if (colIndex < 0 || colIndex >= fields.length) return;
                                        var key = getFieldKey(fields[colIndex]);
                                        if (hasGrandTotalSelected) {
                                            if (tr.classList.contains('grand-total-row')) {
                                                blueTargetCount++;
                                                blueCols[key] = true;
                                            } else if (tr.classList.contains('bg-blue-100')) {
                                                sourceCount++;
                                                sourceCols[key] = true;
                                            }
                                        } else if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) {
                                            blueTargetCount++;
                                            blueCols[key] = true;
                                        } else {
                                            sourceCount++;
                                            sourceCols[key] = true;
                                        }
                                    });
                                    var blueRowFormulaSourceCount = 0;
                                    var blueRowFormulaSourceCols = {};
                                    var formulaLabel = '';
                                    if (blueTargetCount > 0 && sourceCount === 0) {
                                        var blueTd = selected.find(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'));
                                        });
                                        var mapping = blueTd ? getCellFormulaMapping(blueTd) : null;
                                        if (mapping && (String(mapping.ui_calc_type || '').trim() === 'blue-row-formula' || String(mapping.ui_calc_type || '').trim() === 'blue-row-formula-multi' || String(mapping.ui_calc_type || '').trim() === 'blue-row-formula-custom' || String(mapping.ui_calc_type || '').trim() === 'grand-total')) {
                                            var blueRow = blueTd ? blueTd.closest('tr.data-row') : null;
                                            if (blueRow && blueRow.classList.contains('bg-blue-100')) {
                                                var forBlue = blueRow.querySelectorAll('td.cell-source-for-blue');
                                                forBlue.forEach(function(td) {
                                                    var c = getColIndex(td);
                                                    if (c >= 0 && c < fields.length) {
                                                        blueRowFormulaSourceCount++;
                                                        blueRowFormulaSourceCols[getFieldKey(fields[c])] = true;
                                                    }
                                                });
                                            }
                                            if (String(mapping.ui_calc_type || '').trim() === 'grand-total') {
                                                var opGT = String(mapping.ui_formula_operation || 'sum').trim();
                                                formulaLabel = opGT === 'sum' ? 'Sum (all blue)' : opGT === 'avg' ? 'Average (all blue)' : (opGT === 'percent_of' ? 'A A- B A 100' : opGT);
                                            } else if (mapping.custom_expr) {
                                                formulaLabel = String(mapping.custom_expr).trim();
                                            } else if (String(mapping.ui_calc_type || '').trim() === 'blue-row-formula-multi') {
                                                var op = String(mapping.ui_formula_operation || mapping.operation || 'sum').trim();
                                                if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                    formulaLabel = mapping.custom_expr ? String(mapping.custom_expr).trim() : (op.indexOf('custom:') === 0 ? op.substring(7) : 'Custom');
                                                } else {
                                                    var multiOpLabels = { sum: 'Sum', avg: 'Average', subtract: 'A - B', multiply: 'A A B', divide: 'A A- B', percent_of: 'A A- B A 100', ratio_percent: 'A A- B A 100', ratio: 'A A- B', sum_over_b_percent: '(A+B)A-BA100', diff_over_b_percent: '(A-B)A-BA100' };
                                                    formulaLabel = multiOpLabels[op] || op;
                                                }
                                            } else {
                                                var op = String(mapping.ui_formula_operation || mapping.operation || 'sum').trim();
                                                var opLabels = { sum: 'A + B', subtract: 'A - B', multiply: 'A A B', divide: 'A A- B', percent_of: 'A A- B A 100', ratio_percent: 'A A- B A 100', ratio: 'A A- B', sum_over_b_percent: '(A+B)A-BA100', diff_over_b_percent: '(A-B)A-BA100' };
                                                formulaLabel = opLabels[op] || op;
                                            }
                                        }
                                    }
                                    var isBlueRowFormulaSource = false;
                                    if (blueRowFormulaSourceCount > 0) {
                                        sourceCount = blueRowFormulaSourceCount;
                                        sourceCols = blueRowFormulaSourceCols;
                                        isBlueRowFormulaSource = true;
                                    }
                                    return {
                                        totalSelected: selected.length,
                                        sourceCount: sourceCount,
                                        blueTargetCount: blueTargetCount,
                                        sourceColCount: Object.keys(sourceCols).length,
                                        blueColCount: Object.keys(blueCols).length,
                                        isBlueRowFormulaSource: isBlueRowFormulaSource,
                                        formulaLabel: formulaLabel,
                                    };
                                }
                                function buildSelectionHintText(stats) {
                                    var sourcePart = stats.isBlueRowFormulaSource
                                        ? ('Source: ' + stats.sourceCount + ' formula source cell(s) in ' + stats.sourceColCount + ' column(s)')
                                        : ('Source: ' + stats.sourceCount + ' cell(s) in ' + stats.sourceColCount + ' column(s)');
                                    var base = sourcePart + ' -> Target: ' + stats.blueTargetCount + ' blue cell(s) in ' + stats.blueColCount + ' column(s)';
                                    if (stats.formulaLabel) {
                                        base += ' | Formula: ' + stats.formulaLabel;
                                    }
                                    return base;
                                }
                                function updateCompareCampusTargetValuePreview() {
                                    if (!compareCampusTargetValuePreview || !compareCampusTargetColumn || !tableBody) return;
                                    var colKey = (compareCampusTargetColumn.value || '').trim();
                                    if (!colKey) {
                                        compareCampusTargetValuePreview.classList.add('hidden');
                                        return;
                                    }
                                    var colIndex = getFieldIndexByKeyFlexible(colKey);
                                    if (colIndex < 0) {
                                        compareCampusTargetValuePreview.classList.add('hidden');
                                        return;
                                    }
                                    var selected = Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected'));
                                    var blueSelected = selected.filter(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return !!(tr && tr.classList.contains('bg-blue-100'));
                                    });
                                    if (blueSelected.length === 0) {
                                        compareCampusTargetValuePreview.textContent = 'Select a blue cell to see its value.';
                                        compareCampusTargetValuePreview.classList.remove('hidden');
                                        return;
                                    }
                                    var values = [];
                                    blueSelected.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return;
                                        var tds = window.getRowTdCells(tr);
                                        var valueTd = tds[colIndex] || null;
                                        if (valueTd) {
                                            var val = getCellRawValue(valueTd);
                                            var display = String(val || '').trim() || '-';
                                            var campusLabel = getSectionCampusLabelForRow(tr) || 'Campus';
                                            values.push(campusLabel + ': ' + display);
                                        }
                                    });
                                    var label = (fields[colIndex] ? (fields[colIndex].label || getFieldKey(fields[colIndex])) : colKey);
                                    compareCampusTargetValuePreview.textContent = 'Value in "' + label + '": ' + (values.length > 0 ? values.join(' | ') : '-');
                                    compareCampusTargetValuePreview.classList.remove('hidden');
                                }
                                function updateSelectionCalcTypeForGrandTotal() {
                                    if (!selectionCalcTypeSelect) return;
                                    var selectedTds = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')) : [];
                                    var hasGrandTotal = selectedTds.some(function(t) {
                                        var r = t.closest('tr.data-row');
                                        return r && r.classList.contains('grand-total-row');
                                    });
                                    var selectedGrandTotalTd = null;
                                    for (var gtI = 0; gtI < selectedTds.length; gtI++) {
                                        var gtRow = selectedTds[gtI].closest('tr.data-row');
                                        if (gtRow && gtRow.classList.contains('grand-total-row')) {
                                            selectedGrandTotalTd = selectedTds[gtI];
                                            break;
                                        }
                                    }
                                    var manualGrandTotalMode = !!(hasGrandTotal && selectedGrandTotalTd && isGrandTotalManualSelectionMode(selectedGrandTotalTd));
                                    var hasBlueRow = selectedTds.some(function(t) {
                                        var r = t.closest('tr.data-row');
                                        return r && (r.classList.contains('bg-blue-100') || String(r.getAttribute('data-manual-total-row') || '') === '1');
                                    });
                                    var opts = Array.prototype.slice.call(selectionCalcTypeSelect.querySelectorAll('option'));
                                    opts.forEach(function(opt) {
                                        var baseLabel = opt.getAttribute('data-base-label');
                                        if (!baseLabel) {
                                            baseLabel = String(opt.textContent || '').replace(/\s+\(Manual\)\s*$/i, '').trim();
                                            opt.setAttribute('data-base-label', baseLabel);
                                        }
                                        opt.textContent = (opt.value === '') ? baseLabel : (baseLabel + (manualGrandTotalMode ? ' (Manual)' : ''));
                                        if (opt.value === '') return;
                                        opt.hidden = !!(hasGrandTotal && opt.getAttribute('data-hide-for-grand-total') === '1') || !!(hasBlueRow && opt.getAttribute('data-hide-for-blue-row') === '1');
                                    });
                                    var cur = selectionCalcTypeSelect.value;
                                    var curOpt = cur ? opts.find(function(o) { return o.value === cur; }) : null;
                                    if (curOpt && curOpt.hidden) selectionCalcTypeSelect.value = '';
                                }
                                function updateGrandTotalQuarterVisibility() {
                                    if (!grandTotalQuarterOptions || !tableBody) return;
                                    if (typeof isGrandTotalWizardContext === 'function' && isGrandTotalWizardContext()) {
                                        grandTotalQuarterOptions.classList.add('hidden');
                                        return;
                                    }
                                    var selectedTds = Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected'));
                                    var hasGrandTotal = selectedTds.some(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && tr.classList.contains('grand-total-row');
                                    });
                                    grandTotalQuarterOptions.classList.toggle('hidden', !hasGrandTotal);
                                }
                                function isQuarterOnlyGrandTotalTarget() {
                                    var gtCell = findActiveGrandTotalTargetCell();
                                    if (!gtCell) return false;
                                    var tr = gtCell.closest('tr.data-row');
                                    if (!tr || !tr.classList.contains('grand-total-row')) return false;
                                    var quarterColIdx = getQuarterColumnIndex();
                                    if (quarterColIdx < 0) return false;
                                    return getColIndex(gtCell) === quarterColIdx;
                                }
                                function updateQuarterOnlyModeUI() {
                                    if (!selectionCalcTypeSelect) return;
                                    var quarterOnly = isQuarterOnlyGrandTotalTarget();
                                    var inGrandTotalWizard = typeof isGrandTotalWizardContext === 'function' && isGrandTotalWizardContext();
                                    // Grand total wizard replaces the legacy "Select action" dropdown; quarter-only mode hides it too.
                                    selectionCalcTypeSelect.classList.toggle('hidden', quarterOnly || inGrandTotalWizard);
                                }
                                function updateSelectionLiveHints() {
                                    updateSelectionCalcTypeForGrandTotal();
                                    updateGrandTotalWizardVisibility();
                                    updateGrandTotalQuarterVisibility();
                                    updateQuarterOnlyModeUI();
                                    var calcType = selectionCalcTypeSelect ? selectionCalcTypeSelect.value : '';
                                    var showGrandTotalWizard = typeof isGrandTotalWizardContext === 'function' && isGrandTotalWizardContext();
                                    if (compareCampusTargetOptions) {
                                        compareCampusTargetOptions.classList.toggle('hidden', showGrandTotalWizard || calcType !== 'compare-campus-target');
                                    }
                                    var gtWizStep2ValHint = '';
                                    var gtWizStep3Val = '';
                                    var gtWizTypeValHint = '';
                                    if (showGrandTotalWizard) {
                                        var gtS2h = document.getElementById('gt-wizard-step2');
                                        var gtS3 = document.getElementById('gt-wizard-step3');
                                        var gtTh = document.getElementById('gt-wizard-type');
                                        gtWizStep2ValHint = gtS2h ? String(gtS2h.value || '').trim() : '';
                                        gtWizStep3Val = gtS3 ? String(gtS3.value || '').trim() : '';
                                        gtWizTypeValHint = gtTh ? String(gtTh.value || '').trim() : '';
                                    }
                                    var calcWizardUniqueAdjust = showGrandTotalWizard && gtWizTypeValHint === 'calculation' && (gtWizStep2ValHint === 'unique_adjust' || gtWizStep2ValHint === 'unique_adjust_manual');
                                    var showUniqueAdjustPanel = (!showGrandTotalWizard && calcType === 'unique_adjust') || (showGrandTotalWizard && !calcWizardUniqueAdjust && (gtWizStep3Val === 'unique_adjust' || gtWizStep3Val === 'unique_adjust_manual')) || calcWizardUniqueAdjust;
                                    if (uniqueAdjustOptions) {
                                        uniqueAdjustOptions.classList.toggle('hidden', !showUniqueAdjustPanel);
                                    }
                                    if (showUniqueAdjustPanel && typeof updateUniqueAdjustPreview === 'function') updateUniqueAdjustPreview();
                                    if (showGrandTotalWizard) {
                                        if (typeof reSelectGrandTotalWizardTargetIfNeeded === 'function') reSelectGrandTotalWizardTargetIfNeeded();
                                        var gtwCellHint = findActiveGrandTotalTargetCell();
                                        if (gtwCellHint && typeof syncGrandTotalWizardStep2FromResolvedQuarter === 'function') {
                                            syncGrandTotalWizardStep2FromResolvedQuarter(gtwCellHint);
                                        }
                                        var gtWizT = document.getElementById('gt-wizard-type');
                                        var gtWizS2 = document.getElementById('gt-wizard-step2');
                                        var gtWizW3 = document.getElementById('gt-wizard-step3-wrap');
                                        if (gtWizT && gtWizS2 && gtWizW3 && (gtWizT.value === 'quarter' || gtWizT.value === 'school_year') && String(gtWizS2.value || '').trim() !== '' && !gtWizW3.classList.contains('hidden')) {
                                            rebuildGrandTotalWizardStep3QuarterCalcOptions(true);
                                        }
                                        if (gtWizT && gtWizS2 && gtWizT.value === 'calculation' && String(gtWizS2.value || '').trim() !== '') {
                                            rebuildGrandTotalWizardCalculationTypeStep2(true);
                                        }
                                        var gtwCell = findActiveGrandTotalTargetCell();
                                        var gtwIdx = gtwCell ? getColIndex(gtwCell) : -1;
                                        var gtwLbl = (gtwIdx >= 0 && fields[gtwIdx]) ? String(fields[gtwIdx].label || getFieldKey(fields[gtwIdx]) || '').trim() : '';
                                        var stepsHint = (gtWizT && gtWizT.value === 'calculation') ? '1a2' : '1a3';
                                        if (selectionLiveHint) {
                                            selectionLiveHint.textContent = gtwLbl
                                                ? ('Grand total column: "' + gtwLbl + '". Complete steps ' + stepsHint + ', then Apply.')
                                                : ('Grand total: complete steps ' + stepsHint + ', then Apply.');
                                        }
                                        if (selectionModeHint) {
                                            selectionModeHint.textContent = '';
                                            selectionModeHint.classList.add('hidden');
                                        }
                                        return;
                                    }
                                    if (calcType === 'compare-campus-target') {
                                        updateCompareCampusTargetValuePreview();
                                        var colKey = compareCampusTargetColumn ? (compareCampusTargetColumn.value || '').trim() : '';
                                        var stats = getSelectedCellsStats();
                                        var hintText = 'Select column (value to compare), select blue cell (result destination), then Apply.';
                                        if (colKey && stats.blueTargetCount > 0) hintText = 'Value from "' + (fields[getFieldIndexByKeyFlexible(colKey)] ? (fields[getFieldIndexByKeyFlexible(colKey)].label || colKey) : colKey) + '" \u00F7 Campus Target \u2192 selected cell. Click Apply.';
                                        else if (!colKey) hintText = 'Select a column above (value to compare), then select a blue cell, then Apply.';
                                        else hintText = 'Select a blue cell (where result will appear), then Apply.';
                                        if (selectionLiveHint) selectionLiveHint.textContent = hintText;
                                        if (selectionModeHint) { selectionModeHint.textContent = ''; selectionModeHint.classList.add('hidden'); }
                                        return;
                                    }
                                    var stats = getSelectedCellsStats();
                                    var hintText = buildSelectionHintText(stats);
                                    if (selectionLiveHint) {
                                        selectionLiveHint.textContent = hintText;
                                    }
                                    if (autocalcSelectedInfo && autocalcModal && !autocalcModal.classList.contains('hidden')) {
                                        autocalcSelectedInfo.textContent = hintText;
                                    }
                                }
                                function collectBySubmission() {
                                    var bySub = {};
                                    var rows = tableBody ? tableBody.querySelectorAll('tr.data-row') : [];
                                    var firstKey = fields.length > 0 ? getFieldKey(fields[0]) : '';
                                    for (var i = 0; i < rows.length; i++) {
                                        var tr = rows[i];
                                        if (tr.classList.contains('separator-row')) continue;
                                        if (tr.classList.contains('grand-total-row') || tr.getAttribute('data-row-type') === 'grand-total') continue;
                                        if (tr.classList.contains('kpi-finalize-total-row')) continue;
                                        if (String(tr.getAttribute('data-manual-total-row') || '') === '1') continue;
                                        var subId = tr.getAttribute('data-submission-id') || '';
                                        var userId = tr.getAttribute('data-user-id') || '';
                                        if ((!subId || subId === '') && (!userId || userId === '')) {
                                            var prev = tr.previousElementSibling;
                                            while (prev) {
                                                var pSub = prev.getAttribute ? prev.getAttribute('data-submission-id') : null;
                                                var pUser = prev.getAttribute ? prev.getAttribute('data-user-id') : null;
                                                if ((pSub && pSub !== '') || (pUser && pUser !== '')) {
                                                    subId = pSub || '';
                                                    userId = pUser || '';
                                                    break;
                                                }
                                                prev = prev.previousElementSibling;
                                            }
                                        }
                                        var key = (subId && subId !== '') ? subId : (userId && userId !== '' ? 'new_' + userId : null);
                                        if (!key) continue;
                                        if (!bySub[key]) bySub[key] = { submission_id: (subId && subId !== '') ? parseInt(subId, 10) : null, user_id: (userId && userId !== '') ? parseInt(userId, 10) : null, table_data: [] };
                                        if (!bySub[key].table_data) bySub[key].table_data = [];
                                        var cells = window.getRowTdCells(tr);
                                        var row = {};
                                        var isSummary = tr.getAttribute('data-row-type') === 'summary' || tr.classList.contains('bg-blue-100');
                                        if (isSummary) {
                                            row._meta = { row_type: 'summary' };
                                            var manualOverrideFields = [];
                                            var summaryCellMappings = {};
                                            for (var c = 0; c < fields.length; c++) {
                                                var field = fields[c];
                                                var keyF = getFieldKey(field);
                                                var cell = cells[c];
                                                var val = '';
                                                if (cell) {
                                                    var input = cell.querySelector('input, select, textarea');
                                                    var span = cell.querySelector('span');
                                                    if (input) {
                                                        val = (input.value || '').toString().trim();
                                                        if (input.tagName === 'SELECT') {
                                                            var opt = input.options[input.selectedIndex];
                                                            val = opt ? (opt.value || '') : '';
                                                        }
                                                    } else if (span) {
                                                        val = (span.textContent || '').trim();
                                                    } else {
                                                        val = (cell.textContent || '').trim().replace(/\s+/g, ' ').trim();
                                                    }
                                                    if (cell.classList.contains('manual-override') || cell.getAttribute('data-manual-override') === '1') {
                                                        manualOverrideFields.push(keyF);
                                                    }
                                                    var sourceColumnsRaw = cell.getAttribute('data-formula-source-columns');
                                                    var rowUidsRaw = cell.getAttribute('data-formula-row-uids');
                                                    var rowIndicesRaw = cell.getAttribute('data-formula-row-indices');
                                                    var sourceAAttr = cell.getAttribute('data-formula-source-a');
                                                    var sourceBAttr = cell.getAttribute('data-formula-source-b');
                                                    var sourceKeysAttr = cell.getAttribute('data-formula-source-keys');
                                                    var sectionRefAttr = cell.getAttribute('data-formula-section-ref');
                                                    var uiCalcTypeAttr = cell.getAttribute('data-formula-ui-calc-type');
                                                    var uiFormulaOperationAttr = cell.getAttribute('data-formula-ui-formula-operation');
                                                    var countAdjustSaveAttr = cell.getAttribute('data-formula-count-adjust');
                                                    if (sourceColumnsRaw || sourceAAttr || sourceBAttr || sourceKeysAttr || rowUidsRaw || rowIndicesRaw || sectionRefAttr || uiCalcTypeAttr || uiFormulaOperationAttr || countAdjustSaveAttr != null) {
                                                        var cellMeta = {};
                                                        try { cellMeta.source_columns = sourceColumnsRaw ? JSON.parse(sourceColumnsRaw) : []; } catch (err) { cellMeta.source_columns = []; }
                                                        try { cellMeta.row_uids = rowUidsRaw ? JSON.parse(rowUidsRaw) : []; } catch (err2) { cellMeta.row_uids = []; }
                                                        try { cellMeta.row_indices = rowIndicesRaw ? JSON.parse(rowIndicesRaw) : []; } catch (err3) { cellMeta.row_indices = []; }
                                                        try { cellMeta.source_keys = sourceKeysAttr ? JSON.parse(sourceKeysAttr) : []; } catch (err4) { cellMeta.source_keys = []; }
                                                        if (sourceAAttr) cellMeta.sourceA = sourceAAttr;
                                                        if (sourceBAttr) cellMeta.sourceB = sourceBAttr;
                                                        if (sectionRefAttr) cellMeta.section_ref = sectionRefAttr;
                                                        if (uiCalcTypeAttr) cellMeta.ui_calc_type = uiCalcTypeAttr;
                                                        if (uiFormulaOperationAttr) cellMeta.ui_formula_operation = uiFormulaOperationAttr;
                                                        if (countAdjustSaveAttr !== null && countAdjustSaveAttr !== '') {
                                                            var cAdj = parseInt(String(countAdjustSaveAttr), 10);
                                                            cellMeta.count_adjust = isNaN(cAdj) ? 0 : cAdj;
                                                        }
                                                        summaryCellMappings[keyF] = cellMeta;
                                                    }
                                                }
                                                row[keyF] = (val !== '' ? val : '');
                                            }
                                            for (var fc = 0; fc < fields.length; fc++) {
                                                var fk = getFieldKey(fields[fc]);
                                                if (row[fk] === undefined) row[fk] = '';
                                            }
                                            if (manualOverrideFields.length > 0) {
                                                row._meta.manual_override_fields = manualOverrideFields;
                                            }
                                            if (Object.keys(summaryCellMappings).length > 0) {
                                                row._meta.summary_cell_mappings = summaryCellMappings;
                                            }
                                            var finalizedQ1 = tr.getAttribute('data-finalized-accomp-q1');
                                            var finalizedQ2 = tr.getAttribute('data-finalized-accomp-q2');
                                            var finalizedQ3 = tr.getAttribute('data-finalized-accomp-q3');
                                            var finalizedQ4 = tr.getAttribute('data-finalized-accomp-q4');
                                            if (finalizedQ1 !== null || finalizedQ2 !== null || finalizedQ3 !== null || finalizedQ4 !== null) {
                                                row._meta.finalized_accomp = {
                                                    q1: finalizedQ1 !== null ? finalizedQ1 : '0',
                                                    q2: finalizedQ2 !== null ? finalizedQ2 : '0',
                                                    q3: finalizedQ3 !== null ? finalizedQ3 : '0',
                                                    q4: finalizedQ4 !== null ? finalizedQ4 : '0'
                                                };
                                            }
                                            if (row[firstKey] === undefined) row[firstKey] = '';
                                            bySub[key].table_data.push(row);
                                            continue;
                                        } else {
                                            for (var c = 0; c < fields.length; c++) {
                                                var field = fields[c];
                                                var keyF = getFieldKey(field);
                                                var cell = cells[c];
                                                if (!cell) {
                                                    row[keyF] = '';
                                                    continue;
                                                }
                                                var input = cell.querySelector('input, select, textarea');
                                                if (input) {
                                                    if (input.tagName === 'SELECT') {
                                                        var opt = input.options[input.selectedIndex];
                                                        row[keyF] = opt ? (opt.value || '') : '';
                                                    } else {
                                                        row[keyF] = (input.value || '').toString().trim();
                                                    }
                                                } else {
                                                    var span = cell.querySelector('span');
                                                    row[keyF] = (span ? (span.textContent || '').trim() : (cell.textContent || '').trim().replace(/\s+/g, ' ').trim()) || '';
                                                }
                                                if (row[keyF] === undefined) row[keyF] = '';
                                            }
                                            if (tr.previousElementSibling && tr.previousElementSibling.classList.contains('separator-row')) {
                                                row._after_separator = true;
                                            }
                                            var rowUid = ensureDataRowUid(tr);
                                            row._meta = {
                                                row_type: 'data',
                                                row_uid: rowUid
                                            };
                                            // Include every data row (including placeholders) so each campus block is sent and summary formulas run for all sections
                                        }
                                        bySub[key].table_data.push(row);
                                    }
                                    return Object.keys(bySub).map(function(k) {
                                        var o = bySub[k];
                                        return { submission_id: o.submission_id, user_id: o.user_id, table_data: o.table_data };
                                    }).filter(function(o) {
                                        return o.table_data && o.table_data.length > 0;
                                    });
                                }
                                function collectGrandTotals() {
                                    var rows = [];
                                    var gtRows = tableBody ? tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)') : [];
                                    for (var i = 0; i < gtRows.length; i++) {
                                        var tr = gtRows[i];
                                        var cells = window.getRowTdCells(tr);
                                        var row = {};
                                        var cellMappings = {};
                                        for (var c = 0; c < fields.length && c < cells.length; c++) {
                                            var field = fields[c];
                                            var keyF = getFieldKey(field);
                                            var cell = cells[c];
                                            if (!cell) continue;
                                            var input = cell.querySelector('input, select, textarea');
                                            var span = cell.querySelector('span');
                                            var val = '';
                                            if (input) {
                                                val = (input.value || '').toString().trim();
                                                if (input.tagName === 'SELECT') {
                                                    var opt = input.options[input.selectedIndex];
                                                    val = opt ? (opt.value || '') : '';
                                                }
                                            } else if (span) {
                                                val = (span.textContent || '').trim();
                                            }
                                            row[keyF] = val !== '' ? val : '';
                                            var sourceA = cell.getAttribute('data-formula-source-a');
                                            var sourceB = cell.getAttribute('data-formula-source-b');
                                            var uiCalcType = cell.getAttribute('data-formula-ui-calc-type');
                                            var uiFormulaOp = cell.getAttribute('data-formula-ui-formula-operation');
                                            var sourceQuarter = cell.getAttribute('data-formula-source-quarter');
                                            var grandTotalCtcAggregate = cell.getAttribute('data-formula-grand-total-ctc-aggregate');
                                            var gtCountAdjust = cell.getAttribute('data-formula-count-adjust');
                                            var gtRowUidsRaw = cell.getAttribute('data-formula-row-uids');
                                            var gtRowIndicesRaw = cell.getAttribute('data-formula-row-indices');
                                            if (sourceA || sourceB || uiCalcType || uiFormulaOp || sourceQuarter || grandTotalCtcAggregate || gtCountAdjust != null || gtRowUidsRaw || gtRowIndicesRaw) {
                                                cellMappings[keyF] = { sourceA: sourceA || '', sourceB: sourceB || '', ui_calc_type: uiCalcType || '', ui_formula_operation: uiFormulaOp || '' };
                                                if (sourceQuarter) cellMappings[keyF].source_quarter = sourceQuarter;
                                                if (String(grandTotalCtcAggregate || '') === '1') cellMappings[keyF].grand_total_ctc_aggregate = '1';
                                                if (gtCountAdjust !== null && gtCountAdjust !== '') {
                                                    var gca = parseInt(String(gtCountAdjust), 10);
                                                    cellMappings[keyF].count_adjust = isNaN(gca) ? 0 : gca;
                                                }
                                                if (gtRowUidsRaw) {
                                                    try { cellMappings[keyF].row_uids = JSON.parse(gtRowUidsRaw); } catch (gtUidErr) { cellMappings[keyF].row_uids = []; }
                                                }
                                                if (gtRowIndicesRaw) {
                                                    try { cellMappings[keyF].row_indices = JSON.parse(gtRowIndicesRaw); } catch (gtIdxErr) { cellMappings[keyF].row_indices = []; }
                                                }
                                            }
                                        }
                                        var schemeIdx = i % 6;
                                        var schemeNames = ['amber', 'emerald', 'teal', 'sky', 'violet', 'rose'];
                                        rows.push({ row: row, cell_mappings: cellMappings, color_scheme: schemeNames[schemeIdx], label: row[getFieldKey(fields[0])] || 'Grand total' });
                                    }
                                    return rows;
                                }
                                function collectManualTotalRow() {
                                    if (!tableBody) return null;
                                    var tr = tableBody.querySelector('tr[data-manual-total-row="1"]:not(#manual-total-empty-row-template)');
                                    if (!tr) return null;
                                    var cells = window.getRowTdCells(tr);
                                    var row = {};
                                    var cellMappings = {};
                                    for (var c = 0; c < fields.length && c < cells.length; c++) {
                                        var field = fields[c];
                                        var keyF = getFieldKey(field);
                                        var cell = cells[c];
                                        if (!cell) continue;
                                        var input = cell.querySelector('input, select, textarea');
                                        var span = cell.querySelector('span');
                                        var val = '';
                                        if (input) {
                                            val = (input.value || '').toString().trim();
                                            if (input.tagName === 'SELECT') {
                                                var opt = input.options[input.selectedIndex];
                                                val = opt ? (opt.value || '') : '';
                                            }
                                        } else if (span) {
                                            val = (span.textContent || '').trim();
                                        }
                                        row[keyF] = val !== '' ? val : '';
                                        var mapping = getCellFormulaMapping(cell);
                                        if (mapping) {
                                            cellMappings[keyF] = Object.assign({}, mapping);
                                        }
                                    }
                                    return { row: row, cell_mappings: cellMappings };
                                }
                                function collectKpiFinalizeTotalRow() {
                                    if (!tableBody) return null;
                                    var tr = tableBody.querySelector('tr.kpi-finalize-total-row:not(#kpi-finalize-total-row-template)');
                                    if (!tr) return null;
                                    var cells = window.getRowTdCells(tr);
                                    var row = {};
                                    for (var c = 0; c < fields.length && c < cells.length; c++) {
                                        var field = fields[c];
                                        var keyF = getFieldKey(field);
                                        var cell = cells[c];
                                        if (!cell) continue;
                                        var input = cell.querySelector('input, select, textarea');
                                        var span = cell.querySelector('span');
                                        var val = '';
                                        if (input) {
                                            val = (input.value || '').toString().trim();
                                            if (input.tagName === 'SELECT') {
                                                var opt = input.options[input.selectedIndex];
                                                val = opt ? (opt.value || '') : '';
                                            }
                                        } else if (span) {
                                            val = (span.textContent || '').trim();
                                        }
                                        row[keyF] = val !== '' ? val : '';
                                    }
                                    var labelSpan = tr.querySelector('td:first-child span');
                                    var om = tr.getAttribute('data-finalize-overall-mode');
                                    return {
                                        row: row,
                                        label: (labelSpan && labelSpan.textContent) ? labelSpan.textContent.trim() : 'Overall total',
                                        overall_mode: om === 'avg' ? 'avg' : 'sum'
                                    };
                                }
                                /** Q1aQ4 from Finalize; persisted in template fields_json for Form VPASS when submission merge skips _meta. */
                                function collectFinalizedAccompForTemplateSave() {
                                    if (!tableBody) return null;
                                    var tr = tableBody.querySelector('tr.kpi-finalize-total-row:not(#kpi-finalize-total-row-template)');
                                    if (!tr) return null;
                                    var q1 = tr.getAttribute('data-finalized-accomp-q1');
                                    var q2 = tr.getAttribute('data-finalized-accomp-q2');
                                    var q3 = tr.getAttribute('data-finalized-accomp-q3');
                                    var q4 = tr.getAttribute('data-finalized-accomp-q4');
                                    if (q1 === null && q2 === null && q3 === null && q4 === null) return null;
                                    return {
                                        q1: q1 !== null ? String(q1) : '0',
                                        q2: q2 !== null ? String(q2) : '0',
                                        q3: q3 !== null ? String(q3) : '0',
                                        q4: q4 !== null ? String(q4) : '0'
                                    };
                                }
                                if (tableBody) {
                                    tableBody.addEventListener('input', function(e) {
                                        if (e.target.matches('input, select, textarea')) {
                                            // Autosave is reserved for calculations/actions; typing should not autosave.
                                            var td = e.target.closest('td');
                                            var tr = td ? td.closest('tr.data-row') : null;
                                            if (td && tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1')) {
                                                // Keep saved source mapping metadata so clicking this blue cell can still
                                                // re-highlight the exact manual source scope (row_uids/row_indices).
                                                td.classList.add('manual-override');
                                                td.setAttribute('data-manual-override', '1');
                                                return;
                                            }
                                            if (td && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(td);
                                        }
                                    });
                                    tableBody.addEventListener('change', function(e) {
                                        if (e.target.matches('input, select, textarea')) {
                                            // Autosave is reserved for calculations/actions; typing should not autosave.
                                            var td = e.target.closest('td');
                                            var tr = td ? td.closest('tr.data-row') : null;
                                            if (td && tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1')) {
                                                // Preserve saved mapping metadata while user manually edits value.
                                                td.classList.add('manual-override');
                                                td.setAttribute('data-manual-override', '1');
                                                return;
                                            }
                                            if (td && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(td);
                                        }
                                    });
                                    tableBody.addEventListener('blur', function(e) {
                                        if (e.target.matches('input, select, textarea')) {
                                            var td = e.target.closest('td');
                                            var tr = td ? td.closest('tr.data-row') : null;
                                            if (td && tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1')) {
                                                return;
                                            }
                                            if (td && typeof scheduleRecomputeFormulas === 'function') scheduleRecomputeFormulas(td);
                                        }
                                    }, true);
                                }
                                var autoSaveTimeout = null;
                                var autosaveStatusTimeout = null;
                                function setAutosaveStatus(state) {
                                    var statusEl = document.getElementById('autosave-status');
                                    if (!statusEl) return;
                                    if (autosaveStatusTimeout) {
                                        clearTimeout(autosaveStatusTimeout);
                                        autosaveStatusTimeout = null;
                                    }
                                    statusEl.classList.remove('hidden', 'text-gray-400', 'text-gray-500', 'text-green-700', 'text-red-600');
                                    if (state === 'saving') {
                                        statusEl.classList.add('text-gray-500');
                                        statusEl.textContent = 'Saving draft...';
                                        return;
                                    }
                                    if (state === 'saved') {
                                        statusEl.classList.add('text-green-700');
                                        statusEl.textContent = 'Draft saved';
                                        autosaveStatusTimeout = setTimeout(function() {
                                            autosaveStatusTimeout = null;
                                            if (!window.tableDataDirty) {
                                                statusEl.classList.remove('text-green-700');
                                                statusEl.classList.add('text-gray-400');
                                                statusEl.textContent = 'Draft autosave on';
                                            }
                                        }, 5000);
                                        return;
                                    }
                                    if (state === 'error') {
                                        statusEl.classList.add('text-red-600');
                                        statusEl.textContent = 'Draft save failed';
                                        autosaveStatusTimeout = setTimeout(function() {
                                            statusEl.classList.remove('text-red-600');
                                            statusEl.classList.add('text-gray-400');
                                            statusEl.textContent = 'Draft autosave on';
                                        }, 2500);
                                        return;
                                    }
                                    statusEl.classList.add('text-gray-400');
                                    statusEl.textContent = 'Draft autosave on';
                                }
                                function scheduleAutoSave() {
                                    window.tableDataDirty = true;
                                    if (autoSaveTimeout) clearTimeout(autoSaveTimeout);
                                    autoSaveTimeout = setTimeout(function() {
                                        autoSaveTimeout = null;
                                        if (!window.tableDataDirty) return;
                                        if (typeof window.performSaveTableData !== 'function') return;
                                        if (window.__templateAutosave && window.__templateAutosave.inFlight) {
                                            window.__templateAutosave.queued = true;
                                            return;
                                        }
                                        setAutosaveStatus('saving');
                                        window.performSaveTableData({
                                            onSuccess: function() {
                                                setAutosaveStatus('saved');
                                            }
                                        });
                                    }, 1500);
                                }
                                function saveOnUnload(e) {
                                    if (!window.tableDataDirty) return;
                                    if (typeof normalizeAllSectionsBlueRows === 'function') normalizeAllSectionsBlueRows();
                                    var bySub = collectBySubmission();
                                    if (bySub.length === 0 || bySub.every(function(s) { return !s.table_data || s.table_data.length === 0; })) return;
                                    var token = document.querySelector('meta[name="csrf-token"]');
                                    token = token ? token.getAttribute('content') : '';
                                    var bySubPayload = bySub.map(function(o) {
                                        return { submission_id: o.submission_id != null ? parseInt(o.submission_id, 10) : null, user_id: o.user_id != null ? parseInt(o.user_id, 10) : null, table_data: o.table_data || [] };
                                    });
                                    var grandTotalsPayload = typeof collectGrandTotals === 'function' ? collectGrandTotals() : [];
                                    var kpiFinPayload = typeof collectKpiFinalizeTotalRow === 'function' ? collectKpiFinalizeTotalRow() : null;
                                    var manualTotalPayload = typeof collectManualTotalRow === 'function' ? collectManualTotalRow() : null;
                                    var finalizedAccompPayload = typeof collectFinalizedAccompForTemplateSave === 'function' ? collectFinalizedAccompForTemplateSave() : null;
                                    var payload = { by_submission: bySubPayload, grand_totals: grandTotalsPayload, kpi_finalize_total_row: kpiFinPayload, manual_total_row: manualTotalPayload, finalized_accomp: finalizedAccompPayload };
                                    fetch(saveUrl, {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                        body: JSON.stringify(payload),
                                        keepalive: true
                                    });
                                    window.tableDataDirty = false;
                                    if (e && e.type === 'beforeunload') {
                                        e.preventDefault();
                                        e.returnValue = '';
                                    }
                                }
                                window.addEventListener('beforeunload', saveOnUnload);
                                window.addEventListener('pagehide', function(e) { saveOnUnload(e); });
                                function setCellSelected(tr, td, selected) {
                                    if (!tr || !td) return;
                                    if (selected) {
                                        td.classList.add('cell-selected');
                                    } else {
                                        td.classList.remove('ring-2', 'ring-indigo-500', 'cell-selected', 'bg-indigo-50');
                                    }
                                    // Track rows that have at least one selected cell
                                    var hasSelectedInRow = tr.querySelector('td.cell-selected') !== null;
                                    var idx = selectedRowsMulti.indexOf(tr);
                                    if (hasSelectedInRow && idx === -1) {
                                        selectedRowsMulti.push(tr);
                                    } else if (!hasSelectedInRow && idx !== -1) {
                                        selectedRowsMulti.splice(idx, 1);
                                    }
                                    updateFormulaButtonState();
                                }

                                function clearSelectionMulti(opts) {
                                    opts = opts || {};
                                    // Remove highlight from any previously selected cells
                                    var highlighted = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    highlighted.forEach(function(td) {
                                        td.classList.remove('ring-2', 'ring-indigo-500', 'cell-selected', 'bg-indigo-50');
                                    });
                                    var sourceForBlue = tableBody ? tableBody.querySelectorAll('td.cell-source-for-blue') : [];
                                    sourceForBlue.forEach(function(td) {
                                        td.classList.remove('cell-source-for-blue');
                                    });
                                    selectedRowsMulti.forEach(function(tr) {
                                        tr.classList.remove('row-selected');
                                    });
                                    selectedRowsMulti = [];
                                    lastClickedRowMulti = null;
                                    lastClickedCellMulti = null;
                                    setSelectionModeState('');
                                    if (!opts.skipPopoverShell) {
                                        if (selectionPopover) selectionPopover.classList.add('hidden');
                                        var gtCascadeWizClear = document.getElementById('grand-total-cascade-wizard');
                                        if (gtCascadeWizClear) gtCascadeWizClear.classList.add('hidden');
                                    }
                                    hideCampusTargetComparePanel();
                                    hideRowActionsDots();
                                    if (!opts.skipPopoverShell) {
                                        updateSelectionLiveHints();
                                    }
                                }

                                function getPrimarySelectedRowMulti() {
                                    if (selectedRowsMulti.length === 0) return null;
                                    return selectedRowsMulti[selectedRowsMulti.length - 1];
                                }

                                function updateFormulaButtonState() {
                                    var selected = tableBody ? tableBody.querySelectorAll('td.cell-selected, td.cell-source-for-blue') : [];
                                    var count = selected.length;
                                    if (!tableContainerMulti) return;
                                    if (count === 0) {
                                        if (selectionPopover) selectionPopover.classList.add('hidden');
                                        hideRowActionsPopover();
                                        hideRowActionsDots();
                                        hideCampusTargetComparePanel();
                                        updateSelectionLiveHints();
                                        return;
                                    }
                                    var singleCellSelection = (count === 1);
                                    var hasBlueCellSelected = false;
                                    for (var i = 0; i < selected.length; i++) {
                                        var trSel = selected[i].closest('tr.data-row');
                                        if (trSel && (trSel.classList.contains('bg-blue-100') || trSel.classList.contains('grand-total-row') || String(trSel.getAttribute('data-manual-total-row') || '') === '1')) {
                                            hasBlueCellSelected = true;
                                            break;
                                        }
                                    }
                                    if (!hasBlueCellSelected) {
                                        if (selectionPopover) selectionPopover.classList.add('hidden');
                                        var lastCell = selected[selected.length - 1];
                                        var tr = lastCell ? lastCell.closest('tr.data-row') : null;
                                        if (singleCellSelection && tr && !tr.classList.contains('bg-blue-100') && !tr.classList.contains('grand-total-row') && !tr.classList.contains('kpi-finalize-total-row') && String(tr.getAttribute('data-manual-total-row') || '') !== '1' && rowActionsPopover) {
                                            showRowActionsDots(tr, false, lastCell);
                                        } else {
                                            hideRowActionsPopover();
                                            hideRowActionsDots();
                                        }
                                        updateSelectionLiveHints();
                                        return;
                                    }
                                    var lastCell = selected[selected.length - 1];
                                    var tr = lastCell ? lastCell.closest('tr.data-row') : null;
                                    // For blue-row workflows, never auto-open the calc popover; require explicit "..." -> Calculation.
                                    // Grand total cascade wizard (Quarter / Average steps) must stay usable: autoSelectSourcesForBlueCell
                                    // clears/rebuilds selection without forceOpen - keep the popover while that wizard is visible.
                                    var grandTotalCascadeWizardEl = document.getElementById('grand-total-cascade-wizard');
                                    // Wizard lives inside the popover; its .hidden state can be stale after the shell was
                                    // closed - require the popover to actually be visible so a yellow-cell click does not
                                    // immediately reopen "Choose calculation" until a a Calculation (forceOpen) again.
                                    var selectionPopoverAlreadyVisible = !!(selectionPopover && !selectionPopover.classList.contains('hidden'));
                                    var grandTotalWizardPanelOpen = !!(grandTotalCascadeWizardEl && !grandTotalCascadeWizardEl.classList.contains('hidden') && selectionPopoverAlreadyVisible);
                                    var shouldShowSelectionPopover = hasBlueCellSelected
                                        ? (!!forceOpenSelectionPopoverOnce || grandTotalWizardPanelOpen)
                                        : true;
                                    if (selectionPopover) {
                                        if (shouldShowSelectionPopover) selectionPopover.classList.remove('hidden');
                                        else selectionPopover.classList.add('hidden');
                                    }
                                    forceOpenSelectionPopoverOnce = false;
                                    updateSelectionLiveHints();
                                    if (hasBlueCellSelected && rowActionsPopoverBlue) {
                                        var blueAnchorCell = null;
                                        for (var sb = selected.length - 1; sb >= 0; sb--) {
                                            var trb = selected[sb].closest('tr.data-row');
                                            // Grand total rows use the same "blue-row" action popover/dots workflow.
                                            if (trb && (trb.classList.contains('bg-blue-100') || trb.classList.contains('grand-total-row') || String(trb.getAttribute('data-manual-total-row') || '') === '1')) {
                                                blueAnchorCell = selected[sb];
                                                break;
                                            }
        @include('super-admin.templates.partials.show-heavy-script-mid')
                                            var addSourceBtn = document.getElementById('formula-add-source-btn');
                                            var formulaCustomWrap = document.getElementById('formula-custom-wrap');
                                            var formulaOperationWrap = document.getElementById('formula-operation-wrap');
                                            var formulaCustomExpr = document.getElementById('formula-custom-expr');
                                            if (formulaCustomMode) {
                                                if (formulaCustomWrap) formulaCustomWrap.classList.remove('hidden');
                                                if (formulaOperationWrap) formulaOperationWrap.classList.add('hidden');
                                                if (extraContainer) { extraContainer.classList.add('hidden'); extraContainer.innerHTML = ''; }
                                                if (addSourceBtn) addSourceBtn.classList.add('hidden');
                                                if (formulaCustomExpr) {
                                                    formulaCustomExpr.value = (savedMapping && String(savedMapping.ui_calc_type || '').trim() === 'blue-row-formula-custom' && savedMapping.custom_expr) ? String(savedMapping.custom_expr) : '';
                                                }
                                                if (savedMapping && String(savedMapping.ui_calc_type || '').trim() === 'blue-row-formula-custom') {
                                                    if (savedMapping.sourceA && formulaSourceASelect) formulaSourceASelect.value = savedMapping.sourceA;
                                                    if (savedMapping.sourceB && formulaSourceBSelect) formulaSourceBSelect.value = savedMapping.sourceB || '';
                                                }
                                            } else {
                                                if (formulaCustomWrap) formulaCustomWrap.classList.add('hidden');
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                            }
                                            function addFormulaSourceRow(preselectedKey) {
                                                if (!extraContainer || !blueRow) return;
                                                var letters = 'CDEFGHIJ';
                                                var idx = extraContainer.children.length;
                                                var letter = letters[idx] || String.fromCharCode(67 + idx);
                                                var wrap = document.createElement('div');
                                                wrap.className = 'flex items-center gap-2';
                                                var label = document.createElement('label');
                                                label.className = 'text-xs font-medium text-gray-600 w-8 shrink-0';
                                                label.textContent = letter + '.';
                                                var sel = document.createElement('select');
                                                sel.className = 'formula-source-select flex-1 border border-gray-300 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-indigo-500 bg-white';
                                                sel.setAttribute('data-source-letter', letter);
                                                populateSourceWithBlueRowValues(sel);
                                                if (preselectedKey) sel.value = preselectedKey;
                                                var rm = document.createElement('button');
                                                rm.type = 'button';
                                                rm.className = 'shrink-0 p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors';
                                                rm.title = 'Remove source';
                                                rm.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M18 18L6 6"></path></svg>';
                                                rm.addEventListener('click', function() {
                                                    var removedLetter = sel.getAttribute('data-source-letter');
                                                    wrap.remove();
                                                    var extraOpBtns = document.getElementById('formula-op-btns-extra');
                                                    if (extraOpBtns && removedLetter) {
                                                        var toRemove = extraOpBtns.querySelector('.formula-op-btn-extra[data-char="' + removedLetter + '"]');
                                                        if (toRemove) toRemove.remove();
                                                    }
                                                    updateFormulaPreview();
                                                });
                                                wrap.appendChild(label);
                                                wrap.appendChild(sel);
                                                wrap.appendChild(rm);
                                                extraContainer.appendChild(wrap);
                                                sel.addEventListener('change', updateFormulaPreview);
                                                if (!preselectedKey && formulaMultiSourceMode) {
                                                    var op = formulaOperationSelect ? formulaOperationSelect.value : '';
                                                    var isCustomOp = op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0);
                                                    if (isCustomOp) {
                                                        var inputEl = document.getElementById('formula-custom-expr');
                                                        if (inputEl) {
                                                            var cur = String(inputEl.value || '').trim();
                                                            var toInsert = cur.length === 0 ? letter : ' + ' + letter;
                                                            var pos = inputEl.selectionStart != null ? inputEl.selectionStart : cur.length;
                                                            var before = cur.substring(0, pos);
                                                            var after = cur.substring(pos);
                                                            inputEl.value = before + toInsert + after;
                                                            inputEl.selectionStart = inputEl.selectionEnd = pos + toInsert.length;
                                                            inputEl.focus();
                                                        }
                                                    }
                                                }
                                                var extraBtns = document.getElementById('formula-op-btns-extra');
                                                if (extraBtns && formulaMultiSourceMode) {
                                                    var existingLetters = [];
                                                    extraBtns.querySelectorAll('.formula-op-btn-extra').forEach(function(b) { existingLetters.push(b.getAttribute('data-char')); });
                                                    if (existingLetters.indexOf(letter) === -1) {
                                                        var btn = document.createElement('button');
                                                        btn.type = 'button';
                                                        btn.className = 'formula-op-btn formula-op-btn-extra px-2.5 py-1.5 text-sm font-mono bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-700 border border-indigo-200 rounded-md transition-colors';
                                                        btn.setAttribute('data-char', letter);
                                                        btn.textContent = letter;
                                                        btn.addEventListener('click', function() {
                                                            var input = document.getElementById('formula-custom-expr');
                                                            if (!input) return;
                                                            var ch = this.getAttribute('data-char') || '';
                                                            var start = input.selectionStart || 0, end = input.selectionEnd || start, text = input.value || '';
                                                            var newText = text.substring(0, start) + ch + text.substring(end);
                                                            input.value = newText;
                                                            input.selectionStart = input.selectionEnd = start + ch.length;
                                                            input.focus();
                                                            if (typeof updateFormulaPreview === 'function') updateFormulaPreview();
                                                        });
                                                        extraBtns.appendChild(btn);
                                                    }
                                                }
                                                updateFormulaPreview();
                                            }
                                            if (formulaCustomMode) {
                                                // Custom mode UI already set above; sources A/B populated
                                                updateFormulaPreview();
                                            } else if (formulaMultiSourceMode) {
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                                if (extraContainer) { extraContainer.classList.remove('hidden'); extraContainer.innerHTML = ''; }
                                                var extraOpBtns = document.getElementById('formula-op-btns-extra');
                                                if (extraOpBtns) extraOpBtns.innerHTML = '';
                                                if (addSourceBtn) addSourceBtn.classList.remove('hidden');
                                                if (formulaOperationSelect) {
                                                    formulaOperationSelect.innerHTML = '';
                                                    var multiOps = [
                                                        { v: 'sum', t: 'Sum (A+B+C...)' },
                                                        { v: 'avg', t: 'Average (A+B+C...)' },
                                                        { v: 'subtract', t: 'A - B (Difference)' },
                                                        { v: 'multiply', t: 'A A B (Product)' },
                                                        { v: 'divide', t: 'A A- B (Quotient)' },
                                                        { v: 'percent_of', t: 'A A- B A 100 (Percent of)' },
                                                        { v: 'sum_over_b_percent', t: '(A + B) A- B A 100' },
                                                        { v: 'diff_over_b_percent', t: '(A - B) A- B A 100' }
                                                    ];
                                                    multiOps.forEach(function(o) {
                                                        var opt = document.createElement('option');
                                                        opt.value = o.v;
                                                        opt.textContent = o.t;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    var savedCustom = getSavedCustomFormulas();
                                                    if (savedMapping && savedMapping.custom_expr) {
                                                        var ce = normalizeCustomExpr(savedMapping.custom_expr);
                                                        if (ce) {
                                                            var normed = savedCustom.map(normalizeCustomExpr);
                                                            if (normed.indexOf(ce) === -1) savedCustom.unshift(ce);
                                                        }
                                                    }
                                                    var seenNorm = {};
                                                    var predefNorm = ['a + b', 'a - b', 'a \u00D7 b', 'a \u00F7 b', 'a \u00F7 b \u00D7 100', '(a + b) \u00F7 b \u00D7 100', '(a - b) \u00F7 b \u00D7 100'];
                                                    var deduped = [];
                                                    savedCustom.forEach(function(expr) {
                                                        var displayExpr = normalizeCustomExpr(expr) || expr;
                                                        var n = displayExpr.toLowerCase();
                                                        if (seenNorm[n] || predefNorm.indexOf(n) !== -1) return;
                                                        seenNorm[n] = true;
                                                        deduped.push(displayExpr);
                                                        var opt = document.createElement('option');
                                                        opt.value = 'custom:' + displayExpr;
                                                        opt.textContent = displayExpr;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    if (deduped.length !== savedCustom.length) {
                                                        try { localStorage.setItem(customFormulasStorageKey, JSON.stringify(deduped)); } catch (e) {}
                                                    }
                                                    var optCustom = document.createElement('option');
                                                    optCustom.value = 'custom';
                                                    optCustom.textContent = 'Custom (enter your own expression)';
                                                    formulaOperationSelect.appendChild(optCustom);
                                                }
                                                var savedSources = savedMapping && Array.isArray(savedMapping.source_keys) ? savedMapping.source_keys.slice() : [];
                                                if (savedMapping && savedMapping.sourceA && savedSources.indexOf(savedMapping.sourceA) === -1) savedSources.unshift(savedMapping.sourceA);
                                                if (savedMapping && savedMapping.sourceB && savedSources.indexOf(savedMapping.sourceB) === -1) savedSources.push(savedMapping.sourceB);
                                                for (var si = 2; si < savedSources.length; si++) addFormulaSourceRow(savedSources[si]);
                                                if (savedMapping && String(savedMapping.ui_calc_type || '').trim() === 'blue-row-formula-multi') {
                                                    if (savedMapping.sourceA && formulaSourceASelect) formulaSourceASelect.value = savedMapping.sourceA;
                                                    if (savedMapping.sourceB && formulaSourceBSelect) formulaSourceBSelect.value = savedMapping.sourceB;
                                                    var opMulti = String(savedMapping.ui_formula_operation || savedMapping.operation || 'sum').trim();
                                                    var customExprVal = savedMapping.custom_expr ? String(savedMapping.custom_expr).trim() : '';
                                                    if (formulaOperationSelect && opMulti) {
                                                        var opToSelect = opMulti;
                                                        if (opMulti === 'custom' && customExprVal) {
                                                            var normVal = normalizeCustomExpr(customExprVal);
                                                            var foundOpt = Array.prototype.slice.call(formulaOperationSelect.options || []).find(function(o) {
                                                                var v = String(o.value);
                                                                return v.indexOf('custom:') === 0 && normalizeCustomExpr(v.substring(7)) === normVal;
                                                            });
                                                            opToSelect = foundOpt ? foundOpt.value : ('custom:' + normVal);
                                                        }
                                                        var hasOp = Array.prototype.slice.call(formulaOperationSelect.options || []).some(function(o) { return String(o.value) === opToSelect; });
                                                        if (hasOp) formulaOperationSelect.value = opToSelect;
                                                        else if (opMulti === 'custom' && formulaOperationSelect.querySelector('option[value="custom"]')) formulaOperationSelect.value = 'custom';
                                                    }
                                                    var isCustomOp = opMulti === 'custom' || String(opMulti).indexOf('custom:') === 0;
                                                    if (isCustomOp && formulaCustomExpr) {
                                                        var valToShow = customExprVal || (opMulti.indexOf('custom:') === 0 ? opMulti.substring(7) : '');
                                                        formulaCustomExpr.value = normalizeCustomExpr(valToShow) || valToShow;
                                                        if (formulaCustomWrap) formulaCustomWrap.classList.remove('hidden');
                                                    } else if (formulaCustomWrap) {
                                                        formulaCustomWrap.classList.add('hidden');
                                                    }
                                                } else if (formulaCustomWrap) {
                                                    formulaCustomWrap.classList.add('hidden');
                                                }
                                                if (addSourceBtn) {
                                                    addSourceBtn.onclick = function() {
                                                        if (extraContainer && extraContainer.children.length < 8) addFormulaSourceRow();
                                                    };
                                                }
                                            } else {
                                                if (extraContainer) { extraContainer.classList.add('hidden'); extraContainer.innerHTML = ''; }
                                                if (addSourceBtn) addSourceBtn.classList.add('hidden');
                                                if (formulaOperationWrap) formulaOperationWrap.classList.remove('hidden');
                                                if (formulaCustomWrap) formulaCustomWrap.classList.add('hidden');
                                                if (formulaOperationSelect) {
                                                    formulaOperationSelect.innerHTML = '';
                                                    var baseOps = [
                                                        { v: 'sum', t: 'A + B (Sum)' },
                                                        { v: 'subtract', t: 'A - B (Difference)' },
                                                        { v: 'multiply', t: 'A A B (Product)' },
                                                        { v: 'divide', t: 'A A- B (Quotient)' },
                                                        { v: 'percent_of', t: 'A A- B A 100 (Percent of)' },
                                                        { v: 'sum_over_b_percent', t: '(A + B) A- B A 100' },
                                                        { v: 'diff_over_b_percent', t: '(A - B) A- B A 100' }
                                                    ];
                                                    baseOps.forEach(function(o) {
                                                        var opt = document.createElement('option');
                                                        opt.value = o.v;
                                                        opt.textContent = o.t;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    var savedCustom = getSavedCustomFormulas();
                                                    if (savedMapping && savedMapping.custom_expr) {
                                                        var ce = normalizeCustomExpr(savedMapping.custom_expr);
                                                        if (ce) {
                                                            var normed = savedCustom.map(normalizeCustomExpr);
                                                            if (normed.indexOf(ce) === -1) savedCustom.unshift(ce);
                                                        }
                                                    }
                                                    var seenNorm = {};
                                                    var predefNorm = ['a + b', 'a - b', 'a \u00D7 b', 'a \u00F7 b', 'a \u00F7 b \u00D7 100', '(a + b) \u00F7 b \u00D7 100', '(a - b) \u00F7 b \u00D7 100'];
                                                    var deduped = [];
                                                    savedCustom.forEach(function(expr) {
                                                        var displayExpr = normalizeCustomExpr(expr) || expr;
                                                        var n = displayExpr.toLowerCase();
                                                        if (seenNorm[n] || predefNorm.indexOf(n) !== -1) return;
                                                        seenNorm[n] = true;
                                                        deduped.push(displayExpr);
                                                        var opt = document.createElement('option');
                                                        opt.value = 'custom:' + displayExpr;
                                                        opt.textContent = displayExpr;
                                                        formulaOperationSelect.appendChild(opt);
                                                    });
                                                    if (deduped.length !== savedCustom.length) {
                                                        try { localStorage.setItem(customFormulasStorageKey, JSON.stringify(deduped)); } catch (e) {}
                                                    }
                                                    var optCustom = document.createElement('option');
                                                    optCustom.value = 'custom';
                                                    optCustom.textContent = 'Custom (enter your own expression)';
                                                    formulaOperationSelect.appendChild(optCustom);
                                                }
                                                if (savedMapping && String(savedMapping.ui_calc_type || '').trim() === 'blue-row-formula') {
                                                    if (savedMapping.sourceA && formulaSourceASelect) formulaSourceASelect.value = savedMapping.sourceA;
                                                    if (savedMapping.sourceB && formulaSourceBSelect) formulaSourceBSelect.value = savedMapping.sourceB;
                                                    var op = String(savedMapping.ui_formula_operation || savedMapping.operation || 'sum').trim();
                                                    var customExprVal = savedMapping.custom_expr ? String(savedMapping.custom_expr).trim() : '';
                                                    if (formulaOperationSelect && op) {
                                                        var opToSelect = op;
                                                        if (op === 'custom' && customExprVal) {
                                                            var normVal = normalizeCustomExpr(customExprVal);
                                                            var foundOpt = Array.prototype.slice.call(formulaOperationSelect.options || []).find(function(o) {
                                                                var v = String(o.value);
                                                                return v.indexOf('custom:') === 0 && normalizeCustomExpr(v.substring(7)) === normVal;
                                                            });
                                                            opToSelect = foundOpt ? foundOpt.value : ('custom:' + normVal);
                                                        }
                                                        var hasOp = Array.prototype.slice.call(formulaOperationSelect.options || []).some(function(o) { return String(o.value) === opToSelect; });
                                                        if (hasOp) formulaOperationSelect.value = opToSelect;
                                                        else if (op === 'custom' && formulaOperationSelect.querySelector('option[value="custom"]')) formulaOperationSelect.value = 'custom';
                                                    }
                                                    if ((op === 'custom' || String(op).indexOf('custom:') === 0) && formulaCustomExpr) {
                                                        var valToShow = customExprVal || (op.indexOf('custom:') === 0 ? op.substring(7) : '');
                                                        formulaCustomExpr.value = normalizeCustomExpr(valToShow) || valToShow;
                                                        formulaCustomWrap.classList.remove('hidden');
                                                    } else if (formulaCustomWrap) {
                                                        formulaCustomWrap.classList.add('hidden');
                                                    }
                                                }
                                            }
                                            updateFormulaPreview();
                                        } else {
                                            if (formulaSelectedColumnInfo) {
                                                formulaSelectedColumnInfo.classList.remove('hidden');
                                                formulaSelectedColumnInfo.textContent = 'Target: selected blue cell. A and B are from the same blue row.';
                                            }
                                            var targetSelectWrapAlt = document.getElementById('formula-target-select-wrap');
                                            if (targetSelectWrapAlt) targetSelectWrapAlt.classList.add('hidden');
                                            if (formulaSourceASelect) formulaSourceASelect.value = '';
                                            if (formulaSourceBSelect) formulaSourceBSelect.value = '';
                                        }
                                        }
                                    } else {
                                        if (formulaModalTitle) formulaModalTitle.textContent = 'Apply Formula to Selected Rows';
                                        if (formulaModalDesc) formulaModalDesc.textContent = 'Choose a target column and one or two source columns. The formula will run for each selected row only.';
                                        if (formulaTargetSelect) formulaTargetSelect.disabled = false;
                                        var targetSelectWrapRestore = document.getElementById('formula-target-select-wrap');
                                        if (targetSelectWrapRestore) targetSelectWrapRestore.classList.remove('hidden');
                                        function restoreDefaultSourceOptions() {
                                            if (formulaSourceASelect) {
                                                formulaSourceASelect.innerHTML = '<option value="">-- Select column A --</option>';
                                                fields.forEach(function(f) {
                                                    var opt = document.createElement('option');
                                                    opt.value = getFieldKey(f);
                                                    opt.textContent = (f && (f.label || getFieldKey(f))) || opt.value;
                                                    formulaSourceASelect.appendChild(opt);
                                                });
                                            }
                                            if (formulaSourceBSelect) {
                                                formulaSourceBSelect.innerHTML = '<option value="">-- Select column B --</option>';
                                                fields.forEach(function(f) {
                                                    var opt = document.createElement('option');
                                                    opt.value = getFieldKey(f);
                                                    opt.textContent = (f && (f.label || getFieldKey(f))) || opt.value;
                                                    formulaSourceBSelect.appendChild(opt);
                                                });
                                            }
                                        }
                                        restoreDefaultSourceOptions();
                                        var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                        var colIndices = [];
                                        selectedCells.forEach(function(td) {
                                            var c = getColIndex(td);
                                            if (c >= 0 && c < fields.length && colIndices.indexOf(c) === -1) colIndices.push(c);
                                        });
                                        colIndices.sort(function(a, b) { return a - b; });
                                        var labels = colIndices.map(function(i) { return (fields[i] && (fields[i].label || getFieldKey(fields[i]))) || 'Column ' + (i + 1); });
                                        var keys = colIndices.map(function(i) { return fields[i] ? getFieldKey(fields[i]) : ''; }).filter(Boolean);
                                        if (formulaSelectedColumnInfo) {
                                            var stats = getSelectedCellsStats();
                                            var statsHint = buildSelectionHintText(stats);
                                            if (labels.length === 0) {
                                                formulaSelectedColumnInfo.classList.add('hidden');
                                                formulaSelectedColumnInfo.textContent = '';
                                            } else {
                                                formulaSelectedColumnInfo.classList.remove('hidden');
                                                formulaSelectedColumnInfo.textContent = labels.length === 1
                                                    ? ('Selected column: ' + labels[0] + ' | ' + statsHint)
                                                    : ('Selected columns: ' + labels.join(', ') + ' | ' + statsHint);
                                            }
                                        }
                                        if (formulaUseBlueRow && formulaTargetSelect) {
                                            var preferredTargetKey = getPreferredAccomplishmentTargetKey();
                                            if (preferredTargetKey) formulaTargetSelect.value = preferredTargetKey;
                                        }
                                        if (keys.length > 0 && formulaSourceASelect) formulaSourceASelect.value = keys[0];
                                        if (keys.length > 1 && formulaSourceBSelect) formulaSourceBSelect.value = keys[1];
                                        else if (formulaSourceBSelect) formulaSourceBSelect.value = '';
                                    }
                                }

                                function updateFormulaPreview() {
                                    var preview = document.getElementById('formula-preview');
                                    if (!preview) return;
                                    if (!formulaBlueRowOnlyMode) {
                                        preview.classList.add('hidden');
                                        preview.textContent = '';
                                        return;
                                    }
                                    if (formulaCustomMode) {
                                        var formulaCustomExprEl = document.getElementById('formula-custom-expr');
                                        var expr = formulaCustomExprEl ? String(formulaCustomExprEl.value || '').trim() : '';
                                        var optA = formulaSourceASelect && formulaSourceASelect.options[formulaSourceASelect.selectedIndex];
                                        var optB = formulaSourceBSelect && formulaSourceBSelect.options[formulaSourceBSelect.selectedIndex];
                                        var valA = optA ? (optA.getAttribute('data-value') || (optA.textContent.match(/\(([^)]+)\)$/) && optA.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                        var valB = optB ? (optB.getAttribute('data-value') || (optB.textContent.match(/\(([^)]+)\)$/) && optB.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '0';
                                        var numA = toNumeric(valA);
                                        var numB = toNumeric(valB);
                                        if (!expr || !optA || !optA.value) {
                                            preview.classList.add('hidden');
                                            preview.textContent = '';
                                            return;
                                        }
                                        var safeEval = function(e, a, b) {
                                            var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                            if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                            try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                        };
                                        var result = safeEval(expr, numA, numB);
                                        if (isNaN(result)) {
                                            preview.textContent = expr + ' a Invalid expression';
                                            preview.classList.remove('hidden');
                                            return;
                                        }
                                        preview.textContent = (String(valA || '').trim() || '-') + ', ' + (String(valB || '').trim() || '-') + ' a ' + result.toFixed(2);
                                        preview.classList.remove('hidden');
                                        return;
                                    }
                                    if (formulaMultiSourceMode) {
                                        var op = formulaOperationSelect ? formulaOperationSelect.value : 'sum';
                                        var formulaCustomWrapMulti = document.getElementById('formula-custom-wrap');
                                        var isCustomOp = op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0);
                                        if (formulaCustomWrapMulti) {
                                            if (isCustomOp) formulaCustomWrapMulti.classList.remove('hidden');
                                            else formulaCustomWrapMulti.classList.add('hidden');
                                        }
                                        var removeOpBtnMulti = document.getElementById('formula-remove-operation-btn');
                                        if (removeOpBtnMulti) {
                                            var isRemovable = typeof op === 'string' && op.indexOf('custom:') === 0;
                                            removeOpBtnMulti.disabled = !isRemovable;
                                            removeOpBtnMulti.title = isRemovable ? 'Delete this operation from the saved list' : 'Select a saved custom expression to delete';
                                        }
                                        var useABOnly = op !== 'sum' && op !== 'avg';
                                        if (useABOnly) {
                                            var optA = formulaSourceASelect && formulaSourceASelect.options[formulaSourceASelect.selectedIndex];
                                            var optB = formulaSourceBSelect && formulaSourceBSelect.options[formulaSourceBSelect.selectedIndex];
                                            if (!optA || !optA.value) {
                                                preview.classList.add('hidden');
                                                preview.textContent = '';
                                                return;
                                            }
                                            if (isCustomOp) {
                                                var formulaCustomExprMulti = document.getElementById('formula-custom-expr');
                                                var expr = (typeof op === 'string' && op.indexOf('custom:') === 0) ? op.substring(7) : (formulaCustomExprMulti ? String(formulaCustomExprMulti.value || '').trim() : '');
                                                if (!expr) {
                                                    preview.classList.add('hidden');
                                                    preview.textContent = '';
                                                    return;
                                                }
                                                var multiVals = [];
                                                var multiStrs = [];
                                                if (optA && optA.value) {
                                                    var vA = optA.getAttribute('data-value') || (optA.textContent.match(/\(([^)]+)\)$/) && optA.textContent.match(/\(([^)]+)\)$/)[1]) || '';
                                                    multiVals.push(toNumeric(vA));
                                                    multiStrs.push(String(vA || '').trim() || '-');
                                                }
                                                if (optB && optB.value) {
                                                    var vB = optB.getAttribute('data-value') || (optB.textContent.match(/\(([^)]+)\)$/) && optB.textContent.match(/\(([^)]+)\)$/)[1]) || '';
                                                    multiVals.push(toNumeric(vB));
                                                    multiStrs.push(String(vB || '').trim() || '-');
                                                }
                                                var extra = document.getElementById('formula-sources-extra');
                                                if (extra) {
                                                    extra.querySelectorAll('select.formula-source-select').forEach(function(sel) {
                                                        if (sel.value) {
                                                            var o = sel.options[sel.selectedIndex];
                                                            var v = o ? (o.getAttribute('data-value') || (o.textContent.match(/\(([^)]+)\)$/) && o.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                                            multiVals.push(toNumeric(v));
                                                            multiStrs.push(String(v || '').trim() || '-');
                                                        }
                                                    });
                                                }
                                                var safeEvalMultiVars = function(e, vals) {
                                                    var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/');
                                                    var letters = 'ABCDEFGHIJ';
                                                    for (var i = 0; i < vals.length && i < letters.length; i++) {
                                                        var re = new RegExp('\\b' + letters[i] + '\\b', 'g');
                                                        s = s.replace(re, String(vals[i]));
                                                    }
                                                    if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                                    try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                                };
                                                var result = safeEvalMultiVars(expr, multiVals);
                                                if (isNaN(result)) {
                                                    preview.textContent = expr + ' a Invalid expression';
                                                } else {
                                                    var prevStr = result.toFixed(2);
                                                    if (isPercentOperation(op, expr)) prevStr = prevStr + '%';
                                                    preview.textContent = multiStrs.join(', ') + ' a ' + prevStr;
                                                }
                                                preview.classList.remove('hidden');
                                                return;
                                            }
                                            var valA = optA.getAttribute('data-value') || '';
                                            if (!valA && optA.textContent) { var m = optA.textContent.match(/\(([^)]+)\)$/); if (m) valA = m[1]; }
                                            var valB = '0';
                                            if (optB && optB.value) {
                                                valB = optB.getAttribute('data-value') || '';
                                                if (!valB && optB.textContent) { var m2 = optB.textContent.match(/\(([^)]+)\)$/); if (m2) valB = m2[1]; }
                                                valB = valB || '0';
                                            }
                                            var numA = toNumeric(valA);
                                            var numB = toNumeric(valB);
                                            var result = 0;
                                            switch (op) {
                                                case 'subtract': result = numA - numB; break;
                                                case 'multiply': result = numA * numB; break;
                                                case 'divide': result = numB !== 0 ? (numA / numB) : 0; break;
                                                case 'percent_of': result = numB !== 0 ? ((numA / numB) * 100) : 0; break;
                                                case 'sum_over_b_percent': result = numB !== 0 ? (((numA + numB) / numB) * 100) : 0; break;
                                                case 'diff_over_b_percent': result = numB !== 0 ? (((numA - numB) / numB) * 100) : 0; break;
                                                default: result = numA + numB;
                                            }
                                            var aStr = String(valA || '').trim() || '-';
                                            var bStr = String(valB || '').trim() || '0';
                                            var opSym = { sum: '+', subtract: 'a', multiply: 'A', divide: 'A-', percent_of: 'A-A100', sum_over_b_percent: '(A+B)A-BA100', diff_over_b_percent: '(AaB)A-BA100' }[op] || '+';
                                            if (!optB || !optB.value) {
                                                preview.textContent = aStr + ' (select Source B for full formula)';
                                            } else {
                                                var resStr = result.toFixed(2);
                                                if (isPercentOperation(op)) resStr = resStr + '%';
                                                preview.textContent = aStr + ' ' + opSym + ' ' + bStr + ' = ' + resStr;
                                            }
                                            preview.classList.remove('hidden');
                                            return;
                                        }
                                        var vals = [];
                                        if (formulaSourceASelect && formulaSourceASelect.value) {
                                            var oA = formulaSourceASelect.options[formulaSourceASelect.selectedIndex];
                                            var v = oA ? (oA.getAttribute('data-value') || (oA.textContent.match(/\(([^)]+)\)$/) && oA.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                            vals.push({ str: String(v || '').trim() || '-', num: toNumeric(v) });
                                        }
                                        if (formulaSourceBSelect && formulaSourceBSelect.value) {
                                            var oB = formulaSourceBSelect.options[formulaSourceBSelect.selectedIndex];
                                            var vB = oB ? (oB.getAttribute('data-value') || (oB.textContent.match(/\(([^)]+)\)$/) && oB.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                            vals.push({ str: String(vB || '').trim() || '-', num: toNumeric(vB) });
                                        }
                                        var extra = document.getElementById('formula-sources-extra');
                                        if (extra) {
                                            extra.querySelectorAll('select.formula-source-select').forEach(function(sel) {
                                                if (sel.value) {
                                                    var o = sel.options[sel.selectedIndex];
                                                    var v = o ? (o.getAttribute('data-value') || (o.textContent.match(/\(([^)]+)\)$/) && o.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                                    vals.push({ str: String(v || '').trim() || '-', num: toNumeric(v) });
                                                }
                                            });
                                        }
                                        if (vals.length === 0) {
                                            preview.classList.add('hidden');
                                            preview.textContent = '';
                                            return;
                                        }
                                        var sum = vals.reduce(function(a, x) { return a + x.num; }, 0);
                                        var result = op === 'avg' ? (vals.length > 0 ? sum / vals.length : 0) : sum;
                                        var parts = vals.map(function(x) { return x.str; });
                                        preview.textContent = parts.join(' + ') + ' = ' + result.toFixed(2);
                                        preview.classList.remove('hidden');
                                        return;
                                    }
                                    var optA = formulaSourceASelect && formulaSourceASelect.options[formulaSourceASelect.selectedIndex];
                                    var optB = formulaSourceBSelect && formulaSourceBSelect.options[formulaSourceBSelect.selectedIndex];
                                    var op = formulaOperationSelect ? formulaOperationSelect.value : 'sum';
                                    var formulaCustomWrapAB = document.getElementById('formula-custom-wrap');
                                    var isCustomOp = op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0);
                                    if (formulaCustomWrapAB) {
                                        if (isCustomOp) formulaCustomWrapAB.classList.remove('hidden');
                                        else formulaCustomWrapAB.classList.add('hidden');
                                    }
                                    var removeOpBtn = document.getElementById('formula-remove-operation-btn');
                                    if (removeOpBtn) {
                                        var isRemovable = typeof op === 'string' && op.indexOf('custom:') === 0;
                                        removeOpBtn.disabled = !isRemovable;
                                        removeOpBtn.title = isRemovable ? 'Delete this operation from the saved list' : 'Select a saved custom expression to delete';
                                    }
                                    if (isCustomOp) {
                                        var formulaCustomExprAB = document.getElementById('formula-custom-expr');
                                        var expr = (typeof op === 'string' && op.indexOf('custom:') === 0) ? op.substring(7) : (formulaCustomExprAB ? String(formulaCustomExprAB.value || '').trim() : '');
                                        var valA = optA ? (optA.getAttribute('data-value') || (optA.textContent.match(/\(([^)]+)\)$/) && optA.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '';
                                        var valB = optB ? (optB.getAttribute('data-value') || (optB.textContent.match(/\(([^)]+)\)$/) && optB.textContent.match(/\(([^)]+)\)$/)[1]) || '') : '0';
                                        var numA = toNumeric(valA);
                                        var numB = toNumeric(valB);
                                        if (!expr || !optA || !optA.value) {
                                            preview.classList.add('hidden');
                                            preview.textContent = '';
                                            return;
                                        }
                                        var safeEvalAB = function(e, a, b) {
                                            var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                            if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                            try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                        };
                                        var result = safeEvalAB(expr, numA, numB);
                                        if (isNaN(result)) {
                                            preview.textContent = expr + ' a Invalid expression';
                                        } else {
                                            var prevStr = result.toFixed(2);
                                            if (isPercentOperation(op, expr)) prevStr = prevStr + '%';
                                            preview.textContent = (String(valA || '').trim() || '-') + ', ' + (String(valB || '').trim() || '-') + ' a ' + prevStr;
                                        }
                                        preview.classList.remove('hidden');
                                        return;
                                    }
                                    var opSymbols = { sum: '+', subtract: 'a', multiply: 'A', divide: 'A-', percent_of: 'A-A100', sum_over_b_percent: '(A+B)A-BA100', diff_over_b_percent: '(AaB)A-BA100' };
                                    var sym = opSymbols[op] || '+';
                                    if (!optA || !optA.value) {
                                        preview.classList.add('hidden');
                                        preview.textContent = '';
                                        return;
                                    }
                                    var valA = optA.getAttribute('data-value');
                                    if (!valA && optA.textContent) {
                                        var m = optA.textContent.match(/\(([^)]+)\)$/);
                                        if (m) valA = m[1];
                                    }
                                    valA = valA || '';
                                    var valB = '0';
                                    if (optB && optB.value) {
                                        valB = optB.getAttribute('data-value') || '';
                                        if (!valB && optB.textContent) {
                                            var m2 = optB.textContent.match(/\(([^)]+)\)$/);
                                            if (m2) valB = m2[1];
                                        }
                                        valB = valB || '0';
                                    }
                                    var numA = toNumeric(valA);
                                    var numB = toNumeric(valB);
                                    var result = 0;
                                    switch (op) {
                                        case 'sum': result = numA + numB; break;
                                        case 'subtract': result = numA - numB; break;
                                        case 'multiply': result = numA * numB; break;
                                        case 'divide': result = numB !== 0 ? (numA / numB) : 0; break;
                                        case 'percent_of': result = numB !== 0 ? ((numA / numB) * 100) : 0; break;
                                        case 'sum_over_b_percent': result = numB !== 0 ? (((numA + numB) / numB) * 100) : 0; break;
                                        case 'diff_over_b_percent': result = numB !== 0 ? (((numA - numB) / numB) * 100) : 0; break;
                                        default: result = numA + numB;
                                    }
                                    var aStr = String(valA || '').trim() || '-';
                                    var bStr = String(valB || '').trim() || '0';
                                    if (!optB || !optB.value) {
                                        preview.textContent = aStr + ' (select Source B for full formula)';
                                    } else {
                                        var resStr = result.toFixed(2);
                                        if (isPercentOperation(op)) resStr = resStr + '%';
                                        preview.textContent = aStr + ' ' + sym + ' ' + bStr + ' = ' + resStr;
                                    }
                                    preview.classList.remove('hidden');
                                }
                                function hideFormulaModal() {
                                    if (!formulaModal) return;
                                    formulaModal.classList.add('hidden');
                                    formulaBlueRowOnlyMode = false;
                                    formulaGrandTotalMode = false;
                                    formulaMultiSourceMode = false;
                                    formulaCustomMode = false;
                                    try { window._uapsManualTotalCrossBluesApply = false; } catch (eUapsMt) {}
                                    if (formulaTargetSelect) formulaTargetSelect.disabled = false;
                                    var targetWrap = document.getElementById('formula-target-select-wrap');
                                    if (targetWrap) targetWrap.classList.remove('hidden');
                                    if (selectionCalcTypeSelect) selectionCalcTypeSelect.value = '';
                                    var preview = document.getElementById('formula-preview');
                                    if (preview) { preview.classList.add('hidden'); preview.textContent = ''; }
                                }

                                var autocalcTitles = { unique: 'Count Unique Values', unique_adjust: 'Count unique (A+/- adjust)', countif: 'Count All Values', count_rows: 'Count Rows', sum: 'Sum', avg: 'Average' };
                                function showAutocalcModal(calcType) {
                                    if (!autocalcModal || !autocalcTitles[calcType]) return;
                                    if (autocalcError) { autocalcError.classList.add('hidden'); autocalcError.textContent = ''; }
                                    var addRowWrap = document.getElementById('autocalc-add-row-wrap');
                                    if (addRowWrap) addRowWrap.classList.add('hidden');
                                    if (autocalcModalTitle) autocalcModalTitle.textContent = autocalcTitles[calcType];
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    var colIndices = [];
                                    selectedCells.forEach(function(td) {
                                        var c = getColIndex(td);
                                        if (c >= 0 && c < fields.length && colIndices.indexOf(c) === -1) colIndices.push(c);
                                    });
                                    colIndices.sort(function(a, b) { return a - b; });
                                    var labels = colIndices.map(function(i) { return (fields[i] && (fields[i].label || getFieldKey(fields[i]))) || 'Column ' + (i + 1); });
                                    var keys = colIndices.map(function(i) { return fields[i] ? getFieldKey(fields[i]) : ''; }).filter(Boolean);
                                    if (autocalcSelectedInfo) {
                                        var stats = getSelectedCellsStats();
                                        var statsHint = buildSelectionHintText(stats);
                                        autocalcSelectedInfo.textContent = labels.length
                                            ? ('Selected column(s): ' + labels.join(', ') + ' | ' + statsHint)
                                            : ('Select cells first. ' + statsHint);
                                    }
                                    autocalcModal.classList.remove('hidden');
                                }
                                function hideAutocalcModal() {
                                    if (!autocalcModal) return;
                                    autocalcModal.classList.add('hidden');
                                    var addRowWrap = document.getElementById('autocalc-add-row-wrap');
                                    if (addRowWrap) addRowWrap.classList.add('hidden');
                                    if (selectionCalcTypeSelect) selectionCalcTypeSelect.value = '';
                                }

                                function applyRemoveFormula() {
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    if (selectedCells.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select one or more cells first, then choose Remove formula.');
                                        return;
                                    }
                                    // Only clear cells in the blue summary row or grand total row (formula results), not data cells
                                    var resultCellsOnly = [];
                                    selectedCells.forEach(function(td) {
                                        var tr = td.closest('tr');
                                        if (tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1')) resultCellsOnly.push(td);
                                    });
                                    if (resultCellsOnly.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'No formula result cells selected. Select cells in the blue summary row or grand total row to remove the formula.');
                                        return;
                                    }
                                    // Clear DOM first (per-campus: only the selected blue cells for this/these campus block(s))
                                    resultCellsOnly.forEach(function(td) {
                                        var trGt = td.closest('tr.data-row');
                                        if (trGt && trGt.classList.contains('grand-total-row')) {
                                            setGrandTotalManualOverride(td, false);
                                            var gwk = getGrandTotalCellKey(td);
                                            if (gwk) delete grandTotalWizardStateByKey[gwk];
                                            if (activeGrandTotalCell === td) activeGrandTotalCell = null;
                                        }
                                        var input = td.querySelector('input, select, textarea');
                                        var span = td.querySelector('span');
                                        clearCellFormulaMapping(td);
                                        if (input) {
                                            if (input.tagName === 'SELECT') {
                                                input.selectedIndex = 0;
                                            } else {
                                                input.value = '';
                                            }
                                        } else if (span) {
                                            span.textContent = '';
                                        } else {
                                            td.textContent = '';
                                        }
                                    });
                                    clearSelectionMulti();
                                    if (selectionCalcTypeSelect) selectionCalcTypeSelect.value = '';

                                    // Remove formula from template (summary_rules + summary_cell_mappings) so PC view shows "-" after reload
                                    var removeTargets = [];
                                    var seenKeys = {};
                                    resultCellsOnly.forEach(function(td) {
                                        var colIdx = getColIndex(td);
                                        if (colIdx >= 0 && colIdx < fields.length) {
                                            var targetKey = getFieldKey(fields[colIdx]);
                                            var blueRow = td.closest('tr.data-row');
                                            var sectionRef = blueRow ? buildSectionRefFromRow(blueRow) : '';
                                            var key = targetKey + '\x01' + sectionRef;
                                            if (!seenKeys[key]) { seenKeys[key] = true; removeTargets.push({ target_field: targetKey, section_ref: sectionRef }); }
                                        }
                                    });
                                    var token = document.querySelector('meta[name="csrf-token"]');
                                    token = token ? token.getAttribute('content') : '';
                                    var removePromises = [];
                                    removeTargets.forEach(function(item) {
                                        var targetField = item.target_field || '';
                                        var sectionRef = item.section_ref || '';
                                        if (!targetField) return;
                                        removePromises.push(
                                            fetch(summaryRulesUrl, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                                body: JSON.stringify({ output: { target_field: targetField, section_ref: sectionRef, remove_only: true } })
                                            }).then(function(r) { return r.json(); }).then(function(res) {
                                                if (res.summary_rules) summaryRulesData = res.summary_rules;
                                                if (res.summary_cell_mappings) summaryCellMappingsData = res.summary_cell_mappings;
                                                if (typeof rebuildSelectionMappingsCache === 'function') rebuildSelectionMappingsCache();
                                                return res;
                                            }).catch(function() { return null; })
                                        );
                                    });

                                    // Persist per-campus: save table data so cleared cells are stored for that specific campus only (like Excel).
                                    var bySub = collectBySubmission();
                                    if (bySub.length === 0 || bySub.every(function(s) { return !s.table_data || s.table_data.length === 0; })) {
                                        if (removePromises.length > 0) {
                                            Promise.all(removePromises).then(function() {
                                                if (typeof window.showToast === 'function') window.showToast('success', 'Formula removed from template.');
                                            });
                                        } else if (typeof window.showToast === 'function') {
                                            window.showToast('success', 'Formula result cleared from summary row.');
                                        }
                                        return;
                                    }
                                    var grandTotalsForRemove = typeof collectGrandTotals === 'function' ? collectGrandTotals() : [];
                                    var kpiFinForRemove = typeof collectKpiFinalizeTotalRow === 'function' ? collectKpiFinalizeTotalRow() : null;
                                    var finalizedForRemove = typeof collectFinalizedAccompForTemplateSave === 'function' ? collectFinalizedAccompForTemplateSave() : null;
                                    var payload = {
                                        by_submission: bySub.map(function(o) {
                                            return {
                                                submission_id: o.submission_id != null ? parseInt(o.submission_id, 10) : null,
                                                user_id: o.user_id != null ? parseInt(o.user_id, 10) : null,
                                                table_data: o.table_data || []
                                            };
                                        }),
                                        grand_totals: grandTotalsForRemove,
                                        kpi_finalize_total_row: kpiFinForRemove,
                                        finalized_accomp: finalizedForRemove
                                    };
                                    // Remove from template first, then save table data (so compute uses updated rules and PC sees "-")
                                    Promise.all(removePromises).then(function() {
                                        fetch(saveUrl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                            body: JSON.stringify(payload)
                                        }).then(function(r) {
                                            if (!r.ok) throw new Error('Save failed');
                                            return r.json();
                                        }).then(function(res) {
                                            if (res.success && typeof window.showToast === 'function') {
                                                window.showToast('success', 'Formula result removed and table data saved for the selected campus(s).');
                                                window.tableDataDirty = false;
                                            }
                                        }).catch(function() {
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Save failed. Try again or save table data manually.');
                                        });
                                    }).catch(function() {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Save failed. Try again or save table data manually.');
                                    });
                                }

                                /** Clear result value and per-cell formula mapping only - keeps template summary rules so you can Apply a fresh calculation. */
                                function applyClearCalculation() {
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    if (selectedCells.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select one or more cells first, then choose Clear calculation (recalculate).');
                                        return;
                                    }
                                    var resultCellsOnly = [];
                                    Array.prototype.forEach.call(selectedCells, function(td) {
                                        var tr = td.closest('tr');
                                        if (tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row') || String(tr.getAttribute('data-manual-total-row') || '') === '1')) resultCellsOnly.push(td);
                                    });
                                    if (resultCellsOnly.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select cells in the blue summary row or grand total row to clear the calculation.');
                                        return;
                                    }
                                    var blueRowsToRecompute = [];
                                    resultCellsOnly.forEach(function(td) {
                                        var trGt = td.closest('tr.data-row');
                                        if (trGt && trGt.classList.contains('grand-total-row')) {
                                            setGrandTotalManualOverride(td, false);
                                            var gwk = getGrandTotalCellKey(td);
                                            if (gwk) delete grandTotalWizardStateByKey[gwk];
                                            if (activeGrandTotalCell === td) activeGrandTotalCell = null;
                                        }
                                        var trBlue = td.closest('tr.data-row');
                                        if (trBlue && (trBlue.classList.contains('bg-blue-100') || String(trBlue.getAttribute('data-manual-total-row') || '') === '1') && blueRowsToRecompute.indexOf(trBlue) === -1) {
                                            blueRowsToRecompute.push(trBlue);
                                        }
                                        clearManualOverrideCell(td);
                                        clearCellFormulaMapping(td);
                                        var input = td.querySelector('input, select, textarea');
                                        var span = td.querySelector('span');
                                        if (input) {
                                            if (input.tagName === 'SELECT') {
                                                input.selectedIndex = 0;
                                            } else {
                                                input.value = '';
                                            }
                                        } else if (span) {
                                            span.textContent = '';
                                        } else {
                                            td.textContent = '';
                                        }
                                    });
                                    var srcBlue = tableBody ? tableBody.querySelectorAll('td.cell-source-for-blue') : [];
                                    Array.prototype.forEach.call(srcBlue, function(td) {
                                        td.classList.remove('cell-source-for-blue');
                                    });
                                    resultCellsOnly.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (tr) setCellSelected(tr, td, true);
                                    });
                                    if (resultCellsOnly[0]) {
                                        lastClickedRowMulti = resultCellsOnly[0].closest('tr.data-row');
                                        lastClickedCellMulti = resultCellsOnly[0];
                                    }
                                    blueRowsToRecompute.forEach(function(trB) {
                                        if (trB && typeof recomputeBlueRowPerformance === 'function') {
                                            try { recomputeBlueRowPerformance(trB); } catch (eRe) {}
                                        }
                                    });
                                    if (typeof resetGrandTotalWizardSteps === 'function') resetGrandTotalWizardSteps();
                                    if (selectionCalcTypeSelect) selectionCalcTypeSelect.value = '';
                                    forceOpenSelectionPopoverOnce = true;
                                    updateFormulaButtonState();
                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('success', 'Calculation cleared. Choose a calculation and click Apply.');
                                    }
                                }

                                function toNumeric(val) {
                                    if (val === null || val === undefined) return 0.0;
                                    if (typeof val === 'number') return val;
                                    var s = String(val);
                                    var clean = s.replace(/[^0-9.\-]/g, '');
                                    var n = parseFloat(clean);
                                    return isNaN(n) ? 0.0 : n;
                                }
                                function getCellRawValue(cell) {
                                    if (!cell) return '';
                                    var input = cell.querySelector('input, select, textarea');
                                    if (input) {
                                        if (input.tagName === 'SELECT') {
                                            var opt = input.options[input.selectedIndex];
                                            return opt ? (opt.value || '') : '';
                                        }
                                        return input.value || '';
                                    }
                                    var span = cell.querySelector('span');
                                    if (span) return span.textContent || '';
                                    return cell.textContent || '';
                                }
                                function setCellRawValue(cell, value) {
                                    if (!cell) return;
                                    clearManualOverrideCell(cell);
                                    var stringVal = String(value ?? '');
                                    var input = cell.querySelector('input, select, textarea');
                                    if (input) {
                                        if (input.tagName === 'SELECT') {
                                            var found = false;
                                            for (var i = 0; i < input.options.length; i++) {
                                                if (String(input.options[i].value) === stringVal) {
                                                    input.selectedIndex = i;
                                                    found = true;
                                                    break;
                                                }
                                            }
                                            if (!found) input.value = stringVal;
                                        } else {
                                            input.value = stringVal;
                                        }
                                        return;
                                    }
                                    var span = cell.querySelector('span');
                                    if (span) {
                                        span.textContent = stringVal;
                                        return;
                                    }
                                    cell.textContent = stringVal;
                                }
                                function isBlueSummaryCell(cell) {
                                    if (!cell) return false;
                                    var tr = cell.closest('tr.data-row');
                                    return !!(tr && tr.classList.contains('bg-blue-100'));
                                }
                                /** Blue summary row only: percentages display as whole numbers (e.g. 48% not 47.69%). */
                                function formatBlueSummaryPercentWhole(n) {
                                    var x = Number(n);
                                    if (!isFinite(x)) return '0%';
                                    return String(Math.round(x)) + '%';
                                }
                                function setManualOverrideCell(cell, enabled) {
                                    if (!cell || !isBlueSummaryCell(cell)) return;
                                    if (enabled) {
                                        cell.classList.add('manual-override');
                                        cell.setAttribute('data-manual-override', '1');
                                    } else {
                                        cell.classList.remove('manual-override');
                                        cell.removeAttribute('data-manual-override');
                                    }
                                }
                                function clearManualOverrideCell(cell) {
                                    setManualOverrideCell(cell, false);
                                }
                                function isManualOverrideCell(cell) {
                                    if (!cell) return false;
                                    return cell.classList.contains('manual-override') || cell.getAttribute('data-manual-override') === '1';
                                }
                                function normalizeMetricText(v) {
                                    return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                                }
                                function detectQuarterFromMetric(n) {
                                    if (/(^|_)q1($|_)|(^|_)1st($|_)|(^|_)first($|_)|quarter_?1|1st_?quarter/.test(n)) return 1;
                                    if (/(^|_)q2($|_)|(^|_)2nd($|_)|(^|_)second($|_)|quarter_?2|2nd_?quarter/.test(n)) return 2;
                                    if (/(^|_)q3($|_)|(^|_)3rd($|_)|(^|_)third($|_)|quarter_?3|3rd_?quarter/.test(n)) return 3;
                                    if (/(^|_)q4($|_)|(^|_)4th($|_)|(^|_)fourth($|_)|quarter_?4|4th_?quarter/.test(n)) return 4;
                                    // Dropdowns often store quarter as numeric value only ("1".."4") - normalizeMetricText keeps digits.
                                    if (n === '1' || n === '2' || n === '3' || n === '4') return parseInt(n, 10);
                                    return null;
                                }
                                function findActiveGrandTotalTargetCell() {
                                    if (!tableBody) return null;
                                    var selectedGT = Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).find(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && tr.classList.contains('grand-total-row');
                                    });
                                    if (selectedGT) return selectedGT;
                                    if (activeGrandTotalCell) {
                                        var activeRow = activeGrandTotalCell.closest('tr.data-row');
                                        if (activeRow && activeRow.classList.contains('grand-total-row')) return activeGrandTotalCell;
                                    }
                                    if (lastClickedCellMulti) {
                                        var row = lastClickedCellMulti.closest('tr.data-row');
                                        if (row && row.classList.contains('grand-total-row')) return lastClickedCellMulti;
                                    }
                                    return null;
                                }
                                /** Quarter cell + GRAND TOTAL (Qn) label only - no row-order or column-name inference. */
                                function readGrandTotalQuarterFromRowUi(grandTotalTd) {
                                    var gtCell = grandTotalTd || findActiveGrandTotalTargetCell();
                                    if (!gtCell) return null;
                                    var gtRow = gtCell.closest('tr.data-row');
                                    if (!gtRow) return null;
                                    var quarterColIdx = getQuarterColumnIndex();
                                    if (quarterColIdx >= 0) {
                                        var tds = window.getRowTdCells(gtRow);
                                        if (quarterColIdx < tds.length) {
                                            var qRaw = getCellRawValue(tds[quarterColIdx]);
                                            var fromPicker = detectQuarterFromTextValue(qRaw);
                                            if (fromPicker >= 1 && fromPicker <= 4) return fromPicker;
                                        }
                                    }
                                    if (gtRow.classList.contains('grand-total-row')) {
                                        var fromLabel = detectQuarterFromGrandTotalRow(gtRow);
                                        if (fromLabel >= 1 && fromLabel <= 4) return fromLabel;
                                    }
                                    return null;
                                }
                                /** Active cascade wizard step 2 when type is Quarter (popover visible). */
                                function getGrandTotalWizardQuarterIfAny() {
                                    var wiz = document.getElementById('grand-total-cascade-wizard');
                                    if (!wiz || wiz.classList.contains('hidden')) return null;
                                    var t = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    if (!t || t.value !== 'quarter' || !s2) return null;
                                    var v = String(s2.value || '').trim();
                                    if (!v) return null;
                                    var p = parseInt(v, 10);
                                    if (p >= 1 && p <= 4) return p;
                                    return null;
                                }
                                function isGrandTotalSchoolYearSourceQuarterVal(sq) {
                                    if (sq == null) return false;
                                    var s = String(sq).trim().toLowerCase();
                                    if (s === 'school_year') return true;
                                    return s === 'sy_2nd_sem_2024_2025' || s === 'midyear_2025' || s === 'sy_1st_sem_2025_2026';
                                }
                                function grandTotalSchoolYearStep2FromStoredSourceQuarter(sq) {
                                    var s = String(sq == null ? '' : sq).trim().toLowerCase();
                                    if (s === 'school_year') return 'sy_2nd_sem_2024_2025';
                                    if (isGrandTotalSchoolYearSourceQuarterVal(s) && s !== 'school_year') return s;
                                    return 'sy_2nd_sem_2024_2025';
                                }
                                function isGrandTotalSchoolYearScope(grandTotalTd) {
                                    if (!grandTotalTd) return false;
                                    var m = getCellFormulaMapping(grandTotalTd);
                                    return !!(m && isGrandTotalSchoolYearSourceQuarterVal(m.source_quarter));
                                }
                                function isGrandTotalSchoolYearWizardOrScope(grandTotalTd) {
                                    if (isGrandTotalSchoolYearScope(grandTotalTd)) return true;
                                    var wiz = document.getElementById('grand-total-cascade-wizard');
                                    if (!wiz || wiz.classList.contains('hidden')) return false;
                                    var tw = document.getElementById('gt-wizard-type');
                                    return !!(tw && tw.value === 'school_year');
                                }
                                /** Wizard / mapping scope keys that aggregate campus blue summary cells (not legacy all-rows school_year). */
                                function isGrandTotalSchoolYearBlueScopeKey(sq) {
                                    if (sq == null) return false;
                                    var s = String(sq).trim().toLowerCase();
                                    return s === 'sy_2nd_sem_2024_2025' || s === 'midyear_2025' || s === 'sy_1st_sem_2025_2026';
                                }
                                function getGrandTotalSchoolYearBlueScopeKey(grandTotalTd) {
                                    if (!grandTotalTd) return null;
                                    var m = getCellFormulaMapping(grandTotalTd);
                                    if (m && isGrandTotalSchoolYearBlueScopeKey(m.source_quarter)) return String(m.source_quarter).trim().toLowerCase();
                                    var wiz = document.getElementById('grand-total-cascade-wizard');
                                    if (wiz && !wiz.classList.contains('hidden')) {
                                        var tw = document.getElementById('gt-wizard-type');
                                        var s2 = document.getElementById('gt-wizard-step2');
                                        if (tw && tw.value === 'school_year' && s2 && isGrandTotalSchoolYearBlueScopeKey(s2.value)) return String(s2.value).trim().toLowerCase();
                                    }
                                    return null;
                                }
                                function scopeCellTextMatchesSchoolYearBlueKey(raw, scopeKey) {
                                    if (!scopeKey) return false;
                                    var t = String(raw || '').toLowerCase().replace(/\s+/g, ' ').trim();
                                    if (!t) return false;
                                    if (scopeKey === 'sy_2nd_sem_2024_2025') {
                                        if (!(/\b2nd\b/.test(t) || /\bsecond\b/.test(t))) return false;
                                        if (!(/\bsem\b/.test(t) || /\bsemester\b/.test(t))) return false;
                                        if (t.indexOf('2024') >= 0 && t.indexOf('2025') >= 0) return true;
                                        if (/\b2024\s*[\-–]\s*2025\b/.test(t)) return true;
                                        if (/\b1st\b/.test(t) || /\bfirst\b/.test(t)) return false;
                                        if (/\bmidyear\b/.test(t) || /\bmid\s*year\b/.test(t)) return false;
                                        if (/\b2025\s*[\-–]\s*2026\b/.test(t) || (t.indexOf('2025') >= 0 && t.indexOf('2026') >= 0)) return false;
                                        return true;
                                    }
                                    if (scopeKey === 'midyear_2025') {
                                        if (!(/\bmidyear\b/.test(t) || /\bmid\s*year\b/.test(t))) return false;
                                        return t.indexOf('2025') >= 0;
                                    }
                                    if (scopeKey === 'sy_1st_sem_2025_2026') {
                                        if (!(/\b1st\b/.test(t) || /\bfirst\b/.test(t))) return false;
                                        if (!(/\bsem\b/.test(t) || /\bsemester\b/.test(t))) return false;
                                        if (t.indexOf('2025') >= 0 && t.indexOf('2026') >= 0) return true;
                                        if (/\b2025\s*[\-–]\s*2026\b/.test(t)) return true;
                                        return false;
                                    }
                                    return false;
                                }
                                function sectionRowsMatchSchoolYearBlueScope(sectionRows, semColIdx, scopeKey, targetColIdx) {
                                    if (!sectionRows || !scopeKey) return false;
                                    var dataRows = sectionRows.filter(function(tr) {
                                        return typeof isPlainAggregatableDataRow === 'function' && isPlainAggregatableDataRow(tr);
                                    });
                                    if (dataRows.length === 0) return false;
                                    var qc = typeof getQuarterColumnIndex === 'function' ? getQuarterColumnIndex() : -1;
                                    var cols = [];
                                    if (semColIdx >= 0) cols.push(semColIdx);
                                    else {
                                        var n = fields && fields.length ? fields.length : 0;
                                        for (var c = 0; c < n; c++) {
                                            if (c === targetColIdx) continue;
                                            if (c === qc) continue;
                                            cols.push(c);
                                        }
                                    }
                                    if (cols.length === 0) return false;
                                    for (var i = 0; i < dataRows.length; i++) {
                                        var tds = window.getRowTdCells(dataRows[i]);
                                        for (var j = 0; j < cols.length; j++) {
                                            var td = tds[cols[j]];
                                            if (!td) continue;
                                            var raw = String(getCellRawValue(td) || '').trim();
                                            if (scopeCellTextMatchesSchoolYearBlueKey(raw, scopeKey)) return true;
                                        }
                                    }
                                    return false;
                                }
                                function findGrandTotalSchoolYearScopedBlueSourceCells(grandTotalTd, scopeKey) {
                                    if (!grandTotalTd || !tableBody || !scopeKey || typeof buildTableBodySections !== 'function') return [];
                                    var targetColIdx = getColIndex(grandTotalTd);
                                    if (targetColIdx < 0) return [];
                                    var semColIdx = typeof getSemesterScopeColumnIndex === 'function' ? getSemesterScopeColumnIndex() : -1;
                                    var sections = buildTableBodySections();
                                    var out = [];
                                    var seen = {};
                                    for (var si = 0; si < sections.length; si++) {
                                        var sectionRows = sections[si];
                                        if (!sectionRows || sectionRows.length === 0) continue;
                                        if (!sectionRowsMatchSchoolYearBlueScope(sectionRows, semColIdx, scopeKey, targetColIdx)) continue;
                                        for (var ri = 0; ri < sectionRows.length; ri++) {
                                            var tr = sectionRows[ri];
                                            if (!tr.classList.contains('bg-blue-100')) continue;
                                            var tds = window.getRowTdCells(tr);
                                            var cell = tds[targetColIdx];
                                            if (!cell) continue;
                                            var sk = typeof grandTotalSourceCellKey === 'function' ? grandTotalSourceCellKey(cell) : ('syblue:' + si + ':' + ri + ':' + targetColIdx);
                                            if (seen[sk]) continue;
                                            seen[sk] = true;
                                            out.push(cell);
                                        }
                                    }
                                    return out;
                                }
                                function getSelectedGrandTotalQuarter(grandTotalTd) {
                                    if (grandTotalTd) {
                                        var mSel = getCellFormulaMapping(grandTotalTd);
                                        if (mSel && mSel.source_quarter && String(mSel.source_quarter).trim() !== 'manual') {
                                            if (isGrandTotalSchoolYearSourceQuarterVal(mSel.source_quarter)) return null;
                                            var sqSel = parseInt(String(mSel.source_quarter), 10);
                                            if (sqSel >= 1 && sqSel <= 4) return sqSel;
                                        }
                                    }
                                    var fromWizard = getGrandTotalWizardQuarterIfAny();
                                    if (fromWizard >= 1 && fromWizard <= 4) return fromWizard;
                                    var fromRowSel = readGrandTotalQuarterFromRowUi(grandTotalTd);
                                    if (fromRowSel >= 1 && fromRowSel <= 4) return fromRowSel;
                                    // Shared legacy quarter dropdown: only when that control targets the active cell (quarter column), never for another GT row.
                                    if (grandTotalQuarterSelect && typeof isQuarterOnlyGrandTotalTarget === 'function' && isQuarterOnlyGrandTotalTarget()) {
                                        var rawSelected = String(grandTotalQuarterSelect.value || '').trim();
                                        if (rawSelected) {
                                            var parsed = parseInt(rawSelected, 10);
                                            if (parsed >= 1 && parsed <= 4) return parsed;
                                        }
                                    }
                                    return null;
                                }
                                function isGrandTotalManualSelectionMode(grandTotalTd) {
                                    var gt = grandTotalTd || findActiveGrandTotalTargetCell();
                                    if (!gt) return false;
                                    var mapping = getCellFormulaMapping(gt);
                                    if (mapping && String(mapping.source_quarter || '').trim() === 'manual') return true;
                                    var key = getGrandTotalCellKey(gt);
                                    return !!(key && grandTotalManualOverrideByKey[key]);
                                }
                                function resolveGrandTotalQuarter(targetColIndex, grandTotalTd) {
                                    if (grandTotalTd) {
                                        var m0 = getCellFormulaMapping(grandTotalTd);
                                        if (m0 && m0.source_quarter && String(m0.source_quarter).trim() !== 'manual') {
                                            if (isGrandTotalSchoolYearSourceQuarterVal(m0.source_quarter)) return null;
                                            var sq0 = parseInt(String(m0.source_quarter), 10);
                                            if (sq0 >= 1 && sq0 <= 4) return sq0;
                                        }
                                    }
                                    var fromRowUi = readGrandTotalQuarterFromRowUi(grandTotalTd);
                                    if (fromRowUi >= 1 && fromRowUi <= 4) return fromRowUi;
                                    var fromWizRes = getGrandTotalWizardQuarterIfAny();
                                    if (fromWizRes >= 1 && fromWizRes <= 4) return fromWizRes;
                                    if (grandTotalQuarterSelect && typeof isQuarterOnlyGrandTotalTarget === 'function' && isQuarterOnlyGrandTotalTarget()) {
                                        var r0 = String(grandTotalQuarterSelect.value || '').trim();
                                        if (r0) {
                                            var p0 = parseInt(r0, 10);
                                            if (p0 >= 1 && p0 <= 4) return p0;
                                        }
                                    }
                                    return null;
                                }
                                function detectQuarterFromTextValue(raw) {
                                    var n = normalizeMetricText(raw);
                                    return detectQuarterFromMetric(n);
                                }
                                function detectQuarterFromRow(tr) {
                                    if (!tr) return null;
                                    var tds = window.getRowTdCells(tr);
                                    for (var i = 0; i < tds.length; i++) {
                                        var txt = String(getCellRawValue(tds[i]) || '').trim();
                                        var q = detectQuarterFromTextValue(txt);
                                        if (q) return q;
                                    }
                                    return null;
                                }
                                function getQuarterColumnIndex() {
                                    for (var i = 0; i < fields.length; i++) {
                                        var f = fields[i] || {};
                                        var keyNorm = normalizeMetricText(getFieldKey(f));
                                        var labelNorm = normalizeMetricText(f.label || '');
                                        var lblPlain = String((f.label || '')).toLowerCase();
                                        var keyPlain = String(getFieldKey(f) || '').toLowerCase();
                                        if (/(^|_)quarter($|_)|(^|_)qtr($|_)|(^|_)q($|_)/.test(keyNorm) || /(^|_)quarter($|_)|(^|_)qtr($|_)|(^|_)q($|_)/.test(labelNorm)) {
                                            return i;
                                        }
                                        if ((/\bquarter\b/.test(lblPlain) || /\bquarter\b/.test(keyPlain)) && !/accomp|accomplishment|actual|year/.test(lblPlain + keyPlain)) {
                                            return i;
                                        }
                                    }
                                    return -1;
                                }
                                function getSemesterScopeColumnIndex() {
                                    if (!fields || !fields.length) return -1;
                                    var qIdx = getQuarterColumnIndex();
                                    var best = -1;
                                    var bestScore = 0;
                                    for (var i = 0; i < fields.length; i++) {
                                        if (i === qIdx) continue;
                                        var f = fields[i] || {};
                                        var lblPlain = String((f.label || '')).toLowerCase();
                                        var keyPlain = String(getFieldKey(f) || '').toLowerCase();
                                        var comb = lblPlain + ' ' + keyPlain;
                                        var score = 0;
                                        if (/\bsemester\b/.test(comb)) score += 4;
                                        else if (/\bsem\b/.test(comb)) score += 3;
                                        if (/\bs\.?\s*y\.?\b/.test(comb) || /\bschool\s*year\b/.test(comb)) score += 2;
                                        if (/\bmidyear\b/.test(comb)) score += 2;
                                        if (/\bacademic\b/.test(comb)) score += 1;
                                        if (/\bterm\b/.test(comb)) score += 1;
                                        if (/\bquarter\b/.test(comb) && !/\bsem/.test(comb)) score -= 2;
                                        if (score > bestScore) {
                                            bestScore = score;
                                            best = i;
                                        }
                                    }
                                    return bestScore > 0 ? best : -1;
                                }
                                function formatQuarterText(q) {
                                    if (q === 1) return '1st Q';
                                    if (q === 2) return '2nd Q';
                                    if (q === 3) return '3rd Q';
                                    if (q === 4) return '4th Q';
                                    return '';
                                }
                                function applyGrandTotalQuarterLabel(grandTotalTd) {
                                    if (!grandTotalTd) return;
                                    var tr = grandTotalTd.closest('tr.data-row');
                                    if (!tr) return;
                                    var labelSpan = tr.querySelector('td:first-child span');
                                    if (!labelSpan) return;
                                    var baseLabel = labelSpan.getAttribute('data-base-label');
                                    if (!baseLabel) {
                                        baseLabel = String(labelSpan.textContent || '').replace(/\s*\(Q[1-4]\)\s*$/i, '').replace(/\s*\(School Year\)\s*$/i, '').trim();
                                        labelSpan.setAttribute('data-base-label', baseLabel);
                                    }
                                    if (isGrandTotalSchoolYearScope(grandTotalTd)) {
                                        labelSpan.textContent = baseLabel + ' (School Year)';
                                        return;
                                    }
                                    var q = getSelectedGrandTotalQuarter(grandTotalTd);
                                    labelSpan.textContent = q ? (baseLabel + ' (Q' + q + ')') : baseLabel;
                                }
                                function syncGrandTotalQuarterCellValue(grandTotalTd) {
                                    if (!grandTotalTd || !tableBody) return;
                                    if (isGrandTotalSchoolYearScope(grandTotalTd)) return;
                                    var tr = grandTotalTd.closest('tr.grand-total-row');
                                    if (!tr) return;
                                    var qCol = getQuarterColumnIndex();
                                    if (qCol < 0) return;
                                    var tdsGt = window.getRowTdCells(tr);
                                    if (qCol >= tdsGt.length) return;
                                    var tdQ = tdsGt[qCol];
                                    var qNum = null;
                                    var mp = getCellFormulaMapping(grandTotalTd);
                                    if (mp && mp.source_quarter && String(mp.source_quarter) !== 'manual') {
                                        qNum = parseInt(String(mp.source_quarter), 10);
                                    }
                                    if (!qNum || qNum < 1 || qNum > 4) {
                                        qNum = readGrandTotalQuarterFromRowUi(grandTotalTd);
                                    }
                                    if (!qNum || qNum < 1 || qNum > 4) {
                                        var qw = getGrandTotalWizardQuarterIfAny();
                                        if (qw >= 1 && qw <= 4) qNum = qw;
                                    }
                                    if (!qNum || qNum < 1 || qNum > 4) {
                                        if (grandTotalQuarterSelect && String(grandTotalQuarterSelect.value || '').trim() !== '' &&
                                            typeof isQuarterOnlyGrandTotalTarget === 'function' && isQuarterOnlyGrandTotalTarget()) {
                                            qNum = parseInt(String(grandTotalQuarterSelect.value), 10);
                                        }
                                    }
                                    if (!qNum || qNum < 1 || qNum > 4) return;
                                    var tgtInp = tdQ.querySelector('select, input');
                                    if (tgtInp && tgtInp.tagName === 'SELECT') {
                                        var matched = false;
                                        for (var oi = 0; oi < tgtInp.options.length; oi++) {
                                            var ov = String(tgtInp.options[oi].value || '');
                                            var ot = String(tgtInp.options[oi].textContent || '').trim();
                                            if (detectQuarterFromTextValue(ov) === qNum || detectQuarterFromTextValue(ot) === qNum) {
                                                tgtInp.selectedIndex = oi;
                                                matched = true;
                                                break;
                                            }
                                        }
                                        if (!matched && String(qNum) !== '') {
                                            for (var oj = 0; oj < tgtInp.options.length; oj++) {
                                                if (String(tgtInp.options[oj].value) === String(qNum)) {
                                                    tgtInp.selectedIndex = oj;
                                                    matched = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!matched) setCellRawValue(tdQ, formatQuarterText(qNum));
                                    } else {
                                        setCellRawValue(tdQ, formatQuarterText(qNum));
                                    }
                                }
                                function getGrandTotalSourceCells(grandTotalTd, selectedSourceCells) {
                                    if (!tableBody || !grandTotalTd) return [];
                                    var targetColIdx = getColIndex(grandTotalTd);
                                    if (targetColIdx < 0) return [];
                                    var quarter = resolveGrandTotalQuarter(targetColIdx, grandTotalTd);
                                    var mappingGt = getCellFormulaMapping(grandTotalTd);
                                    var schoolYearLegacyAllRows = !!(mappingGt && String(mappingGt.source_quarter || '').trim().toLowerCase() === 'school_year');
                                    var schoolYearAll = typeof isGrandTotalSchoolYearWizardOrScope === 'function' && isGrandTotalSchoolYearWizardOrScope(grandTotalTd);
                                    var manualMode = isGrandTotalManualSelectionMode(grandTotalTd);
                                    var blueSyKey = typeof getGrandTotalSchoolYearBlueScopeKey === 'function' ? getGrandTotalSchoolYearBlueScopeKey(grandTotalTd) : null;
                                    if (!manualMode && blueSyKey) {
                                        var fromSelBlue = Array.isArray(selectedSourceCells) ? selectedSourceCells.filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && tr.classList.contains('bg-blue-100') && !tr.classList.contains('grand-total-row') && getColIndex(td) === targetColIdx;
                                        }) : [];
                                        if (fromSelBlue.length > 0) return fromSelBlue;
                                        if (typeof findGrandTotalSchoolYearScopedBlueSourceCells === 'function') {
                                            var autoBlues = findGrandTotalSchoolYearScopedBlueSourceCells(grandTotalTd, blueSyKey);
                                            if (autoBlues.length > 0) return autoBlues;
                                        }
                                        return [];
                                    }
                                    if (!manualMode && typeof isGrandTotalWizardCalculationAllColumnMode === 'function' && isGrandTotalWizardCalculationAllColumnMode(grandTotalTd)) {
                                        var allRowsGtCalc = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row')).filter(isPlainAggregatableDataRow);
                                        var outGtCalc = [];
                                        allRowsGtCalc.forEach(function(trR) {
                                            var tdsR2 = window.getRowTdCells(trR);
                                            var cR = tdsR2[targetColIdx];
                                            if (cR) outGtCalc.push(cR);
                                        });
                                        return outGtCalc;
                                    }
                                    var normalizedSelected = Array.isArray(selectedSourceCells) ? selectedSourceCells.filter(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || tr.classList.contains('grand-total-row') || tr.classList.contains('kpi-finalize-total-row')) return false;
                                        if (manualMode) return getColIndex(td) === targetColIdx;
                                        if (tr.classList.contains('bg-blue-100')) return false;
                                        if (!quarter) return getColIndex(td) === targetColIdx;
                                        return detectQuarterFromRow(tr) === quarter;
                                    }) : [];
                                    if (normalizedSelected.length > 0) return normalizedSelected;
                                    if (manualMode && mappingGt && Array.isArray(mappingGt.row_uids) && mappingGt.row_uids.length > 0) {
                                        var uidSetGt = {};
                                        mappingGt.row_uids.forEach(function(u) { if (u != null && String(u).trim() !== '') uidSetGt[String(u)] = true; });
                                        var pickedByUid = [];
                                        var allDr = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row')).filter(isPlainAggregatableDataRow);
                                        allDr.forEach(function(tr) {
                                            var ru = String(tr.getAttribute('data-row-uid') || '');
                                            if (!uidSetGt[ru]) return;
                                            var tdsR = window.getRowTdCells(tr);
                                            var c = tdsR[targetColIdx];
                                            if (c) pickedByUid.push(c);
                                        });
                                        if (pickedByUid.length > 0) return pickedByUid;
                                    }
                                    if (manualMode && normalizedSelected.length === 0) return [];
                                    var quarterColIdx = getQuarterColumnIndex();
                                    var allDataRows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row')).filter(isPlainAggregatableDataRow);
                                    if (!manualMode && !quarter && !schoolYearLegacyAllRows && !schoolYearAll) return [];
                                    if (!manualMode && !quarter && !schoolYearLegacyAllRows && schoolYearAll) {
                                        var wizChk = document.getElementById('grand-total-cascade-wizard');
                                        var twChk = document.getElementById('gt-wizard-type');
                                        var s2Chk = document.getElementById('gt-wizard-step2');
                                        if (wizChk && !wizChk.classList.contains('hidden') && twChk && twChk.value === 'school_year' && (!s2Chk || !String(s2Chk.value || '').trim())) {
                                            return [];
                                        }
                                        return [];
                                    }
                                    var sources = [];
                                    allDataRows.forEach(function(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        var rowQuarter = null;
                                        if (quarterColIdx >= 0 && quarterColIdx < tds.length) {
                                            rowQuarter = detectQuarterFromTextValue(getCellRawValue(tds[quarterColIdx]));
                                        }
                                        if (!rowQuarter) rowQuarter = detectQuarterFromRow(tr);
                                        if (quarter && rowQuarter !== quarter) return;
                                        var sameColCell = tds[targetColIdx] || null;
                                        if (sameColCell) sources.push(sameColCell);
                                    });
                                    return sources;
                                }
                                function suggestGrandTotalAction(grandTotalTd) {
                                    if (!grandTotalTd || !fields || fields.length === 0) return '';
                                    var targetColIdx = getColIndex(grandTotalTd);
                                    if (targetColIdx < 0 || targetColIdx >= fields.length) return '';
                                    var quarterColIdx = getQuarterColumnIndex();
                                    if (quarterColIdx >= 0 && targetColIdx === quarterColIdx) return '';
                                    var field = fields[targetColIdx] || {};
                                    var fieldType = String(field.type || '').toLowerCase();
                                    if (fieldType === 'number') return 'sum';
                                    var sourceCells = getGrandTotalSourceCells(grandTotalTd, []);
                                    var hasNumeric = false;
                                    var hasTextOnly = false;
                                    sourceCells.forEach(function(cell) {
                                        var raw = String(getCellRawValue(cell) || '').trim();
                                        if (!raw || raw === '-') return;
                                        var n = toNumeric(raw);
                                        if (!isNaN(n)) hasNumeric = true;
                                        else hasTextOnly = true;
                                    });
                                    if (hasTextOnly && !hasNumeric) return 'unique';
                                    if (hasNumeric) return 'sum';
                                    return '';
                                }
                                function maybeAutoSelectGrandTotalAction(grandTotalTd) {
                                    if (!selectionCalcTypeSelect || !grandTotalTd) return;
                                    if (isQuarterOnlyGrandTotalTarget()) return;
                                    var current = String(selectionCalcTypeSelect.value || '').trim();
                                    var suggested = suggestGrandTotalAction(grandTotalTd);
                                    if (!suggested) return;
                                    if (current && current === suggested) return;
                                    // If current was previously auto-picked (often stale "sum"), allow correction.
                                    if (current && current !== suggested && current !== 'sum' && current !== 'avg' && current !== 'avg_number' && current !== 'avg_percentage' && current !== 'unique' && current !== 'unique_adjust' && current !== 'countif' && current !== 'count_rows') {
                                        return;
                                    }
                                    setSelectionCalcOption(suggested, '');
                                    selectionCalcState = 'manual';
                                }
                                function normalizeCampusTokenForCompare(value) {
                                    var key = String(value || '').trim();
                                    if (!key) return '';
                                    key = key.replace(/\s*planning\s+coordinator\s*$/i, '');
                                    key = key.replace(/\s*campus\s*$/i, '');
                                    key = key.replace(/^psu\s+/i, '');
                                    key = key.replace(/^pel\.?\s*/i, '');
                                    key = key.replace(/^\s*(the\s+)?(campus\s+of\s+)/i, '');
                                    key = key.replace(/\./g, '');
                                    key = key.replace(/\s+/g, ' ').trim().toUpperCase();
                                    return key;
                                }
                                function hideCampusTargetComparePanel() {
                                    if (campusTargetComparePanel) campusTargetComparePanel.classList.add('hidden');
                                    if (campusTargetCompareContent) campusTargetCompareContent.innerHTML = '';
                                    latestCampusCompareEntries = [];
                                }
                                function clearSelectionVisualsOnly() {
                                    if (!tableBody) return;
                                    var highlighted = tableBody.querySelectorAll('td.cell-selected');
                                    highlighted.forEach(function(td) {
                                        td.classList.remove('ring-2', 'ring-indigo-500', 'cell-selected', 'bg-indigo-50');
                                    });
                                    selectedRowsMulti.forEach(function(tr) {
                                        tr.classList.remove('row-selected');
                                    });
                                }
                                function positionCampusTargetComparePanel() {
                                    if (!campusTargetComparePanel || !tableContainerMulti) return;
                                    var containerRect = tableContainerMulti.getBoundingClientRect();
                                    var left = 8;
                                    var top = 8;
                                    if (selectionPopover && !selectionPopover.classList.contains('hidden')) {
                                        left = selectionPopover.offsetLeft + selectionPopover.offsetWidth + 10;
                                        top = selectionPopover.offsetTop;
                                    } else {
                                        var selected = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                        var lastCell = selected.length ? selected[selected.length - 1] : null;
                                        if (lastCell) {
                                            var rect = lastCell.getBoundingClientRect();
                                            left = (rect.right - containerRect.left) + 10;
                                            top = (rect.top - containerRect.top);
                                        }
                                    }
                                    var panelW = campusTargetComparePanel.offsetWidth || 380;
                                    var panelH = campusTargetComparePanel.offsetHeight || 240;
                                    if (left + panelW > containerRect.width - 8) left = containerRect.width - panelW - 8;
                                    if (left < 8) left = 8;
                                    if (top + panelH > containerRect.height - 8) top = containerRect.height - panelH - 8;
                                    if (top < 8) top = 8;
                                    campusTargetComparePanel.style.left = left + 'px';
                                    campusTargetComparePanel.style.top = top + 'px';
                                    campusTargetComparePanel.style.right = 'auto';
                                }
                                function escapeHtml(text) {
                                    return String(text || '')
                                        .replace(/&/g, '&amp;')
                                        .replace(/</g, '&lt;')
                                        .replace(/>/g, '&gt;')
                                        .replace(/"/g, '&quot;')
                                        .replace(/'/g, '&#39;');
                                }
                                function resolveCampusTargetForRow(tr) {
                                    if (!tr) return null;
                                    var subId = String(tr.getAttribute('data-submission-id') || '');
                                    var userId = String(tr.getAttribute('data-user-id') || '');
                                    var block = null;
                                    for (var i = 0; i < coordinatorBlocks.length; i++) {
                                        var b = coordinatorBlocks[i] || {};
                                        var bSub = String((b.submission_id == null) ? '' : b.submission_id);
                                        var bUser = String((b.user_id == null) ? '' : b.user_id);
                                        if ((subId && bSub === subId) || (userId && bUser === userId)) {
                                            block = b;
                                            break;
                                        }
                                    }
                                    var candidates = [];
                                    if (block) {
                                        candidates.push(block.campus || '');
                                        candidates.push(block.display_label || '');
                                        candidates.push(block.submitter_name || '');
                                    }
                                    var prev = tr.previousElementSibling;
                                    while (prev) {
                                        if (prev.classList && prev.classList.contains('section-header-row')) {
                                            var hdr = prev.querySelector('span');
                                            candidates.push(hdr ? hdr.textContent : '');
                                            break;
                                        }
                                        if (prev.classList && prev.classList.contains('separator-row')) break;
                                        prev = prev.previousElementSibling;
                                    }
                                    for (var j = 0; j < candidates.length; j++) {
                                        var key = normalizeCampusTokenForCompare(candidates[j]);
                                        if (key && campusTargetsByNormalizedKey[key]) {
                                            return { key: key, values: campusTargetsByNormalizedKey[key] };
                                        }
                                    }
                                    return null;
                                }
                                function getSectionCampusLabelForRow(tr) {
                                    if (!tr) return '';
                                    var prev = tr.previousElementSibling;
                                    while (prev) {
                                        if (prev.classList && prev.classList.contains('section-header-row')) {
                                            var hdr = prev.querySelector('span');
                                            return String(hdr ? hdr.textContent : '').trim();
                                        }
                                        if (prev.classList && prev.classList.contains('separator-row')) break;
                                        prev = prev.previousElementSibling;
                                    }
                                    return '';
                                }
                                function resolveTargetValueForSelectedMetric(targetValues, metricNorm) {
                                    if (!targetValues || typeof targetValues !== 'object') return 0.0;
                                    var q = detectQuarterFromMetric(metricNorm || '');
                                    if (q === 1) return toNumeric(targetValues.q1);
                                    if (q === 2) return toNumeric(targetValues.q2);
                                    if (q === 3) return toNumeric(targetValues.q3);
                                    if (q === 4) return toNumeric(targetValues.q4);
                                    var total = toNumeric(targetValues.total);
                                    if (total === 0 && targetValues.total_target != null) total = toNumeric(targetValues.total_target);
                                    return total;
                                }
                                /** diff = target - actual (same convention as KPI variance column) */
                                function statusFromCompare(diff, target, actual) {
                                    if (target <= 0) return 'NO TARGET';
                                    if (actual <= 0) return 'NO ACCOMPLISHMENT';
                                    if (Math.abs(diff) < 0.0001) return 'MET TARGET';
                                    return diff < 0 ? 'ABOVE TARGET' : 'BELOW TARGET';
                                }
                                function isFieldPercentage(field, metricNorm) {
                                    var label = String((field && field.label) || '').toLowerCase();
                                    var key = String(getFieldKey(field || {}) || '').toLowerCase();
                                    var norm = String(metricNorm || '').toLowerCase();
                                    return /percent|rate|%/.test(label) || /percent|rate/.test(key) || /percent|rate/.test(norm) ||
                                        (field && field.meta && field.meta.operation && /percent/.test(String(field.meta.operation)));
                                }
                                function isGrandTotalWizardContext() {
                                    if (!tableBody) return false;
                                    if (typeof isQuarterOnlyGrandTotalTarget === 'function' && isQuarterOnlyGrandTotalTarget()) return false;
                                    var sel = tableBody.querySelectorAll('td.cell-selected');
                                    for (var i = 0; i < sel.length; i++) {
                                        var tr = sel[i].closest('tr.data-row');
                                        if (tr && tr.classList.contains('grand-total-row')) return true;
                                    }
                                    return false;
                                }
                                function findBestPercentageColumnIndex() {
                                    var map = getPerformanceColumnMap();
                                    if (map.rate >= 0 && map.rate < fields.length) return map.rate;
                                    for (var i = 0; i < fields.length; i++) {
                                        var f = fields[i];
                                        if (f && isFieldPercentage(f, normalizeMetricText(getFieldKey(f)))) return i;
                                    }
                                    for (var j = 0; j < fields.length; j++) {
                                        var lbl = String((fields[j] && fields[j].label) || '').toLowerCase();
                                        if (/%|percent/.test(lbl)) return j;
                                    }
                                    return -1;
                                }
                                function getBlueSummaryRows() {
                                    if (!tableBody) return [];
                                    return Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row.bg-blue-100'));
                                }
                                function fieldLooksLikeCtcAccomplishment(field, metricNorm) {
                                    var key = String(getFieldKey(field || {}) || '').toLowerCase();
                                    var label = String((field && field.label) || '').toLowerCase();
                                    var norm = String(metricNorm || '').toLowerCase();
                                    if (/target|variance|rating|descriptive|link|url|evidence|remarks|narrative/.test(norm)) return false;
                                    return /accomp|accomplishment|actual|achievement|result|program/.test(norm) || /accomp|actual|achievement|result|program/.test(key + '_' + label);
                                }
                                function scoreBlueColumnForCtc(colIdx) {
                                    var score = 0;
                                    var rows = getBlueSummaryRows();
                                    rows.forEach(function(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        if (colIdx < 0 || colIdx >= tds.length) return;
                                        var raw = String(getCellRawValue(tds[colIdx]) || '').trim();
                                        if (!raw || raw === '-') return;
                                        var n = toNumeric(raw);
                                        if (!isNaN(n)) {
                                            score += 1;
                                            if (n > 0) score += 2;
                                        }
                                    });
                                    return score;
                                }
                                function findBestCtcAccomplishmentColumnIndex(preferredIdx) {
                                    if (!fields || fields.length === 0) return -1;
                                    var bestIdx = -1;
                                    var bestScore = -1;
                                    var evalIdx = function(idx) {
                                        if (idx < 0 || idx >= fields.length) return;
                                        var f = fields[idx] || {};
                                        var norm = normalizeMetricText(getFieldKey(f) + '_' + (f.label || ''));
                                        if (isFieldPercentage(f, norm)) return;
                                        if ((f.type || '').toLowerCase() !== 'number') return;
                                        var score = scoreBlueColumnForCtc(idx);
                                        if (fieldLooksLikeCtcAccomplishment(f, norm)) score += 10;
                                        if (idx === preferredIdx) score += 2;
                                        if (score > bestScore) { bestScore = score; bestIdx = idx; }
                                    };
                                    if (preferredIdx >= 0) evalIdx(preferredIdx);
                                    var perf = getPerformanceColumnMap();
                                    if (perf.accompTotal >= 0) evalIdx(perf.accompTotal);
                                    [1, 2, 3, 4].forEach(function(q) { if (perf.accompQ[q] >= 0) evalIdx(perf.accompQ[q]); });
                                    for (var i = 0; i < fields.length; i++) evalIdx(i);
                                    return bestIdx;
                                }
                                function resolveCompareColumnKeyForGrandTotalCtc(gtTd) {
                                    if (!gtTd || !fields.length) return '';
                                    var idx = getColIndex(gtTd);
                                    var bestIdx = findBestCtcAccomplishmentColumnIndex(idx);
                                    if (bestIdx >= 0 && bestIdx < fields.length) return getFieldKey(fields[bestIdx]);
                                    var pref = getPreferredAccomplishmentTargetKey();
                                    if (pref) return pref;
                                    return fields[0] ? getFieldKey(fields[0]) : '';
                                }
                                function autoSelectBlueRowPercentageCellsForCtc() {
                                    if (!tableBody) return;
                                    // CTC needs to know which column holds the % (rate) values on each blue row.
                                    // Prefer the current Grand Total target column when it already contains % values,
                                    // otherwise fall back to label/metric heuristics.
                                    var gtTd = findActiveGrandTotalTargetCell();
                                    var gtColIdx = gtTd ? getColIndex(gtTd) : -1;
                                    var pctIdx = -1;
                                    if (gtColIdx >= 0) {
                                        var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                        if (blueRows && blueRows.length > 0) {
                                            for (var br = 0; br < blueRows.length; br++) {
                                                var sampleTds = window.getRowTdCells(blueRows[br]);
                                                var sampleTd = sampleTds[gtColIdx] || null;
                                                var sampleRaw = sampleTd ? String(getCellRawValue(sampleTd) || '').trim() : '';
                                                if (sampleRaw && sampleRaw.indexOf('%') !== -1) {
                                                    pctIdx = gtColIdx;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if (pctIdx < 0) pctIdx = findBestPercentageColumnIndex();
                                    if (pctIdx < 0) {
                                        if (typeof window.showToast === 'function') {
                                            window.showToast('notice', 'No percentage column found in this template - use a column with % or rate in the label.');
                                        }
                                        return;
                                    }
                                    var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                    for (var i = 0; i < blueRows.length; i++) {
                                        var tr = blueRows[i];
                                        var tds = window.getRowTdCells(tr);
                                        var td = tds[pctIdx];
                                        if (td) setCellSelected(tr, td, true);
                                    }
                                    if (compareCampusTargetColumn) {
                                        var ck = resolveCompareColumnKeyForGrandTotalCtc(findActiveGrandTotalTargetCell());
                                        if (ck) compareCampusTargetColumn.value = ck;
                                    }
                                    if (typeof updateCompareCampusTargetValuePreview === 'function') updateCompareCampusTargetValuePreview();
                                }
                                var grandTotalWizardStateByKey = {};
                                var lastRestoredGrandTotalWizardCacheKey = '';
                                var restoringGrandTotalWizard = false;
                                var lastGrandTotalWizardAnchorKey = '';
                                function optionExistsOnSelect(selectEl, val) {
                                    if (!selectEl || val === undefined || val === null) return false;
                                    var qs = String(val);
                                    for (var i = 0; i < selectEl.options.length; i++) {
                                        if (String(selectEl.options[i].value) === qs) return true;
                                    }
                                    return false;
                                }
                                function grandTotalSourceCellKey(td) {
                                    if (!td || !tableBody) return '';
                                    var tr = td.closest('tr.data-row');
                                    var c = getColIndex(td);
                                    if (c < 0) return '';
                                    var uid = tr ? String(tr.getAttribute('data-row-uid') || '').trim() : '';
                                    if (uid) return uid + ':' + c;
                                    var all = tableBody.querySelectorAll('tr.data-row');
                                    for (var ri = 0; ri < all.length; ri++) {
                                        if (all[ri] === tr) return 'rowidx_' + ri + ':' + c;
                                    }
                                    return 'unknown:' + c;
                                }
                                function collectExpectedAutoGrandTotalSourceKeys(gtTd) {
                                    if (!gtTd || !tableBody) return [];
                                    var targetColIndex = getColIndex(gtTd);
                                    if (targetColIndex < 0 || targetColIndex >= fields.length) return [];
                                    var blueSkExp = typeof getGrandTotalSchoolYearBlueScopeKey === 'function' ? getGrandTotalSchoolYearBlueScopeKey(gtTd) : null;
                                    if (blueSkExp && typeof findGrandTotalSchoolYearScopedBlueSourceCells === 'function') {
                                        var blueCellsExp = findGrandTotalSchoolYearScopedBlueSourceCells(gtTd, blueSkExp);
                                        var keysExp = [];
                                        for (var bj = 0; bj < blueCellsExp.length; bj++) {
                                            keysExp.push(grandTotalSourceCellKey(blueCellsExp[bj]));
                                        }
                                        keysExp.sort();
                                        return keysExp;
                                    }
                                    if (typeof isGrandTotalWizardCalculationAllColumnMode === 'function' && isGrandTotalWizardCalculationAllColumnMode(gtTd)) {
                                        return collectExpectedKeysGrandTotalCalculationAllColumn(gtTd);
                                    }
                                    var quarter = resolveGrandTotalQuarter(targetColIndex, gtTd);
                                    var mappingGtExp = getCellFormulaMapping(gtTd);
                                    var schoolYearLegacyAllRowsExp = !!(mappingGtExp && String(mappingGtExp.source_quarter || '').trim().toLowerCase() === 'school_year');
                                    var schoolYearAllExp = typeof isGrandTotalSchoolYearWizardOrScope === 'function' && isGrandTotalSchoolYearWizardOrScope(gtTd);
                                    if (!quarter && !schoolYearLegacyAllRowsExp && !schoolYearAllExp) return [];
                                    if (!quarter && !schoolYearLegacyAllRowsExp && schoolYearAllExp) {
                                        var wizExp = document.getElementById('grand-total-cascade-wizard');
                                        var twExp = document.getElementById('gt-wizard-type');
                                        var s2Exp = document.getElementById('gt-wizard-step2');
                                        if (wizExp && !wizExp.classList.contains('hidden') && twExp && twExp.value === 'school_year' && (!s2Exp || !String(s2Exp.value || '').trim())) return [];
                                        return [];
                                    }
                                    var keys = [];
                                    var quarterColIdx = getQuarterColumnIndex();
                                    var allDataRows = tableBody.querySelectorAll('tr.data-row');
                                    for (var di = 0; di < allDataRows.length; di++) {
                                        var tr = allDataRows[di];
                                        if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) continue;
                                        var tds = window.getRowTdCells(tr);
                                        var rowQuarter = null;
                                        if (quarterColIdx >= 0 && quarterColIdx < tds.length) {
                                            rowQuarter = detectQuarterFromTextValue(getCellRawValue(tds[quarterColIdx]));
                                        }
                                        if (!rowQuarter) rowQuarter = detectQuarterFromRow(tr);
                                        if (quarter && rowQuarter !== quarter) continue;
                                        var srcTd = tds[targetColIndex];
                                        if (srcTd) keys.push(grandTotalSourceCellKey(srcTd));
                                    }
                                    keys.sort();
                                    return keys;
                                }
                                function collectCurrentWizardSourceKeys(gtTd) {
                                    if (!gtTd || !tableBody) return [];
                                    var targetColIndex = getColIndex(gtTd);
                                    if (targetColIndex < 0) return [];
                                    var keys = [];
                                    var selected = tableBody.querySelectorAll('td.cell-selected');
                                    var blueSkCur = typeof getGrandTotalSchoolYearBlueScopeKey === 'function' ? getGrandTotalSchoolYearBlueScopeKey(gtTd) : null;
                                    for (var si = 0; si < selected.length; si++) {
                                        var td = selected[si];
                                        if (getColIndex(td) !== targetColIndex) continue;
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || tr.classList.contains('grand-total-row')) continue;
                                        if (tr.classList.contains('bg-blue-100')) {
                                            if (blueSkCur) keys.push(grandTotalSourceCellKey(td));
                                            continue;
                                        }
                                        keys.push(grandTotalSourceCellKey(td));
                                    }
                                    keys.sort();
                                    return keys;
                                }
                                function isGrandTotalWizardStep3ManualValue(v) {
                                    return !!(v && String(v).indexOf('_manual') !== -1);
                                }
                                function shouldShowGrandTotalWizardManualOptions(gtTd) {
                                    var s3El = document.getElementById('gt-wizard-step3');
                                    var twMan = document.getElementById('gt-wizard-type');
                                    var s2Man = document.getElementById('gt-wizard-step2');
                                    if (twMan && twMan.value === 'calculation' && s2Man && isGrandTotalWizardStep3ManualValue(String(s2Man.value || ''))) return true;
                                    var v3 = s3El ? String(s3El.value || '').trim() : '';
                                    if (isGrandTotalWizardStep3ManualValue(v3)) return true;
                                    if (gtTd) {
                                        var mp = getCellFormulaMapping(gtTd);
                                        if (mp && String(mp.source_quarter || '').trim() === 'manual') return true;
                                    }
                                    if (restoringGrandTotalWizard) return false;
                                    var exp = collectExpectedAutoGrandTotalSourceKeys(gtTd);
                                    var cur = collectCurrentWizardSourceKeys(gtTd);
                                    return JSON.stringify(exp) !== JSON.stringify(cur);
                                }
                                function rebuildGrandTotalWizardStep3QuarterCalcOptions(preservePreviousValue) {
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    var typeEl = document.getElementById('gt-wizard-type');
                                    var w3 = document.getElementById('gt-wizard-step3-wrap');
                                    if (!s3 || !typeEl || (typeEl.value !== 'quarter' && typeEl.value !== 'school_year') || !w3 || w3.classList.contains('hidden')) return;
                                    var prev = preservePreviousValue ? String(s3.value || '') : '';
                                    var gtTd = findActiveGrandTotalTargetCell();
                                    var showManual = shouldShowGrandTotalWizardManualOptions(gtTd);
                                    s3.innerHTML = '';
                                    var p0 = document.createElement('option');
                                    p0.value = '';
                                    p0.textContent = '- Select -';
                                    s3.appendChild(p0);
                                    [['sum', 'Sum'], ['unique', 'Count Unique Values'], ['unique_adjust', 'Count unique (A+/- adjust)'], ['countif', 'Count All Values']].forEach(function(pair) {
                                        var opt = document.createElement('option');
                                        opt.value = pair[0];
                                        opt.textContent = pair[1];
                                        s3.appendChild(opt);
                                    });
                                    if (showManual) {
                                        var sep = document.createElement('option');
                                        sep.disabled = true;
                                        sep.value = '';
                                        sep.textContent = '- Manual (current cell selection) -';
                                        s3.appendChild(sep);
                                        [['sum_manual', 'Sum (Manual)'], ['unique_manual', 'Count Unique Values (Manual)'], ['unique_adjust_manual', 'Count unique (A+/- adjust) (Manual)'], ['countif_manual', 'Count All Values (Manual)']].forEach(function(pair) {
                                            var optM = document.createElement('option');
                                            optM.value = pair[0];
                                            optM.textContent = pair[1];
                                            s3.appendChild(optM);
                                        });
                                    }
                                    if (prev && optionExistsOnSelect(s3, prev)) s3.value = prev;
                                    else s3.value = '';
                                    if (!restoringGrandTotalWizard) persistGrandTotalWizardState();
                                }
                                function isGrandTotalWizardCalculationAllColumnMode(gtTd) {
                                    var m = gtTd ? getCellFormulaMapping(gtTd) : null;
                                    if (m && String(m.grand_total_wizard_type || '').trim() === 'calculation' && String(m.source_quarter || '').trim() === 'gt_calc_all') return true;
                                    var wiz = document.getElementById('grand-total-cascade-wizard');
                                    var tw = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    if (!wiz || wiz.classList.contains('hidden') || !tw || tw.value !== 'calculation' || !s2) return false;
                                    var v2 = String(s2.value || '').trim();
                                    return !!v2 && !/_manual$/.test(v2) && v2 !== 'blue-row-formula';
                                }
                                function collectExpectedKeysGrandTotalCalculationAllColumn(gtTd) {
                                    if (!gtTd || !tableBody) return [];
                                    var targetColIndex = getColIndex(gtTd);
                                    if (targetColIndex < 0 || targetColIndex >= fields.length) return [];
                                    var keys = [];
                                    var allDataRows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row')).filter(isPlainAggregatableDataRow);
                                    allDataRows.forEach(function(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        var srcTd = tds[targetColIndex];
                                        if (srcTd) keys.push(grandTotalSourceCellKey(srcTd));
                                    });
                                    keys.sort();
                                    return keys;
                                }
                                function rebuildGrandTotalWizardCalculationTypeStep2(preservePreviousValue) {
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var typeEl = document.getElementById('gt-wizard-type');
                                    var w2 = document.getElementById('gt-wizard-step2-wrap');
                                    if (!s2 || !typeEl || typeEl.value !== 'calculation' || !w2 || w2.classList.contains('hidden')) return;
                                    var prev = preservePreviousValue ? String(s2.value || '') : '';
                                    var gtTd = findActiveGrandTotalTargetCell();
                                    var showManual = shouldShowGrandTotalWizardManualOptions(gtTd);
                                    s2.innerHTML = '';
                                    var p0 = document.createElement('option');
                                    p0.value = '';
                                    p0.textContent = '- Select -';
                                    s2.appendChild(p0);
                                    [['sum', 'Sum'], ['unique', 'Count Unique Values'], ['unique_adjust', 'Count unique (A+/- adjust)'], ['countif', 'Count All Values'], ['blue-row-formula', 'Formula A & B']].forEach(function(pair) {
                                        var opt = document.createElement('option');
                                        opt.value = pair[0];
                                        opt.textContent = pair[1];
                                        s2.appendChild(opt);
                                    });
                                    if (showManual) {
                                        var sep = document.createElement('option');
                                        sep.disabled = true;
                                        sep.value = '';
                                        sep.textContent = '- Manual (current cell selection) -';
                                        s2.appendChild(sep);
                                        [['sum_manual', 'Sum (Manual)'], ['unique_manual', 'Count Unique Values (Manual)'], ['unique_adjust_manual', 'Count unique (A+/- adjust) (Manual)'], ['countif_manual', 'Count All Values (Manual)']].forEach(function(pair) {
                                            var optM = document.createElement('option');
                                            optM.value = pair[0];
                                            optM.textContent = pair[1];
                                            s2.appendChild(optM);
                                        });
                                    }
                                    if (prev && optionExistsOnSelect(s2, prev)) s2.value = prev;
                                    else s2.value = '';
                                    if (!restoringGrandTotalWizard) persistGrandTotalWizardState();
                                }
                                function persistGrandTotalWizardState() {
                                    if (restoringGrandTotalWizard) return;
                                    var gt = findActiveGrandTotalTargetCell();
                                    if (!gt) return;
                                    var key = getGrandTotalCellKey(gt);
                                    if (!key) return;
                                    var t = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    if (!t) return;
                                    grandTotalWizardStateByKey[key] = {
                                        type: String(t.value || ''),
                                        step2: s2 ? String(s2.value || '') : '',
                                        step3: s3 ? String(s3.value || '') : ''
                                    };
                                }
                                function inferGrandTotalWizardStateFromCell(gtTd) {
                                    if (!gtTd) return null;
                                    var m = getCellFormulaMapping(gtTd);
                                    if (!m) return null;
                                    var trInf = gtTd.closest('tr.data-row');
                                    if (trInf && trInf.classList.contains('grand-total-row') && String(m.ui_calc_type || '').trim() === 'blue-row-formula') {
                                        return { type: 'calculation', step2: 'blue-row-formula', step3: '' };
                                    }
                                    if (String(m.ui_calc_type || '').trim() !== 'grand-total') return null;
                                    var op = String(m.ui_formula_operation || '').trim();
                                    var sq = m.source_quarter;
                                    var ctc = String(m.grand_total_ctc_aggregate || '') === '1';
                                    if (!ctc && m.sourceA && (op === 'avg_number' || op === 'avg_percentage')) {
                                        ctc = true;
                                    }
                                    if (ctc) {
                                        return {
                                            type: 'average',
                                            step2: op === 'avg_percentage' ? 'avg_percentage' : 'avg_number',
                                            step3: 'ctc'
                                        };
                                    }
                                    if (String(m.grand_total_wizard_type || '').trim() === 'calculation') {
                                        var opCalc = op;
                                        if (opCalc === 'count_unique') opCalc = 'unique';
                                        if (opCalc === 'count_total') opCalc = 'countif';
                                        if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(opCalc) === -1) opCalc = 'sum';
                                        if (sq != null && String(sq).trim() === 'manual') {
                                            return { type: 'calculation', step2: opCalc + '_manual', step3: '' };
                                        }
                                        return { type: 'calculation', step2: opCalc, step3: '' };
                                    }
                                    if (sq != null && String(sq).trim() === 'manual') {
                                        if (String(m.grand_total_wizard_type || '').trim() === 'school_year') {
                                            var opSyM = op;
                                            if (opSyM === 'count_unique') opSyM = 'unique';
                                            if (opSyM === 'count_total') opSyM = 'countif';
                                            if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(opSyM) === -1) opSyM = 'unique';
                                            return { type: 'school_year', step2: 'sy_2nd_sem_2024_2025', step3: opSyM + '_manual' };
                                        }
                                        var opM = op;
                                        if (opM === 'count_unique') opM = 'unique';
                                        if (opM === 'count_total') opM = 'countif';
                                        if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(opM) === -1) opM = 'unique';
                                        var qUi = readGrandTotalQuarterFromRowUi(gtTd);
                                        if (qUi >= 1 && qUi <= 4) {
                                            return { type: 'quarter', step2: String(qUi), step3: opM + '_manual' };
                                        }
                                        return { type: 'quarter', step2: '', step3: opM + '_manual' };
                                    }
                                    if (sq != null && isGrandTotalSchoolYearSourceQuarterVal(sq)) {
                                        var opSy = op;
                                        if (opSy === 'unique_adjust') { /* keep */ }
                                        else if (opSy === 'count_unique') opSy = 'unique';
                                        if (opSy === 'count_total') opSy = 'countif';
                                        if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(opSy) === -1) opSy = 'sum';
                                        return { type: 'school_year', step2: grandTotalSchoolYearStep2FromStoredSourceQuarter(sq), step3: opSy };
                                    }
                                    if (sq != null && String(sq).trim() !== '' && String(sq) !== 'manual') {
                                        var qNum = parseInt(String(sq), 10);
                                        if (qNum >= 1 && qNum <= 4) {
                                            var op2 = op;
                                            if (op2 === 'unique_adjust') { /* keep */ }
                                            else if (op2 === 'count_unique') op2 = 'unique';
                                            if (op2 === 'count_total') op2 = 'countif';
                                            if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(op2) === -1) op2 = 'sum';
                                            return { type: 'quarter', step2: String(qNum), step3: op2 };
                                        }
                                    }
                                    return null;
                                }
                                function restoreGrandTotalWizardForCell(gtRef) {
                                    if (!gtRef) return;
                                    var key = getGrandTotalCellKey(gtRef);
                                    if (!key) return;
                                    restoringGrandTotalWizard = true;
                                    try {
                                        var st = grandTotalWizardStateByKey[key];
                                        if (!st || !st.type) {
                                            st = inferGrandTotalWizardStateFromCell(gtRef);
                                            if (st && st.type) grandTotalWizardStateByKey[key] = st;
                                        }
                                        if (!st || !st.type) {
                                            resetGrandTotalWizardSteps();
                                            return;
                                        }
                                        var t = document.getElementById('gt-wizard-type');
                                        var s2 = document.getElementById('gt-wizard-step2');
                                        var s3 = document.getElementById('gt-wizard-step3');
                                        if (!t || !s2 || !s3) return;
                                        t.value = st.type;
                                        onGrandTotalWizardTypeChange();
                                        if (st.step2 && optionExistsOnSelect(s2, st.step2)) s2.value = st.step2;
                                        onGrandTotalWizardStep2Change();
                                        if (st.type === 'quarter') {
                                            syncGrandTotalWizardStep2FromResolvedQuarter(gtRef);
                                        }
                                        if (st.step3 && optionExistsOnSelect(s3, st.step3)) s3.value = st.step3;
                                        if (st.type === 'average' && String(st.step3 || '') === 'ctc' && typeof autoSelectBlueRowPercentageCellsForCtc === 'function') {
                                            autoSelectBlueRowPercentageCellsForCtc();
                                        }
                                    } finally {
                                        restoringGrandTotalWizard = false;
                                    }
                                    persistGrandTotalWizardState();
                                }
                                function updateGrandTotalWizardVisibility() {
                                    var wiz = document.getElementById('grand-total-cascade-wizard');
                                    if (!wiz || !selectionCalcTypeSelect) return;
                                    var show = isGrandTotalWizardContext();
                                    wiz.classList.toggle('hidden', !show);
                                    if (show) {
                                        selectionCalcTypeSelect.classList.add('hidden');
                                        var gtRef = findActiveGrandTotalTargetCell();
                                        var rk = gtRef ? getGrandTotalCellKey(gtRef) : '';
                                        if (gtRef && rk && rk !== lastRestoredGrandTotalWizardCacheKey) {
                                            lastRestoredGrandTotalWizardCacheKey = rk;
                                            restoreGrandTotalWizardForCell(gtRef);
                                        }
                                    } else {
                                        lastRestoredGrandTotalWizardCacheKey = '';
                                        if (!isQuarterOnlyGrandTotalTarget()) {
                                            selectionCalcTypeSelect.classList.remove('hidden');
                                        }
                                    }
                                }
                                function resetGrandTotalWizardSteps() {
                                    var t = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    var w2 = document.getElementById('gt-wizard-step2-wrap');
                                    var w3 = document.getElementById('gt-wizard-step3-wrap');
                                    if (t) t.value = '';
                                    if (s2) s2.innerHTML = '';
                                    if (s3) s3.innerHTML = '';
                                    if (w2) w2.classList.add('hidden');
                                    if (w3) w3.classList.add('hidden');
                                }
                                /** When Quarter is chosen, row/mapping may already define Q1aQ4 while step 2 is still placeholder - align dropdown so step 3 can render. */
                                function syncGrandTotalWizardStep2FromResolvedQuarter(grandTotalTd) {
                                    var typeEl = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var w3 = document.getElementById('gt-wizard-step3-wrap');
                                    if (!typeEl || typeEl.value !== 'quarter' || !s2) return;
                                    var gt = grandTotalTd || findActiveGrandTotalTargetCell();
                                    if (!gt) return;
                                    var qNum = getSelectedGrandTotalQuarter(gt);
                                    if (!qNum || qNum < 1 || qNum > 4) return;
                                    var v = String(qNum);
                                    if (!optionExistsOnSelect(s2, v)) return;
                                    if (String(s2.value || '').trim() === v && w3 && !w3.classList.contains('hidden')) return;
                                    s2.value = v;
                                    onGrandTotalWizardStep2Change();
                                }
                                function onGrandTotalWizardTypeChange() {
                                    var typeEl = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    var w2 = document.getElementById('gt-wizard-step2-wrap');
                                    var w3 = document.getElementById('gt-wizard-step3-wrap');
                                    var l2 = document.getElementById('gt-wizard-step2-label');
                                    if (!typeEl || !s2 || !s3 || !w2 || !w3) return;
                                    var type = typeEl.value || '';
                                    s2.innerHTML = '';
                                    s3.innerHTML = '';
                                    w3.classList.add('hidden');
                                    if (!type) {
                                        w2.classList.add('hidden');
                                        if (!restoringGrandTotalWizard) {
                                            persistGrandTotalWizardState();
                                            if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                        }
                                        return;
                                    }
                                    w2.classList.remove('hidden');
                                    if (type === 'quarter') {
                                        if (l2) l2.textContent = '2. Quarter';
                                        [[1, 'Q1'], [2, 'Q2'], [3, 'Q3'], [4, 'Q4']].forEach(function(pair) {
                                            var opt = document.createElement('option');
                                            opt.value = String(pair[0]);
                                            opt.textContent = pair[1];
                                            s2.appendChild(opt);
                                        });
                                        var ph = document.createElement('option');
                                        ph.value = '';
                                        ph.textContent = '- Select quarter -';
                                        s2.insertBefore(ph, s2.firstChild);
                                        s2.value = '';
                                    } else if (type === 'school_year') {
                                        if (l2) l2.textContent = '2. Scope';
                                        var syPh = document.createElement('option');
                                        syPh.value = '';
                                        syPh.textContent = '- Select -';
                                        s2.appendChild(syPh);
                                        [['sy_2nd_sem_2024_2025', '2nd Sem, SY 2024-2025'], ['midyear_2025', 'Midyear 2025'], ['sy_1st_sem_2025_2026', '1st Sem, SY 2025-2026']].forEach(function(pair) {
                                            var syOpt = document.createElement('option');
                                            syOpt.value = pair[0];
                                            syOpt.textContent = pair[1];
                                            s2.appendChild(syOpt);
                                        });
                                        s2.value = '';
                                    } else if (type === 'calculation') {
                                        if (l2) l2.textContent = '2. Calculation';
                                        rebuildGrandTotalWizardCalculationTypeStep2(false);
                                    } else if (type === 'average') {
                                        if (l2) l2.textContent = '2. Average';
                                        var a1 = document.createElement('option');
                                        a1.value = '';
                                        a1.textContent = '- Select -';
                                        s2.appendChild(a1);
                                        var a2 = document.createElement('option');
                                        a2.value = 'avg_number';
                                        a2.textContent = 'Average (Numbers)';
                                        s2.appendChild(a2);
                                        var a3 = document.createElement('option');
                                        a3.value = 'avg_percentage';
                                        a3.textContent = 'Average (Percentage)';
                                        s2.appendChild(a3);
                                    }
                                    if (type === 'quarter' && !restoringGrandTotalWizard) {
                                        syncGrandTotalWizardStep2FromResolvedQuarter(findActiveGrandTotalTargetCell());
                                    }
                                    if (type === 'calculation') {
                                        w3.classList.add('hidden');
                                    }
                                    if (!restoringGrandTotalWizard) {
                                        persistGrandTotalWizardState();
                                        if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                    }
                                }
                                function onGrandTotalWizardStep2Change() {
                                    var typeEl = document.getElementById('gt-wizard-type');
                                    var s2 = document.getElementById('gt-wizard-step2');
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    var w3 = document.getElementById('gt-wizard-step3-wrap');
                                    var l3 = document.getElementById('gt-wizard-step3-label');
                                    if (!typeEl || !s2 || !s3 || !w3) return;
                                    var type = typeEl.value || '';
                                    var v2 = s2.value || '';
                                    s3.innerHTML = '';
                                    if (!v2) {
                                        w3.classList.add('hidden');
                                        if (!restoringGrandTotalWizard && type === 'calculation') {
                                            var gtClearS2 = findActiveGrandTotalTargetCell();
                                            if (gtClearS2) setGrandTotalManualOverride(gtClearS2, false);
                                        }
                                        if (!restoringGrandTotalWizard) {
                                            persistGrandTotalWizardState();
                                            if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                        }
                                        return;
                                    }
                                    if (type === 'calculation') {
                                        w3.classList.add('hidden');
                                        if (!restoringGrandTotalWizard && v2) {
                                            var gtCalcStep = findActiveGrandTotalTargetCell();
                                            if (gtCalcStep) {
                                                if (v2 === 'blue-row-formula') {
                                                    setGrandTotalManualOverride(gtCalcStep, false);
                                                    var trFb = gtCalcStep.closest('tr.data-row');
                                                    if (trFb) setCellSelected(trFb, gtCalcStep, true);
                                                    lastClickedRowMulti = trFb;
                                                    lastClickedCellMulti = gtCalcStep;
                                                    setSelectionModeState('Grand total: choose Formula A & B, then click Apply to configure.');
                                                    if (typeof updateFormulaButtonState === 'function') updateFormulaButtonState();
                                                } else {
                                                    if (isGrandTotalWizardStep3ManualValue(v2)) setGrandTotalManualOverride(gtCalcStep, true);
                                                    else setGrandTotalManualOverride(gtCalcStep, false);
                                                    autoSelectSourcesForBlueCell(gtCalcStep, { silent: true, preferFullSection: true });
                                                }
                                            }
                                        }
                                        rebuildGrandTotalWizardCalculationTypeStep2(true);
                                        if (!restoringGrandTotalWizard) {
                                            persistGrandTotalWizardState();
                                            if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                        }
                                        return;
                                    }
                                    w3.classList.remove('hidden');
                                    if (type === 'quarter') {
                                        if (l3) l3.textContent = '3. Calculation';
                                        if (!restoringGrandTotalWizard && v2) {
                                            var qPickStep = parseInt(String(v2), 10);
                                            if (qPickStep >= 1 && qPickStep <= 4) {
                                                var gtAnchorStep = findActiveGrandTotalTargetCell();
                                                if (gtAnchorStep) {
                                                    applyGrandTotalQuarterLabel(gtAnchorStep);
                                                    syncGrandTotalQuarterCellValue(gtAnchorStep);
                                                    autoSelectSourcesForBlueCell(gtAnchorStep, { silent: true, preferFullSection: true });
                                                }
                                            }
                                        }
                                        rebuildGrandTotalWizardStep3QuarterCalcOptions(true);
                                    } else if (type === 'school_year' && v2) {
                                        if (l3) l3.textContent = '3. Calculation';
                                        if (!restoringGrandTotalWizard) {
                                            var gtSyStep = findActiveGrandTotalTargetCell();
                                            if (gtSyStep) autoSelectSourcesForBlueCell(gtSyStep, { silent: true, preferFullSection: true });
                                        }
                                        rebuildGrandTotalWizardStep3QuarterCalcOptions(true);
                                    } else if (type === 'average') {
                                        if (l3) l3.textContent = '3. Result';
                                        var ctc = document.createElement('option');
                                        ctc.value = 'ctc';
                                        ctc.textContent = 'CTC Results (Campus Target Comparison)';
                                        s3.appendChild(ctc);
                                        s3.value = 'ctc';
                                        autoSelectBlueRowPercentageCellsForCtc();
                                    }
                                    if (!restoringGrandTotalWizard) {
                                        persistGrandTotalWizardState();
                                        if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                    }
                                }
                                function onGrandTotalWizardStep3Change() {
                                    reSelectGrandTotalWizardTargetIfNeeded();
                                    var t = document.getElementById('gt-wizard-type');
                                    var s3 = document.getElementById('gt-wizard-step3');
                                    if (t && t.value === 'average' && s3 && s3.value === 'ctc') {
                                        autoSelectBlueRowPercentageCellsForCtc();
                                    }
                                    if (!restoringGrandTotalWizard) {
                                        persistGrandTotalWizardState();
                                        if (typeof updateSelectionLiveHints === 'function') updateSelectionLiveHints();
                                    }
                                }
                                function reSelectGrandTotalWizardTargetIfNeeded() {
                                    if (!isGrandTotalWizardContext() || !tableBody) return;
                                    var gt = findActiveGrandTotalTargetCell();
                                    if (!gt) return;
                                    var k = getGrandTotalCellKey(gt) || String(getColIndex(gt));
                                    if (k === lastGrandTotalWizardAnchorKey && gt.classList.contains('cell-selected')) return;
                                    lastGrandTotalWizardAnchorKey = k;
                                    var tr = gt.closest('tr.data-row');
                                    if (tr) setCellSelected(tr, gt, true);
                                }
                                function compareCampusTargetAggregateToGrandTotal(gtTd, compareColumnKey, formatAsPercent) {
                                    if (!tableBody || !gtTd) return;
                                    var blueRowsForAvg = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row.bg-blue-100'));
                                    // Fast path for "Average (Percentage)": average existing per-campus CTC % values from blue rows
                                    // (prefer the same column as selected grand total target).
                                    if (formatAsPercent && blueRowsForAvg.length > 0) {
                                        var gtColIdx = getColIndex(gtTd);
                                        var pctColIdx = -1;
                                        if (gtColIdx >= 0) {
                                            var hasPctInGtCol = blueRowsForAvg.some(function(tr) {
                                                var tds = window.getRowTdCells(tr);
                                                var td = tds[gtColIdx] || null;
                                                var raw = td ? String(getCellRawValue(td) || '').trim() : '';
                                                return raw.indexOf('%') !== -1;
                                            });
                                            if (hasPctInGtCol) pctColIdx = gtColIdx;
                                        }
                                        if (pctColIdx < 0) pctColIdx = findBestPercentageColumnIndex();
                                        if (pctColIdx >= 0) {
                                            var pctVals = [];
                                            blueRowsForAvg.forEach(function(tr) {
                                                var tds = window.getRowTdCells(tr);
                                                var td = tds[pctColIdx] || null;
                                                if (!td) return;
                                                var n = toNumeric(getCellRawValue(td));
                                                if (!isNaN(n)) pctVals.push(n);
                                            });
                                            if (pctVals.length > 0) {
                                                var meanPct = pctVals.reduce(function(a, b) { return a + b; }, 0) / pctVals.length;
                                                setCellRawValue(gtTd, meanPct.toFixed(2) + '%');
                                                var sourceKeyPct = (fields[pctColIdx] ? getFieldKey(fields[pctColIdx]) : compareColumnKey);
                                                setCellFormulaMapping(gtTd, {
                                                    ui_calc_type: 'grand-total',
                                                    ui_formula_operation: 'avg_percentage',
                                                    section_ref: 'grand_total',
                                                    sourceA: sourceKeyPct,
                                                    grand_total_ctc_aggregate: '1'
                                                });
                                                if (typeof window.tableDataDirty !== 'undefined') window.tableDataDirty = true;
                                                if (typeof window.performSaveTableData === 'function') {
                                                    if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                                    window.performSaveTableData({ onSuccess: function() { if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved'); } });
                                                    setTimeout(function() {
                                                        window.tableDataDirty = true;
                                                        window.performSaveTableData({ onSuccess: function() { if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved'); } });
                                                    }, 250);
                                                } else if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Grand total CTC average applied.');
                                                if (selectionPopover) selectionPopover.classList.add('hidden');
                                                clearSelectionVisualsOnly();
                                                selectedRowsMulti = [];
                                                lastClickedRowMulti = null;
                                                lastClickedCellMulti = null;
                                                if (typeof autoSelectBlueRowPercentageCellsForCtc === 'function') autoSelectBlueRowPercentageCellsForCtc();
                                                var gtRowAfter = gtTd.closest('tr.data-row');
                                                if (gtRowAfter && gtTd) {
                                                    setCellSelected(gtRowAfter, gtTd, true);
                                                    lastClickedRowMulti = gtRowAfter;
                                                    lastClickedCellMulti = gtTd;
                                                }
                                                if (typeof updateFormulaButtonState === 'function') updateFormulaButtonState();
                                                if (selectionPopover) selectionPopover.classList.add('hidden');
                                                return;
                                            }
                                        }
                                    }
                                    var valueColIndex = getFieldIndexByKeyFlexible(compareColumnKey);
                                    if (valueColIndex < 0 || valueColIndex >= fields.length) {
                                        var fallbackKey = resolveCompareColumnKeyForGrandTotalCtc(gtTd);
                                        valueColIndex = getFieldIndexByKeyFlexible(fallbackKey);
                                        compareColumnKey = fallbackKey || compareColumnKey;
                                    }
                                    if (valueColIndex < 0 || valueColIndex >= fields.length) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Invalid compare column for CTC.');
                                        return;
                                    }
                                    var blueRows = Array.prototype.slice.call(tableBody.querySelectorAll('tr.data-row.bg-blue-100'));
                                    if (blueRows.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'No blue summary rows found.');
                                        return;
                                    }
                                    var ctcs = [];
                                    latestCampusCompareEntries = [];
                                    var previewRowsHtml = [];
                                    blueRows.forEach(function(tr) {
                                        var tds = window.getRowTdCells(tr);
                                        var valueTd = tds[valueColIndex] || null;
                                        if (!valueTd) return;
                                        var actual = toNumeric(getCellRawValue(valueTd));
                                        var field = fields[valueColIndex] || {};
                                        var fieldKey = getFieldKey(field);
                                        var label = String(field.label || fieldKey || ('Column ' + (valueColIndex + 1)));
                                        var metricNorm = normalizeMetricText(fieldKey + '_' + label);
                                        var campusLabel = getSectionCampusLabelForRow(tr) || 'Campus';
                                        var campusTarget = resolveCampusTargetForRow(tr);
                                        if (!campusTarget || !campusTarget.values) return;
                                        var target = resolveTargetValueForSelectedMetric(campusTarget.values, metricNorm);
                                        var variance = target - actual;
                                        var rate = target > 0 ? ((actual / target) * 100) : 0.0;
                                        var status = statusFromCompare(variance, target, actual);
                                        ctcs.push(rate);
                                        var statusClass = status === 'ABOVE TARGET' ? 'text-emerald-700' : (status === 'MET TARGET' ? 'text-indigo-700' : (status === 'BELOW TARGET' ? 'text-amber-700' : 'text-gray-600'));
                                        latestCampusCompareEntries.push({
                                            row: tr,
                                            colIndex: valueColIndex,
                                            metricNorm: metricNorm,
                                            campusLabel: campusLabel,
                                            label: label,
                                            actual: actual,
                                            target: target,
                                            variance: variance,
                                            rate: rate,
                                            status: status
                                        });
                                        previewRowsHtml.push(
                                            '<tr>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + escapeHtml(campusLabel) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + escapeHtml(label) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + Number(target || 0).toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + Number(actual || 0).toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + Number(variance || 0).toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + Number(rate || 0).toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-left font-semibold ' + statusClass + '">' + escapeHtml(status) + '</td>' +
                                            '</tr>'
                                        );
                                    });
                                    if (ctcs.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'No campus targets or data for CTC aggregation.');
                                        return;
                                    }
                                    var allActualZero = latestCampusCompareEntries.length > 0 && latestCampusCompareEntries.every(function(e) { return Number(e.actual || 0) <= 0; });
                                    if (allActualZero) {
                                        var betterKey = resolveCompareColumnKeyForGrandTotalCtc(gtTd);
                                        var betterIdx = getFieldIndexByKeyFlexible(betterKey);
                                        if (betterKey && betterKey !== compareColumnKey && betterIdx >= 0 && betterIdx !== valueColIndex) {
                                            if (compareCampusTargetColumn) compareCampusTargetColumn.value = betterKey;
                                            if (typeof updateCompareCampusTargetValuePreview === 'function') updateCompareCampusTargetValuePreview();
                                            compareCampusTargetAggregateToGrandTotal(gtTd, betterKey, formatAsPercent);
                                            return;
                                        }
                                    }
                                    var compareFieldForSummary = fields[valueColIndex] || {};
                                    var compareLabelStr = String(compareFieldForSummary.label || getFieldKey(compareFieldForSummary) || compareColumnKey || '');
                                    var mean = ctcs.reduce(function(a, b) { return a + b; }, 0) / ctcs.length;
                                    var resultStr = formatAsPercent ? (mean.toFixed(2) + '%') : mean.toFixed(2);
                                    setCellRawValue(gtTd, resultStr);
                                    var gtMap = {
                                        ui_calc_type: 'grand-total',
                                        ui_formula_operation: formatAsPercent ? 'avg_percentage' : 'avg_number',
                                        section_ref: 'grand_total',
                                        sourceA: compareColumnKey,
                                        grand_total_ctc_aggregate: '1'
                                    };
                                    setCellFormulaMapping(gtTd, gtMap);
                                    if (typeof window.tableDataDirty !== 'undefined') window.tableDataDirty = true;
                                    if (campusTargetCompareContent) {
                                        var meanLabel = formatAsPercent ? (mean.toFixed(2) + '%') : mean.toFixed(2);
                                        campusTargetCompareContent.innerHTML =
                                            '<div class="mb-2 text-[11px] text-gray-600">Grand total (average of campus <span class="font-semibold">CTC %</span> across <span class="font-semibold">' + ctcs.length + '</span> blue row(s)). ' +
                                            'Value written to selected grand total cell: <span class="font-semibold text-emerald-800">' + escapeHtml(meanLabel) + '</span>.</div>' +
                                            '<div class="mb-2 text-[11px] text-gray-500">Compared using column <span class="font-semibold">' + escapeHtml(compareLabelStr) + '</span> (accomplishment) vs campus target.</div>' +
                                            '<table class="w-full border-collapse text-[11px]">' +
                                                '<thead><tr class="bg-gray-50">' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Campus</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Metric</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Target</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Accomplishment</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Variance</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Rate %</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Rating</th>' +
                                                '</tr></thead>' +
                                                '<tbody>' + previewRowsHtml.join('') + '</tbody>' +
                                            '</table>';
                                    }
                                    if (campusTargetComparePanel) {
                                        campusTargetComparePanel.classList.remove('hidden');
                                        if (typeof requestAnimationFrame === 'function') requestAnimationFrame(positionCampusTargetComparePanel);
                                    }
                                    if (typeof window.performSaveTableData === 'function') {
                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                        window.performSaveTableData({ onSuccess: function() { if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved'); } });
                                        setTimeout(function() {
                                            window.tableDataDirty = true;
                                            window.performSaveTableData({ onSuccess: function() { if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved'); } });
                                        }, 250);
                                    } else if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('notice', 'Grand total CTC average applied.');
                                    }
                                    if (selectionPopover) selectionPopover.classList.add('hidden');
                                    clearSelectionVisualsOnly();
                                    selectedRowsMulti = [];
                                    lastClickedRowMulti = null;
                                    lastClickedCellMulti = null;
                                    // CTC flow intentionally highlights blue-row % cells; clearSelectionVisualsOnly removed that - restore it after apply.
                                    if (typeof autoSelectBlueRowPercentageCellsForCtc === 'function') {
                                        autoSelectBlueRowPercentageCellsForCtc();
                                    }
                                    var gtRowAfter = gtTd.closest('tr.data-row');
                                    if (gtRowAfter && gtTd) {
                                        setCellSelected(gtRowAfter, gtTd, true);
                                        lastClickedRowMulti = gtRowAfter;
                                        lastClickedCellMulti = gtTd;
                                    }
                                    if (typeof updateFormulaButtonState === 'function') updateFormulaButtonState();
                                    if (selectionPopover) selectionPopover.classList.add('hidden');
                                }
                                function compareCampusTargetForSelectedBlueCells() {
                                    if (!tableBody) return;
                                    var columnKey = compareCampusTargetColumn ? (compareCampusTargetColumn.value || '').trim() : '';
                                    if (!columnKey) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select a column (value to compare) from the dropdown above.');
                                        return;
                                    }
                                    var valueColIndex = getFieldIndexByKeyFlexible(columnKey);
                                    if (valueColIndex < 0 || valueColIndex >= fields.length) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Invalid column selected.');
                                        return;
                                    }
                                    var selected = Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected'));
                                    var blueSelected = selected.filter(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return !!(tr && tr.classList.contains('bg-blue-100'));
                                    });
                                    if (blueSelected.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select a blue cell (where result will appear), then click Apply.');
                                        return;
                                    }
                                    latestCampusCompareEntries = [];
                                    pendingCompareCampusTargetResults = [];
                                    var rowsHtml = [];
                                    blueSelected.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return;
                                        var resultColIndex = getColIndex(td);
                                        if (resultColIndex < 0 || resultColIndex >= fields.length) return;
                                        var tds = window.getRowTdCells(tr);
                                        var valueTd = tds[valueColIndex] || null;
                                        if (!valueTd) return;
                                        var actual = toNumeric(getCellRawValue(valueTd));
                                        var field = fields[valueColIndex] || {};
                                        var fieldKey = getFieldKey(field);
                                        var label = String(field.label || fieldKey || ('Column ' + (valueColIndex + 1)));
                                        var metricNorm = normalizeMetricText(fieldKey + '_' + label);
                                        var safeLabel = escapeHtml(label);
                                        var campusLabel = getSectionCampusLabelForRow(tr) || 'Campus';
                                        var campusTarget = resolveCampusTargetForRow(tr);
                                        if (!campusTarget || !campusTarget.values) {
                                            rowsHtml.push(
                                                '<tr><td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + safeLabel + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-gray-500" colspan="5">No campus target found for this block.</td></tr>'
                                            );
                                            return;
                                        }
                                        var target = resolveTargetValueForSelectedMetric(campusTarget.values, metricNorm);
                                        var variance = target - actual;
                                        var rate = target > 0 ? ((actual / target) * 100) : 0.0;
                                        var status = statusFromCompare(variance, target, actual);
                                        // Write Result / Target only into the selected blue cell (e.g. SUMMARY), never into PROGRAM NAME or MAJOR NAME
                                        var quotient = target > 0 ? (actual / target) : 0;
                                        var resultStr = formatBlueSummaryPercentWhole(quotient * 100);
                                        setCellRawValue(td, resultStr);
                                        // Persist compare result on refresh: treat as manual override + mapping on this blue cell.
                                        setManualOverrideCell(td, true);
                                        var compareMap = {
                                            ui_calc_type: 'compare-campus-target',
                                            ui_formula_operation: 'compare-campus-target',
                                            sourceA: columnKey
                                        };
                                        var secRef = (typeof buildSectionRefFromRow === 'function') ? buildSectionRefFromRow(tr) : '';
                                        if (secRef) compareMap.section_ref = secRef;
                                        setCellFormulaMapping(td, compareMap);
                                        var resultFieldKey = getFieldKey(fields[resultColIndex] || {});
                                        var subId = tr.getAttribute('data-submission-id') || '';
                                        var uId = tr.getAttribute('data-user-id') || '';
                                        if (!subId || !uId) {
                                            var prev = tr.previousElementSibling;
                                            while (prev) {
                                                var pSub = prev.getAttribute ? prev.getAttribute('data-submission-id') : null;
                                                var pUser = prev.getAttribute ? prev.getAttribute('data-user-id') : null;
                                                if ((pSub && pSub !== '') || (pUser && pUser !== '')) { subId = pSub || ''; uId = pUser || ''; break; }
                                                prev = prev.previousElementSibling;
                                            }
                                        }
                                        pendingCompareCampusTargetResults.push({ submission_id: subId ? parseInt(subId, 10) : null, user_id: uId ? parseInt(uId, 10) : null, resultFieldKey: resultFieldKey, resultValue: resultStr });
                                        var statusClass = status === 'ABOVE TARGET'
                                            ? 'text-emerald-700'
                                            : (status === 'MET TARGET' ? 'text-indigo-700' : (status === 'BELOW TARGET' ? 'text-amber-700' : 'text-gray-600'));
                                        latestCampusCompareEntries.push({
                                            row: tr,
                                            colIndex: valueColIndex,
                                            metricNorm: metricNorm,
                                            campusLabel: campusLabel,
                                            label: label,
                                            actual: actual,
                                            target: target,
                                            variance: variance,
                                            rate: rate,
                                            status: status
                                        });
                                        rowsHtml.push(
                                            '<tr>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + safeLabel + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + actual.toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + target.toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + variance.toFixed(2) + '</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 text-right">' + String(Math.round(rate)) + '%</td>' +
                                                '<td class="px-2 py-1.5 border border-gray-200 font-semibold ' + statusClass + '">' + status + '</td>' +
                                            '</tr>'
                                        );
                                    });
                                    if (rowsHtml.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'No valid blue cells selected for comparison.');
                                        return;
                                    }
                                    var uniqueRows = [];
                                    latestCampusCompareEntries.forEach(function(entry) {
                                        if (entry && entry.row && uniqueRows.indexOf(entry.row) === -1) uniqueRows.push(entry.row);
                                    });
                                    var previewRowsHtml = latestCampusCompareEntries.map(function(entry) {
                                        var campusSafe = escapeHtml(entry.campusLabel || 'Campus');
                                        var metricSafe = escapeHtml(entry.label || '');
                                        var actual = Number(entry.actual || 0).toFixed(2);
                                        var target = Number(entry.target || 0).toFixed(2);
                                        var variance = Number(entry.variance || 0).toFixed(2);
                                        var rate = String(Math.round(Number(entry.rate || 0)));
                                        var status = escapeHtml(entry.status || 'NO TARGET');
                                        var statusClass = 'text-gray-700';
                                        if (entry.status === 'ABOVE TARGET') statusClass = 'text-emerald-700';
                                        else if (entry.status === 'MET TARGET') statusClass = 'text-indigo-700';
                                        else if (entry.status === 'BELOW TARGET') statusClass = 'text-amber-700';
                                        return '<tr>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + campusSafe + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-gray-700">' + metricSafe + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-right">' + target + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-right">' + actual + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-right">' + variance + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-right">' + rate + '</td>' +
                                            '<td class="px-2 py-1.5 border border-gray-200 text-left font-semibold ' + statusClass + '">' + status + '</td>' +
                                        '</tr>';
                                    }).join('');
                                    if (campusTargetCompareContent) {
                                        var campusesShown = [];
                                        latestCampusCompareEntries.forEach(function(e) { if (e && e.campusLabel && campusesShown.indexOf(e.campusLabel) === -1) campusesShown.push(e.campusLabel); });
                                        var campusDisplay = campusesShown.length > 0 ? (' for ' + campusesShown.map(function(c) { return '<span class="font-semibold text-gray-800">' + escapeHtml(c) + '</span>'; }).join(', ')) : '';
                                        campusTargetCompareContent.innerHTML =
                                            '<div class="mb-2 text-[11px] text-gray-600">Compared <span class="font-semibold text-gray-800">' + uniqueRows.length + '</span> blue row(s)' + campusDisplay + '. <span class="text-emerald-700 font-medium">Result \u00F7 Target written to selected cells.</span></div>' +
                                            '<div class="mb-2 flex items-center justify-between">' +
                                                '<span class="text-[11px] text-gray-500">Balance scorecard preview (per campus)</span>' +
                                            '</div>' +
                                            '<table class="w-full border-collapse text-[11px]">' +
                                                '<thead><tr class="bg-gray-50">' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Campus</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Metric</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Target</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Accomplishment</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Variance</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-right">Rate</th>' +
                                                    '<th class="px-2 py-1.5 border border-gray-200 text-left">Rating</th>' +
                                                '</tr></thead>' +
                                                '<tbody>' + previewRowsHtml + '</tbody>' +
                                            '</table>';
                                    }
                                    if (campusTargetComparePanel) {
                                        campusTargetComparePanel.classList.remove('hidden');
                                        requestAnimationFrame(positionCampusTargetComparePanel);
                                    }
                                    // Trigger save immediately; pendingCompareCampusTargetResults patches payload so result persists on reload
                                    if (typeof window.tableDataDirty !== 'undefined') window.tableDataDirty = true;
                                    if (typeof window.performSaveTableData === 'function') {
                                        if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                        window.performSaveTableData({ onSuccess: function() {
                                            if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved');
                                        } });
                                        setTimeout(function() {
                                            window.tableDataDirty = true;
                                            window.performSaveTableData({ onSuccess: function() {
                                                if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saved');
                                            } });
                                        }, 250);
                                    }
                                    if (typeof window.showToast === 'function') {
                                        window.showToast('notice', 'Compare applied: Result \u00F7 Target written to ' + latestCampusCompareEntries.length + ' selected cell(s).');
                                    }
                                    // Hide selection outlines after opening compare panel to reduce visual noise.
                                    clearSelectionVisualsOnly();
                                }
                                var perfColumnMapCache = null;
                                function getPerformanceColumnMap() {
                                    if (perfColumnMapCache) return perfColumnMapCache;
                                    var map = {
                                        targetTotal: -1,
                                        accompTotal: -1,
                                        variance: -1,
                                        rate: -1,
                                        rating: -1,
                                        targetQ: {1: -1, 2: -1, 3: -1, 4: -1},
                                        accompQ: {1: -1, 2: -1, 3: -1, 4: -1},
                                    };
                                    fields.forEach(function(field, idx) {
                                        var key = getFieldKey(field);
                                        var label = field && field.label ? field.label : '';
                                        var n = normalizeMetricText(key + '_' + label);
                                        var q = detectQuarterFromMetric(n);
                                        var isTarget = /target/.test(n);
                                        var isAccomp = /accomp|accomplishment|actual|achievement/.test(n);
                                        var isTotal = /total/.test(n);
                                        if (/variance/.test(n)) map.variance = idx;
                                        if (/rate/.test(n) && /accomp|accomplishment/.test(n)) map.rate = idx;
                                        if (/descriptive/.test(n) || /rating/.test(n)) map.rating = idx;
                                        if (isTarget && q) map.targetQ[q] = idx;
                                        if (isAccomp && q) map.accompQ[q] = idx;
                                        if (isTarget && isTotal) map.targetTotal = idx;
                                        if (isAccomp && isTotal) map.accompTotal = idx;
                                    });
                                    perfColumnMapCache = map;
                                    return map;
                                }
                                function getPreferredAccomplishmentTargetKey() {
                                    var map = getPerformanceColumnMap();
                                    if (map.accompTotal >= 0 && map.accompTotal < fields.length) {
                                        return getFieldKey(fields[map.accompTotal]);
                                    }
                                    for (var q = 1; q <= 4; q++) {
                                        if (map.accompQ[q] >= 0 && map.accompQ[q] < fields.length) {
                                            return getFieldKey(fields[map.accompQ[q]]);
                                        }
                                    }
                                    return '';
                                }
                                function recomputeBlueRowPerformance(row, opts) {
                                    if (!row || !row.classList.contains('bg-blue-100')) return;
                                    opts = opts || {};
                                    var map = getPerformanceColumnMap();
                                    var tds = window.getRowTdCells(row);
                                    if (!tds || tds.length === 0) return;
                                    var sumQuarters = function(qMap) {
                                        var total = 0.0;
                                        var hasAny = false;
                                        [1, 2, 3, 4].forEach(function(q) {
                                            var idx = qMap[q];
                                            if (idx >= 0 && idx < tds.length) {
                                                var raw = getCellRawValue(tds[idx]);
                                                if (String(raw).trim() !== '') hasAny = true;
                                                total += toNumeric(raw);
                                            }
                                        });
                                        return { total: total, hasAny: hasAny };
                                    };
                                    var targetTotal = 0.0;
                                    if (map.targetTotal >= 0 && map.targetTotal < tds.length) {
                                        targetTotal = toNumeric(getCellRawValue(tds[map.targetTotal]));
                                    } else {
                                        targetTotal = sumQuarters(map.targetQ).total;
                                    }
                                    var accompFromQs = sumQuarters(map.accompQ);
                                    var accompTotal = 0.0;
                                    if (map.accompTotal >= 0 && map.accompTotal < tds.length) {
                                        var accompTd = tds[map.accompTotal];
                                        var currentAccompTotal = toNumeric(getCellRawValue(accompTd));
                                        // Quarter-driven sync is for plain total cells only; do not replace column sums / aggregate_chain / etc.
                                        var quarterDrivenAccomp = accompFromQs.hasAny && !cellHasNonPerformanceAutoFormula(accompTd);
                                        accompTotal = quarterDrivenAccomp ? accompFromQs.total : currentAccompTotal;
                                        if (quarterDrivenAccomp && !opts.skipAccompTotalCell) {
                                            setCellRawValue(accompTd, accompTotal.toFixed(2));
                                        }
                                    } else {
                                        accompTotal = accompFromQs.total;
                                    }
                                    if (map.variance >= 0 && map.variance < tds.length && !cellHasNonPerformanceAutoFormula(tds[map.variance])) {
                                        var variance = targetTotal - accompTotal;
                                        setCellRawValue(tds[map.variance], variance.toFixed(2));
                                    }
                                    if (map.rate >= 0 && map.rate < tds.length && !cellHasNonPerformanceAutoFormula(tds[map.rate])) {
                                        var rate = targetTotal > 0 ? ((accompTotal / targetTotal) * 100) : 0.0;
                                        setCellRawValue(tds[map.rate], formatBlueSummaryPercentWhole(rate));
                                    }
                                    if (map.rating >= 0 && map.rating < tds.length && !cellHasNonPerformanceAutoFormula(tds[map.rating])) {
                                        var rating = 'NO TARGET';
                                        if (targetTotal > 0 && accompTotal <= 0) rating = 'NO ACCOMPLISHMENT';
                                        else if (targetTotal > 0 && accompTotal < targetTotal) rating = 'BELOW TARGET';
                                        else if (targetTotal > 0 && Math.abs(accompTotal - targetTotal) < 0.0001) rating = 'MET TARGET';
                                        else if (targetTotal > 0 && accompTotal > targetTotal) rating = 'ABOVE TARGET';
                                        setCellRawValue(tds[map.rating], rating);
                                    }
                                }
                                function cellUsesBlueRowSameRowFormula(td) {
                                    if (!td) return false;
                                    var m = getCellFormulaMapping(td);
                                    if (m) {
                                        var ui = String(m.ui_calc_type || '').trim();
                                        if (ui === 'blue-row-formula' || ui === 'blue-row-formula-multi' || ui === 'blue-row-formula-custom') return true;
                                    }
                                    // DOM attrs are present before _formulaMapping is always populated; avoid performance row overwriting manual % formulas on first paint.
                                    var uiAttr = String(td.getAttribute && td.getAttribute('data-formula-ui-calc-type') || '').trim();
                                    return uiAttr === 'blue-row-formula' || uiAttr === 'blue-row-formula-multi' || uiAttr === 'blue-row-formula-custom';
                                }
                                /** Cells with autocalc / aggregate formulas must not be overwritten by getPerformanceColumnMap() (avoids wrong totals and column shifts). */
                                function cellHasNonPerformanceAutoFormula(td) {
                                    if (!td) return false;
                                    if (cellUsesBlueRowSameRowFormula(td)) return true;
                                    var uiAttr = String(td.getAttribute && td.getAttribute('data-formula-ui-calc-type') || '').trim();
                                    var aggAttr = ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total', 'aggregate_chain', 'grand-total'];
                                    if (aggAttr.indexOf(uiAttr) !== -1) return true;
                                    var m = getCellFormulaMapping(td);
                                    if (!m) return false;
                                    var ui = String(m.ui_calc_type || '').trim();
                                    var op = String(m.ui_formula_operation || m.operation || '').trim();
                                    if (ui === 'aggregate_chain' || ui === 'grand-total') return true;
                                    var agg = ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total'];
                                    if (agg.indexOf(ui) !== -1 || agg.indexOf(op) !== -1) return true;
                                    return false;
                                }
                                function reapplyBlueRowSameRowFormulaFromMapping(blueRow, blueTd, mapping) {
                                    if (!blueRow || !blueTd || !mapping) return;
                                    mapping = enrichMappingWithTemplateRowScope(blueTd, blueRow, mapping) || mapping;
                                    var tds = window.getRowTdCells(blueRow);
                                    if (!tds || tds.length === 0) return;
                                    if (String(blueRow.getAttribute('data-manual-total-row') || '') === '1' && mapping && mapping.manual_total_from_all_blues) {
                                        var otherRowsR = otherCampusBlueRowsExcludingManualTotal();
                                        if (!otherRowsR.length) return;
                                        var uiCalcMt = String(mapping.ui_calc_type || '').trim();
                                        var opMt = String(mapping.ui_formula_operation || mapping.operation || '').trim();
                                        var resMt;
                                        var multiCustMt = (opMt === 'custom' || (typeof opMt === 'string' && opMt.indexOf('custom:') === 0)) ? (mapping.custom_expr || (typeof opMt === 'string' && opMt.indexOf('custom:') === 0 ? opMt.substring(7) : '')) : '';
                                        if (uiCalcMt === 'blue-row-formula-multi') {
                                            var sourceKeysR = Array.isArray(mapping.source_keys) && mapping.source_keys.length > 0 ? mapping.source_keys.slice() : [];
                                            if (sourceKeysR.length === 0) {
                                                if (mapping.sourceA) sourceKeysR.push(mapping.sourceA);
                                                if (mapping.sourceB) sourceKeysR.push(mapping.sourceB);
                                            }
                                            if (sourceKeysR.length === 0) return;
                                            var saMt = mapping.sourceA || sourceKeysR[0] || '';
                                            var sbMt = mapping.sourceB || sourceKeysR[1] || '';
                                            resMt = evalBlueMultiOpAcrossOtherCampusRows(otherRowsR, opMt, sourceKeysR, saMt, sbMt, multiCustMt);
                                        } else if (uiCalcMt === 'blue-row-formula-custom') {
                                            var ceMt = mapping.custom_expr || '';
                                            var sAMt = mapping.sourceA || '';
                                            var sBMt = mapping.sourceB || '';
                                            if (!ceMt || !sAMt) return;
                                            resMt = evalBlueSimpleOrCustomAcrossOtherCampusRows(otherRowsR, 'custom', sAMt, sBMt, ceMt);
                                        } else if (uiCalcMt === 'blue-row-formula') {
                                            var sAF = mapping.sourceA || '';
                                            var sBF = mapping.sourceB || '';
                                            var ceF2 = (opMt === 'custom' || (typeof opMt === 'string' && opMt.indexOf('custom:') === 0)) ? (mapping.custom_expr || (typeof opMt === 'string' && opMt.indexOf('custom:') === 0 ? opMt.substring(7) : '')) : '';
                                            if (!sAF || !opMt) return;
                                            resMt = evalBlueSimpleOrCustomAcrossOtherCampusRows(otherRowsR, opMt, sAF, sBF, ceF2);
                                        } else {
                                            return;
                                        }
                                        if (isNaN(resMt)) return;
                                        var pctKey = mapping.custom_expr || multiCustMt;
                                        var rStrMt = isPercentOperation(opMt, pctKey) ? formatBlueSummaryPercentWhole(resMt) : resMt.toFixed(2);
                                        setCellRawValue(blueTd, rStrMt);
                                        return;
                                    }
                                    var uiCalc = String(mapping.ui_calc_type || '').trim();
                                    var op = String(mapping.ui_formula_operation || mapping.operation || '').trim();
                                    var safeEvalCustom = function(e, a, b) {
                                        var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                        if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                        try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                    };
                                    var safeEvalMulti = function(e, vals) {
                                        var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/');
                                        var letters = 'ABCDEFGHIJ';
                                        for (var vi = 0; vi < vals.length && vi < letters.length; vi++) {
                                            var re = new RegExp('\\b' + letters[vi] + '\\b', 'g');
                                            s = s.replace(re, String(vals[vi]));
                                        }
                                        if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                        try { return (new Function('return (' + s + ')'))(); } catch (err2) { return NaN; }
                                    };
                                    if (uiCalc === 'blue-row-formula-custom') {
                                        var sA = mapping.sourceA || '';
                                        var sB = mapping.sourceB || '';
                                        var customExpr = mapping.custom_expr || '';
                                        var idxA = getFieldIndexByKeyFlexible(sA);
                                        var idxB = sB ? getFieldIndexByKeyFlexible(sB) : -1;
                                        if (idxA < 0 || !customExpr) return;
                                        var valA = toNumeric(getCellRawValue(tds[idxA]));
                                        var valB = idxB >= 0 ? toNumeric(getCellRawValue(tds[idxB])) : 0;
                                        var result = safeEvalCustom(customExpr, valA, valB);
                                        if (isNaN(result)) return;
                                        var resultStr = isPercentOperation('custom', customExpr) ? formatBlueSummaryPercentWhole(result) : result.toFixed(2);
                                        setCellRawValue(blueTd, resultStr);
                                        return;
                                    }
                                    if (uiCalc === 'blue-row-formula') {
                                        var sourceAKey = mapping.sourceA || '';
                                        var sourceBKey = mapping.sourceB || '';
                                        if (!sourceAKey || !op) return;
                                        if ((op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent') && !sourceBKey) return;
                                        var idxAf = getFieldIndexByKeyFlexible(sourceAKey);
                                        var idxBf = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                        if (idxAf < 0) return;
                                        var valAf = toNumeric(getCellRawValue(tds[idxAf]));
                                        var valBf = idxBf >= 0 ? toNumeric(getCellRawValue(tds[idxBf])) : 0;
                                        var resF = 0;
                                        if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                            var customExprForOp = mapping.custom_expr || (typeof op === 'string' && op.indexOf('custom:') === 0 ? op.substring(7) : '');
                                            if (!customExprForOp) return;
                                            resF = safeEvalCustom(customExprForOp, valAf, valBf);
                                        } else {
                                            switch (op) {
                                                case 'sum': resF = valAf + valBf; break;
                                                case 'subtract': resF = valAf - valBf; break;
                                                case 'multiply': resF = valAf * valBf; break;
                                                case 'divide': resF = valBf !== 0 ? (valAf / valBf) : 0; break;
                                                case 'percent_of': resF = valBf !== 0 ? ((valAf / valBf) * 100) : 0; break;
                                                case 'sum_over_b_percent': resF = valBf !== 0 ? (((valAf + valBf) / valBf) * 100) : 0; break;
                                                case 'diff_over_b_percent': resF = valBf !== 0 ? (((valAf - valBf) / valBf) * 100) : 0; break;
                                                default: resF = valAf + valBf;
                                            }
                                        }
                                        if (isNaN(resF)) return;
                                        var ceF = mapping.custom_expr || '';
                                        var resultStrF = isPercentOperation(op, ceF) ? formatBlueSummaryPercentWhole(resF) : resF.toFixed(2);
                                        setCellRawValue(blueTd, resultStrF);
                                        return;
                                    }
                                    if (uiCalc === 'blue-row-formula-multi') {
                                        var sourceKeys = Array.isArray(mapping.source_keys) && mapping.source_keys.length > 0 ? mapping.source_keys.slice() : [];
                                        if (sourceKeys.length === 0) {
                                            if (mapping.sourceA) sourceKeys.push(mapping.sourceA);
                                            if (mapping.sourceB) sourceKeys.push(mapping.sourceB);
                                        }
                                        if (sourceKeys.length === 0) return;
                                        var multiCustomExpr = (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? (mapping.custom_expr || (typeof op === 'string' && op.indexOf('custom:') === 0 ? op.substring(7) : '')) : '';
                                        if ((op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) && !String(multiCustomExpr).trim()) return;
                                        var multiOpRequiresB = (op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent');
                                        var sourceAKeyM = mapping.sourceA || sourceKeys[0] || '';
                                        var sourceBKeyM = mapping.sourceB || sourceKeys[1] || '';
                                        if (multiOpRequiresB && !sourceBKeyM) return;
                                        var useABOnlyMulti = op !== 'sum' && op !== 'avg';
                                        var resultM;
                                        if (useABOnlyMulti) {
                                            var idxAMulti = getFieldIndexByKeyFlexible(sourceAKeyM);
                                            var idxBMulti = sourceBKeyM ? getFieldIndexByKeyFlexible(sourceBKeyM) : -1;
                                            if (idxAMulti < 0 || (multiOpRequiresB && idxBMulti < 0)) return;
                                            var sourceIdxs = sourceKeys.map(function(k) { return getFieldIndexByKeyFlexible(k); });
                                            if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                var valsM = sourceIdxs.map(function(idx) { return idx >= 0 && tds[idx] ? toNumeric(getCellRawValue(tds[idx])) : 0; });
                                                resultM = safeEvalMulti(multiCustomExpr, valsM);
                                            } else {
                                                var valAm = toNumeric(getCellRawValue(tds[idxAMulti]));
                                                var valBm = idxBMulti >= 0 ? toNumeric(getCellRawValue(tds[idxBMulti])) : 0;
                                                switch (op) {
                                                    case 'subtract': resultM = valAm - valBm; break;
                                                    case 'multiply': resultM = valAm * valBm; break;
                                                    case 'divide': resultM = valBm !== 0 ? (valAm / valBm) : 0; break;
                                                    case 'percent_of': resultM = valBm !== 0 ? ((valAm / valBm) * 100) : 0; break;
                                                    case 'sum_over_b_percent': resultM = valBm !== 0 ? (((valAm + valBm) / valBm) * 100) : 0; break;
                                                    case 'diff_over_b_percent': resultM = valBm !== 0 ? (((valAm - valBm) / valBm) * 100) : 0; break;
                                                    default: resultM = valAm + valBm;
                                                }
                                            }
                                        } else {
                                            var totalM = 0;
                                            var countM = 0;
                                            sourceKeys.forEach(function(key) {
                                                var idx = getFieldIndexByKeyFlexible(key);
                                                if (idx >= 0 && tds[idx]) {
                                                    totalM += toNumeric(getCellRawValue(tds[idx]));
                                                    countM++;
                                                }
                                            });
                                            resultM = op === 'avg' ? (countM > 0 ? totalM / countM : 0) : totalM;
                                        }
                                        var resultStrM;
                                        if (isPercentOperation(op, multiCustomExpr)) {
                                            resultStrM = isNaN(resultM) ? '0%' : formatBlueSummaryPercentWhole(resultM);
                                        } else {
                                            resultStrM = isNaN(resultM) ? '0.00' : resultM.toFixed(2);
                                        }
                                        setCellRawValue(blueTd, resultStrM);
                                    }
                                }
                                function recomputeBlueRowFormulasInSection(sectionRows) {
                                    if (!tableBody || !sectionRows || sectionRows.length === 0) return;
                                    var sectionRowsSet = {};
                                    sectionRows.forEach(function(tr) { sectionRowsSet[tr] = true; });
                                    // Same non-blue row ordering as apply-autocalc row_indices (not isPlainAggregatable-only).
                                    var dataRowsInSection = sectionRows.filter(function(tr) {
                                        if (String(tr.getAttribute('data-manual-total-row') || '') === '1') return false;
                                        return !tr.classList.contains('bg-blue-100');
                                    });
                                    var blueRowsInSection = sectionRows.filter(function(tr) {
                                        if (String(tr.getAttribute('data-manual-total-row') || '') === '1') return true;
                                        return tr.classList.contains('bg-blue-100');
                                    });
                                    function isBlankishValue(v) {
                                        var s = String(v == null ? '' : v).trim();
                                        if (s === '' || s === '-' || s === '-') return true;
                                        if (/^0+(\.0+)?%?$/.test(s)) return true;
                                        return false;
                                    }
                                    function rowHasMeaningfulInput(tr) {
                                        if (!tr || !isPlainAggregatableDataRow(tr)) return false;
                                        var tdsLocal = window.getRowTdCells(tr);
                                        for (var iLocal = 0; iLocal < tdsLocal.length; iLocal++) {
                                            var tdLocal = tdsLocal[iLocal];
                                            if (!tdLocal) continue;
                                            var inputLocal = tdLocal.querySelector('input, select, textarea');
                                            var rawLocal = '';
                                            if (inputLocal) {
                                                if (inputLocal.tagName === 'SELECT') {
                                                    var optLocal = inputLocal.options[inputLocal.selectedIndex];
                                                    rawLocal = (optLocal ? optLocal.value : '') || '';
                                                } else {
                                                    rawLocal = inputLocal.value || '';
                                                }
                                            } else {
                                                rawLocal = getCellRawValue(tdLocal);
                                            }
                                            if (!isBlankishValue(rawLocal)) return true;
                                        }
                                        return false;
                                    }
                                    var sectionHasData = dataRowsInSection.some(function(tr) { return rowHasMeaningfulInput(tr); });
                                    if (!sectionHasData) {
                                        // Keep new/empty sections visually clean: do not show stale formula totals until data is entered.
                                        blueRowsInSection.forEach(function(blueRow) {
                                            var blueTds = window.getRowTdCells(blueRow);
                                            for (var bi = 0; bi < fields.length && bi < blueTds.length; bi++) {
                                                var blueTd = blueTds[bi];
                                                if (!blueTd) continue;
                                                if (blueTd.classList.contains('manual-override') || blueTd.getAttribute('data-manual-override') === '1') continue;
                                                if (bi === 0) {
                                                    setCellRawValue(blueTd, '');
                                                    continue;
                                                }
                                                if (getCellFormulaMapping(blueTd)) setCellRawValue(blueTd, '');
                                            }
                                        });
                                        return;
                                    }
                                    blueRowsInSection.forEach(function(blueRow) {
                                        var tds = window.getRowTdCells(blueRow);
                                        for (var c = 0; c < fields.length && c < tds.length; c++) {
                                            var blueTd = tds[c];
                                            var mapping = getCellFormulaMapping(blueTd);
                                            if (!mapping) continue;
                                            mapping = enrichMappingWithTemplateRowScope(blueTd, blueRow, mapping);
                                            if (blueTd.classList.contains('manual-override') || blueTd.getAttribute('data-manual-override') === '1') continue;
                                            var op = String(mapping.ui_formula_operation || mapping.operation || '').trim();
                                            var uiCalc = String(mapping.ui_calc_type || '').trim();
                                            if (uiCalc === 'aggregate_chain') {
                                                var acNum = computeAggregateChainNumeric(mapping, dataRowsInSection);
                                                if (acNum !== null && !isNaN(acNum)) {
                                                    setCellRawValue(blueTd, acNum.toFixed(2));
                                                }
                                                continue;
                                            }
                                            var isSimpleOp = ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total'].indexOf(op) !== -1
                                                || ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total'].indexOf(uiCalc) !== -1;
                                            if (!isSimpleOp && (uiCalc === 'blue-row-formula' || uiCalc === 'blue-row-formula-multi' || uiCalc === 'blue-row-formula-custom')) continue;
                                            var calcKey = op || uiCalc;
                                            if (!calcKey) continue;
                                            var sourceCols = Array.isArray(mapping.source_columns) && mapping.source_columns.length > 0
                                                ? mapping.source_columns
                                                : (mapping.sourceA ? [mapping.sourceA] : []);
                                            if (sourceCols.length === 0) continue;
                                            var useAllCols = (calcKey === 'countif' || calcKey === 'count_total') && sourceCols.length > 1;
                                            var colIndices = sourceCols.map(function(k) { return getFieldIndexByKeyFlexible(k); }).filter(function(i) { return i >= 0 && i < fields.length; });
                                            if (colIndices.length === 0) continue;
                                            var rowIndices = Array.isArray(mapping.row_indices) ? mapping.row_indices : [];
                                            var rowUids = Array.isArray(mapping.row_uids) ? mapping.row_uids : [];
                                            var hasRowScope = rowIndices.length > 0 || rowUids.length > 0;
                                            var sourceCells = [];
                                            dataRowsInSection.forEach(function(tr, idx) {
                                                if (!isPlainAggregatableDataRow(tr)) return;
                                                if (rowIndices.length > 0 && rowIndices.indexOf(idx) === -1) return;
                                                if (rowUids.length > 0) {
                                                    var uid = (tr.getAttribute('data-row-uid') || '').trim();
                                                    if (!uid || rowUids.indexOf(uid) === -1) return;
                                                }
                                                var tdsR = window.getRowTdCells(tr);
                                                if (useAllCols) {
                                                    colIndices.forEach(function(colIdx) {
                                                        var cell = tdsR[colIdx];
                                                        if (cell) sourceCells.push(cell);
                                                    });
                                                } else {
                                                    var cell = tdsR[colIndices[0]];
                                                    if (cell) sourceCells.push(cell);
                                                }
                                            });
                                            if (sourceCells.length === 0 && dataRowsInSection.length > 0 && !hasRowScope) {
                                                dataRowsInSection.forEach(function(tr) {
                                                    if (!isPlainAggregatableDataRow(tr)) return;
                                                    var tdsR = window.getRowTdCells(tr);
                                                    if (useAllCols) {
                                                        colIndices.forEach(function(colIdx) {
                                                            var cell = tdsR[colIdx];
                                                            if (cell) sourceCells.push(cell);
                                                        });
                                                    } else {
                                                        var cell = tdsR[colIndices[0]];
                                                        if (cell) sourceCells.push(cell);
                                                    }
                                                });
                                            }
                                            if (hasRowScope && sourceCells.length === 0) continue;
                                            var values = [];
                                            sourceCells.forEach(function(td) {
                                                var input = td.querySelector('input, select, textarea');
                                                if (input) {
                                                    if (input.tagName === 'SELECT') {
                                                        var opt = input.options[input.selectedIndex];
                                                        values.push(((opt ? opt.value : '') || '').toString().trim());
                                                    } else values.push((input.value || '').toString().trim());
                                                } else {
                                                    var span = td.querySelector('span');
                                                    values.push((span ? span.textContent : td.textContent || '').toString().trim());
                                                }
                                            });
                                            var resultStr = '';
                                            if (calcKey === 'unique' || calcKey === 'count_unique' || calcKey === 'unique_adjust') {
                                                var seen = {};
                                                values.forEach(function(v) { var n = (v || '').toString().trim().toLowerCase(); if (n !== '' && !seen[n]) seen[n] = true; });
                                                var baseCnt = Object.keys(seen).length;
                                                var adjCnt = 0;
                                                if (uiCalc === 'unique_adjust' || calcKey === 'unique_adjust') {
                                                    if (mapping.count_adjust !== undefined && mapping.count_adjust !== null && String(mapping.count_adjust).trim() !== '') {
                                                        var ap = parseInt(String(mapping.count_adjust), 10);
                                                        adjCnt = isNaN(ap) ? 0 : ap;
                                                    }
                                                }
                                                resultStr = String(Math.max(0, baseCnt + adjCnt));
                                            } else if (calcKey === 'countif' || calcKey === 'count_total') {
                                                resultStr = String(values.filter(function(v) { return (v || '').toString().trim() !== '' && (v || '').toString().trim() !== '0'; }).length);
                                            } else if (calcKey === 'count_rows') {
                                                var uniqueRows = [];
                                                sourceCells.forEach(function(td) {
                                                    var tr = td.closest('tr.data-row');
                                                    if (tr && uniqueRows.indexOf(tr) === -1) uniqueRows.push(tr);
                                                });
                                                resultStr = String(uniqueRows.length);
                                            } else if (['sum', 'avg', 'avg_number', 'avg_percentage'].indexOf(calcKey) !== -1) {
                                                var nums = values.map(function(v) { return toNumeric(v); });
                                                var total = nums.reduce(function(a, b) { return a + b; }, 0);
                                                var isAvg = ['avg', 'avg_number', 'avg_percentage'].indexOf(calcKey) !== -1;
                                                if (calcKey === 'avg_percentage') {
                                                    var meanBp = nums.length ? total / nums.length : 0;
                                                    resultStr = formatBlueSummaryPercentWhole(meanBp);
                                                } else {
                                                    resultStr = isAvg ? (nums.length ? (total / nums.length).toFixed(2) : '0.00') : total.toFixed(2);
                                                }
                                            } else continue;
                                            setCellRawValue(blueTd, resultStr);
                                        }
                                        for (var c2 = 0; c2 < fields.length && c2 < tds.length; c2++) {
                                            var blueTd2 = tds[c2];
                                            var map2 = getCellFormulaMapping(blueTd2);
                                            if (!map2) continue;
                                            if (blueTd2.classList.contains('manual-override') || blueTd2.getAttribute('data-manual-override') === '1') continue;
                                            var ui2 = String(map2.ui_calc_type || '').trim();
                                            if (ui2 !== 'blue-row-formula' && ui2 !== 'blue-row-formula-multi' && ui2 !== 'blue-row-formula-custom') continue;
                                            reapplyBlueRowSameRowFormulaFromMapping(blueRow, blueTd2, map2);
                                        }
                                        recomputeBlueRowPerformance(blueRow);
                                    });
                                }
                                function recomputeAllBlueSections() {
                                    if (!tableBody) return;
                                    buildTableBodySections().forEach(function(sr) {
                                        recomputeBlueRowFormulasInSection(sr);
                                    });
                                }
                                var BLUE_ROW_SNAPSHOT_KEY = 'sa_blue_row_snapshot_v2_' + String({{ (int) $template->id }});
                                function saveBlueRowSnapshotLocal() {
                                    if (!tableBody || !fields || fields.length === 0) return;
                                    var snap = {};
                                    var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                    blueRows.forEach(function(blueRow) {
                                        var sectionRef = buildSectionRefFromRow(blueRow);
                                        if (!sectionRef) return;
                                        var tds = window.getRowTdCells(blueRow);
                                        var perRow = {};
                                        for (var c = 0; c < fields.length && c < tds.length; c++) {
                                            var key = getFieldKey(fields[c]);
                                            if (!key) continue;
                                            var raw = String(getCellRawValue(tds[c]) || '').trim();
                                            perRow[key] = raw;
                                        }
                                        snap[sectionRef] = perRow;
                                    });
                                    try { localStorage.setItem(BLUE_ROW_SNAPSHOT_KEY, JSON.stringify(snap)); } catch (e) {}
                                }
                                function restoreBlueRowSnapshotLocal() {
                                    if (!tableBody || !fields || fields.length === 0) return;
                                    var snapRaw = null;
                                    try { snapRaw = localStorage.getItem(BLUE_ROW_SNAPSHOT_KEY); } catch (e) {}
                                    if (!snapRaw) return;
                                    var snap = null;
                                    try { snap = JSON.parse(snapRaw); } catch (e2) { snap = null; }
                                    if (!snap || typeof snap !== 'object') return;
                                    var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                    blueRows.forEach(function(blueRow) {
                                        var sectionRef = buildSectionRefFromRow(blueRow);
                                        var perRow = sectionRef ? snap[sectionRef] : null;
                                        if (!perRow || typeof perRow !== 'object') return;
                                        var tds = window.getRowTdCells(blueRow);
                                        for (var c = 0; c < fields.length && c < tds.length; c++) {
                                            var td = tds[c];
                                            var key = getFieldKey(fields[c]);
                                            if (!key) continue;
                                            if (!(key in perRow)) continue;
                                            // Prevent stale local snapshot from populating brand-new/blank sections.
                                            // Only restore where the cell is actually formula-driven or manually overridden.
                                            var hasMapping = !!getCellFormulaMapping(td);
                                            var isManualOverride = td && (td.classList.contains('manual-override') || td.getAttribute('data-manual-override') === '1');
                                            if (!hasMapping && !isManualOverride) continue;
                                            var current = String(getCellRawValue(td) || '').trim();
                                            var saved = String(perRow[key] || '').trim();
                                            if ((current === '' || current === '-') && saved !== '' && saved !== '-') {
                                                setCellRawValue(td, saved);
                                            }
                                        }
                                    });
                                }
                                function hydrateBlueFormulaMappingsFromTemplate() {
                                    if (!tableBody || !fields || fields.length === 0) return;
                                    var blueRows = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                    blueRows.forEach(function(blueRow) {
                                        var tds = window.getRowTdCells(blueRow);
                                        for (var c = 0; c < fields.length && c < tds.length; c++) {
                                            var td = tds[c];
                                            var existingHydrate = getCellFormulaMapping(td);
                                            if (existingHydrate) {
                                                enrichMappingWithTemplateRowScope(td, blueRow, existingHydrate);
                                                continue;
                                            }
                                            var targetKey = getFieldKey(fields[c]);
                                            if (!targetKey) continue;
                                            var saved = findSummaryOutputByTarget(targetKey, blueRow);
                                            if (!saved) continue;
                                            var uiCalc = String(saved.ui_calc_type || '').trim();
                                            var uiOp = String(saved.ui_formula_operation || saved.operation || '').trim();
                                            var isSimple = ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total'].indexOf(uiCalc) !== -1
                                                || ['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows', 'count_unique', 'count_total'].indexOf(uiOp) !== -1
                                                || uiCalc === 'blue-row-formula' || uiCalc === 'blue-row-formula-multi' || uiCalc === 'blue-row-formula-custom';
                                            if (!isSimple) continue;
                                            setCellFormulaMapping(td, saved);
                                        }
                                    });
                                }
                                /** Grand total: sum or average every numeric source cell (quarter rows, manual picks, or scoped blue summaries). */
                                function aggregateGrandTotalNumericSourceValues(sourceCells, op) {
                                    op = String(op || 'sum').trim();
                                    if (!sourceCells || sourceCells.length === 0) return null;
                                    var nums = [];
                                    for (var i = 0; i < sourceCells.length; i++) {
                                        var n = toNumeric(getCellRawValue(sourceCells[i]));
                                        if (!isNaN(n)) nums.push(n);
                                    }
                                    if (nums.length === 0) return null;
                                    var total = nums.reduce(function(a, b) { return a + b; }, 0);
                                    if (op === 'avg' || op === 'avg_number' || op === 'avg_percentage') {
                                        return total / nums.length;
                                    }
                                    return total;
                                }
                                function recomputeAllGrandTotals() {
                                    if (!tableBody) return;
                                    var gtRows = tableBody.querySelectorAll('tr.grand-total-row:not(#grand-total-row-template)');
                                    for (var gi = 0; gi < gtRows.length; gi++) {
                                        var gtTr = gtRows[gi];
                                        var gtCells = window.getRowTdCells(gtTr);
                                        for (var c = 0; c < fields.length && c < gtCells.length; c++) {
                                            var gtTd = gtCells[c];
                                            if (gtTd.classList.contains('manual-override') || gtTd.getAttribute('data-manual-override') === '1') continue;
                                            var mapping = getCellFormulaMapping(gtTd);
                                            if (!mapping) continue;
                                            var op = String(mapping.ui_formula_operation || '').trim();
                                            if (['sum', 'avg', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif', 'count_rows'].indexOf(op) === -1) continue;
                                            // Must match applyAutocalc / formula grand-total: quarter-scoped data rows (or manual picks), not every campus blue row.
                                            // Summing all tr.bg-blue-100 gave N_campuses A per-campus summary (e.g. 9A51=459) and broke Finalize a KPI accomplishment.
                                            var sourceCells = getGrandTotalSourceCells(gtTd, []);
                                            if (!sourceCells || sourceCells.length === 0) continue;
                                            var displayVal;
                                            if (op === 'sum' || op === 'avg' || op === 'avg_number' || op === 'avg_percentage') {
                                                var aggGt = aggregateGrandTotalNumericSourceValues(sourceCells, op);
                                                if (aggGt === null) continue;
                                                displayVal = aggGt.toFixed(2);
                                                if (op === 'avg_percentage') displayVal = displayVal + '%';
                                            } else if (op === 'unique' || op === 'unique_adjust') {
                                                // Count unique values per-campus then sum - matches accomplishment roll-up behavior.
                                                // For unique_adjust, apply the saved count_adjust offset from the cell mapping.
                                                var uniqueByCampus = {};
                                                sourceCells.forEach(function(cell) {
                                                    var v = String(getCellRawValue(cell) || '').trim();
                                                    if (!v || v === '-') return;
                                                    var tr = cell.closest('tr.data-row');
                                                    var campusKey = 'global';
                                                    if (tr) {
                                                        var subKey = String(tr.getAttribute('data-submission-id') || '').trim();
                                                        var userKey = String(tr.getAttribute('data-user-id') || '').trim();
                                                        campusKey = subKey || (userKey ? ('user_' + userKey) : 'global');
                                                    }
                                                    if (!uniqueByCampus[campusKey]) uniqueByCampus[campusKey] = {};
                                                    uniqueByCampus[campusKey][v] = true;
                                                });
                                                var overallUniqueTotal = 0;
                                                Object.keys(uniqueByCampus).forEach(function(k) {
                                                    overallUniqueTotal += Object.keys(uniqueByCampus[k] || {}).length;
                                                });
                                                var adj = (op === 'unique_adjust') ? (parseInt(String(mapping.count_adjust != null ? mapping.count_adjust : 0), 10) || 0) : 0;
                                                displayVal = String(Math.max(0, overallUniqueTotal + adj));
                                            } else if (op === 'countif') {
                                                var countAll = 0;
                                                sourceCells.forEach(function(cell) {
                                                    var v = String(getCellRawValue(cell) || '').trim();
                                                    if (!v || v === '-') return;
                                                    countAll++;
                                                });
                                                displayVal = String(countAll);
                                            } else if (op === 'count_rows') {
                                                var rowSet = [];
                                                sourceCells.forEach(function(cell) {
                                                    var row = cell.closest('tr.data-row');
                                                    if (row && rowSet.indexOf(row) === -1) rowSet.push(row);
                                                });
                                                displayVal = String(rowSet.length);
                                            }
                                            if (displayVal !== undefined) setCellRawValue(gtTd, displayVal);
                                        }
                                    }
                                }
                                function recomputeFormulasFromDataChange(editedTd) {
                                    if (!editedTd || !tableBody) return;
                                    var tr = editedTd.closest('tr.data-row');
                                    if (!tr) return;
                                    if (tr.classList.contains('grand-total-row')) return;
                                    if (tr.classList.contains('bg-blue-100') || String(tr.getAttribute('data-manual-total-row') || '') === '1') {
                                        recomputeAllGrandTotals();
                                        if (String(tr.getAttribute('data-manual-total-row') || '') !== '1' && tableBody) {
                                            var manualTotTr = tableBody.querySelector('tr[data-manual-total-row="1"]');
                                            if (manualTotTr && manualTotTr !== tr) {
                                                var tdsMan = window.getRowTdCells(manualTotTr);
                                                for (var mi = 0; mi < tdsMan.length; mi++) {
                                                    var tdM = tdsMan[mi];
                                                    var mapM = getCellFormulaMapping(tdM);
                                                    if (mapM && mapM.manual_total_from_all_blues) {
                                                        reapplyBlueRowSameRowFormulaFromMapping(manualTotTr, tdM, mapM);
                                                    }
                                                }
                                            }
                                        }
                                        if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                        return;
                                    }
                                    var sectionRows = findSectionRowsContainingRow(tr);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    recomputeBlueRowFormulasInSection(sectionRows);
                                    recomputeAllGrandTotals();
                                    if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                }
                                var recomputeFormulasRaf = null;
                                function scheduleRecomputeFormulas(editedTd) {
                                    if (typeof requestAnimationFrame === 'function') {
                                        if (recomputeFormulasRaf != null) cancelAnimationFrame(recomputeFormulasRaf);
                                        recomputeFormulasRaf = requestAnimationFrame(function() {
                                            recomputeFormulasRaf = null;
                                            recomputeFormulasFromDataChange(editedTd);
                                        });
                                    } else {
                                        setTimeout(function() { recomputeFormulasFromDataChange(editedTd); }, 0);
                                    }
                                }

                                function otherCampusBlueRowsExcludingManualTotal() {
                                    var tb = document.getElementById('table-body-multi');
                                    if (!tb) return [];
                                    return Array.prototype.slice.call(tb.querySelectorAll('tr.data-row.bg-blue-100')).filter(function(tr) {
                                        return tr && String(tr.getAttribute('data-manual-total-row') || '') !== '1';
                                    });
                                }
                                function isManualTotalCrossBluesApplyActive(selectedBlueCells) {
                                    if (!window._uapsManualTotalCrossBluesApply || !selectedBlueCells || !selectedBlueCells.length) return false;
                                    for (var i = 0; i < selectedBlueCells.length; i++) {
                                        var tr = selectedBlueCells[i].closest && selectedBlueCells[i].closest('tr.data-row');
                                        if (tr && String(tr.getAttribute('data-manual-total-row') || '') === '1') return true;
                                    }
                                    return false;
                                }
                                function evalBlueMultiOpAcrossOtherCampusRows(otherRows, op, sourceKeys, sourceAKey, sourceBKey, multiCustomExpr) {
                                    var multiOpRequiresB = (op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent');
                                    var useABOnlyMulti = op !== 'sum' && op !== 'avg';
                                    var safeEvalMulti = function(e, vals) {
                                        var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/');
                                        var letters = 'ABCDEFGHIJ';
                                        for (var vi = 0; vi < vals.length && vi < letters.length; vi++) {
                                            var re = new RegExp('\\b' + letters[vi] + '\\b', 'g');
                                            s = s.replace(re, String(vals[vi]));
                                        }
                                        if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                        try { return (new Function('return (' + s + ')'))(); } catch (err2) { return NaN; }
                                    };
                                    var rowResults = [];
                                    if (useABOnlyMulti) {
                                        var idxAM = getFieldIndexByKeyFlexible(sourceAKey);
                                        var idxBM = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                        if (idxAM < 0 || (multiOpRequiresB && idxBM < 0)) return NaN;
                                        var sourceIdxs = sourceKeys.map(function(k) { return getFieldIndexByKeyFlexible(k); });
                                        for (var r = 0; r < otherRows.length; r++) {
                                            var tds = window.getRowTdCells(otherRows[r]);
                                            var result;
                                            if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                var vals = sourceIdxs.map(function(idx) { return idx >= 0 && tds[idx] ? toNumeric(getCellRawValue(tds[idx])) : 0; });
                                                result = safeEvalMulti(multiCustomExpr || '', vals);
                                            } else {
                                                var valA = toNumeric(getCellRawValue(tds[idxAM]));
                                                var valB = idxBM >= 0 ? toNumeric(getCellRawValue(tds[idxBM])) : 0;
                                                switch (op) {
                                                    case 'subtract': result = valA - valB; break;
                                                    case 'multiply': result = valA * valB; break;
                                                    case 'divide': result = valB !== 0 ? (valA / valB) : 0; break;
                                                    case 'percent_of': result = valB !== 0 ? ((valA / valB) * 100) : 0; break;
                                                    case 'sum_over_b_percent': result = valB !== 0 ? (((valA + valB) / valB) * 100) : 0; break;
                                                    case 'diff_over_b_percent': result = valB !== 0 ? (((valA - valB) / valB) * 100) : 0; break;
                                                    default: result = valA + valB;
                                                }
                                            }
                                            if (!isNaN(result)) rowResults.push(result);
                                        }
                                    } else {
                                        for (var r2 = 0; r2 < otherRows.length; r2++) {
                                            var tds2 = window.getRowTdCells(otherRows[r2]);
                                            var total = 0;
                                            var count = 0;
                                            sourceKeys.forEach(function(key) {
                                                var idx = getFieldIndexByKeyFlexible(key);
                                                if (idx >= 0 && tds2[idx]) {
                                                    total += toNumeric(getCellRawValue(tds2[idx]));
                                                    count++;
                                                }
                                            });
                                            var rr = op === 'avg' ? (count > 0 ? total / count : 0) : total;
                                            rowResults.push(rr);
                                        }
                                    }
                                    if (rowResults.length === 0) return NaN;
                                    var sumRows = rowResults.reduce(function(a, b) { return a + b; }, 0);
                                    return (op === 'avg' && !useABOnlyMulti) ? (sumRows / rowResults.length) : sumRows;
                                }
                                function evalBlueSimpleOrCustomAcrossOtherCampusRows(otherRows, op, sourceAKey, sourceBKey, customExprForOp) {
                                    var idxA = getFieldIndexByKeyFlexible(sourceAKey);
                                    var idxB = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                    if (idxA < 0) return NaN;
                                    var safeEvalOp = function(e, a, b) {
                                        var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                        if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                        try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                    };
                                    var rowResults = [];
                                    for (var r = 0; r < otherRows.length; r++) {
                                        var tds = window.getRowTdCells(otherRows[r]);
                                        var cellA = tds[idxA];
                                        var cellB = idxB >= 0 ? tds[idxB] : null;
                                        var valA = toNumeric(getCellRawValue(cellA));
                                        var valB = cellB ? toNumeric(getCellRawValue(cellB)) : 0;
                                        var result = 0;
                                        if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                            result = safeEvalOp(customExprForOp, valA, valB);
                                        } else {
                                            switch (op) {
                                                case 'sum': result = valA + valB; break;
                                                case 'subtract': result = valA - valB; break;
                                                case 'multiply': result = valA * valB; break;
                                                case 'divide': result = valB !== 0 ? (valA / valB) : 0; break;
                                                case 'percent_of': result = valB !== 0 ? ((valA / valB) * 100) : 0; break;
                                                case 'sum_over_b_percent': result = valB !== 0 ? (((valA + valB) / valB) * 100) : 0; break;
                                                case 'diff_over_b_percent': result = valB !== 0 ? (((valA - valB) / valB) * 100) : 0; break;
                                                default: result = valA + valB;
                                            }
                                        }
                                        if (!isNaN(result)) rowResults.push(result);
                                    }
                                    if (rowResults.length === 0) return NaN;
                                    var sumR = rowResults.reduce(function(a, b) { return a + b; }, 0);
                                    if (op === 'avg') return sumR / rowResults.length;
                                    return sumR;
                                }

                                function applyFormulaToSelectedRows() {
                                    if (!tableBody) return;
                                    var targetKey = formulaTargetSelect ? formulaTargetSelect.value : '';
                                    var sourceAKey = formulaSourceASelect ? formulaSourceASelect.value : '';
                                    var sourceBKey = formulaSourceBSelect ? formulaSourceBSelect.value : '';
                                    var op = formulaOperationSelect ? formulaOperationSelect.value : 'sum';
                                    var formulaCustomExprEl = document.getElementById('formula-custom-expr');
                                    var customExpr = formulaCustomExprEl ? String(formulaCustomExprEl.value || '').trim() : '';
                                    function restoreSelectionAfterFormulaApply(targetCells) {
                                        var keep = Array.isArray(targetCells) ? targetCells.filter(function(td) {
                                            return !!(td && td.isConnected);
                                        }) : [];
                                        if (keep.length === 0) return;
                                        clearSelectionMulti();
                                        keep.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            if (tr) setCellSelected(tr, td, true);
                                        });
                                        var anchorCell = keep[0];
                                        var anchorRow = anchorCell ? anchorCell.closest('tr.data-row') : null;
                                        lastClickedRowMulti = anchorRow || null;
                                        lastClickedCellMulti = anchorCell || null;
                                        setSelectionModeState('manual');
                                        updateFormulaButtonState();
                                    }
                                    // Grand total: aggregate from all blue cells in selected column(s)
                                    if (formulaGrandTotalMode) {
                                        var selectedGrandTotalCells = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && tr.classList.contains('grand-total-row');
                                        }) : [];
                                        var selectedSourceCells = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && tr.classList.contains('bg-blue-100');
                                        }) : [];
                                        if (selectedGrandTotalCells.length === 0) return;
                                        var grandTotalTd = selectedGrandTotalCells[0];
                                        var targetColIdx = getColIndex(grandTotalTd);
                                        if (targetColIdx < 0) return;
                                        if (formulaMultiSourceMode) {
                                            var sourceKeysGT = [];
                                            if (sourceAKey) sourceKeysGT.push(sourceAKey);
                                            if (sourceBKey) sourceKeysGT.push(sourceBKey);
                                            var extraContainerGT = document.getElementById('formula-sources-extra');
                                            if (extraContainerGT) {
                                                extraContainerGT.querySelectorAll('select.formula-source-select').forEach(function(sel) {
                                                    if (sel.value) sourceKeysGT.push(sel.value);
                                                });
                                            }
                                            var multiOpRequiresBGT = (op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent');
                                            if (multiOpRequiresBGT && !sourceBKey) {
                                                if (formulaError) { formulaError.textContent = 'This operation requires Source B.'; formulaError.classList.remove('hidden'); }
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Choose Source B for this operation.');
                                                return;
                                            }
                                            if (sourceKeysGT.length === 0) {
                                                if (formulaError) { formulaError.textContent = 'Choose at least Source A.'; formulaError.classList.remove('hidden'); }
                                                return;
                                            }
                                            var multiCustomExprGT = (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? (customExpr || (typeof op === 'string' && op.indexOf('custom:') === 0 ? op.substring(7) : '')) : '';
                                            if ((op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) && !String(multiCustomExprGT || '').trim()) {
                                                if (formulaError) { formulaError.textContent = 'Enter a custom expression (e.g. A + B + C - D).'; formulaError.classList.remove('hidden'); }
                                                return;
                                            }
                                            var allBlueRowsGT = tableBody.querySelectorAll('tr.data-row.bg-blue-100');
                                            if (allBlueRowsGT.length === 0) {
                                                if (formulaError) { formulaError.textContent = 'No campus blue rows found to aggregate.'; formulaError.classList.remove('hidden'); }
                                                return;
                                            }
                                            var writeResultToCellGTMulti = function(cell, resultStr) {
                                                if (!cell) return;
                                                setCellRawValue(cell, resultStr);
                                            };
                                            var useABOnlyGT = op !== 'sum' && op !== 'avg';
                                            var rowResultsGT = [];
                                            if (useABOnlyGT) {
                                                var idxAM = getFieldIndexByKeyFlexible(sourceAKey);
                                                var idxBM = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                                if (idxAM < 0 || (multiOpRequiresBGT && idxBM < 0)) {
                                                    if (formulaError) { formulaError.textContent = 'Invalid Source A or B.'; formulaError.classList.remove('hidden'); }
                                                    return;
                                                }
                                                var sourceIdxsGT = sourceKeysGT.map(function(k) { return getFieldIndexByKeyFlexible(k); });
                                                var safeEvalGT = function(e, vals) {
                                                    var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/');
                                                    var letters = 'ABCDEFGHIJ';
                                                    for (var vi = 0; vi < vals.length && vi < letters.length; vi++) {
                                                        var re = new RegExp('\\b' + letters[vi] + '\\b', 'g');
                                                        s = s.replace(re, String(vals[vi]));
                                                    }
                                                    if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                                    try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                                };
                                                Array.prototype.forEach.call(allBlueRowsGT, function(blueRowTr) {
                                                    var tds = window.getRowTdCells(blueRowTr);
                                                    var result;
                                                    if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                        var vals = sourceIdxsGT.map(function(idx) { return idx >= 0 && tds[idx] ? toNumeric(getCellRawValue(tds[idx])) : 0; });
                                                        result = safeEvalGT(multiCustomExprGT, vals);
                                                    } else {
                                                        var valA = toNumeric(getCellRawValue(tds[idxAM]));
                                                        var valB = idxBM >= 0 ? toNumeric(getCellRawValue(tds[idxBM])) : 0;
                                                        switch (op) {
                                                            case 'subtract': result = valA - valB; break;
                                                            case 'multiply': result = valA * valB; break;
                                                            case 'divide': result = valB !== 0 ? (valA / valB) : 0; break;
                                                            case 'percent_of': result = valB !== 0 ? ((valA / valB) * 100) : 0; break;
                                                            case 'sum_over_b_percent': result = valB !== 0 ? (((valA + valB) / valB) * 100) : 0; break;
                                                            case 'diff_over_b_percent': result = valB !== 0 ? (((valA - valB) / valB) * 100) : 0; break;
                                                            default: result = valA + valB;
                                                        }
                                                    }
                                                    if (!isNaN(result)) rowResultsGT.push(result);
                                                });
                                            } else {
                                                Array.prototype.forEach.call(allBlueRowsGT, function(blueRowTr) {
                                                    var tds = window.getRowTdCells(blueRowTr);
                                                    var total = 0;
                                                    var count = 0;
                                                    sourceKeysGT.forEach(function(key) {
                                                        var idx = getFieldIndexByKeyFlexible(key);
                                                        if (idx >= 0 && tds[idx]) {
                                                            var v = toNumeric(getCellRawValue(tds[idx]));
                                                            total += v;
                                                            count++;
                                                        }
                                                    });
                                                    var rr = op === 'avg' ? (count > 0 ? total / count : 0) : total;
                                                    rowResultsGT.push(rr);
                                                });
                                            }
                                            var aggregateGT = 0;
                                            if (rowResultsGT.length > 0) {
                                                var sumRows = rowResultsGT.reduce(function(a, b) { return a + b; }, 0);
                                                aggregateGT = (op === 'avg' && !useABOnlyGT) ? (sumRows / rowResultsGT.length) : sumRows;
                                            }
                                            var resultStrGT = isPercentOperation(op, multiCustomExprGT) ? formatBlueSummaryPercentWhole(aggregateGT) : aggregateGT.toFixed(2);
                                            writeResultToCellGTMulti(grandTotalTd, resultStrGT);
                                            var gtMappingMulti = {
                                                ui_calc_type: 'grand-total',
                                                ui_formula_operation: op,
                                                section_ref: 'grand_total',
                                                source_keys: sourceKeysGT.slice()
                                            };
                                            var selectedQuarterM = getSelectedGrandTotalQuarter(grandTotalTd);
                                            if (isGrandTotalManualSelectionMode(grandTotalTd)) gtMappingMulti.source_quarter = 'manual';
                                            else if (typeof isGrandTotalSchoolYearScope === 'function' && isGrandTotalSchoolYearScope(grandTotalTd)) {
                                                var mSyMulti = getCellFormulaMapping(grandTotalTd) || {};
                                                var syStoreMulti = String(mSyMulti.source_quarter || '').trim().toLowerCase();
                                                gtMappingMulti.source_quarter = isGrandTotalSchoolYearSourceQuarterVal(syStoreMulti) ? syStoreMulti : 'sy_2nd_sem_2024_2025';
                                            }
                                            else if (selectedQuarterM) gtMappingMulti.source_quarter = selectedQuarterM;
                                            if (sourceKeysGT[0]) gtMappingMulti.sourceA = sourceKeysGT[0];
                                            if (sourceKeysGT[1]) gtMappingMulti.sourceB = sourceKeysGT[1];
                                            if (multiCustomExprGT && (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0))) {
                                                gtMappingMulti.custom_expr = normalizeCustomExpr(multiCustomExprGT) || multiCustomExprGT;
                                                addSavedCustomFormula(gtMappingMulti.custom_expr);
                                            }
                                            setCellFormulaMapping(grandTotalTd, gtMappingMulti);
                                            if (formulaError) formulaError.classList.add('hidden');
                                            hideFormulaModal();
                                            restoreSelectionAfterFormulaApply([grandTotalTd]);
                                            formulaGrandTotalMode = false;
                                            formulaMultiSourceMode = false;
                                            if (typeof window.performSaveTableData === 'function') {
                                                window.tableDataDirty = true;
                                                setAutosaveStatus('saving');
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                                setTimeout(function() {
                                                    window.tableDataDirty = true;
                                                    window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                                }, 250);
                                            } else scheduleAutoSave();
                                            return;
                                        }
                                        var grandTotalSourceCells = getGrandTotalSourceCells(grandTotalTd, selectedSourceCells);
                                        var writeResultToCellGT = function(cell, resultStr) {
                                            if (!cell) return;
                                            setCellRawValue(cell, resultStr);
                                        };
                                        if (op === 'sum' || op === 'avg') {
                                            var aggF = aggregateGrandTotalNumericSourceValues(grandTotalSourceCells, op);
                                            var resultF = aggF === null ? 0 : aggF;
                                            writeResultToCellGT(grandTotalTd, resultF.toFixed(2));
                                        } else {
                                            if (!sourceAKey || !sourceBKey) {
                                                if (formulaError) { formulaError.textContent = 'Grand total formula requires Source A and B.'; formulaError.classList.remove('hidden'); }
                                                return;
                                            }
                                            var idxA = getFieldIndexByKeyFlexible(sourceAKey);
                                            var idxB = getFieldIndexByKeyFlexible(sourceBKey);
                                            if (idxA < 0 || idxB < 0) return;
                                            var trF = grandTotalTd.closest('tr.data-row');
                                            if (!trF) return;
                                            var tdsF = window.getRowTdCells(trF);
                                            var cellAF = tdsF[idxA], cellBF = tdsF[idxB];
                                            if (!cellAF || !cellBF) return;
                                            var sumA = toNumeric(getCellRawValue(cellAF));
                                            var sumB = toNumeric(getCellRawValue(cellBF));
                                            if (isNaN(sumA) || isNaN(sumB)) return;
                                            var result = 0;
                                            if (op === 'percent_of') result = sumB !== 0 ? (sumA / sumB) * 100 : 0;
                                            else if (op === 'sum_over_b_percent') result = sumB !== 0 ? ((sumA + sumB) / sumB) * 100 : 0;
                                            else if (op === 'diff_over_b_percent') result = sumB !== 0 ? ((sumA - sumB) / sumB) * 100 : 0;
                                            else if (op === 'divide') result = sumB !== 0 ? sumA / sumB : 0;
                                            else if (op === 'multiply') result = sumA * sumB;
                                            else if (op === 'subtract') result = sumA - sumB;
                                            else result = sumA + sumB;
                                            var resultStr = result.toFixed(2);
                                            if (isPercentOperation(op)) resultStr = resultStr + '%';
                                            writeResultToCellGT(grandTotalTd, resultStr);
                                        }
                                        var gtMapping = { ui_calc_type: 'grand-total', ui_formula_operation: op, section_ref: 'grand_total' };
                                        var selectedQuarterForMapping = getSelectedGrandTotalQuarter(grandTotalTd);
                                        if (isGrandTotalManualSelectionMode(grandTotalTd)) gtMapping.source_quarter = 'manual';
                                        else if (typeof isGrandTotalSchoolYearScope === 'function' && isGrandTotalSchoolYearScope(grandTotalTd)) {
                                            var mSyGt = getCellFormulaMapping(grandTotalTd) || {};
                                            var syStoreGt = String(mSyGt.source_quarter || '').trim().toLowerCase();
                                            gtMapping.source_quarter = isGrandTotalSchoolYearSourceQuarterVal(syStoreGt) ? syStoreGt : 'sy_2nd_sem_2024_2025';
                                        }
                                        else if (selectedQuarterForMapping) gtMapping.source_quarter = selectedQuarterForMapping;
                                        if (sourceAKey) gtMapping.sourceA = sourceAKey;
                                        if (sourceBKey) gtMapping.sourceB = sourceBKey;
                                        setCellFormulaMapping(grandTotalTd, gtMapping);
                                        if (formulaError) formulaError.classList.add('hidden');
                                        hideFormulaModal();
                                        restoreSelectionAfterFormulaApply([grandTotalTd]);
                                        formulaGrandTotalMode = false;
                                        if (typeof window.performSaveTableData === 'function') {
                                            window.tableDataDirty = true;
                                            setAutosaveStatus('saving');
                                            window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                            setTimeout(function() {
                                                window.tableDataDirty = true;
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                            }, 250);
                                        } else scheduleAutoSave();
                                        return;
                                    }
                                    // Blue row only, custom expression
                                    if (formulaBlueRowOnlyMode && formulaCustomMode) {
                                        var selectedBlueCellsCustom = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && rowIsBlueOrManualSummaryForFormula(tr);
                                        }) : [];
                                        if (selectedBlueCellsCustom.length === 0 || !sourceAKey || !customExpr) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Select a blue cell, choose Source A, and enter a custom expression.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Choose Source A (required) and enter a custom expression, then click Apply.');
                                            return;
                                        }
                                        var idxACustom = getFieldIndexByKeyFlexible(sourceAKey);
                                        var idxBCustom = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                        if (idxACustom < 0) {
                                            if (formulaError) { formulaError.textContent = 'Invalid Source A.'; formulaError.classList.remove('hidden'); }
                                            return;
                                        }
                                        var safeEvalCustom = function(e, a, b) {
                                            var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                            if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                            try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                        };
                                        var writeResultToCellCustom = function(cell, resultStr) {
                                            if (!cell) return;
                                            var span = cell.querySelector('span');
                                            var anyInput = cell.querySelector('input, select, textarea');
                                            if (span) span.textContent = resultStr;
                                            else if (anyInput) {
                                                if (anyInput.tagName === 'SELECT') {
                                                    var found = false;
                                                    for (var i = 0; i < anyInput.options.length; i++) {
                                                        if (String(anyInput.options[i].value) === resultStr) { anyInput.selectedIndex = i; found = true; break; }
                                                    }
                                                    if (!found) anyInput.value = resultStr;
                                                } else anyInput.value = resultStr;
                                            } else cell.textContent = resultStr;
                                        };
                                        var crossManualCust = isManualTotalCrossBluesApplyActive(selectedBlueCellsCustom);
                                        var otherBluesCust = crossManualCust ? otherCampusBlueRowsExcludingManualTotal() : [];
                                        if (crossManualCust && otherBluesCust.length === 0) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Need at least one campus blue summary row.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Manual total reads each campus blue row. Add campus data first.');
                                            return;
                                        }
                                        var customFormulaError = false;
                                        selectedBlueCellsCustom.forEach(function(td) {
                                            var blueRow = td.closest('tr.data-row');
                                            if (!rowMatchesBlueFormulaApplyTarget(blueRow, crossManualCust)) return;
                                            var result;
                                            if (crossManualCust) {
                                                result = evalBlueSimpleOrCustomAcrossOtherCampusRows(otherBluesCust, 'custom', sourceAKey, sourceBKey, customExpr);
                                            } else {
                                                var tds = window.getRowTdCells(blueRow);
                                                var cellA = tds[idxACustom];
                                                var cellB = idxBCustom >= 0 ? tds[idxBCustom] : null;
                                                var valA = toNumeric(getCellRawValue(cellA));
                                                var valB = cellB ? toNumeric(getCellRawValue(cellB)) : 0;
                                                result = safeEvalCustom(customExpr, valA, valB);
                                            }
                                            if (isNaN(result)) {
                                                customFormulaError = true;
                                                return;
                                            }
                                            var resultStrCustom = isPercentOperation('custom', customExpr)
                                                ? formatBlueSummaryPercentWhole(result)
                                                : result.toFixed(2);
                                            writeResultToCellCustom(td, resultStrCustom);
                                        });
                                        if (customFormulaError) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Invalid expression or values. Use A, B, +, -, *, /, ( ).';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                        selectedBlueCellsCustom.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            var mapping = { sourceA: sourceAKey, custom_expr: customExpr, ui_calc_type: 'blue-row-formula-custom' };
                                            if (sourceBKey) mapping.sourceB = sourceBKey;
                                            if (crossManualCust) mapping.manual_total_from_all_blues = true;
                                            setCellFormulaMapping(td, mapping);
                                            if (tr) recomputeBlueRowPerformance(tr);
                                        });
                                        var normCust = normalizeCustomExpr(customExpr) || customExpr;
                                        var custTpl = {
                                            operation: 'custom',
                                            sourceA: sourceAKey,
                                            ui_calc_type: 'blue-row-formula-custom',
                                            ui_formula_operation: 'custom',
                                            custom_expr: normCust
                                        };
                                        if (sourceBKey) custTpl.sourceB = sourceBKey;
                                        if (crossManualCust) custTpl.manual_total_from_all_blues = true;
                                        persistBlueRowSameRowFormulasToTemplate(selectedBlueCellsCustom, custTpl);
                                        if (formulaError) formulaError.classList.add('hidden');
                                        hideFormulaModal();
                                        restoreSelectionAfterFormulaApply(selectedBlueCellsCustom);
                                        if (typeof window.performSaveTableData === 'function') {
                                            window.tableDataDirty = true;
                                            setAutosaveStatus('saving');
                                            requestAnimationFrame(function() {
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                            });
                                        } else scheduleAutoSave();
                                        return;
                                    }
                                    // Blue row only, multiple sources (A+B+C...)
                                    if (formulaBlueRowOnlyMode && formulaMultiSourceMode) {
                                        var selectedBlueCells = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && rowIsBlueOrManualSummaryForFormula(tr);
                                        }) : [];
                                        var sourceKeys = [];
                                        if (sourceAKey) sourceKeys.push(sourceAKey);
                                        if (sourceBKey) sourceKeys.push(sourceBKey);
                                        var extraContainer = document.getElementById('formula-sources-extra');
                                        if (extraContainer) {
                                            extraContainer.querySelectorAll('select.formula-source-select').forEach(function(sel) {
                                                if (sel.value) sourceKeys.push(sel.value);
                                            });
                                        }
                                        if (selectedBlueCells.length === 0 || sourceKeys.length === 0) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Select a blue cell and at least Source A.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                        var multiOpRequiresB = (op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent');
                                        if (multiOpRequiresB && !sourceBKey) {
                                            if (formulaError) {
                                                formulaError.textContent = 'This operation requires Source B.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Choose Source B for this operation.');
                                            return;
                                        }
                                        var multiCustomExpr = (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? (customExpr || (typeof op === 'string' && op.indexOf('custom:') === 0 ? op.substring(7) : '')) : '';
                                        if ((op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) && !multiCustomExpr.trim()) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Enter a custom expression.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                        var writeResultToCell = function(cell, resultStr) {
                                            if (!cell) return;
                                            var span = cell.querySelector('span');
                                            var anyInput = cell.querySelector('input, select, textarea');
                                            if (span) span.textContent = resultStr;
                                            else if (anyInput) {
                                                if (anyInput.tagName === 'SELECT') {
                                                    var found = false;
                                                    for (var i = 0; i < anyInput.options.length; i++) {
                                                        if (String(anyInput.options[i].value) === resultStr) { anyInput.selectedIndex = i; found = true; break; }
                                                    }
                                                    if (!found) anyInput.value = resultStr;
                                                } else anyInput.value = resultStr;
                                            } else cell.textContent = resultStr;
                                        };
                                        var crossManual = isManualTotalCrossBluesApplyActive(selectedBlueCells);
                                        var otherBluesCross = crossManual ? otherCampusBlueRowsExcludingManualTotal() : [];
                                        if (crossManual && otherBluesCross.length === 0) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Need at least one campus blue summary row.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Manual total reads each campus blue row. Add campus data first.');
                                            return;
                                        }
                                        var useABOnlyMulti = op !== 'sum' && op !== 'avg';
                                        if (useABOnlyMulti) {
                                            var idxAMulti = getFieldIndexByKeyFlexible(sourceAKey);
                                            var idxBMulti = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                            if (idxAMulti < 0 || (multiOpRequiresB && idxBMulti < 0)) {
                                                if (formulaError) { formulaError.textContent = 'Invalid Source A or B.'; formulaError.classList.remove('hidden'); }
                                                return;
                                            }
                                            var sourceIdxs = sourceKeys.map(function(k) { return getFieldIndexByKeyFlexible(k); });
                                            var safeEvalMulti = function(e, vals) {
                                                var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/');
                                                var letters = 'ABCDEFGHIJ';
                                                for (var vi = 0; vi < vals.length && vi < letters.length; vi++) {
                                                    var re = new RegExp('\\b' + letters[vi] + '\\b', 'g');
                                                    s = s.replace(re, String(vals[vi]));
                                                }
                                                if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                                try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                            };
                                            selectedBlueCells.forEach(function(td) {
                                                var blueRow = td.closest('tr.data-row');
                                                if (!rowMatchesBlueFormulaApplyTarget(blueRow, crossManual)) return;
                                                var result;
                                                if (crossManual) {
                                                    result = evalBlueMultiOpAcrossOtherCampusRows(otherBluesCross, op, sourceKeys, sourceAKey, sourceBKey, multiCustomExpr);
                                                } else {
                                                    var tds = window.getRowTdCells(blueRow);
                                                    if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                        var vals = sourceIdxs.map(function(idx) { return idx >= 0 && tds[idx] ? toNumeric(getCellRawValue(tds[idx])) : 0; });
                                                        result = safeEvalMulti(multiCustomExpr, vals);
                                                    } else {
                                                        var valA = toNumeric(getCellRawValue(tds[idxAMulti]));
                                                        var valB = idxBMulti >= 0 ? toNumeric(getCellRawValue(tds[idxBMulti])) : 0;
                                                        switch (op) {
                                                            case 'subtract': result = valA - valB; break;
                                                            case 'multiply': result = valA * valB; break;
                                                            case 'divide': result = valB !== 0 ? (valA / valB) : 0; break;
                                                            case 'percent_of': result = valB !== 0 ? ((valA / valB) * 100) : 0; break;
                                                            case 'sum_over_b_percent': result = valB !== 0 ? (((valA + valB) / valB) * 100) : 0; break;
                                                            case 'diff_over_b_percent': result = valB !== 0 ? (((valA - valB) / valB) * 100) : 0; break;
                                                            default: result = valA + valB;
                                                        }
                                                    }
                                                }
                                                var resultStr;
                                                if (isPercentOperation(op, multiCustomExpr)) {
                                                    resultStr = isNaN(result) ? '0%' : formatBlueSummaryPercentWhole(result);
                                                } else {
                                                    resultStr = isNaN(result) ? '0.00' : result.toFixed(2);
                                                }
                                                writeResultToCell(td, resultStr);
                                            });
                                        } else {
                                            selectedBlueCells.forEach(function(td) {
                                                var blueRow = td.closest('tr.data-row');
                                                if (!rowMatchesBlueFormulaApplyTarget(blueRow, crossManual)) return;
                                                var result;
                                                if (crossManual) {
                                                    result = evalBlueMultiOpAcrossOtherCampusRows(otherBluesCross, op, sourceKeys, sourceAKey, sourceBKey, multiCustomExpr);
                                                } else {
                                                    var tds = window.getRowTdCells(blueRow);
                                                    var total = 0;
                                                    var count = 0;
                                                    sourceKeys.forEach(function(key) {
                                                        var idx = getFieldIndexByKeyFlexible(key);
                                                        if (idx >= 0 && tds[idx]) {
                                                            var v = toNumeric(getCellRawValue(tds[idx]));
                                                            total += v;
                                                            count++;
                                                        }
                                                    });
                                                    result = op === 'avg' ? (count > 0 ? total / count : 0) : total;
                                                }
                                                var resultStrElse = isPercentOperation(op, multiCustomExpr)
                                                    ? (isNaN(result) ? '0%' : formatBlueSummaryPercentWhole(result))
                                                    : (isNaN(result) ? '0.00' : result.toFixed(2));
                                                writeResultToCell(td, resultStrElse);
                                            });
                                        }
                                        selectedBlueCells.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            var mapping = { source_keys: sourceKeys, operation: op, ui_calc_type: 'blue-row-formula-multi', ui_formula_operation: op };
                                            if (crossManual) mapping.manual_total_from_all_blues = true;
                                            if (sourceKeys[0]) mapping.sourceA = sourceKeys[0];
                                            if (sourceKeys[1]) mapping.sourceB = sourceKeys[1];
                                            if (multiCustomExpr && (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0))) {
                                                mapping.custom_expr = normalizeCustomExpr(multiCustomExpr) || multiCustomExpr;
                                                addSavedCustomFormula(mapping.custom_expr);
                                            }
                                            setCellFormulaMapping(td, mapping);
                                            if (tr) recomputeBlueRowPerformance(tr);
                                        });
                                        var templateBaseMulti = {
                                            operation: (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? 'custom' : op,
                                            sourceA: sourceKeys[0] || '',
                                            ui_calc_type: 'blue-row-formula-multi',
                                            ui_formula_operation: op,
                                            source_keys: sourceKeys.slice()
                                        };
                                        if (sourceKeys[1]) templateBaseMulti.sourceB = sourceKeys[1];
                                        if (multiCustomExpr && (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0))) {
                                            templateBaseMulti.custom_expr = normalizeCustomExpr(multiCustomExpr) || multiCustomExpr;
                                        }
                                        if (crossManual) templateBaseMulti.manual_total_from_all_blues = true;
                                        persistBlueRowSameRowFormulasToTemplate(selectedBlueCells, templateBaseMulti);
                                        if (formulaError) formulaError.classList.add('hidden');
                                        hideFormulaModal();
                                        restoreSelectionAfterFormulaApply(selectedBlueCells);
                                        if (typeof window.performSaveTableData === 'function') {
                                            window.tableDataDirty = true;
                                            setAutosaveStatus('saving');
                                            requestAnimationFrame(function() {
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                            });
                                        } else scheduleAutoSave();
                                        return;
                                    }
                                    // Blue row only: A and B from same blue row, result in selected blue cell
                                    if (formulaBlueRowOnlyMode) {
                                        var selectedBlueCells = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            return tr && rowIsBlueOrManualSummaryForFormula(tr);
                                        }) : [];
                                        if (selectedBlueCells.length === 0 || !sourceAKey) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Select a blue cell and choose Source A.';
                                                formulaError.classList.remove('hidden');
                                                formulaError.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Choose Source A (required) and Source B (if needed) from the dropdowns, then click Apply.');
                                            return;
                                        }
                                        if ((op === 'divide' || op === 'percent_of' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent') && !sourceBKey) {
                                            if (formulaError) {
                                                formulaError.textContent = 'This operation requires Source B.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                        var customExprFromOp = (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0))
                                            ? (formulaCustomExprEl ? String(formulaCustomExprEl.value || '').trim() : ((typeof op === 'string' && op.indexOf('custom:') === 0) ? op.substring(7) : ''))
                                            : '';
                                        if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                            if (!customExprFromOp) {
                                                if (formulaError) {
                                                    formulaError.textContent = 'Enter a custom expression (e.g. A + B, A * B / 100).';
                                                    formulaError.classList.remove('hidden');
                                                }
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Enter a custom expression in the Custom expression field.');
                                                return;
                                            }
                                        }
                                        var idxA = getFieldIndexByKeyFlexible(sourceAKey);
                                        var idxB = sourceBKey ? getFieldIndexByKeyFlexible(sourceBKey) : -1;
                                        if (idxA < 0) {
                                            if (formulaError) { formulaError.textContent = 'Invalid Source A.'; formulaError.classList.remove('hidden'); }
                                            return;
                                        }
                                        var writeResultToCell = function(cell, resultStr) {
                                            if (!cell) return;
                                            var span = cell.querySelector('span');
                                            var anyInput = cell.querySelector('input, select, textarea');
                                            if (span) span.textContent = resultStr;
                                            else if (anyInput) {
                                                if (anyInput.tagName === 'SELECT') {
                                                    var found = false;
                                                    for (var i = 0; i < anyInput.options.length; i++) {
                                                        if (String(anyInput.options[i].value) === resultStr) { anyInput.selectedIndex = i; found = true; break; }
                                                    }
                                                    if (!found) anyInput.value = resultStr;
                                                } else anyInput.value = resultStr;
                                            } else cell.textContent = resultStr;
                                        };
                                        var customExprForOp = (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? customExprFromOp : '';
                                        var safeEvalOp = function(e, a, b) {
                                            var s = String(e).replace(/[xA]/g, '*').replace(/A-/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                                            if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                            try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                                        };
                                        var crossSimple = isManualTotalCrossBluesApplyActive(selectedBlueCells);
                                        var otherBluesSimple = crossSimple ? otherCampusBlueRowsExcludingManualTotal() : [];
                                        if (crossSimple && otherBluesSimple.length === 0) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Need at least one campus blue summary row.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Manual total reads each campus blue row. Add campus data first.');
                                            return;
                                        }
                                        var customOpError = false;
                                        selectedBlueCells.forEach(function(td) {
                                            var blueRow = td.closest('tr.data-row');
                                            if (!rowMatchesBlueFormulaApplyTarget(blueRow, crossSimple)) return;
                                            var result = 0;
                                            if (crossSimple) {
                                                result = evalBlueSimpleOrCustomAcrossOtherCampusRows(otherBluesSimple, op, sourceAKey, sourceBKey, customExprForOp);
                                                if (isNaN(result)) {
                                                    customOpError = true;
                                                    return;
                                                }
                                            } else {
                                                var tds = window.getRowTdCells(blueRow);
                                                var cellA = tds[idxA];
                                                var cellB = idxB >= 0 ? tds[idxB] : null;
                                                var valA = toNumeric(getCellRawValue(cellA));
                                                var valB = cellB ? toNumeric(getCellRawValue(cellB)) : 0;
                                                if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                    result = safeEvalOp(customExprForOp, valA, valB);
                                                    if (isNaN(result)) {
                                                        customOpError = true;
                                                        return;
                                                    }
                                                } else {
                                                    switch (op) {
                                                        case 'sum': result = valA + valB; break;
                                                        case 'subtract': result = valA - valB; break;
                                                        case 'multiply': result = valA * valB; break;
                                                        case 'divide': result = valB !== 0 ? (valA / valB) : 0; break;
                                                        case 'percent_of': result = valB !== 0 ? ((valA / valB) * 100) : 0; break;
                                                        case 'sum_over_b_percent': result = valB !== 0 ? (((valA + valB) / valB) * 100) : 0; break;
                                                        case 'diff_over_b_percent': result = valB !== 0 ? (((valA - valB) / valB) * 100) : 0; break;
                                                        default: result = valA + valB;
                                                    }
                                                }
                                            }
                                            var resultStr = isPercentOperation(op, customExprForOp) ? formatBlueSummaryPercentWhole(result) : result.toFixed(2);
                                            writeResultToCell(td, resultStr);
                                        });
                                        if (customOpError) {
                                            if (formulaError) {
                                                formulaError.textContent = 'Invalid expression or values. Use A, B, +, -, *, /, ( ).';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                        selectedBlueCells.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            var mapping = { sourceA: sourceAKey, operation: op, ui_calc_type: 'blue-row-formula', ui_formula_operation: op };
                                            if (sourceBKey) mapping.sourceB = sourceBKey;
                                            if (crossSimple) mapping.manual_total_from_all_blues = true;
                                            if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                                var normForOp = normalizeCustomExpr(customExprForOp);
                                                mapping.custom_expr = normForOp || customExprForOp;
                                                mapping.operation = 'custom';
                                                mapping.ui_formula_operation = 'custom';
                                                addSavedCustomFormula(customExprForOp);
                                            }
                                            setCellFormulaMapping(td, mapping);
                                            if (tr) recomputeBlueRowPerformance(tr);
                                        });
                                        var templateBaseBr = {
                                            sourceA: sourceAKey,
                                            ui_calc_type: 'blue-row-formula',
                                            ui_formula_operation: (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? 'custom' : op,
                                            operation: (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) ? 'custom' : op
                                        };
                                        if (sourceBKey) templateBaseBr.sourceB = sourceBKey;
                                        if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                                            var normBr = normalizeCustomExpr(customExprForOp);
                                            templateBaseBr.custom_expr = normBr || customExprForOp;
                                        }
                                        if (crossSimple) templateBaseBr.manual_total_from_all_blues = true;
                                        persistBlueRowSameRowFormulasToTemplate(selectedBlueCells, templateBaseBr);
                                        if (formulaError) formulaError.classList.add('hidden');
                                        hideFormulaModal();
                                        restoreSelectionAfterFormulaApply(selectedBlueCells);
                                        if (typeof window.performSaveTableData === 'function') {
                                            window.tableDataDirty = true;
                                            setAutosaveStatus('saving');
                                            requestAnimationFrame(function() {
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                            });
                                        } else scheduleAutoSave();
                                        return;
                                    }
                                    if (!tableBody || selectedRowsMulti.length === 0) return;
                                    if (!formulaUseBlueRow && (!targetKey || !sourceAKey)) {
                                        if (formulaError) {
                                            formulaError.textContent = 'Target column and Source A are required.';
                                            formulaError.classList.remove('hidden');
                                        }
                                        return;
                                    }
                                    if ((op !== 'sum' && op !== 'multiply') && !sourceBKey) {
                                        // For Summary Formula with selected cells, Source B can be inferred from the second selected source column.
                                        if (!formulaUseBlueRow) {
                                            if (formulaError) {
                                                formulaError.textContent = 'This operation uses both A and B. Please select Source B.';
                                                formulaError.classList.remove('hidden');
                                            }
                                            return;
                                        }
                                    }
                                    // Summary Formula multi-cell mode:
                                    // use selected cells as source (including blue-row cells) and selected blue target cell(s).
                                    var selectedCellsForFormula = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')) : [];
                                    if (formulaUseBlueRow && selectedCellsForFormula.length > 0) {
                                        var selectedBlueCells = [];
                                        var selectedNonBlueCells = [];
                                        selectedCellsForFormula.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            if (!tr) return;
                                            if (tr.classList.contains('bg-blue-100')) selectedBlueCells.push(td);
                                            else selectedNonBlueCells.push(td);
                                        });
                                        if (selectedBlueCells.length > 0) {
                                            var firstAnchorCell = selectedNonBlueCells[0] || selectedBlueCells[0];
                                            var firstAnchorRow = firstAnchorCell ? firstAnchorCell.closest('tr.data-row') : null;
                                            var sectionRowsSel = firstAnchorRow ? findSectionRowsContainingRow(firstAnchorRow) : null;
                                            if (sectionRowsSel && sectionRowsSel.length > 0) {
                                                var sectionRowsSetSel = {};
                                                sectionRowsSel.forEach(function(tr) { sectionRowsSetSel[tr] = true; });
                                                selectedBlueCells = selectedBlueCells.filter(function(td) {
                                                    var tr = td.closest('tr.data-row');
                                                    return tr && sectionRowsSetSel[tr];
                                                });
                                                selectedNonBlueCells = selectedNonBlueCells.filter(function(td) {
                                                    var tr = td.closest('tr.data-row');
                                                    return tr && sectionRowsSetSel[tr];
                                                });
                                                // Determine blue targets:
                                                // prefer selected blue cells that match chosen target column; fallback to all selected blue cells.
                                                var selectedBlueTargetCells = [];
                                                var targetColIndexSel = -1;
                                                if (targetKey) {
                                                    for (var tf = 0; tf < fields.length; tf++) {
                                                        if (getFieldKey(fields[tf]) === targetKey) {
                                                            targetColIndexSel = tf;
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (targetColIndexSel >= 0) {
                                                    selectedBlueCells.forEach(function(td) {
                                                        if (getColIndex(td) === targetColIndexSel) selectedBlueTargetCells.push(td);
                                                    });
                                                }
                                                if (selectedBlueTargetCells.length === 0) {
                                                    selectedBlueTargetCells = selectedBlueCells.slice();
                                                }

                                                // Sources are all selected cells except selected blue targets.
                                                var sourceCells = selectedCellsForFormula.filter(function(td) {
                                                    return selectedBlueTargetCells.indexOf(td) === -1;
                                                }).filter(function(td) {
                                                    var tr = td.closest('tr.data-row');
                                                    return tr && sectionRowsSetSel[tr];
                                                });

                                                if (sourceCells.length > 0 && selectedBlueTargetCells.length > 0) {
                                                    var colSums = {};
                                                    var selectedSourceCols = [];
                                                    sourceCells.forEach(function(td) {
                                                        var tr = td.closest('tr.data-row');
                                                        if (!tr) return;
                                                        var colIndex = getColIndex(td);
                                                        if (colIndex < 0 || colIndex >= fields.length) return;
                                                        var key = getFieldKey(fields[colIndex]);
                                                        var rawVal = getCellRawValue(td);
                                                        var num = toNumeric(rawVal);
                                                        if (!colSums[key]) colSums[key] = 0.0;
                                                        colSums[key] += num;
                                                        if (selectedSourceCols.indexOf(key) === -1) selectedSourceCols.push(key);
                                                    });
                                                    selectedSourceCols.sort();
                                                    var aKeyEffective = sourceAKey || (selectedSourceCols[0] || '');
                                                    var bKeyEffective = sourceBKey || (selectedSourceCols.length > 1 ? selectedSourceCols[1] : '');
                                                    if (!aKeyEffective) {
                                                        if (formulaError) {
                                                            formulaError.textContent = 'No source values found in selected data cells.';
                                                            formulaError.classList.remove('hidden');
                                                        }
                                                        return;
                                                    }
                                                    if ((op !== 'sum' && op !== 'multiply') && !bKeyEffective) {
                                                        if (formulaError) {
                                                            formulaError.textContent = 'Select two source columns (or set Source B) for this operation.';
                                                            formulaError.classList.remove('hidden');
                                                        }
                                                        return;
                                                    }
                                                    var aSel = colSums[aKeyEffective] || 0.0;
                                                    var bSel = bKeyEffective ? (colSums[bKeyEffective] || 0.0) : 0.0;
                                                    var resultSel = 0.0;
                                                    switch (op) {
                                                        case 'sum':
                                                            resultSel = aSel + bSel;
                                                            break;
                                                        case 'subtract':
                                                            resultSel = aSel - bSel;
                                                            break;
                                                        case 'multiply':
                                                            resultSel = aSel * bSel;
                                                            break;
                                                        case 'divide':
                                                            resultSel = bSel !== 0 ? (aSel / bSel) : 0.0;
                                                            break;
                                                        case 'percent_of':
                                                            resultSel = bSel !== 0 ? ((aSel / bSel) * 100) : 0.0;
                                                            break;
                                                        case 'sum_over_b_percent':
                                                            resultSel = bSel !== 0 ? (((aSel + bSel) / bSel) * 100) : 0.0;
                                                            break;
                                                        case 'diff_over_b_percent':
                                                            resultSel = bSel !== 0 ? (((aSel - bSel) / bSel) * 100) : 0.0;
                                                            break;
                                                        default:
                                                            resultSel = 0.0;
                                                    }
                                                    var formattedSel = isPercentOperation(op) ? formatBlueSummaryPercentWhole(resultSel) : resultSel.toFixed(2);
                                                    var writeResultToCellSel = function(cell, value) {
                                                        if (!cell) return;
                                                        var span = cell.querySelector('span');
                                                        var anyInput = cell.querySelector('input, select, textarea');
                                                        if (span) {
                                                            span.textContent = value;
                                                        } else if (anyInput) {
                                                            if (anyInput.tagName === 'SELECT') {
                                                                var found = false;
                                                                for (var i = 0; i < anyInput.options.length; i++) {
                                                                    if (String(anyInput.options[i].value) === value) {
                                                                        anyInput.selectedIndex = i;
                                                                        found = true;
                                                                        break;
                                                                    }
                                                                }
                                                                if (!found) anyInput.value = value;
                                                            } else {
                                                                anyInput.value = value;
                                                            }
                                                        } else {
                                                            cell.textContent = value;
                                                        }
                                                    };
                                                    selectedBlueTargetCells.forEach(function(td) { writeResultToCellSel(td, formattedSel); });
                                                    // Keep performance columns aligned automatically on affected blue rows.
                                                    var touchedBlueRows = [];
                                                    selectedBlueTargetCells.forEach(function(td) {
                                                        var tr = td.closest('tr.data-row');
                                                        if (tr && touchedBlueRows.indexOf(tr) === -1) touchedBlueRows.push(tr);
                                                    });
                                                    touchedBlueRows.forEach(function(tr) { recomputeBlueRowPerformance(tr); });

                                                    if (formulaError) {
                                                        formulaError.classList.add('hidden');
                                                    }
                                                    hideFormulaModal();
                                                    restoreSelectionAfterFormulaApply(selectedBlueTargetCells);

                                                    // Persist summary formula only when target/source mapping is unambiguous.
                                                    var targetKeysSel = [];
                                                    selectedBlueTargetCells.forEach(function(td) {
                                                        var c = getColIndex(td);
                                                        if (c >= 0 && c < fields.length) {
                                                            var key = getFieldKey(fields[c]);
                                                            if (targetKeysSel.indexOf(key) === -1) targetKeysSel.push(key);
                                                        }
                                                    });
                                                    targetKeysSel.sort();
                                                    if (typeof summaryRulesUrl !== 'undefined') {
                                                        var formulaOpMapSel = { sum: 'sum', divide: 'ratio', percent_of: 'ratio_percent' };
                                                        var backendOpSel = formulaOpMapSel[op];
                                                        if (backendOpSel && targetKeysSel.length === 1 && aKeyEffective) {
                                                            var tokenSel = document.querySelector('meta[name="csrf-token"]');
                                                            tokenSel = tokenSel ? tokenSel.getAttribute('content') : '';
                                                            var payloadSel = { output: { target_field: targetKeysSel[0], operation: backendOpSel, sourceA: aKeyEffective, ui_calc_type: 'summary-formula', ui_formula_operation: op } };
                                                            if (selectedSourceCols.length > 0) payloadSel.output.source_columns = selectedSourceCols.slice();
                                                            if (bKeyEffective) payloadSel.output.sourceB = bKeyEffective;
                                                            if (!bKeyEffective && (backendOpSel === 'ratio' || backendOpSel === 'ratio_percent')) payloadSel.output.sourceB = aKeyEffective;
                                                            payloadSel.output.section_ref = buildSectionRefFromRow(firstAnchorRow);
                                                            upsertSelectionMapping(payloadSel.output);
                                                            // Preserve selection scope with stable row identities.
                                                            var dataRowsInSectionSel = sectionRowsSel.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                                            var rowIndicesSel = [];
                                                            var rowUidsSel = [];
                                                            sourceCells.forEach(function(td) {
                                                                var tr = td.closest('tr.data-row');
                                                                if (!tr || tr.classList.contains('bg-blue-100')) return;
                                                                var idx = dataRowsInSectionSel.indexOf(tr);
                                                                if (idx >= 0 && rowIndicesSel.indexOf(idx) === -1) rowIndicesSel.push(idx);
                                                                var rowUid = ensureDataRowUid(tr);
                                                                if (rowUid && rowUidsSel.indexOf(rowUid) === -1) rowUidsSel.push(rowUid);
                                                            });
                                                            rowIndicesSel.sort(function(a, b) { return a - b; });
                                                            rowUidsSel.sort();
                                                            if (rowIndicesSel.length > 0) payloadSel.output.row_indices = rowIndicesSel;
                                                            if (rowUidsSel.length > 0) payloadSel.output.row_uids = rowUidsSel;
                                                            selectedBlueTargetCells.forEach(function(td) {
                                                                setCellFormulaMapping(td, payloadSel.output);
                                                            });
                                                            fetch(summaryRulesUrl, {
                                                                method: 'POST',
                                                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': tokenSel, 'X-Requested-With': 'XMLHttpRequest' },
                                                                body: JSON.stringify(payloadSel)
                                                            }).then(function(r) { return r.json(); }).then(function(res) { updateSummaryRulesCacheFromResponse(res); }).catch(function() {});
                                                        }
                                                    }

                                                    if (selectedBlueTargetCells[0] && selectedBlueTargetCells[0].scrollIntoView) {
                                                        selectedBlueTargetCells[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                                    }
                                                    if (typeof window.performSaveTableData === 'function') {
                                                        window.tableDataDirty = true;
                                                        setAutosaveStatus('saving');
                                                        var doSaveSel = function() {
                                                            window.performSaveTableData({
                                                                onSuccess: function() {
                                                                    setAutosaveStatus('saved');
                                                                }
                                                            });
                                                        };
                                                        if (typeof requestAnimationFrame === 'function') requestAnimationFrame(function() { doSaveSel(); });
                                                        else setTimeout(doSaveSel, 0);
                                                    } else {
                                                        scheduleAutoSave();
                                                    }
                                                    return;
                                                }
                                            }
                                        }
                                    }

                                    var firstRow = selectedRowsMulti[0];
                                    if (!firstRow || firstRow.classList.contains('bg-blue-100')) return;
                                    var sectionRows = findSectionRowsContainingRow(firstRow);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    var resultRow = null;
                                    for (var i = sectionRows.length - 1; i >= 0; i--) {
                                        if (sectionRows[i].classList.contains('bg-blue-100')) {
                                            resultRow = sectionRows[i];
                                            break;
                                        }
                                    }
                                    if (!resultRow) {
                                        if (formulaError) {
                                            formulaError.textContent = 'No blue result row in this section. Add a result row first.';
                                            formulaError.classList.remove('hidden');
                                        }
                                        return;
                                    }
                                    // Choose where to read A and B from:
                                    // - normal formula: first data row in the section
                                    // - summary-formula: blue result row itself
                                    var sourceRow = formulaUseBlueRow ? resultRow : firstRow;
                                    var aInput = sourceRow.querySelector('input[name="' + sourceAKey + '"], textarea[name="' + sourceAKey + '"], select[name="' + sourceAKey + '"]');
                                    var bInput = sourceBKey ? sourceRow.querySelector('input[name="' + sourceBKey + '"], textarea[name="' + sourceBKey + '"], select[name="' + sourceBKey + '"]') : null;
                                    if (!aInput) return;
                                    var a = toNumeric(aInput.value);
                                    var b = bInput ? toNumeric(bInput.value) : 0.0;
                                    var result = 0.0;
                                    switch (op) {
                                        case 'sum':
                                            result = a + b;
                                            break;
                                        case 'subtract':
                                            result = a - b;
                                            break;
                                        case 'multiply':
                                            result = a * b;
                                            break;
                                        case 'divide':
                                            result = b !== 0 ? (a / b) : 0.0;
                                            break;
                                        case 'percent_of':
                                            result = b !== 0 ? ((a / b) * 100) : 0.0;
                                            break;
                                        case 'sum_over_b_percent':
                                            result = b !== 0 ? (((a + b) / b) * 100) : 0.0;
                                            break;
                                        case 'diff_over_b_percent':
                                            result = b !== 0 ? (((a - b) / b) * 100) : 0.0;
                                            break;
                                        default:
                                            result = 0.0;
                                    }
                                    var formatted = isPercentOperation(op) ? formatBlueSummaryPercentWhole(result) : result.toFixed(2);
                                    var targetInput = resultRow.querySelector('input[name="' + targetKey + '"], textarea[name="' + targetKey + '"], select[name="' + targetKey + '"]');
                                    if (targetInput) {
                                        if (targetInput.tagName === 'SELECT') {
                                            var found = false;
                                            for (var i = 0; i < targetInput.options.length; i++) {
                                                if (String(targetInput.options[i].value) === formatted) {
                                                    targetInput.selectedIndex = i;
                                                    found = true;
                                                    break;
                                                }
                                            }
                                            if (!found) targetInput.value = formatted;
                                        } else {
                                            targetInput.value = formatted;
                                        }
                                    } else {
                                        var colIndex = -1;
                                        for (var c = 0; c < fields.length; c++) {
                                            if (getFieldKey(fields[c]) === targetKey) { colIndex = c; break; }
                                        }
                                        if (colIndex >= 0) {
                                            var tds = window.getRowTdCells(resultRow);
                                            var cell = tds[colIndex];
                                            if (cell) {
                                                var span = cell.querySelector('span');
                                                if (span) span.textContent = formatted;
                                                else {
                                                    var anyInput = cell.querySelector('input, select, textarea');
                                                    if (anyInput) anyInput.value = formatted;
                                                    else cell.textContent = formatted;
                                                }
                                            }
                                        }
                                    }
                                    if (resultRow && resultRow.classList.contains('bg-blue-100')) {
                                        recomputeBlueRowPerformance(resultRow);
                                    }

                                    if (formulaError) {
                                        formulaError.classList.add('hidden');
                                    }
                                    hideFormulaModal();
                                    var targetCellsFinal = [];
                                    if (resultRow && targetKey) {
                                        var targetIdxFinal = getFieldIndexByKeyFlexible(targetKey);
                                        if (targetIdxFinal >= 0) {
                                            var targetTdFinal = window.getRowTdCells(resultRow)[targetIdxFinal];
                                            if (targetTdFinal) targetCellsFinal.push(targetTdFinal);
                                        }
                                    }
                                    restoreSelectionAfterFormulaApply(targetCellsFinal);
                                    // Persist Summary Formula to template so Planning Coordinator sees it (only for backend-supported ops)
                                    if (formulaUseBlueRow && typeof summaryRulesUrl !== 'undefined') {
                                        var formulaOpMap = { sum: 'sum', divide: 'ratio', percent_of: 'ratio_percent' };
                                        var backendOp = formulaOpMap[op];
                                        if (backendOp) {
                                            var payload = { output: { target_field: targetKey, operation: backendOp, sourceA: sourceAKey, ui_calc_type: 'summary-formula', ui_formula_operation: op } };
                                            var formulaSourceColumns = [];
                                            if (sourceAKey) formulaSourceColumns.push(sourceAKey);
                                            if (sourceBKey && formulaSourceColumns.indexOf(sourceBKey) === -1) formulaSourceColumns.push(sourceBKey);
                                            if (formulaSourceColumns.length > 0) payload.output.source_columns = formulaSourceColumns;
                                            if (sourceBKey) payload.output.sourceB = sourceBKey;
                                            if (!sourceBKey && (backendOp === 'ratio' || backendOp === 'ratio_percent')) payload.output.sourceB = sourceAKey;
                                            payload.output.section_ref = resultRow ? buildSectionRefFromRow(resultRow) : '';
                                            upsertSelectionMapping(payload.output);
                                            if (resultRow && targetKey) {
                                                var targetIdxDirect = getFieldIndexByKeyFlexible(targetKey);
                                                if (targetIdxDirect >= 0) {
                                                    var targetTdDirect = window.getRowTdCells(resultRow)[targetIdxDirect];
                                                    setCellFormulaMapping(targetTdDirect, payload.output);
                                                }
                                            }
                                            var token = document.querySelector('meta[name="csrf-token"]');
                                            token = token ? token.getAttribute('content') : '';
                                            fetch(summaryRulesUrl, {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                                body: JSON.stringify(payload)
                                            }).then(function(r) { return r.json(); }).then(function(res) { updateSummaryRulesCacheFromResponse(res); }).catch(function() {});
                                        }
                                    }
                                    if (resultRow && resultRow.scrollIntoView) {
                                        resultRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    }
                                    // Save immediately when formula writes to blue row so result persists on reload (defer 1 tick to ensure DOM is flushed)
                                    if (formulaUseBlueRow && typeof window.performSaveTableData === 'function') {
                                        window.tableDataDirty = true;
                                        setAutosaveStatus('saving');
                                        var doSave = function() {
                                            window.performSaveTableData({
                                                onSuccess: function() {
                                                    setAutosaveStatus('saved');
                                                },
                                                onDone: function() {
                                                    // Keep current status text unless save is still pending
                                                }
                                            });
                                        };
                                        if (typeof requestAnimationFrame === 'function') {
                                            requestAnimationFrame(function() { doSave(); });
                                        } else {
                                            setTimeout(doSave, 0);
                                        }
                                    } else {
                                        scheduleAutoSave();
                                    }
                                }

                                function ensureBlueResultRowInSectionForSelection() {
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    if (selectedCells.length === 0) return null;
                                    var firstCell = selectedCells[0];
                                    var firstTr = firstCell.closest('tr.data-row');
                                    if (!firstTr || firstTr.classList.contains('bg-blue-100')) return null;
                                    var subId = firstTr.getAttribute('data-submission-id') || '';
                                    var userId = firstTr.getAttribute('data-user-id') || '';
                                    var sectionRows = findSectionRowsContainingRow(firstTr);
                                    if (!sectionRows || sectionRows.length === 0) return null;
                                    var resultRow = null;
                                    for (var i = sectionRows.length - 1; i >= 0; i--) {
                                        if (sectionRows[i].classList.contains('bg-blue-100')) { resultRow = sectionRows[i]; break; }
                                    }
                                    if (resultRow) return resultRow;
                                    // Insert after last *data* row (not after a blue row)
                                    var lastDataRow = null;
                                    for (var i = sectionRows.length - 1; i >= 0; i--) {
                                        if (!sectionRows[i].classList.contains('bg-blue-100')) { lastDataRow = sectionRows[i]; break; }
                                    }
                                    if (!lastDataRow) return null;
                                    var newRow = document.createElement('tr');
                                    newRow.className = 'data-row bg-blue-100';
                                    newRow.setAttribute('data-submission-id', subId);
                                    newRow.setAttribute('data-row-type', 'summary');
                                    newRow.setAttribute('data-user-id', userId);
                                    newRow.style.backgroundColor = 'rgb(219, 234, 254)';
                                    var cellCount = window.getRowTdCells(lastDataRow).length;
                                    for (var ci = 0; ci < cellCount; ci++) {
                                        var cell = document.createElement('td');
                                        cell.setAttribute('data-field-col', String(ci));
                                        cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100';
                                        cell.style.backgroundColor = 'rgb(219, 234, 254)';
                                        var span = document.createElement('span');
                                        span.className = 'text-sm font-semibold text-gray-800';
                                        span.textContent = '';
                                        cell.appendChild(span);
                                        newRow.appendChild(cell);
                                    }
                                    lastDataRow.insertAdjacentElement('afterend', newRow);
                                    return newRow;
                                }

                                function blueRowHasAnyFormulaMeta(tr) {
                                    if (!tr || !tr.querySelectorAll) return false;
                                    var tds = window.getRowTdCells(tr);
                                    for (var fi = 0; fi < tds.length; fi++) {
                                        var td = tds[fi];
                                        if (typeof getCellFormulaMapping === 'function' && getCellFormulaMapping(td)) return true;
                                        if (td.hasAttribute && (td.hasAttribute('data-formula-source-columns') || td.hasAttribute('data-formula-row-uids') || td.hasAttribute('data-formula-row-indices') || td.hasAttribute('data-formula-source-a') || td.hasAttribute('data-formula-ui-calc-type') || td.hasAttribute('data-formula-ui-formula-operation'))) return true;
                                    }
                                    return false;
                                }
                                function blueRowAllCellsDashOrEmpty(tr) {
                                    if (!tr) return true;
                                    var tds = window.getRowTdCells(tr);
                                    for (var ei = 0; ei < tds.length; ei++) {
                                        var raw = String(typeof getCellRawValue === 'function' ? getCellRawValue(tds[ei]) : '').trim();
                                        if (raw !== '' && raw !== '-') return false;
                                    }
                                    return true;
                                }
                                function isRemovableBluePlaceholder(tr) {
                                    return !!(tr && tr.classList.contains('bg-blue-100') && !blueRowHasAnyFormulaMeta(tr) && blueRowAllCellsDashOrEmpty(tr));
                                }
                                function pickPrimaryBlueRow(blues) {
                                    if (!blues || blues.length === 0) return null;
                                    // Prefer rows that have formulas or any non-dash value.
                                    var best = null;
                                    var bestScore = -1;
                                    for (var i = 0; i < blues.length; i++) {
                                        var tr = blues[i];
                                        var score = 0;
                                        if (blueRowHasAnyFormulaMeta(tr)) score += 10;
                                        if (!blueRowAllCellsDashOrEmpty(tr)) score += 5;
                                        // Tie-breaker: keep the bottom-most (latest) blue row.
                                        score += i * 0.001;
                                        if (score > bestScore) { bestScore = score; best = tr; }
                                    }
                                    return best || blues[blues.length - 1];
                                }
                                /** Enforce: each section keeps exactly 1 blue summary row. */
                                function enforceSingleBlueRowPerSection() {
                                    if (!tableBody || typeof buildTableBodySections !== 'function') return;
                                    var maxPasses = 6;
                                    while (maxPasses-- > 0) {
                                        var secs = buildTableBodySections();
                                        var removedOne = false;
                                        for (var si = 0; si < secs.length && !removedOne; si++) {
                                            var blues = secs[si].filter(function(r) {
                                                return r.classList.contains('bg-blue-100') && String(r.getAttribute('data-manual-total-row') || '') !== '1';
                                            });
                                            if (blues.length < 2) continue;
                                            var keep = pickPrimaryBlueRow(blues);
                                            for (var bi = 0; bi < blues.length; bi++) {
                                                if (blues[bi] === keep) continue;
                                                if (blues[bi] && blues[bi].parentNode) {
                                                    blues[bi].remove();
                                                    removedOne = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!removedOne) break;
                                    }
                                }
                                function removeBlueRowsAfterGrandTotalControls() {
                                    if (!tableBody) return;
                                    var controlsRow = document.getElementById('add-grand-total-row');
                                    if (!controlsRow || controlsRow.parentNode !== tableBody) return;
                                    var node = controlsRow.nextElementSibling;
                                    while (node) {
                                        var next = node.nextElementSibling;
                                        if (node.classList && node.classList.contains('data-row') && node.classList.contains('bg-blue-100')
                                            && String(node.getAttribute('data-manual-total-row') || '') !== '1') {
                                            node.remove();
                                        }
                                        node = next;
                                    }
                                }
                                /** Mark blue rows in a section with data-row-type summary (does not remove rows). */
                                function normalizeSectionBlueRows(firstTr) {
                                    if (!firstTr || !tableBody) return;
                                    var sectionRows = findSectionRowsContainingRow(firstTr);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    var blueRows = sectionRows.filter(function(tr) {
                                        return tr.classList.contains('bg-blue-100') || String(tr.getAttribute('data-manual-total-row') || '') === '1';
                                    });
                                    blueRows.forEach(function(blueTr) {
                                        if (blueTr && blueTr.parentNode) blueTr.setAttribute('data-row-type', 'summary');
                                    });
                                }

                                /** Keep blue summary row(s) at the bottom of each logical block (separator/header, or blue row followed by more data). */
                                function normalizeAllSectionsBlueRows() {
                                    if (!tableBody) return;
                                    var sections = buildTableBodySections();
                                    var colCount = fields && fields.length > 0 ? fields.length : 1;
                                    sections.forEach(function(sectionRows) {
                                        if (!sectionRows || sectionRows.length === 0) return;
                                        var dataRows = sectionRows.filter(function(r) {
                                            if (String(r.getAttribute('data-manual-total-row') || '') === '1') return false;
                                            return !r.classList.contains('bg-blue-100');
                                        });
                                        var blueRows = sectionRows.filter(function(r) {
                                            if (String(r.getAttribute('data-manual-total-row') || '') === '1') return false;
                                            return r.classList.contains('bg-blue-100');
                                        });
                                        var lastDataRow = dataRows.length > 0 ? dataRows[dataRows.length - 1] : null;
                                        var subId = sectionRows[0] ? (sectionRows[0].getAttribute('data-submission-id') || '') : '';
                                        var userId = sectionRows[0] ? (sectionRows[0].getAttribute('data-user-id') || '') : '';
                                        if (lastDataRow && blueRows.length > 0) {
                                            var bluesOrdered = blueRows.slice();
                                            bluesOrdered.forEach(function(br) {
                                                if (br && br.parentNode) br.remove();
                                            });
                                            var anchor = lastDataRow;
                                            bluesOrdered.forEach(function(br) {
                                                if (!br) return;
                                                anchor.insertAdjacentElement('afterend', br);
                                                anchor = br;
                                            });
                                        }
                                        if (dataRows.length > 0 && blueRows.length === 0) {
                                            var nextAfterData = lastDataRow.nextElementSibling;
                                            if (nextAfterData && nextAfterData.classList.contains('data-row')
                                                && rowMatchesSectionKeys(nextAfterData, subId, userId)
                                                && (nextAfterData.classList.contains('bg-blue-100') || String(nextAfterData.getAttribute('data-manual-total-row') || '') === '1')) {
                                                return;
                                            }
                                            // Insert a fresh summary before the grey divider / next chunk (never clone - avoids duplicated formula attrs).
                                            var newBlue = document.createElement('tr');
                                            newBlue.className = 'data-row bg-blue-100 border-l-4 border-indigo-200';
                                            newBlue.setAttribute('data-submission-id', subId);
                                            newBlue.setAttribute('data-row-type', 'summary');
                                            newBlue.setAttribute('data-user-id', userId);
                                            for (var ci = 0; ci < colCount; ci++) {
                                                var cell = document.createElement('td');
                                                cell.setAttribute('data-field-col', String(ci));
                                                cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100';
                                                var field = fields && fields[ci] ? fields[ci] : null;
                                                var keyF = field ? getFieldKey(field) : '';
                                                if (ci === 0) {
                                                    var span = document.createElement('span');
                                                    span.className = 'text-sm font-semibold text-gray-800';
                                                    span.textContent = '';
                                                    cell.appendChild(span);
                                                } else {
                                                    var inp = document.createElement('input');
                                                    inp.type = 'text';
                                                    inp.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                                    inp.value = '';
                                                    if (keyF) inp.setAttribute('name', keyF);
                                                    cell.appendChild(inp);
                                                }
                                                newBlue.appendChild(cell);
                                            }
                                            newBlue.setAttribute('data-submission-id', subId);
                                            newBlue.setAttribute('data-row-type', 'summary');
                                            newBlue.setAttribute('data-user-id', userId);
                                            lastDataRow.insertAdjacentElement('afterend', newBlue);
                                        }
                                    });
                                    enforceSingleBlueRowPerSection();
                                    removeBlueRowsAfterGrandTotalControls();
                                    if (typeof normalizeBlueRowDashes === 'function') normalizeBlueRowDashes();
                                }

                                function computeAggregateChainNumeric(mapping, dataRowsInSection) {
                                    if (!mapping || typeof mapping !== 'object') return null;
                                    var baseSource = String(mapping.base_source || mapping.sourceA || '').trim();
                                    var baseAgg = String(mapping.base_aggregate || 'sum').toLowerCase();
                                    if (baseAgg !== 'avg') baseAgg = 'sum';
                                    var baseRowUids = Array.isArray(mapping.base_row_uids) ? mapping.base_row_uids : [];
                                    var baseRowIndices = Array.isArray(mapping.base_row_indices) ? mapping.base_row_indices : [];
                                    var chain = Array.isArray(mapping.chain) ? mapping.chain : [];
                                    if (!baseSource) return null;
                                    function uidToTr(uid) {
                                        var u = String(uid || '').trim();
                                        if (!u) return null;
                                        for (var i = 0; i < dataRowsInSection.length; i++) {
                                            var tr = dataRowsInSection[i];
                                            if (!tr || tr.classList.contains('bg-blue-100')) continue;
                                            if (String(tr.getAttribute('data-row-uid') || '').trim() === u) return tr;
                                        }
                                        return null;
                                    }
                                    function cellVal(tr, fieldKey) {
                                        var idx = getFieldIndexByKeyFlexible(fieldKey);
                                        if (idx < 0 || !tr) return NaN;
                                        var tds = window.getRowTdCells(tr);
                                        var td = tds[idx];
                                        if (!td) return NaN;
                                        return toNumeric(getCellRawValue(td));
                                    }
                                    var uidMissing = false;
                                    for (var um = 0; um < baseRowUids.length; um++) {
                                        if (!uidToTr(baseRowUids[um])) {
                                            uidMissing = true;
                                            break;
                                        }
                                    }
                                    var useIdxFallback = uidMissing && baseRowUids.length > 0
                                        && baseRowIndices.length === baseRowUids.length;
                                    var anyUidResolved = false;
                                    for (var ar = 0; ar < baseRowUids.length; ar++) {
                                        if (uidToTr(baseRowUids[ar])) { anyUidResolved = true; break; }
                                    }
                                    var allBaseUidsAbsent = baseRowUids.length > 0 && !anyUidResolved;
                                    var usePositionalFallback = !useIdxFallback && allBaseUidsAbsent
                                        && baseRowUids.length === dataRowsInSection.length && dataRowsInSection.length > 0;
                                    var baseVals = [];
                                    if (useIdxFallback) {
                                        for (var bj = 0; bj < baseRowIndices.length; bj++) {
                                            var ix = parseInt(baseRowIndices[bj], 10);
                                            if (isNaN(ix) || ix < 0 || ix >= dataRowsInSection.length) continue;
                                            var trIx = dataRowsInSection[ix];
                                            if (!trIx) continue;
                                            var nIx = cellVal(trIx, baseSource);
                                            if (!isNaN(nIx)) baseVals.push(nIx);
                                        }
                                    } else if (usePositionalFallback) {
                                        for (var bp = 0; bp < dataRowsInSection.length; bp++) {
                                            var trP = dataRowsInSection[bp];
                                            if (!trP) continue;
                                            var nP = cellVal(trP, baseSource);
                                            if (!isNaN(nP)) baseVals.push(nP);
                                        }
                                    } else {
                                        for (var bi = 0; bi < baseRowUids.length; bi++) {
                                            var trB = uidToTr(baseRowUids[bi]);
                                            if (!trB) continue;
                                            var n = cellVal(trB, baseSource);
                                            if (!isNaN(n)) baseVals.push(n);
                                        }
                                    }
                                    if (baseVals.length === 0) return null;
                                    var sumB = baseVals.reduce(function(a, b) { return a + b; }, 0);
                                    var base = baseAgg === 'avg' ? sumB / baseVals.length : sumB;
                                    var result = base;
                                    for (var ci = 0; ci < chain.length; ci++) {
                                        var step = chain[ci];
                                        if (!step || typeof step !== 'object') continue;
                                        var op = String(step.op || '-').trim();
                                        if (op === 'A-') op = '/';
                                        if (op === 'A') op = '*';
                                        if (['+', '-', '*', '/'].indexOf(op) === -1) continue;
                                        var trC = uidToTr(step.row_uid);
                                        if (!trC && step.row_index != null && step.row_index !== '') {
                                            var ri = parseInt(step.row_index, 10);
                                            if (!isNaN(ri) && ri >= 0 && ri < dataRowsInSection.length) trC = dataRowsInSection[ri];
                                        }
                                        if (!trC) continue;
                                        var sk = String(step.source || baseSource).trim() || baseSource;
                                        var v = cellVal(trC, sk);
                                        if (isNaN(v)) continue;
                                        if (op === '+') result += v;
                                        else if (op === '-') result -= v;
                                        else if (op === '*') result *= v;
                                        else if (op === '/') result = v !== 0 ? result / v : result;
                                    }
                                    return result;
                                }
                                function formatAggregateChainCellDisplay(td) {
                                    return toNumeric(getCellRawValue(td)).toFixed(2);
                                }
                                function hideAggregateChainModal() {
                                    if (aggregateChainModal) aggregateChainModal.classList.add('hidden');
                                    aggregateChainPending = null;
                                    aggregateChainCellMeta = [];
                                    if (aggregateChainError) { aggregateChainError.classList.add('hidden'); aggregateChainError.textContent = ''; }
                                    if (aggregateChainPreview) { aggregateChainPreview.classList.add('hidden'); aggregateChainPreview.textContent = ''; }
                                }
                                function updateAggregateChainPreview() {
                                    if (!aggregateChainPreview || !aggregateChainPending || aggregateChainCellMeta.length === 0) return;
                                    var baseAgg = aggregateChainBaseAgg && aggregateChainBaseAgg.value === 'avg' ? 'avg' : 'sum';
                                    var baseUids = [];
                                    if (aggregateChainBaseList) {
                                        aggregateChainBaseList.querySelectorAll('input[type="checkbox"][data-ac-base-uid]').forEach(function(cb) {
                                            if (cb.checked) baseUids.push(String(cb.getAttribute('data-ac-base-uid') || '').trim());
                                        });
                                    }
                                    var firstMeta = aggregateChainCellMeta[0];
                                    var baseSource = firstMeta ? firstMeta.fieldKey : '';
                                    var mapping = { base_aggregate: baseAgg, base_source: baseSource, base_row_uids: baseUids, chain: [] };
                                    if (aggregateChainTermsWrap) {
                                        aggregateChainTermsWrap.querySelectorAll('[data-ac-term-row]').forEach(function(row) {
                                            var opSel = row.querySelector('select[data-ac-term-op]');
                                            var cellSel = row.querySelector('select[data-ac-term-cell]');
                                            if (!opSel || !cellSel || !cellSel.value) return;
                                            var idx = parseInt(cellSel.value, 10);
                                            if (isNaN(idx) || !aggregateChainCellMeta[idx]) return;
                                            var meta = aggregateChainCellMeta[idx];
                                            mapping.chain.push({ op: opSel.value, row_uid: meta.rowUid, source: meta.fieldKey });
                                        });
                                    }
                                    var dataRowsInSection = [];
                                    if (aggregateChainPending.firstTr) {
                                        var sr = findSectionRowsContainingRow(aggregateChainPending.firstTr);
                                        if (sr) dataRowsInSection = sr.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    }
                                    var num = computeAggregateChainNumeric(mapping, dataRowsInSection);
                                    if (num === null || isNaN(num)) {
                                        aggregateChainPreview.textContent = 'Preview: -';
                                    } else {
                                        aggregateChainPreview.textContent = 'Preview: ' + num.toFixed(2);
                                    }
                                    aggregateChainPreview.classList.remove('hidden');
                                }
                                function renderAggregateChainTermsEmpty() {
                                    if (!aggregateChainTermsWrap) return;
                                    aggregateChainTermsWrap.innerHTML = '';
                                    var p = document.createElement('p');
                                    p.id = 'aggregate-chain-terms-empty';
                                    p.className = 'text-[11px] text-gray-500';
                                    p.textContent = 'No extra terms. Use aAdd terma to apply + a A A- another cell after the base.';
                                    aggregateChainTermsWrap.appendChild(p);
                                }
                                function addAggregateChainTermRow(defaultIdx) {
                                    if (!aggregateChainTermsWrap || aggregateChainCellMeta.length === 0) return;
                                    var emptyP = document.getElementById('aggregate-chain-terms-empty');
                                    if (emptyP) emptyP.remove();
                                    var row = document.createElement('div');
                                    row.setAttribute('data-ac-term-row', '1');
                                    row.className = 'flex flex-wrap items-center gap-2';
                                    var opSel = document.createElement('select');
                                    opSel.setAttribute('data-ac-term-op', '1');
                                    opSel.className = 'text-xs border border-gray-300 rounded py-1 px-2 bg-white';
                                    [['+', '+'], ['a', '-'], ['A', '*'], ['A-', '/']].forEach(function(pair) {
                                        var o = document.createElement('option');
                                        o.value = pair[1];
                                        o.textContent = pair[0];
                                        opSel.appendChild(o);
                                    });
                                    opSel.value = '-';
                                    var cellSel = document.createElement('select');
                                    cellSel.setAttribute('data-ac-term-cell', '1');
                                    cellSel.className = 'text-xs border border-gray-300 rounded py-1 px-2 bg-white min-w-[10rem] flex-1';
                                    aggregateChainCellMeta.forEach(function(meta, idx) {
                                        var o = document.createElement('option');
                                        o.value = String(idx);
                                        o.textContent = meta.label + ' (' + formatAggregateChainCellDisplay(meta.td) + ')';
                                        cellSel.appendChild(o);
                                    });
                                    if (typeof defaultIdx === 'number' && defaultIdx >= 0 && defaultIdx < aggregateChainCellMeta.length) {
                                        cellSel.value = String(defaultIdx);
                                    }
                                    var rm = document.createElement('button');
                                    rm.type = 'button';
                                    rm.className = 'text-xs text-red-600 hover:text-red-800';
                                    rm.textContent = 'Remove';
                                    rm.addEventListener('click', function() {
                                        row.remove();
                                        if (aggregateChainTermsWrap && aggregateChainTermsWrap.querySelectorAll('[data-ac-term-row]').length === 0) {
                                            renderAggregateChainTermsEmpty();
                                        }
                                        updateAggregateChainPreview();
                                    });
                                    opSel.addEventListener('change', updateAggregateChainPreview);
                                    cellSel.addEventListener('change', updateAggregateChainPreview);
                                    row.appendChild(opSel);
                                    row.appendChild(cellSel);
                                    row.appendChild(rm);
                                    aggregateChainTermsWrap.appendChild(row);
                                }
                                function openAggregateChainModal() {
                                    if (READ_ONLY_TEMPLATE_VIEW) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Template is read-only.');
                                        return;
                                    }
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    if (selectedCells.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select source data cells and at least one blue result cell first.');
                                        return;
                                    }
                                    var hasGrandTotalAc = Array.prototype.some.call(selectedCells, function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && tr.classList.contains('grand-total-row');
                                    });
                                    if (hasGrandTotalAc) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'This chain formula applies to campus summary (blue) rows only, not Grand Total.');
                                        return;
                                    }
                                    var firstDataCell = Array.prototype.slice.call(selectedCells).find(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && isPlainAggregatableDataRow(tr);
                                    });
                                    var firstCell = firstDataCell || selectedCells[0];
                                    var firstTr = firstCell.closest('tr.data-row');
                                    if (!firstTr || (firstTr.classList.contains('bg-blue-100') && !firstDataCell)) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select data cells (white rows) and blue target cells.');
                                        return;
                                    }
                                    normalizeSectionBlueRows(firstTr);
                                    var sectionRows = findSectionRowsContainingRow(firstTr);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    var sectionRowsSet = {};
                                    sectionRows.forEach(function(r) { sectionRowsSet[r] = true; });
                                    var sourceCells = [];
                                    var targetBlueCells = [];
                                    Array.prototype.forEach.call(selectedCells, function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || !sectionRowsSet[tr]) return;
                                        if (tr.classList.contains('bg-blue-100')) targetBlueCells.push(td);
                                        else sourceCells.push(td);
                                    });
                                    if (sourceCells.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select at least one white data cell for the formula.');
                                        return;
                                    }
                                    if (targetBlueCells.length === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select at least one blue result cell.');
                                        return;
                                    }
                                    var colIdx0 = getColIndex(sourceCells[0]);
                                    var sameCol = sourceCells.every(function(td) { return getColIndex(td) === colIdx0; });
                                    if (!sameCol || colIdx0 < 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'For this formula, select white cells in one column only (same as a single Excel column).');
                                        return;
                                    }
                                    var baseField = fields[colIdx0];
                                    var baseKey = baseField ? getFieldKey(baseField) : '';
                                    if (!baseKey) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Could not resolve column key.');
                                        return;
                                    }
                                    var cellsToWrite = [];
                                    targetBlueCells.forEach(function(td) {
                                        var c = getColIndex(td);
                                        if (c >= 0 && c < fields.length) cellsToWrite.push({ cell: td, colIndex: c });
                                    });
                                    aggregateChainPending = { sourceCells: sourceCells, targetBlueCells: targetBlueCells, firstTr: firstTr, sectionRows: sectionRows, cellsToWrite: cellsToWrite };
                                    aggregateChainCellMeta = [];
                                    var dataRowsInSection = sectionRows.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    sourceCells.forEach(function(td, si) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr) return;
                                        var uid = typeof ensureDataRowUid === 'function' ? ensureDataRowUid(tr) : String(tr.getAttribute('data-row-uid') || '').trim();
                                        var cidx = getColIndex(td);
                                        var fk = fields[cidx] ? getFieldKey(fields[cidx]) : '';
                                        var label = (fields[cidx] && fields[cidx].label) ? fields[cidx].label : fk;
                                        var drIdx = dataRowsInSection.indexOf(tr);
                                        aggregateChainCellMeta.push({
                                            td: td,
                                            tr: tr,
                                            rowUid: uid,
                                            fieldKey: fk,
                                            label: 'Row ' + (drIdx >= 0 ? drIdx + 1 : si + 1) + ' - ' + label
                                        });
                                    });
                                    if (aggregateChainBaseList) {
                                        aggregateChainBaseList.innerHTML = '';
                                        aggregateChainCellMeta.forEach(function(meta, idx) {
                                            var row = document.createElement('label');
                                            row.className = 'flex items-center gap-2 px-2 py-1.5 hover:bg-gray-50 cursor-pointer';
                                            var cb = document.createElement('input');
                                            cb.type = 'checkbox';
                                            cb.className = 'rounded border-gray-300 text-indigo-600';
                                            cb.setAttribute('data-ac-base-uid', meta.rowUid);
                                            cb.checked = true;
                                            cb.addEventListener('change', updateAggregateChainPreview);
                                            var sp = document.createElement('span');
                                            sp.className = 'flex-1 min-w-0 flex items-center justify-between gap-2';
                                            var spLabel = document.createElement('span');
                                            spLabel.className = 'truncate';
                                            spLabel.textContent = meta.label;
                                            var spVal = document.createElement('span');
                                            spVal.className = 'text-gray-500 tabular-nums shrink-0 text-[11px]';
                                            spVal.textContent = '(' + formatAggregateChainCellDisplay(meta.td) + ')';
                                            sp.appendChild(spLabel);
                                            sp.appendChild(spVal);
                                            row.appendChild(cb);
                                            row.appendChild(sp);
                                            aggregateChainBaseList.appendChild(row);
                                        });
                                    }
                                    if (aggregateChainBaseAgg) aggregateChainBaseAgg.value = 'sum';
                                    renderAggregateChainTermsEmpty();
                                    if (aggregateChainError) { aggregateChainError.classList.add('hidden'); aggregateChainError.textContent = ''; }
                                    updateAggregateChainPreview();
                                    if (aggregateChainModal) aggregateChainModal.classList.remove('hidden');
                                }
                                function applyAggregateChainFromModal() {
                                    if (!aggregateChainPending || aggregateChainCellMeta.length === 0) {
                                        hideAggregateChainModal();
                                        return;
                                    }
                                    var baseUids = [];
                                    if (aggregateChainBaseList) {
                                        aggregateChainBaseList.querySelectorAll('input[type="checkbox"][data-ac-base-uid]').forEach(function(cb) {
                                            if (cb.checked) baseUids.push(String(cb.getAttribute('data-ac-base-uid') || '').trim());
                                        });
                                    }
                                    if (baseUids.length === 0) {
                                        if (aggregateChainError) {
                                            aggregateChainError.textContent = 'Check at least one row for the base total.';
                                            aggregateChainError.classList.remove('hidden');
                                        }
                                        return;
                                    }
                                    var baseAgg = aggregateChainBaseAgg && aggregateChainBaseAgg.value === 'avg' ? 'avg' : 'sum';
                                    var firstMeta = aggregateChainCellMeta[0];
                                    var baseSource = firstMeta.fieldKey;
                                    var dataRowsInSection = [];
                                    if (aggregateChainPending.firstTr) {
                                        var sr = findSectionRowsContainingRow(aggregateChainPending.firstTr);
                                        if (sr) dataRowsInSection = sr.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    }
                                    var chain = [];
                                    if (aggregateChainTermsWrap) {
                                        aggregateChainTermsWrap.querySelectorAll('[data-ac-term-row]').forEach(function(row) {
                                            var opSel = row.querySelector('select[data-ac-term-op]');
                                            var cellSel = row.querySelector('select[data-ac-term-cell]');
                                            if (!opSel || !cellSel || cellSel.value === '') return;
                                            var idx = parseInt(cellSel.value, 10);
                                            if (isNaN(idx) || !aggregateChainCellMeta[idx]) return;
                                            var meta = aggregateChainCellMeta[idx];
                                            var rowIx = -1;
                                            for (var rii = 0; rii < dataRowsInSection.length; rii++) {
                                                if (dataRowsInSection[rii] === meta.tr) {
                                                    rowIx = rii;
                                                    break;
                                                }
                                            }
                                            var stepObj = { op: opSel.value, row_uid: meta.rowUid, source: meta.fieldKey };
                                            if (rowIx >= 0) stepObj.row_index = rowIx;
                                            chain.push(stepObj);
                                        });
                                    }
                                    var baseRowIndices = [];
                                    var allBaseIdxOk = true;
                                    baseUids.forEach(function(uid) {
                                        var u = String(uid || '').trim();
                                        var found = -1;
                                        for (var di = 0; di < dataRowsInSection.length; di++) {
                                            var trD = dataRowsInSection[di];
                                            if (trD && String(trD.getAttribute('data-row-uid') || '').trim() === u) {
                                                found = di;
                                                break;
                                            }
                                        }
                                        if (found < 0) allBaseIdxOk = false;
                                        baseRowIndices.push(found);
                                    });
                                    var mappingPayload = {
                                        ui_calc_type: 'aggregate_chain',
                                        operation: 'aggregate_chain',
                                        ui_formula_operation: 'aggregate_chain',
                                        base_aggregate: baseAgg,
                                        base_source: baseSource,
                                        base_row_uids: baseUids,
                                        chain: chain,
                                        sourceA: baseSource,
                                        source_columns: [baseSource],
                                        section_ref: buildSectionRefFromRow(aggregateChainPending.firstTr)
                                    };
                                    if (allBaseIdxOk && baseRowIndices.length === baseUids.length && baseUids.length > 0) {
                                        mappingPayload.base_row_indices = baseRowIndices;
                                    }
                                    var num = computeAggregateChainNumeric(mappingPayload, dataRowsInSection);
                                    var resultStr = (num === null || isNaN(num)) ? '0.00' : num.toFixed(2);
                                    var writeResultToCell = function(cell, rs) {
                                        if (!cell) return;
                                        var span = cell.querySelector('span');
                                        var anyInput = cell.querySelector('input, select, textarea');
                                        if (span) span.textContent = rs;
                                        else if (anyInput) {
                                            if (anyInput.tagName === 'SELECT') {
                                                var found = false;
                                                for (var i = 0; i < anyInput.options.length; i++) {
                                                    if (String(anyInput.options[i].value) === rs) { anyInput.selectedIndex = i; found = true; break; }
                                                }
                                                if (!found) anyInput.value = rs;
                                            } else anyInput.value = rs;
                                        } else cell.textContent = rs;
                                    };
                                    aggregateChainPending.cellsToWrite.forEach(function(o) {
                                        writeResultToCell(o.cell, resultStr);
                                        setCellFormulaMapping(o.cell, mappingPayload);
                                        var trb = o.cell.closest('tr.data-row');
                                        if (trb) recomputeBlueRowPerformance(trb);
                                    });
                                    if (typeof persistBlueRowSameRowFormulasToTemplate === 'function' && aggregateChainPending.cellsToWrite.length > 0) {
                                        var cellsForPersist = aggregateChainPending.cellsToWrite.map(function(o) { return o.cell; });
                                        persistBlueRowSameRowFormulasToTemplate(cellsForPersist, mappingPayload);
                                    }
                                    if (typeof window.performSaveTableData === 'function') {
                                        window.tableDataDirty = true;
                                        setAutosaveStatus('saving');
                                        requestAnimationFrame(function() {
                                            window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                        });
                                    } else if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                    hideAggregateChainModal();
                                    if (selectionCalcTypeSelect) selectionCalcTypeSelect.value = '';
                                    clearSelectionMulti();
                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Aggregate chain applied.');
                                }

                                function applyAutocalcToSelectedRows(explicitCalcType) {
                                    var selectedCells = tableBody ? tableBody.querySelectorAll('td.cell-selected') : [];
                                    if (selectedCells.length === 0) {
                                        if (autocalcError) {
                                            autocalcError.textContent = 'Select cells first.';
                                            autocalcError.classList.remove('hidden');
                                        }
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select cells first, then choose a calculation and click Apply.');
                                        return;
                                    }
                                    var grandTotalCells = Array.prototype.slice.call(selectedCells).filter(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && tr.classList.contains('grand-total-row');
                                    });
                                    if (grandTotalCells.length > 0 && (explicitCalcType === 'sum' || explicitCalcType === 'avg' || explicitCalcType === 'avg_number' || explicitCalcType === 'avg_percentage' || explicitCalcType === 'unique' || explicitCalcType === 'unique_adjust' || explicitCalcType === 'countif' || explicitCalcType === 'count_rows')) {
                                        var gtTd = grandTotalCells[0];
                                        var targetColIdx = getColIndex(gtTd);
                                        if (targetColIdx >= 0 && targetColIdx < fields.length) {
                                            var selectedDataSources = Array.prototype.slice.call(selectedCells).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && !tr.classList.contains('grand-total-row') && !tr.classList.contains('kpi-finalize-total-row');
                                            });
                                            if (!isGrandTotalManualSelectionMode(gtTd)) {
                                                selectedDataSources = selectedDataSources.filter(function(td) {
                                                    var tr = td.closest('tr.data-row');
                                                    return tr && !tr.classList.contains('bg-blue-100');
                                                });
                                            }
                                            var sourceCellsForGrandTotal = getGrandTotalSourceCells(gtTd, selectedDataSources);
                                            if (sourceCellsForGrandTotal.length === 0) {
                                                if (autocalcError) {
                                                    autocalcError.textContent = 'No source rows found for the selected quarter. Use the wizard / quarter cell to set Q1aQ4, and ensure each row\'s Quarter column matches (Q1, 1st Q, or 1a4).';
                                                    autocalcError.classList.remove('hidden');
                                                }
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'No source rows match this grand total quarter.');
                                                return;
                                            }
                                            var displayVal = '0.00';
                                            if (explicitCalcType === 'sum' || explicitCalcType === 'avg' || explicitCalcType === 'avg_number' || explicitCalcType === 'avg_percentage') {
                                                var vals = [];
                                                var nonEmptyRawCount = 0;
                                                sourceCellsForGrandTotal.forEach(function(cell) {
                                                    var raw = String(getCellRawValue(cell) || '').trim();
                                                    if (raw && raw !== '-') nonEmptyRawCount++;
                                                    var n = toNumeric(getCellRawValue(cell));
                                                    if (!isNaN(n)) vals.push(n);
                                                });
                                                if (vals.length === 0 && nonEmptyRawCount > 0) {
                                                    setSelectionCalcOption('unique', '');
                                                    selectionCalcState = 'manual';
                                                    if (autocalcError) {
                                                        autocalcError.textContent = 'Selected column has non-numeric values. Use Count Unique / Count All Values instead of Sum.';
                                                        autocalcError.classList.remove('hidden');
                                                    }
                                                    if (typeof window.showToast === 'function') window.showToast('notice', 'This column is non-numeric. Use Count Unique/Count All Values.');
                                                    return;
                                                }
                                                var aggAc = aggregateGrandTotalNumericSourceValues(sourceCellsForGrandTotal, explicitCalcType);
                                                var result = aggAc === null ? 0 : aggAc;
                                                displayVal = result.toFixed(2);
                                                if (explicitCalcType === 'avg_percentage') displayVal = displayVal + '%';
                                            } else if (explicitCalcType === 'unique' || explicitCalcType === 'unique_adjust') {
                                                // Grand total unique = sum of per-campus unique counts
                                                // (matches accomplishment overall roll-up behavior).
                                                var uniqueByCampus = {};
                                                sourceCellsForGrandTotal.forEach(function(cell) {
                                                    var v = String(getCellRawValue(cell) || '').trim();
                                                    if (!v || v === '-') return;
                                                    var tr = cell.closest('tr.data-row');
                                                    var campusKey = 'global';
                                                    if (tr) {
                                                        var subKey = String(tr.getAttribute('data-submission-id') || '').trim();
                                                        var userKey = String(tr.getAttribute('data-user-id') || '').trim();
                                                        campusKey = subKey || ('user_' + userKey) || 'global';
                                                    }
                                                    if (!uniqueByCampus[campusKey]) uniqueByCampus[campusKey] = {};
                                                    uniqueByCampus[campusKey][v] = true;
                                                });
                                                var overallUniqueTotal = 0;
                                                Object.keys(uniqueByCampus).forEach(function(k) {
                                                    overallUniqueTotal += Object.keys(uniqueByCampus[k] || {}).length;
                                                });
                                                var gtAdj = explicitCalcType === 'unique_adjust' ? parseUniqueCountAdjustFromUi() : 0;
                                                displayVal = String(Math.max(0, overallUniqueTotal + gtAdj));
                                            } else if (explicitCalcType === 'countif') {
                                                var countAll = 0;
                                                sourceCellsForGrandTotal.forEach(function(cell) {
                                                    var v = String(getCellRawValue(cell) || '').trim();
                                                    if (!v || v === '-') return;
                                                    countAll++;
                                                });
                                                displayVal = String(countAll);
                                            } else if (explicitCalcType === 'count_rows') {
                                                var rowSet = [];
                                                sourceCellsForGrandTotal.forEach(function(cell) {
                                                    var row = cell.closest('tr.data-row');
                                                    if (row && rowSet.indexOf(row) === -1) rowSet.push(row);
                                                });
                                                displayVal = String(rowSet.length);
                                            }
                                            setCellRawValue(gtTd, displayVal);
                                            var gtAutoMap = { ui_calc_type: 'grand-total', ui_formula_operation: explicitCalcType, section_ref: 'grand_total' };
                                            if (explicitCalcType === 'unique_adjust') {
                                                gtAutoMap.count_adjust = parseUniqueCountAdjustFromUi();
                                            }
                                            if (isGrandTotalManualSelectionMode(gtTd)) {
                                                gtAutoMap.source_quarter = 'manual';
                                                var prevGtMap = getCellFormulaMapping(gtTd) || {};
                                                if (String(prevGtMap.grand_total_wizard_type || '') === 'school_year') {
                                                    gtAutoMap.grand_total_wizard_type = 'school_year';
                                                }
                                                if (String(prevGtMap.grand_total_wizard_type || '').trim() === 'calculation') {
                                                    gtAutoMap.grand_total_wizard_type = 'calculation';
                                                }
                                                var uidsGt = [];
                                                sourceCellsForGrandTotal.forEach(function(cell) {
                                                    var trS = cell.closest('tr.data-row');
                                                    if (!trS || trS.classList.contains('grand-total-row') || trS.classList.contains('bg-blue-100')) return;
                                                    var u = typeof ensureDataRowUid === 'function' ? ensureDataRowUid(trS) : (trS.getAttribute('data-row-uid') || '');
                                                    if (u && uidsGt.indexOf(u) === -1) uidsGt.push(u);
                                                });
                                                if (uidsGt.length > 0) gtAutoMap.row_uids = uidsGt;
                                            } else {
                                                var mPrevNonMan = getCellFormulaMapping(gtTd) || {};
                                                if (String(mPrevNonMan.grand_total_wizard_type || '').trim() === 'calculation' && String(mPrevNonMan.source_quarter || '').trim() === 'gt_calc_all') {
                                                    gtAutoMap.grand_total_wizard_type = 'calculation';
                                                    gtAutoMap.source_quarter = 'gt_calc_all';
                                                } else {
                                                    var qForMap = getSelectedGrandTotalQuarter(gtTd);
                                                    if (typeof isGrandTotalSchoolYearScope === 'function' && isGrandTotalSchoolYearScope(gtTd)) {
                                                        var mSyAuto = getCellFormulaMapping(gtTd) || {};
                                                        var syStoreAuto = String(mSyAuto.source_quarter || '').trim().toLowerCase();
                                                        gtAutoMap.source_quarter = isGrandTotalSchoolYearSourceQuarterVal(syStoreAuto) ? syStoreAuto : 'sy_2nd_sem_2024_2025';
                                                    } else if (qForMap) gtAutoMap.source_quarter = qForMap;
                                                }
                                                delete gtAutoMap.row_uids;
                                                delete gtAutoMap.row_indices;
                                            }
                                            setCellFormulaMapping(gtTd, gtAutoMap);
                                            setSelectionCalcOption(explicitCalcType, '');
                                            selectionCalcState = 'manual';
                                            if (autocalcError) autocalcError.classList.add('hidden');
                                            clearSelectionMulti();
                                            if (typeof window.performSaveTableData === 'function') {
                                                window.tableDataDirty = true;
                                                setAutosaveStatus('saving');
                                                window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                                setTimeout(function() {
                                                    window.tableDataDirty = true;
                                                    window.performSaveTableData({ onSuccess: function() { setAutosaveStatus('saved'); } });
                                                }, 250);
                                            } else if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Grand total ' + explicitCalcType + ' applied.');
                                            return;
                                        }
                                    }
                                    var firstDataCell = Array.prototype.slice.call(selectedCells).find(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        return tr && isPlainAggregatableDataRow(tr);
                                    });
                                    var firstCell = firstDataCell || selectedCells[0];
                                    var firstTr = firstCell.closest('tr.data-row');
                                    if (!firstTr || (firstTr.classList.contains('bg-blue-100') && !firstDataCell)) {
                                        if (autocalcError) {
                                            autocalcError.textContent = 'Select data cells (not the blue result row).';
                                            autocalcError.classList.remove('hidden');
                                        }
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Select data cells (not the blue result row).');
                                        return;
                                    }
                                    normalizeSectionBlueRows(firstTr);
                                    var sectionRows = findSectionRowsContainingRow(firstTr);
                                    if (!sectionRows || sectionRows.length === 0) return;
                                    var sectionRowsSet = {};
                                    sectionRows.forEach(function(r) { sectionRowsSet[r] = true; });
                                    var sourceCells = [];
                                    var targetBlueCells = [];
                                    selectedCells.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || !sectionRowsSet[tr]) return;
                                        var c = getColIndex(td);
                                        if (c < 0 || c >= fields.length) return;
                                        if (tr.classList.contains('bg-blue-100')) targetBlueCells.push(td);
                                        else sourceCells.push(td);
                                    });
                                    if (sourceCells.length === 0) {
                                        var msgNoValues = 'Select source data cells in this campus section.';
                                        if (autocalcError) {
                                            autocalcError.textContent = msgNoValues;
                                            autocalcError.classList.remove('hidden');
                                        }
                                        if (typeof window.showToast === 'function') window.showToast('error', msgNoValues);
                                        return;
                                    }
                                    if (targetBlueCells.length === 0) {
                                        var msgNoBlueTarget = 'Select at least one blue result cell as target.';
                                        if (autocalcError) {
                                            autocalcError.textContent = msgNoBlueTarget;
                                            autocalcError.classList.remove('hidden');
                                        }
                                        if (typeof window.showToast === 'function') window.showToast('notice', msgNoBlueTarget);
                                        return;
                                    }
                                    var values = [];
                                    sourceCells.forEach(function(td) {
                                        var input = td.querySelector('input, select, textarea');
                                        if (input) {
                                            if (input.tagName === 'SELECT') {
                                                var opt = input.options[input.selectedIndex];
                                                values.push(((opt ? opt.value : '') || '').toString().trim());
                                            } else {
                                                values.push((input.value || '').toString().trim());
                                            }
                                        } else {
                                            var span = td.querySelector('span');
                                            values.push((span ? span.textContent : td.textContent || '').toString().trim());
                                        }
                                    });
                                    var titleEl = document.getElementById('autocalc-modal-title');
                                    var calcTypeKey = explicitCalcType || (selectionCalcTypeSelect && selectionCalcTypeSelect.value) || (titleEl && autocalcTitles ? Object.keys(autocalcTitles).find(function(k) { return autocalcTitles[k] === titleEl.textContent; }) : '');
                                    if (!calcTypeKey) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Choose a calculation from the list above, then click Apply.');
                                        return;
                                    }
                                    var resultStr = '';
                                    if (calcTypeKey === 'unique' || calcTypeKey === 'unique_adjust') {
                                        var seen = {};
                                        values.forEach(function(v) {
                                            if (v !== '' && !seen[v]) seen[v] = true;
                                        });
                                        var baseU = Object.keys(seen).length;
                                        var adjU = calcTypeKey === 'unique_adjust' ? parseUniqueCountAdjustFromUi() : 0;
                                        resultStr = String(Math.max(0, baseU + adjU));
                                    } else if (calcTypeKey === 'countif') {
                                        resultStr = String(values.filter(function(v) { return v !== ''; }).length);
                                    } else if (calcTypeKey === 'count_rows') {
                                        var uniqueRows = [];
                                        sourceCells.forEach(function(td) {
                                            var tr = td.closest('tr.data-row');
                                            if (tr && !tr.classList.contains('bg-blue-100') && uniqueRows.indexOf(tr) === -1) uniqueRows.push(tr);
                                        });
                                        resultStr = String(uniqueRows.length);
                                    } else if (calcTypeKey === 'sum' || calcTypeKey === 'avg' || calcTypeKey === 'avg_number' || calcTypeKey === 'avg_percentage') {
                                        var nums = values.map(function(v) { return toNumeric(v); });
                                        var total = nums.reduce(function(a, b) { return a + b; }, 0);
                                        var isAvg = calcTypeKey === 'avg' || calcTypeKey === 'avg_number' || calcTypeKey === 'avg_percentage';
                                        if (calcTypeKey === 'avg_percentage') {
                                            var meanAc = nums.length ? total / nums.length : 0;
                                            resultStr = formatBlueSummaryPercentWhole(meanAc);
                                        } else {
                                            resultStr = isAvg
                                                ? (nums.length ? (total / nums.length).toFixed(2) : '0.00')
                                                : total.toFixed(2);
                                        }
                                    } else {
                                        return;
                                    }
                                    var resultRow = targetBlueCells[0].closest('tr.data-row');
                                    // Build row_indices (0-based data row indices within this section) so backend computes over selected cells only
                                    var dataRowsInSection = sectionRows.filter(function(tr) { return !tr.classList.contains('bg-blue-100'); });
                                    var rowIndices = [];
                                    var rowUids = [];
                                    sourceCells.forEach(function(td) {
                                        var tr = td.closest('tr.data-row');
                                        if (!tr || tr.classList.contains('bg-blue-100')) return;
                                        var idx = dataRowsInSection.indexOf(tr);
                                        if (idx >= 0 && rowIndices.indexOf(idx) === -1) rowIndices.push(idx);
                                        var rowUid = ensureDataRowUid(tr);
                                        if (rowUid && rowUids.indexOf(rowUid) === -1) rowUids.push(rowUid);
                                    });
                                    rowIndices.sort(function(a, b) { return a - b; });
                                    rowUids.sort();
                                    var selectedRowIndices = rowIndices.length > 0 ? rowIndices : null;
                                    var selectedRowUids = rowUids.length > 0 ? rowUids : null;
                                    var cellsToWrite = [];
                                    targetBlueCells.forEach(function(td) {
                                        var c = getColIndex(td);
                                        if (c >= 0 && c < fields.length) cellsToWrite.push({ cell: td, colIndex: c });
                                    });
                                    if (cellsToWrite.length === 0) {
                                        var msgInvalidBlue = 'Selected blue target is outside data columns. Select a blue data cell.';
                                        if (autocalcError) {
                                            autocalcError.textContent = msgInvalidBlue;
                                            autocalcError.classList.remove('hidden');
                                        }
                                        if (typeof window.showToast === 'function') window.showToast('notice', msgInvalidBlue);
                                        return;
                                    }
                                    var writeResultToCell = function(cell, resultStr) {
                                        if (!cell) return;
                                        var span = cell.querySelector('span');
                                        var anyInput = cell.querySelector('input, select, textarea');
                                        if (span) {
                                            span.textContent = resultStr;
                                        } else if (anyInput) {
                                            if (anyInput.tagName === 'SELECT') {
                                                var found = false;
                                                for (var i = 0; i < anyInput.options.length; i++) {
                                                    if (String(anyInput.options[i].value) === resultStr) {
                                                        anyInput.selectedIndex = i;
                                                        found = true;
                                                        break;
                                                    }
                                                }
                                                if (!found) anyInput.value = resultStr;
                                            } else {
                                                anyInput.value = resultStr;
                                            }
                                        } else {
                                            cell.textContent = resultStr;
                                        }
                                    };
                                    cellsToWrite.forEach(function(o) { writeResultToCell(o.cell, resultStr); });
                                    var touchedRows = [];
                                    cellsToWrite.forEach(function(o) {
                                        var tr = o.cell ? o.cell.closest('tr.data-row') : null;
                                        if (tr && tr.classList.contains('bg-blue-100') && touchedRows.indexOf(tr) === -1) touchedRows.push(tr);
                                    });
                                    touchedRows.forEach(function(tr) { recomputeBlueRowPerformance(tr); });
                                    var sourceColIndices = [];
                                    sourceCells.forEach(function(td) {
                                        var c = getColIndex(td);
                                        if (c >= 0 && c < fields.length && sourceColIndices.indexOf(c) === -1) sourceColIndices.push(c);
                                    });
                                    sourceColIndices.sort(function(a, b) { return a - b; });
                                    var targetColIndices = [];
                                    cellsToWrite.forEach(function(o) {
                                        if (targetColIndices.indexOf(o.colIndex) === -1) targetColIndices.push(o.colIndex);
                                    });
                                    targetColIndices.sort(function(a, b) { return a - b; });
                                    var primarySourceKey = sourceColIndices.length > 0 ? getFieldKey(fields[sourceColIndices[0]]) : '';
                                    var singleTargetKey = targetColIndices.length === 1 ? getFieldKey(fields[targetColIndices[0]]) : '';
                                    if (autocalcError) { autocalcError.classList.add('hidden'); autocalcError.textContent = ''; }
                                    hideAutocalcModal();
                                    clearSelectionMulti();
                                    // Save immediately so formula result persists on reload (defer 1 tick to ensure DOM is flushed)
                                    if (typeof window.performSaveTableData === 'function') {
                                        window.tableDataDirty = true;
                                        setAutosaveStatus('saving');
                                        var doSave = function() {
                                            window.performSaveTableData({
                                                onSuccess: function() {
                                                    setAutosaveStatus('saved');
                                                },
                                                onDone: function() {
                                                    // Keep current status text unless save is still pending
                                                }
                                            });
                                        };
                                        if (typeof requestAnimationFrame === 'function') {
                                            requestAnimationFrame(function() { doSave(); });
                                        } else {
                                            setTimeout(doSave, 0);
                                        }
                                    } else {
                                        scheduleAutoSave();
                                    }
                                    // Persist summary rule so Planning Coordinator sees the same formula and result on load (with row_indices for selected-cells-only)
                                    var opMap = { unique: 'count_unique', unique_adjust: 'count_unique', countif: 'count_total', count_rows: 'count_rows', sum: 'sum', avg: 'avg', avg_number: 'avg', avg_percentage: 'avg' };
                                    var backendOp = opMap[calcTypeKey];
                                    if (backendOp && typeof summaryRulesUrl !== 'undefined' && primarySourceKey && singleTargetKey) {
                                        var token = document.querySelector('meta[name="csrf-token"]');
                                        token = token ? token.getAttribute('content') : '';
                                        var outputPayload = { target_field: singleTargetKey, operation: backendOp, sourceA: primarySourceKey, ui_calc_type: calcTypeKey };
                                        if (calcTypeKey === 'unique_adjust') {
                                            outputPayload.count_adjust = parseUniqueCountAdjustFromUi();
                                        }
                                        if (sourceColIndices.length > 0) {
                                            outputPayload.source_columns = sourceColIndices.map(function(idx) { return getFieldKey(fields[idx]); });
                                        }
                                        outputPayload.section_ref = buildSectionRefFromRow(firstTr);
                                        upsertSelectionMapping(outputPayload);
                                        if (selectedRowIndices && selectedRowIndices.length > 0) {
                                            outputPayload.row_indices = selectedRowIndices;
                                        }
                                        if (selectedRowUids && selectedRowUids.length > 0) {
                                            outputPayload.row_uids = selectedRowUids;
                                        }
                                        cellsToWrite.forEach(function(o) {
                                            setCellFormulaMapping(o.cell, outputPayload);
                                        });
                                        fetch(summaryRulesUrl, {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                                            body: JSON.stringify({ output: outputPayload })
                                        }).then(function(r) { return r.json(); }).then(function(res) { updateSummaryRulesCacheFromResponse(res); }).catch(function() {});
                                    }
                                    if (resultRow && resultRow.scrollIntoView) {
                                        resultRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    }
                                }

                                if (selectionApplyCalcBtn) {
                                    selectionApplyCalcBtn.addEventListener('click', function() {
                                        if (isQuarterOnlyGrandTotalTarget()) {
                                            var selectedGTQuarter = findActiveGrandTotalTargetCell();
                                            var chosenQ = getSelectedGrandTotalQuarter(selectedGTQuarter || null);
                                            if (!selectedGTQuarter) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select a grand total quarter cell first.');
                                                return;
                                            }
                                            if (!chosenQ && !isGrandTotalManualSelectionMode(selectedGTQuarter)) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Type quarter in the grand total quarter cell first (e.g., 1st Q).');
                                                return;
                                            }
                                            var qChoice = String(chosenQ || '');
                                            var qMap = getCellFormulaMapping(selectedGTQuarter) || {};
                                            qMap.ui_calc_type = 'grand-total';
                                            qMap.section_ref = 'grand_total';
                                            qMap.source_quarter = qChoice;
                                            setCellFormulaMapping(selectedGTQuarter, qMap);
                                            syncGrandTotalQuarterCellValue(selectedGTQuarter);
                                            applyGrandTotalQuarterLabel(selectedGTQuarter);
                                            autoSelectSourcesForBlueCell(selectedGTQuarter, { silent: true, preferFullSection: true });
                                            if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Quarter applied to Grand Total cell.');
                                            if (selectionPopover) selectionPopover.classList.add('hidden');
                                            return;
                                        }
                                        if (typeof isGrandTotalWizardContext === 'function' && isGrandTotalWizardContext()) {
                                            var gtWizTypeEl = document.getElementById('gt-wizard-type');
                                            var gtWizStep2El = document.getElementById('gt-wizard-step2');
                                            var gtWizStep3El = document.getElementById('gt-wizard-step3');
                                            if (!gtWizTypeEl || !gtWizStep2El) return;
                                            var wt = String(gtWizTypeEl.value || '').trim();
                                            if ((wt === 'quarter' || wt === 'school_year' || wt === 'average') && !gtWizStep3El) return;
                                            var w2 = String(gtWizStep2El.value || '').trim();
                                            var w3 = gtWizStep3El ? String(gtWizStep3El.value || '').trim() : '';
                                            if (!wt) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Choose Type of Grand Total (step 1).');
                                                return;
                                            }
                                            if (!w2) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Complete step 2.');
                                                return;
                                            }
                                            var needsWizardStep3 = (wt === 'quarter' || wt === 'school_year' || wt === 'average');
                                            if (needsWizardStep3 && !w3) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Complete step 3.');
                                                return;
                                            }
                                            var gtTdWizard = findActiveGrandTotalTargetCell();
                                            if (!gtTdWizard) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select a grand total cell first.');
                                                return;
                                            }
                                            if (wt === 'quarter' || wt === 'school_year') {
                                                if (wt === 'quarter' && grandTotalQuarterSelect) grandTotalQuarterSelect.value = w2;
                                                var w3Manual = /_manual$/.test(w3);
                                                var w3Op = w3Manual ? w3.replace(/_manual$/, '') : w3;
                                                setGrandTotalManualOverride(gtTdWizard, w3Manual);
                                                var mapQ = getCellFormulaMapping(gtTdWizard) || {};
                                                mapQ.ui_calc_type = 'grand-total';
                                                mapQ.section_ref = 'grand_total';
                                                if (wt === 'school_year') {
                                                    mapQ.grand_total_wizard_type = 'school_year';
                                                    mapQ.source_quarter = w3Manual ? 'manual' : w2;
                                                } else {
                                                    delete mapQ.grand_total_wizard_type;
                                                    mapQ.source_quarter = w3Manual ? 'manual' : w2;
                                                }
                                                mapQ.ui_formula_operation = w3Op;
                                                if (w3Op === 'unique_adjust') {
                                                    mapQ.count_adjust = typeof parseUniqueCountAdjustFromUi === 'function' ? parseUniqueCountAdjustFromUi() : 0;
                                                } else {
                                                    delete mapQ.count_adjust;
                                                }
                                                if (!w3Manual) {
                                                    delete mapQ.row_uids;
                                                    delete mapQ.row_indices;
                                                }
                                                setCellFormulaMapping(gtTdWizard, mapQ);
                                                applyGrandTotalQuarterLabel(gtTdWizard);
                                                if (wt === 'quarter') syncGrandTotalQuarterCellValue(gtTdWizard);
                                                if (!w3Manual) {
                                                    autoSelectSourcesForBlueCell(gtTdWizard, { silent: true, preferFullSection: true });
                                                } else {
                                                    var trW = gtTdWizard.closest('tr.data-row');
                                                    if (trW) setCellSelected(trW, gtTdWizard, true);
                                                    lastClickedRowMulti = trW;
                                                    lastClickedCellMulti = gtTdWizard;
                                                    setSelectionModeState('Using your current manual selection.');
                                                    if (typeof updateFormulaButtonState === 'function') updateFormulaButtonState();
                                                }
                                                applyAutocalcToSelectedRows(w3Op);
                                                return;
                                            }
                                            if (wt === 'calculation') {
                                                if (w2 === 'blue-row-formula') {
                                                    var trGtF = gtTdWizard.closest('tr.data-row');
                                                    if (trGtF) setCellSelected(trGtF, gtTdWizard, true);
                                                    lastClickedRowMulti = trGtF;
                                                    lastClickedCellMulti = gtTdWizard;
                                                    formulaUseBlueRow = true;
                                                    formulaBlueRowOnlyMode = true;
                                                    formulaGrandTotalMode = true;
                                                    formulaMultiSourceMode = false;
                                                    showFormulaModal();
                                                    return;
                                                }
                                                var w2ManualCalc = /_manual$/.test(w2);
                                                var w2OpCalc = w2ManualCalc ? w2.replace(/_manual$/, '') : w2;
                                                if (w2OpCalc === 'count_unique') w2OpCalc = 'unique';
                                                if (w2OpCalc === 'count_total') w2OpCalc = 'countif';
                                                if (['sum', 'unique', 'unique_adjust', 'countif'].indexOf(w2OpCalc) === -1) {
                                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Choose a calculation in step 2.');
                                                    return;
                                                }
                                                setGrandTotalManualOverride(gtTdWizard, w2ManualCalc);
                                                var mapCalcW = getCellFormulaMapping(gtTdWizard) || {};
                                                mapCalcW.ui_calc_type = 'grand-total';
                                                mapCalcW.section_ref = 'grand_total';
                                                mapCalcW.grand_total_wizard_type = 'calculation';
                                                mapCalcW.ui_formula_operation = w2OpCalc;
                                                mapCalcW.source_quarter = w2ManualCalc ? 'manual' : 'gt_calc_all';
                                                if (w2OpCalc === 'unique_adjust') {
                                                    mapCalcW.count_adjust = typeof parseUniqueCountAdjustFromUi === 'function' ? parseUniqueCountAdjustFromUi() : 0;
                                                } else {
                                                    delete mapCalcW.count_adjust;
                                                }
                                                if (!w2ManualCalc) {
                                                    delete mapCalcW.row_uids;
                                                    delete mapCalcW.row_indices;
                                                }
                                                setCellFormulaMapping(gtTdWizard, mapCalcW);
                                                applyGrandTotalQuarterLabel(gtTdWizard);
                                                if (!w2ManualCalc) {
                                                    autoSelectSourcesForBlueCell(gtTdWizard, { silent: true, preferFullSection: true });
                                                } else {
                                                    var trWC = gtTdWizard.closest('tr.data-row');
                                                    if (trWC) setCellSelected(trWC, gtTdWizard, true);
                                                    lastClickedRowMulti = trWC;
                                                    lastClickedCellMulti = gtTdWizard;
                                                    setSelectionModeState('Using your current manual selection.');
                                                    if (typeof updateFormulaButtonState === 'function') updateFormulaButtonState();
                                                }
                                                applyAutocalcToSelectedRows(w2OpCalc);
                                                return;
                                            }
                                            if (wt === 'average' && w3 === 'ctc') {
                                                var ckAgg = resolveCompareColumnKeyForGrandTotalCtc(gtTdWizard);
                                                if (compareCampusTargetColumn) compareCampusTargetColumn.value = ckAgg;
                                                compareCampusTargetAggregateToGrandTotal(gtTdWizard, ckAgg, w2 === 'avg_percentage');
                                                return;
                                            }
                                            return;
                                        }
                                        var calcType = selectionCalcTypeSelect ? selectionCalcTypeSelect.value : '';
                                        if (!calcType) {
                                            if (typeof window.showToast === 'function') {
                                                window.showToast('notice', 'Choose a calculation from the dropdown above (e.g. Count Unique Values for text, Sum for numbers), then click Apply.');
                                            }
                                            return;
                                        }
                                        if (calcType === 'saved-summary-formula') {
                                            if (typeof window.showToast === 'function') {
                                                window.showToast('notice', 'This blue result was created by a previously saved formula. Use the current preset options if you want to replace or standardize it.');
                                            }
                                            return;
                                        }
                                        if (calcType === 'summary-formula') {
                                            formulaUseBlueRow = true;
                                            formulaBlueRowOnlyMode = false;
                                            showFormulaModal();
                                            return;
                                        }
                                        if (calcType === 'blue-row-formula') {
                                            var selectedBlue = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'));
                                            }) : [];
                                            if (selectedBlue.length === 0) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select a blue result cell or grand total cell first, then choose Formula (A & B) and click Apply.');
                                                return;
                                            }
                                            formulaUseBlueRow = true;
                                            formulaBlueRowOnlyMode = true;
                                            formulaGrandTotalMode = selectedBlue.some(function(td) { var tr = td.closest('tr'); return tr && tr.classList.contains('grand-total-row'); });
                                            formulaMultiSourceMode = false;
                                            showFormulaModal();
                                            return;
                                        }
                                        if (calcType === 'blue-row-formula-multi') {
                                            var selectedBlueMulti = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'));
                                            }) : [];
                                            if (selectedBlueMulti.length === 0) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select a blue result cell or grand total cell first, then choose Formula (A+B+C...).');
                                                return;
                                            }
                                            formulaUseBlueRow = true;
                                            formulaBlueRowOnlyMode = true;
                                            formulaGrandTotalMode = selectedBlueMulti.some(function(td) { var tr = td.closest('tr'); return tr && tr.classList.contains('grand-total-row'); });
                                            formulaMultiSourceMode = true;
                                            formulaCustomMode = false;
                                            showFormulaModal();
                                            return;
                                        }
                                        if (calcType === 'blue-row-formula-custom') {
                                            var selectedBlueCustom = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && tr.classList.contains('bg-blue-100');
                                            }) : [];
                                            if (selectedBlueCustom.length === 0) {
                                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select a blue result cell first, then choose Formula (Custom).');
                                                return;
                                            }
                                            formulaUseBlueRow = true;
                                            formulaBlueRowOnlyMode = true;
                                            formulaMultiSourceMode = false;
                                            formulaCustomMode = true;
                                            showFormulaModal();
                                            return;
                                        }
                                        if (calcType === 'compare-campus-target') {
                                            compareCampusTargetForSelectedBlueCells();
                                            return;
                                        }
                                        if (calcType === 'clear-calculation') {
                                            applyClearCalculation();
                                            return;
                                        }
                                        if (calcType === 'aggregate-chain') {
                                            openAggregateChainModal();
                                            return;
                                        }
                                        if (['unique', 'unique_adjust', 'countif', 'count_rows', 'sum', 'avg', 'avg_number', 'avg_percentage'].indexOf(calcType) !== -1) {
                                            applyAutocalcToSelectedRows(calcType);
                                        }
                                    });
                                }
                                if (selectionCalcTypeSelect) {
                                    // Native <select> option lists render outside #selection-popover in the hit-test tree;
                                    // without this, document mousedown clears cell selection when picking a calculation type.
                                    selectionCalcTypeSelect.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                    selectionCalcTypeSelect.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                    selectionCalcTypeSelect.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                    selectionCalcTypeSelect.addEventListener('change', function() {
                                        suppressSelectionClear(1200);
                                        var val = selectionCalcTypeSelect ? selectionCalcTypeSelect.value : '';
                                        selectionCalcState = val ? 'manual' : '';
                                        if (compareCampusTargetOptions) {
                                            compareCampusTargetOptions.classList.toggle('hidden', val !== 'compare-campus-target');
                                            if (val !== 'compare-campus-target' && compareCampusTargetColumn) compareCampusTargetColumn.value = '';
                                        }
                                        if (uniqueAdjustOptions) {
                                            uniqueAdjustOptions.classList.toggle('hidden', val !== 'unique_adjust');
                                            if (val !== 'unique_adjust') {
                                                if (uniqueAdjustOperatorSelect) uniqueAdjustOperatorSelect.value = 'add';
                                                if (uniqueAdjustAmountInput) uniqueAdjustAmountInput.value = '0';
                                            }
                                            if (typeof updateUniqueAdjustPreview === 'function') updateUniqueAdjustPreview();
                                        }
                                        // Keep modal/popover open: changing action should only select it.
                                        // Execution must happen only when user clicks Apply.
                                        updateSelectionLiveHints();
                                        return;
                                        if (val === 'blue-row-formula') {
                                            var selectedBlue = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'));
                                            }) : [];
                                            if (selectedBlue.length > 0) {
                                                formulaUseBlueRow = true;
                                                formulaBlueRowOnlyMode = true;
                                                formulaMultiSourceMode = false;
                                                showFormulaModal();
                                            } else if (typeof window.showToast === 'function') {
                                                window.showToast('notice', 'Select a blue result cell first, then choose Formula (A & B).');
                                            }
                                        } else if (val === 'blue-row-formula-multi') {
                                            var selectedBlueMulti = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row'));
                                            }) : [];
                                            if (selectedBlueMulti.length > 0) {
                                                formulaUseBlueRow = true;
                                                formulaBlueRowOnlyMode = true;
                                                formulaMultiSourceMode = true;
                                                formulaCustomMode = false;
                                                showFormulaModal();
                                            } else if (typeof window.showToast === 'function') {
                                                window.showToast('notice', 'Select a blue result cell first, then choose Formula (A+B+C...).');
                                            }
                                        } else if (val === 'blue-row-formula-custom') {
                                            var selectedBlueCustom = tableBody ? Array.prototype.slice.call(tableBody.querySelectorAll('td.cell-selected')).filter(function(td) {
                                                var tr = td.closest('tr.data-row');
                                                return tr && tr.classList.contains('bg-blue-100');
                                            }) : [];
                                            if (selectedBlueCustom.length > 0) {
                                                formulaUseBlueRow = true;
                                                formulaBlueRowOnlyMode = true;
                                                formulaMultiSourceMode = false;
                                                formulaCustomMode = true;
                                                showFormulaModal();
                                            } else if (typeof window.showToast === 'function') {
                                                window.showToast('notice', 'Select a blue result cell first, then choose Formula (Custom).');
                                            }
                                        } else if (['sum', 'avg_number', 'avg_percentage', 'unique', 'unique_adjust', 'countif'].indexOf(val) !== -1) {
                                            applyAutocalcToSelectedRows(val);
                                        }
                                    });
                                }
                                if (compareCampusTargetColumn) {
                                    compareCampusTargetColumn.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                    compareCampusTargetColumn.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                    compareCampusTargetColumn.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                    compareCampusTargetColumn.addEventListener('change', function() {
                                        suppressSelectionClear(1200);
                                        updateCompareCampusTargetValuePreview();
                                        updateSelectionLiveHints();
                                    });
                                }
                                if (uniqueAdjustOperatorSelect) {
                                    uniqueAdjustOperatorSelect.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                    uniqueAdjustOperatorSelect.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                    uniqueAdjustOperatorSelect.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                    uniqueAdjustOperatorSelect.addEventListener('change', function() {
                                        suppressSelectionClear(1200);
                                        if (typeof updateUniqueAdjustPreview === 'function') updateUniqueAdjustPreview();
                                    });
                                }
                                if (uniqueAdjustAmountInput) {
                                    uniqueAdjustAmountInput.addEventListener('mousedown', function() { suppressSelectionClear(1600); });
                                    uniqueAdjustAmountInput.addEventListener('focus', function() { suppressSelectionClear(1600); });
                                    uniqueAdjustAmountInput.addEventListener('touchstart', function() { suppressSelectionClear(1600); }, { passive: true });
                                    uniqueAdjustAmountInput.addEventListener('input', function() {
                                        if (typeof updateUniqueAdjustPreview === 'function') updateUniqueAdjustPreview();
                                    });
                                }
                                if (grandTotalQuarterSelect) {
                                    grandTotalQuarterSelect.addEventListener('mousedown', function() { suppressSelectionClear(1800); });
                                    grandTotalQuarterSelect.addEventListener('focus', function() { suppressSelectionClear(1800); });
                                    grandTotalQuarterSelect.addEventListener('change', function() {
                                        suppressSelectionClear(1200);
                                        var gtCell = findActiveGrandTotalTargetCell();
                                        if (gtCell) {
                                            setGrandTotalManualOverride(gtCell, false);
                                            var qVal = String(grandTotalQuarterSelect.value || '').trim();
                                            if (qVal) {
                                                var m = getCellFormulaMapping(gtCell) || {};
                                                m.source_quarter = qVal;
                                                setCellFormulaMapping(gtCell, m);
                                            }
                                            applyGrandTotalQuarterLabel(gtCell);
                                            syncGrandTotalQuarterCellValue(gtCell);
                                            autoSelectSourcesForBlueCell(gtCell, { silent: true, preferFullSection: true });
                                        }
                                        updateSelectionLiveHints();
                                    });
                                }
                                (function wireGrandTotalCascadeWizard() {
                                    var gtWiz1 = document.getElementById('gt-wizard-type');
                                    var gtWiz2 = document.getElementById('gt-wizard-step2');
                                    var gtWiz3 = document.getElementById('gt-wizard-step3');
                                    if (gtWiz1) {
                                        gtWiz1.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                        gtWiz1.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                        gtWiz1.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                        gtWiz1.addEventListener('change', function() { suppressSelectionClear(1200); onGrandTotalWizardTypeChange(); });
                                    }
                                    if (gtWiz2) {
                                        gtWiz2.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                        gtWiz2.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                        gtWiz2.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                        gtWiz2.addEventListener('change', function() { suppressSelectionClear(1200); onGrandTotalWizardStep2Change(); });
                                    }
                                    if (gtWiz3) {
                                        gtWiz3.addEventListener('mousedown', function() { suppressSelectionClear(2800); });
                                        gtWiz3.addEventListener('focus', function() { suppressSelectionClear(2800); });
                                        gtWiz3.addEventListener('touchstart', function() { suppressSelectionClear(2800); }, { passive: true });
                                        gtWiz3.addEventListener('change', function() { suppressSelectionClear(1200); onGrandTotalWizardStep3Change(); });
                                    }
                                })();
                                updateFormulaButtonState();
                                updateSelectionLiveHints();
                                if (formulaClose) formulaClose.addEventListener('click', hideFormulaModal);
                                if (formulaCancel) formulaCancel.addEventListener('click', hideFormulaModal);
                                if (formulaApplyConfirm) formulaApplyConfirm.addEventListener('click', applyFormulaToSelectedRows);
                                function doRemoveSelectedCustomOperation() {
                                    var v = formulaOperationSelect ? formulaOperationSelect.value : '';
                                    var expr = (typeof v === 'string' && v.indexOf('custom:') === 0) ? v.substring(7) : (document.getElementById('formula-custom-expr') ? String(document.getElementById('formula-custom-expr').value || '').trim() : '');
                                    if (!expr) return;
                                    var normExpr = normalizeCustomExpr(expr);
                                    var savedList = getSavedCustomFormulas();
                                    if (!savedList.some(function(x) { return normalizeCustomExpr(x) === normExpr; })) return;
                                    removeSavedCustomFormula(expr);
                                    var opts = formulaOperationSelect ? formulaOperationSelect.options : [];
                                    for (var i = opts.length - 1; i >= 0; i--) {
                                        if (String(opts[i].value).indexOf('custom:') === 0 && normalizeCustomExpr(String(opts[i].value).substring(7)) === normExpr) { opts[i].remove(); break; }
                                    }
                                    if (formulaOperationSelect) formulaOperationSelect.value = 'custom';
                                    var formulaCustomExprRm = document.getElementById('formula-custom-expr');
                                    if (formulaCustomExprRm) formulaCustomExprRm.value = normExpr;
                                    if (typeof updateFormulaPreview === 'function') updateFormulaPreview();
                                    if (typeof window.showToast === 'function') window.showToast('notice', 'Deleted from saved list. Expression kept in field for editing.');
                                }
                                var formulaRemoveOperationBtn = document.getElementById('formula-remove-operation-btn');
                                if (formulaRemoveOperationBtn) formulaRemoveOperationBtn.addEventListener('click', doRemoveSelectedCustomOperation);
                                [formulaSourceASelect, formulaSourceBSelect, formulaOperationSelect].forEach(function(el) {
                                    if (el) el.addEventListener('change', function() {
                                        if (el === formulaOperationSelect) {
                                            var v = formulaOperationSelect ? formulaOperationSelect.value : '';
                                            if (typeof v === 'string' && v.indexOf('custom:') === 0) {
                                                var formulaCustomExprSync = document.getElementById('formula-custom-expr');
                                                if (formulaCustomExprSync) formulaCustomExprSync.value = v.substring(7);
                                            }
                                        }
                                        updateFormulaPreview();
                                    });
                                });
                                var formulaCustomExprEl = document.getElementById('formula-custom-expr');
                                if (formulaCustomExprEl) {
                                    formulaCustomExprEl.addEventListener('input', updateFormulaPreview);
                                    formulaCustomExprEl.addEventListener('change', updateFormulaPreview);
                                }
                                document.querySelectorAll('.formula-op-btn').forEach(function(btn) {
                                    btn.addEventListener('click', function() {
                                        var input = document.getElementById('formula-custom-expr');
                                        if (!input) return;
                                        var char = btn.getAttribute('data-char') || '';
                                        if (!char) return;
                                        var start = input.selectionStart || 0;
                                        var end = input.selectionEnd || start;
                                        var text = input.value || '';
                                        var newText = text.substring(0, start) + char + text.substring(end);
                                        input.value = newText;
                                        input.selectionStart = input.selectionEnd = start + char.length;
                                        input.focus();
                                        if (typeof updateFormulaPreview === 'function') updateFormulaPreview();
                                    });
                                });
                                if (autocalcModalClose) autocalcModalClose.addEventListener('click', hideAutocalcModal);
                                if (autocalcCancel) autocalcCancel.addEventListener('click', hideAutocalcModal);
                                if (autocalcApply) autocalcApply.addEventListener('click', applyAutocalcToSelectedRows);
                                if (aggregateChainModalClose) aggregateChainModalClose.addEventListener('click', hideAggregateChainModal);
                                if (aggregateChainCancel) aggregateChainCancel.addEventListener('click', hideAggregateChainModal);
                                if (aggregateChainApplyBtn) aggregateChainApplyBtn.addEventListener('click', applyAggregateChainFromModal);
                                if (aggregateChainAddTerm) aggregateChainAddTerm.addEventListener('click', function() {
                                    var existing = aggregateChainTermsWrap ? aggregateChainTermsWrap.querySelectorAll('[data-ac-term-row]').length : 0;
                                    var maxIdx = aggregateChainCellMeta.length > 0 ? aggregateChainCellMeta.length - 1 : 0;
                                    var nextIdx = Math.min(existing, maxIdx);
                                    addAggregateChainTermRow(nextIdx);
                                    updateAggregateChainPreview();
                                });
                                if (aggregateChainBaseAgg) aggregateChainBaseAgg.addEventListener('change', updateAggregateChainPreview);
                                if (aggregateChainModal) {
                                    aggregateChainModal.addEventListener('click', function(e) {
                                        if (e.target === aggregateChainModal) hideAggregateChainModal();
                                    });
                                }
                                if (campusTargetCompareClose) campusTargetCompareClose.addEventListener('click', hideCampusTargetComparePanel);
                                var autocalcAddResultRowBtn = document.getElementById('autocalc-add-result-row-btn');
                                if (autocalcAddResultRowBtn) {
                                    autocalcAddResultRowBtn.addEventListener('click', function() {
                                        ensureBlueResultRowInSectionForSelection();
                                        applyAutocalcToSelectedRows();
                                    });
                                }
                                var deleteLastBtn = document.getElementById('delete-last-row-btn-multi');
                                if (deleteLastBtn) deleteLastBtn.addEventListener('click', deleteLastRowMulti);
                                var separateRowBtn = document.getElementById('separate-row-btn');
                                if (separateRowBtn) separateRowBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    var anchor = null;
                                    if (typeof getPrimarySelectedRowMulti === 'function') {
                                        anchor = getPrimarySelectedRowMulti();
                                    }
                                    // Do not use blue/grand-total anchor for data-row inserts
                                    if (anchor && (anchor.classList.contains('bg-blue-100') || anchor.classList.contains('grand-total-row'))) anchor = null;
                                    showRowCountDialog(1).then(function(count) {
                                        if (count === null) return;
                                        addMultipleNewRowsMulti(anchor, count);
                                    });
                                });
                                var addRowBtn = document.getElementById('add-row-btn');
                                if (addRowBtn) addRowBtn.addEventListener('click', function(e) { e.preventDefault(); addNewRowMulti(null); });
                                window.__wrapTemplateTableSave(function runPerformSaveTableData(opts) {
                                    opts = opts || {};
                                    normalizeAllSectionsBlueRows();
                                    if (tableBody && typeof window.placeManualTotalRowAfterBlueResults === 'function') {
                                        var manualTotBeforeSave = tableBody.querySelector('tr[data-manual-total-row="1"]:not(#manual-total-empty-row-template)');
                                        if (manualTotBeforeSave) window.placeManualTotalRowAfterBlueResults(manualTotBeforeSave);
                                    }
                                    recomputeAllBlueSections();
                                    recomputeAllGrandTotals();
                                    saveBlueRowSnapshotLocal();
                                    var bySub = collectBySubmission();
                                    var totalRows = bySub.reduce(function(sum, s) { return sum + (s.table_data ? s.table_data.length : 0); }, 0);
                                    if (totalRows === 0) {
                                        if (typeof window.showToast === 'function') window.showToast('notice', 'Add at least one row before saving.');
                                        window.__templateAutosaveFinish(opts);
                                        return;
                                    }
                                    if (Array.isArray(pendingCompareCampusTargetResults) && pendingCompareCampusTargetResults.length > 0) {
                                        pendingCompareCampusTargetResults.forEach(function(pending) {
                                            var subId = pending.submission_id;
                                            var userId = pending.user_id;
                                            var match = bySub.find(function(s) {
                                                var sSub = s.submission_id != null ? parseInt(s.submission_id, 10) : null;
                                                var sUser = s.user_id != null ? parseInt(s.user_id, 10) : null;
                                                if (subId != null && subId > 0 && sSub === subId) return true;
                                                if ((subId == null || subId === 0) && userId != null && userId > 0 && sUser === userId) return true;
                                                return false;
                                            });
                                            if (match && match.table_data) {
                                                var summaryRows = match.table_data.filter(function(r) { var m = r._meta || {}; return (m.row_type || '') === 'summary'; });
                                                var summaryRow = summaryRows.length > 0 ? summaryRows[summaryRows.length - 1] : null;
                                                if (summaryRow && pending.resultFieldKey) {
                                                    summaryRow[pending.resultFieldKey] = pending.resultValue;
                                                }
                                            }
                                        });
                                        pendingCompareCampusTargetResults.length = 0;
                                    }
                                    var token = document.querySelector('meta[name="csrf-token"]');
                                    token = token ? token.getAttribute('content') : '';
                                    var grandTotals = typeof collectGrandTotals === 'function' ? collectGrandTotals() : [];
                                    var kpiFinalizePayload = typeof collectKpiFinalizeTotalRow === 'function' ? collectKpiFinalizeTotalRow() : null;
                                    var manualTotalPayload = typeof collectManualTotalRow === 'function' ? collectManualTotalRow() : null;
                                    var finalizedAccompPayload2 = typeof collectFinalizedAccompForTemplateSave === 'function' ? collectFinalizedAccompForTemplateSave() : null;
                                    var payload = {
                                        by_submission: bySub.map(function(o) {
                                            return {
                                                submission_id: o.submission_id != null ? parseInt(o.submission_id, 10) : null,
                                                user_id: o.user_id != null ? parseInt(o.user_id, 10) : null,
                                                table_data: o.table_data || []
                                            };
                                        }),
                                        grand_totals: grandTotals,
                                        kpi_finalize_total_row: kpiFinalizePayload,
                                        manual_total_row: manualTotalPayload,
                                        finalized_accomp: finalizedAccompPayload2
                                    };
                                    var fetchOpts = {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'X-Draft-Autosave': '1' },
                                        body: JSON.stringify(payload)
                                    };
                                    if (opts.keepalive) fetchOpts.keepalive = true;
                                    fetch(saveUrl, fetchOpts)
                                    .then(function(r) {
                                        if (!r.ok) {
                                            var e = new Error('Server returned ' + r.status);
                                            e.httpStatus = r.status;
                                            throw e;
                                        }
                                        return r.json();
                                    })
                                    .then(function(res) {
                                        if (res.success) {
                                            window.__templateAutosave.retryCount = 0;
                                            window.tableDataDirty = false;
                                            if (opts.onSuccess) opts.onSuccess();
                                            else {
                                                setAutosaveStatus('saved');
                                            }
                                            window.__templateAutosaveFinish(opts);
                                        } else {
                                            setAutosaveStatus('error');
                                            if (typeof window.showToast === 'function') window.showToast('error', res.message || 'Save failed.');
                                            window.__templateAutosaveFinish(opts);
                                        }
                                    })
                                    .catch(function(err) {
                                        console.error('Save table data error:', err);
                                        var httpStatus = err.httpStatus || 0;
                                        if (!httpStatus) {
                                            var m = String(err.message || '').match(/(\d{3})/);
                                            httpStatus = m ? parseInt(m[1], 10) : 0;
                                        }
                                        window.__templateAutosaveHandleFailure(opts, httpStatus, function() {
                                            if (typeof window.showToast === 'function') {
                                                window.showToast('error', 'Failed to save: ' + (err.message || 'Please try again.') + ' Changes will retry on next edit.');
                                            }
                                        });
                                    });
                                });
                                updateDeleteBtnMulti();
                                normalizeAllSectionsBlueRows();
                                if (tableBody && typeof window.placeManualTotalRowAfterBlueResults === 'function') {
                                    var manualTotAfterNorm = tableBody.querySelector('tr[data-manual-total-row="1"]:not(#manual-total-empty-row-template)');
                                    if (manualTotAfterNorm) window.placeManualTotalRowAfterBlueResults(manualTotAfterNorm);
                                }
                                hydrateBlueFormulaMappingsFromTemplate();
                                restoreBlueRowSnapshotLocal();
                                // Server already merged stored blue-row values (compute + applyPersistedSummaryRowsFromSource).
                                // Re-running section sums on load was overwriting correct totals with cross-section template mappings (e.g. 58.68 / 54.32).
                                if (typeof recomputeAllGrandTotals === 'function') recomputeAllGrandTotals();
                            })();
                            </script>
                        @else
                        @if($hasSubmissionData)
                            <p class="text-sm text-gray-600 mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                    Planning Coordinator data @if($latestSubmission->is_draft)(Draft)@endif A- Last updated {{ $latestSubmission->updated_at->format('M d, Y H:i') }}
                                </span>
                            </p>
                        @endif
                        <style>
                            /* Single-table readability: min column width so data is visible */
                            #table-container table { table-layout: auto; width: 100%; }
                            #table-container th, #table-container td { min-width: 10rem; }
                            #table-container th, #table-container td { white-space: normal; word-wrap: break-word; overflow-wrap: break-word; vertical-align: top; }
                            #table-container td input[type="text"], #table-container td input[type="number"], #table-container td select, #table-container td textarea { min-width: 0; width: 100%; max-width: 100%; }
                        </style>
                        <div class="border border-gray-200 rounded-lg relative" id="table-container">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200" id="field-structure-table">
                                    <thead class="bg-gray-50">
                                        @if(!empty($tableHeaderTwoRows))
                                            <tr>
                                                @php $h2ExpIdx = 0; @endphp
                                                @foreach($tableHeaderPlan as $h2)
                                                    @if(($h2['kind'] ?? '') === 'single')
                                                        @php
                                                            $h2ExpIdx++;
                                                            $f2 = $fields[$h2ExpIdx - 1];
                                                            $sh2 = $f2['subheaders'] ?? [];
                                                            $sh2 = is_array($sh2) ? array_values(array_filter(array_map('strval', $sh2))) : [];
                                                            $hasOneSub2 = count($sh2) === 1 && ($sh2[0] ?? '') !== '';
                                                        @endphp
                                                        @if($hasOneSub2)
                                                            <th rowspan="2" class="p-0 align-top text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200">
                                                                <div class="border-b border-gray-300 px-4 py-2 text-center">
                                                                    <span class="block">{{ $f2['label'] ?? 'N/A' }}</span>
                                                                </div>
                                                                <div class="px-4 py-2 text-center">
                                                                    <span class="block text-[10px] font-normal normal-case text-gray-600 leading-tight">{{ $sh2[0] }}</span>
                                                                </div>
                                                            </th>
                                                        @else
                                                            <th rowspan="2" class="px-4 py-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 align-middle">
                                                                <span class="block">{{ $f2['label'] ?? 'N/A' }}</span>
                                                            </th>
                                                        @endif
                                                    @else
                                                        @php $n2 = count($h2['subs'] ?? []); $h2ExpIdx += $n2; @endphp
                                                        <th colspan="{{ $n2 }}" class="px-4 py-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-b border-gray-300 align-middle">
                                                            <span class="block">{{ $h2['parent_label'] ?? '' }}</span>
                                                        </th>
                                                    @endif
                                                @endforeach
                                            </tr>
                                            <tr>
                                                @php $h2ExpIdx = 0; @endphp
                                                @foreach($tableHeaderPlan as $h2)
                                                    @if(($h2['kind'] ?? '') === 'single')
                                                        @php $h2ExpIdx++; @endphp
                                                    @else
                                                        @foreach(($h2['subs'] ?? []) as $sub2)
                                                            @php $h2ExpIdx++; @endphp
                                                            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200">
                                                                <span class="block">{{ $sub2 }}</span>
                                                            </th>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </tr>
                                        @else
                                            <tr>
                                                @foreach($fields as $field)
                                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200">
                                                        <span class="block">{{ $field['label'] ?? 'N/A' }}</span>
                                                        @php
                                                            $sh2b = $field['subheaders'] ?? [];
                                                            $sh2b = is_array($sh2b) ? array_values(array_filter(array_map('strval', $sh2b))) : [];
                                                        @endphp
                                                        @if(count($sh2b) === 1 && ($sh2b[0] ?? '') !== '')
                                                            <span class="block text-[10px] font-normal normal-case text-gray-600 mt-0.5 leading-tight">{{ $sh2b[0] }}</span>
                                                        @endif
                                                    </th>
                                                @endforeach
                                            </tr>
                                        @endif
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="table-body">
                                        @for($i = 0; $i < $defaultRows; $i++)
                                            @php
                                                $row = $hasSubmissionData && isset($submissionRows[$i]) ? $submissionRows[$i] : [];
                                                if (is_object($row)) $row = (array) $row;
                                            @endphp
                                            <tr class="data-row" data-row-index="{{ $i }}">
                                                @foreach($fields as $fIdxSingle => $field)
                                            @php
                                                $fieldKey = $getFieldKey($field);
                                                $valueRaw = $row[$fieldKey] ?? $row[$field['key'] ?? ''] ?? $row[$field['label'] ?? ''] ?? '';
                                                if (is_array($valueRaw) || is_object($valueRaw)) $valueRaw = json_encode($valueRaw);
                                                $valueRaw = (string) $valueRaw;
                                                $value = e(old('table_data.'.$i.'.'.$fieldKey, $valueRaw));
                                            @endphp
                                            <td data-field-col="{{ $fIdxSingle }}" class="px-4 py-1.5 border-r border-gray-200">
                                                @if(($field['type'] ?? 'text') === 'dropdown' && isset($field['options']) && is_array($field['options']) && count($field['options']) > 0)
                                                    <select class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent">
                                                        <option value="">Select...</option>
                                                        @foreach($field['options'] as $option)
                                                            <option value="{{ $option }}" {{ (string)$value === (string)$option ? 'selected' : '' }}>{{ $option }}</option>
                                                        @endforeach
                                                    </select>
                                                @elseif(($field['type'] ?? 'text') === 'textarea')
                                                    <textarea class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none resize-none" rows="2" placeholder="">{{ $value }}</textarea>
                                                @elseif(($field['type'] ?? 'text') === 'number')
                                                    <input type="number" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" placeholder="" value="{{ $value }}">
                                                @else
                                                    @php
                                                        $label = $field['label'] ?? '';
                                                        $isGoogleDriveLink = (stripos($label, 'google') !== false && stripos($label, 'drive') !== false) || (stripos($label, 'supporting') !== false && stripos($label, 'document') !== false);
                                                        $isUrl = $valueRaw && (strpos($valueRaw, 'http') === 0 || strpos($valueRaw, 'https') === 0);
                                                    @endphp
                                                    @if($isGoogleDriveLink)
                                                        @if($isUrl && $valueRaw !== '-')
                                                            <div class="flex items-center justify-center w-full min-h-[28px]">
                                                                <input type="hidden" name="table_data[{{ $i }}][{{ $fieldKey }}]" value="{{ $valueRaw }}">
                                                                <a href="{{ $valueRaw }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 text-sm font-medium underline whitespace-nowrap">Open link</a>
                                                            </div>
                                                        @else
                                                            <div class="flex items-center gap-2 flex-wrap w-full">
                                                                <input type="text" class="flex-1 min-w-0 text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" placeholder="Paste or type link" value="{{ $value }}">
                                                            </div>
                                                        @endif
                                                    @else
                                                        <input type="text" class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" placeholder="" value="{{ $value }}">
                                                    @endif
                                                @endif
                                            </td>
                                                @endforeach
                                            </tr>
                                        @endfor
                                    </tbody>
                                    @if(!$readOnly)
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="{{ count($fields) }}" class="px-4 py-2 align-top"></td>
                                        </tr>
                                    </tfoot>
                                    @endif
                                </table>
                            </div>
                            <div id="delete-last-row-container" class="absolute right-0 flex items-center justify-center opacity-0 transition-opacity duration-200" style="width: 48px;">
                                <button type="button" id="delete-last-row-btn" class="text-red-600 hover:text-red-800 font-bold text-xl leading-none" title="Delete last row">
                                    A
                                </button>
                            </div>
                        </div>

                        <script>
                            @if($readOnly)
                            document.addEventListener('DOMContentLoaded', function() {
                                window.tableDataDirty = false;
                                window.performSaveTableData = function(opts) {
                                    opts = opts || {};
                                    if (typeof opts.onDone === 'function') opts.onDone();
                                    if (typeof opts.onSuccess === 'function') opts.onSuccess();
                                };
                                window.TEMPLATE_SHOW_READ_ONLY = true;
                                var tc = document.getElementById('table-container');
                                if (tc) {
                                    tc.classList.add('template-show-readonly');
                                    tc.querySelectorAll('input, select, textarea').forEach(function(el) {
                                        el.readOnly = true;
                                        el.disabled = true;
                                    });
                                    tc.querySelectorAll('button').forEach(function(b) {
                                        b.disabled = true;
                                        b.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
                                    });
                                }
                            });
                            @else
                            document.addEventListener('DOMContentLoaded', function() {
                                window.tableDataDirty = false;
                                @include('super-admin.templates.partials.template-autosave-queue')
                                const addRowBtn = document.getElementById('add-row-btn');
                                const tableBody = document.getElementById('table-body');
                                const fields = @json($fields);
                                const templateId = {{ $template->id }};
                                const storageKey = 'template_' + templateId + '_row_count';
                                const ROW_COPY_BUFFER_KEY = 'uaps_row_copy_v1';
                                let selectedRowSingle = null;

                                // Load saved row count from localStorage
                                function loadRowCount() {
                                    const savedCount = localStorage.getItem(storageKey);
                                    if (savedCount && parseInt(savedCount) > 5) {
                                        const currentRows = tableBody.querySelectorAll('.data-row').length;
                                        const rowsToAdd = parseInt(savedCount) - currentRows;
                                        if (rowsToAdd > 0) {
                                            for (let i = 0; i < rowsToAdd; i++) {
                                                addNewRow(null);
                                            }
                                        }
                                    }
                                    updateDeleteButtonPosition();
                                }

                                function clearSelectionSingle() {
                                    if (selectedRowSingle) {
                                        selectedRowSingle.classList.remove('ring-2', 'ring-indigo-500');
                                        selectedRowSingle = null;
                                    }
                                }

                                function isCellEmptyValue(v) {
                                    var s = (v === null || v === undefined) ? '' : String(v);
                                    s = s.trim();
                                    return s === '' || s === '-' || s === '-' || s.toLowerCase() === 'select...' || s.toLowerCase() === 'select';
                                }

                                function setInputValueSingle(inputEl, value) {
                                    if (!inputEl) return;
                                    if (inputEl.tagName === 'SELECT') {
                                        inputEl.value = value || '';
                                        inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                        return;
                                    }
                                    inputEl.value = value || '';
                                    inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                                    inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                                }

                                // Save row count to localStorage
                                function saveRowCount() {
                                    const rowCount = tableBody.querySelectorAll('.data-row').length;
                                    localStorage.setItem(storageKey, rowCount.toString());
                                }

                                // Store the last row hover handlers
                                let lastRowMouseEnterHandler = null;
                                let lastRowMouseLeaveHandler = null;
                                let containerMouseEnterHandler = null;
                                let containerMouseLeaveHandler = null;

                                // Update delete button position and hover events
                                function updateDeleteButtonPosition() {
                                    const deleteContainer = document.getElementById('delete-last-row-container');
                                    const rows = tableBody.querySelectorAll('.data-row');
                                    
                                    if (rows.length > 0 && deleteContainer) {
                                        const lastRow = rows[rows.length - 1];
                                        const rowRect = lastRow.getBoundingClientRect();
                                        const containerRect = document.getElementById('table-container').getBoundingClientRect();
                                        
                                        // Calculate position relative to table container
                                        const topOffset = rowRect.top - containerRect.top;
                                        const rowHeight = rowRect.height;
                                        
                                        // Position the button to align with the last row
                                        deleteContainer.style.top = (topOffset + (rowHeight / 2) - 12) + 'px';
                                        
                                        // Remove old event listeners if they exist
                                        if (lastRowMouseEnterHandler && lastRowMouseLeaveHandler) {
                                            lastRow.removeEventListener('mouseenter', lastRowMouseEnterHandler);
                                            lastRow.removeEventListener('mouseleave', lastRowMouseLeaveHandler);
                                        }
                                        
                                        // Create new event handlers
                                        lastRowMouseEnterHandler = function() {
                                            deleteContainer.classList.remove('opacity-0');
                                            deleteContainer.classList.add('opacity-100');
                                        };
                                        
                                        lastRowMouseLeaveHandler = function() {
                                            deleteContainer.classList.remove('opacity-100');
                                            deleteContainer.classList.add('opacity-0');
                                        };
                                        
                                        // Add hover events to the last row
                                        lastRow.addEventListener('mouseenter', lastRowMouseEnterHandler);
                                        lastRow.addEventListener('mouseleave', lastRowMouseLeaveHandler);
                                        
                                        // Remove old container event listeners if they exist
                                        if (containerMouseEnterHandler && containerMouseLeaveHandler) {
                                            deleteContainer.removeEventListener('mouseenter', containerMouseEnterHandler);
                                            deleteContainer.removeEventListener('mouseleave', containerMouseLeaveHandler);
                                        }
                                        
                                        // Create new container event handlers
                                        containerMouseEnterHandler = function() {
                                            deleteContainer.classList.remove('opacity-0');
                                            deleteContainer.classList.add('opacity-100');
                                        };
                                        
                                        containerMouseLeaveHandler = function() {
                                            deleteContainer.classList.remove('opacity-100');
                                            deleteContainer.classList.add('opacity-0');
                                        };
                                        
                                        // Also show on hover over the delete button itself
                                        deleteContainer.addEventListener('mouseenter', containerMouseEnterHandler);
                                        deleteContainer.addEventListener('mouseleave', containerMouseLeaveHandler);
                                    } else if (deleteContainer) {
                                        deleteContainer.classList.remove('opacity-100');
                                        deleteContainer.classList.add('opacity-0');
                                    }
                                }

                                // Add new row (optional: insert after this element; otherwise append at end)
                                function addNewRow(insertAfterElement) {
                                    window.tableDataDirty = true;
                                    const newRow = document.createElement('tr');
                                    newRow.className = 'data-row';
                                    const rowIndex = tableBody.querySelectorAll('.data-row').length;
                                    newRow.setAttribute('data-row-index', rowIndex);

                                    fields.forEach(function(field, fi) {
                                        const cell = document.createElement('td');
                                        cell.setAttribute('data-field-col', String(fi));
                                        cell.className = 'px-4 py-1.5 border-r border-gray-200';

                                        const fieldType = field.type || 'text';
                                        let input;

                                        if (fieldType === 'dropdown' && field.options && Array.isArray(field.options) && field.options.length > 0) {
                                            input = document.createElement('select');
                                            input.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent';

                                            const defaultOption = document.createElement('option');
                                            defaultOption.value = '';
                                            defaultOption.textContent = 'Select...';
                                            input.appendChild(defaultOption);

                                            field.options.forEach(function(option) {
                                                const optionEl = document.createElement('option');
                                                optionEl.value = option;
                                                optionEl.textContent = option;
                                                input.appendChild(optionEl);
                                            });
                                        } else if (fieldType === 'textarea') {
                                            input = document.createElement('textarea');
                                            input.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none resize-none';
                                            input.rows = 2;
                                            input.placeholder = '';
                                        } else if (fieldType === 'number') {
                                            input = document.createElement('input');
                                            input.type = 'number';
                                            input.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none';
                                            input.placeholder = '';
                                        } else {
                                            input = document.createElement('input');
                                            input.type = 'text';
                                            input.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none';
                                            input.placeholder = '';
                                        }

                                        cell.appendChild(input);
                                        newRow.appendChild(cell);
                                    });

                                    if (insertAfterElement) {
                                        insertAfterElement.insertAdjacentElement('afterend', newRow);
                                        clearSelectionSingle();
                                        selectedRowSingle = newRow;
                                        newRow.classList.add('ring-2', 'ring-indigo-500');
                                    } else {
                                        tableBody.appendChild(newRow);
                                    }
                                    updateDeleteButtonPosition();
                                }

                                // Delete last row (and its separator if the last row is right after a gray separator)
                                function deleteLastRow() {
                                    const rows = tableBody.querySelectorAll('.data-row');
                                    if (rows.length > 0) {
                                        window.tableDataDirty = true;
                                        const lastRow = rows[rows.length - 1];
                                        const prev = lastRow.previousElementSibling;
                                        const next = lastRow.nextElementSibling;
                                        const lastWasBlue = lastRow.classList.contains('bg-blue-100');
                                        lastRow.remove();
                                        if (prev && prev.classList.contains('separator-row')) prev.remove();
                                        if (lastWasBlue && next && next.classList.contains('separator-row')) next.remove();
                                        saveRowCount();
                                        updateDeleteButtonPosition();
                                    }
                                }

                                // Separate: gray separator, then start a new section with one clean data row.
                                function addBlueSummaryRowAfterSepSingle(separatorRowEl) {
                                    const newRow = document.createElement('tr');
                                    newRow.className = 'data-row bg-blue-100 group border-l-4 border-indigo-200';
                                    newRow.setAttribute('data-row-type', 'summary');
                                    fields.forEach(function(field, ci) {
                                        const cell = document.createElement('td');
                                        cell.setAttribute('data-field-col', String(ci));
                                        cell.className = 'px-4 py-1.5 border-r border-gray-200 bg-blue-100';
                                        if (ci === 0) {
                                            const span = document.createElement('span');
                                            span.className = 'text-sm font-semibold text-gray-800';
                                            span.textContent = '';
                                            cell.appendChild(span);
                                        } else {
                                            const inp = document.createElement('input');
                                            inp.type = 'text';
                                            inp.className = 'w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent font-semibold';
                                            inp.value = '';
                                            cell.appendChild(inp);
                                        }
                                        newRow.appendChild(cell);
                                    });
                                    separatorRowEl.insertAdjacentElement('afterend', newRow);
                                    return newRow;
                                }
                                function addSeparateRowSingle() {
                                    const colCount = fields.length;
                                    const separatorRow = document.createElement('tr');
                                    separatorRow.className = 'separator-row';
                                    const sepCell = document.createElement('td');
                                    sepCell.setAttribute('colspan', colCount);
                                    sepCell.className = 'h-4 min-h-[1rem] px-4 py-2 bg-gray-200 border-t-2 border-b-2 border-gray-300';
                                    separatorRow.appendChild(sepCell);

                                    let firstDataRow = null;
                                    if (selectedRowSingle) {
                                        selectedRowSingle.insertAdjacentElement('afterend', separatorRow);
                                        firstDataRow = addNewRow(separatorRow);
                                    } else {
                                        tableBody.appendChild(separatorRow);
                                        firstDataRow = addNewRow(separatorRow);
                                    }
                                    if (!firstDataRow) firstDataRow = separatorRow.nextElementSibling;
                                    if (firstDataRow && firstDataRow.classList && firstDataRow.classList.contains('data-row')) {
                                        addBlueSummaryRowAfterSepSingle(firstDataRow);
                                        clearSelectionSingle();
                                        selectedRowSingle = firstDataRow;
                                        firstDataRow.classList.add('ring-2', 'ring-indigo-500');
                                        const firstInput = firstDataRow.querySelector('input, select, textarea');
                                        if (firstInput && typeof firstInput.focus === 'function') firstInput.focus();
                                    }
                                    window.tableDataDirty = true;
                                    saveRowCount();
                                    updateDeleteButtonPosition();
                                }

                                // Add row button event
                                if (addRowBtn && tableBody) {
                                    addRowBtn.addEventListener('click', function() {
                                        addNewRow(selectedRowSingle || null);
                                        saveRowCount();
                                    });
                                }

                                // Row selection: click a data row to select it (for Add Row / Separate)
                                if (tableBody) {
                                    tableBody.addEventListener('click', function(e) {
                                        const tr = e.target.closest('tr.data-row');
                                        if (tr && !tr.classList.contains('separator-row')) {
                                            clearSelectionSingle();
                                            selectedRowSingle = tr;
                                            tr.classList.add('ring-2', 'ring-indigo-500');
                                        }
                                    });
                                    tableBody.addEventListener('input', function(e) { if (e.target.matches('input, select, textarea')) window.tableDataDirty = true; });
                                    tableBody.addEventListener('change', function(e) { if (e.target.matches('input, select, textarea')) window.tableDataDirty = true; });
                                }

                                // Delete last row button event
                                const deleteLastRowBtn = document.getElementById('delete-last-row-btn');
                                if (deleteLastRowBtn) {
                                    deleteLastRowBtn.addEventListener('click', function() {
                                        deleteLastRow();
                                    });
                                }
                                
                                // Custom modal dialog to replace window.prompt for "add N rows" (single-table editor)
                                function showRowCountDialog(defaultCount) {
                                    return new Promise(function(resolve) {
                                        var backdropId = 'sa-rowcount-dialog-backdrop';
                                        var existing = document.getElementById(backdropId);
                                        var backdrop = existing || document.createElement('div');
                                        if (!existing) {
                                            backdrop.id = backdropId;
                                            backdrop.className = 'fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[100000] flex items-center justify-center';
                                            backdrop.style.display = 'none';
                                            var modal = document.createElement('div');
                                            modal.className = 'bg-white rounded-xl shadow-2xl max-w-[90vw] w-[420px] border border-gray-200';
                                            modal.innerHTML = '' +
                                                '<div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">' +
                                                '  <h4 class="text-sm font-semibold text-gray-900">Add rows</h4>' +
                                                '  <button type="button" id="sa-rowcount-dialog-close-single" class="text-gray-400 hover:text-gray-600" aria-label="Close">' +
                                                '    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                                                '  </button>' +
                                                '</div>' +
                                                '<div class="px-5 py-4 space-y-3">' +
                                                '  <p class="text-xs text-gray-600">How many rows do you want to add?</p>' +
                                                '  <input id="sa-rowcount-dialog-input-single" type="number" min="1" max="100" step="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />' +
                                                '</div>' +
                                                '<div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">' +
                                                '  <button type="button" id="sa-rowcount-dialog-cancel-single" class="px-4 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>' +
                                                '  <button type="button" id="sa-rowcount-dialog-ok-single" class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">OK</button>' +
                                                '</div>';
                                            backdrop.appendChild(modal);
                                            document.body.appendChild(backdrop);
                                        }
                                        
                                        var input = document.getElementById('sa-rowcount-dialog-input-single') || document.getElementById('sa-rowcount-dialog-input');
                                        var okBtn = document.getElementById('sa-rowcount-dialog-ok-single') || document.getElementById('sa-rowcount-dialog-ok');
                                        var cancelBtn = document.getElementById('sa-rowcount-dialog-cancel-single') || document.getElementById('sa-rowcount-dialog-cancel');
                                        var closeBtn = document.getElementById('sa-rowcount-dialog-close-single') || document.getElementById('sa-rowcount-dialog-close');

                                        var resolved = false;
                                        function cleanup() {
                                            if (resolved) return;
                                            resolved = true;
                                            backdrop.style.display = 'none';
                                            if (typeof document !== 'undefined') document.body.style.overflow = '';
                                        }
                                        function closeWith(val) {
                                            cleanup();
                                            resolve(val);
                                        }

                                        backdrop.style.display = 'flex';
                                        document.body.style.overflow = 'hidden';

                                        if (input) {
                                            input.value = (defaultCount && Number.isFinite(defaultCount)) ? String(defaultCount) : '1';
                                            setTimeout(function() { try { input.focus(); input.select(); } catch (e) {} }, 0);
                                        }

                                        var escHandler = function(ev) {
                                            if (ev.key === 'Escape') {
                                                ev.preventDefault();
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                            }
                                        };
                                        document.addEventListener('keydown', escHandler, true);

                                        // Enter should trigger OK when focus is on the input.
                                        var enterHandler = function(ev) {
                                            if (ev.key !== 'Enter') return;
                                            var active = document.activeElement;
                                            if (!input) return;
                                            var isOnInput = (active === input);
                                            if (!isOnInput) return;
                                            ev.preventDefault();
                                            if (okBtn && typeof okBtn.onclick === 'function') okBtn.onclick();
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        document.addEventListener('keydown', enterHandler, true);

                                        if (okBtn) okBtn.onclick = function() {
                                            var raw = input ? String(input.value || '').trim() : '';
                                            var count = parseInt(raw, 10);
                                            if (!Number.isFinite(count) || count <= 0) {
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                                document.removeEventListener('keydown', enterHandler, true);
                                                return;
                                            }
                                            count = Math.min(count, 100);
                                            closeWith(count);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        if (cancelBtn) cancelBtn.onclick = function() {
                                            closeWith(null);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        if (closeBtn) closeBtn.onclick = function() {
                                            closeWith(null);
                                            document.removeEventListener('keydown', escHandler, true);
                                            document.removeEventListener('keydown', enterHandler, true);
                                        };
                                        backdrop.onclick = function(e) {
                                            if (e.target === backdrop) {
                                                closeWith(null);
                                                document.removeEventListener('keydown', escHandler, true);
                                                document.removeEventListener('keydown', enterHandler, true);
                                            }
                                        };
                                    });
                                }
                                const separateRowBtnSingle = document.getElementById('separate-row-btn');
                                if (separateRowBtnSingle) {
                                    separateRowBtnSingle.addEventListener('click', function() {
                                        showRowCountDialog(1).then(function(count) {
                                            if (count === null) return;
                                            for (var i = 0; i < count; i++) addNewRow(null);
                                            updateDeleteButtonPosition();
                                        });
                                    });
                                }

                                // Update button position on window resize
                                window.addEventListener('resize', function() {
                                    updateDeleteButtonPosition();
                                });

                                // Initial position update
                                setTimeout(function() {
                                    updateDeleteButtonPosition();
                                }, 100);

                                // Load saved row count on page load
                                loadRowCount();

                                // Ctrl+Shift+C/V: copy/paste whole row (cross-template via localStorage)
                                // - Ctrl+Shift+C copies the selected (or focused) row into localStorage
                                // - Ctrl+Shift+V pastes into the selected row, only filling empty cells
                                document.addEventListener('keydown', function(e) {
                                    var ctrl = !!(e.ctrlKey || e.metaKey);
                                    if (!ctrl) return;
                                    if (!e.shiftKey) return;
                                    var key = String(e.key || '').toLowerCase();
                                    if (key !== 'c' && key !== 'v') return;
                                    if (!tableBody) return;

                                    var active = document.activeElement;
                                    if (!active) return;

                                    var tr = selectedRowSingle;
                                    if (!tr && tableBody.contains(active) && active.closest) {
                                        tr = active.closest('tr.data-row');
                                    }
                                    if (!tr) return;
                                    if (tr.classList.contains('bg-blue-100') || tr.classList.contains('grand-total-row')) return;

                                    if (key === 'c') {
                                        var rowData = {};
                                        var tds = window.getRowTdCells(tr);
                                        for (var c = 0; c < fields.length; c++) {
                                            var td = tds[c];
                                            if (!td) continue;
                                            var field = fields[c];
                                            var fieldKey = getFieldKey(field);
                                            var input = td.querySelector('input, select, textarea');
                                            if (!input) continue;
                                            var rawVal2 = input.value || '';
                                            rowData[String(fieldKey)] = isCellEmptyValue(rawVal2) ? '' : String(rawVal2);
                                        }
                                        try {
                                            localStorage.setItem(ROW_COPY_BUFFER_KEY, JSON.stringify({ v: 2, rows: [rowData] }));
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Copied row (cross-template).');
                                        } catch (err) {}
                                        e.preventDefault();
                                        return;
                                    }

                                    if (key === 'v') {
                                        var raw = null;
                                        try { raw = localStorage.getItem(ROW_COPY_BUFFER_KEY); } catch (err2) {}
                                        if (!raw) return;
                                        var parsedClipSingle = null;
                                        try { parsedClipSingle = JSON.parse(raw || '{}'); } catch (err3) { parsedClipSingle = null; }
                                        if (!parsedClipSingle || typeof parsedClipSingle !== 'object') return;
                                        var clipboardRowsSingle = [];
                                        if (parsedClipSingle.v === 2 && Array.isArray(parsedClipSingle.rows)) {
                                            clipboardRowsSingle = parsedClipSingle.rows.filter(function(r) { return r && typeof r === 'object'; });
                                        } else {
                                            clipboardRowsSingle = [parsedClipSingle];
                                        }
                                        if (!clipboardRowsSingle.length) return;
                                        // Single-table editor: paste first copied row into the selected row (multi-row clipboard from Field Structure uses first row here).
                                        var rowData2 = clipboardRowsSingle[0];

                                        var targetTr = selectedRowSingle || tr;
                                        var targetTds = window.getRowTdCells(targetTr);
                                        var applied = 0;
                                        for (var c2 = 0; c2 < fields.length; c2++) {
                                            var td2 = targetTds[c2];
                                            if (!td2) continue;
                                            var field2 = fields[c2];
                                            var fieldKey2 = getFieldKey(field2);
                                            if (!Object.prototype.hasOwnProperty.call(rowData2, String(fieldKey2))) continue;
                                            var input2 = td2.querySelector('input, select, textarea');
                                            if (!input2) continue;
                                            var currentVal = input2.value || '';
                                            if (!isCellEmptyValue(currentVal)) continue;
                                            var nextVal2 = rowData2[String(fieldKey2)];
                                            if (isCellEmptyValue(nextVal2)) continue; // avoid writing placeholders
                                            setInputValueSingle(input2, nextVal2);
                                            // If this cell is a link cell, keep the visible href in sync.
                                            var a2 = td2.querySelector('a[href]');
                                            if (a2 && nextVal2) a2.setAttribute('href', nextVal2);
                                            applied++;
                                        }

                                        if (applied > 0) {
                                            window.tableDataDirty = true;
                                            if (typeof scheduleAutoSave === 'function') scheduleAutoSave();
                                            if (typeof setAutosaveStatus === 'function') setAutosaveStatus('saving');
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Pasted row into selected template.');
                                        } else {
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'No empty cells to paste into.');
                                        }
                                        e.preventDefault();
                                        return;
                                    }
                                });

                                // Field key (same as PHP getFieldKey)
                                function getFieldKey(field) {
                                    var k = field.key || field.name || null;
                                    if (k) return String(k);
                                    var label = field.label || '';
                                    return label.replace(/[^a-z0-9]+/gi, '_').toLowerCase().replace(/^_+|_+$/g, '');
                                }

                                // Collect table data from current DOM for Save
                                function collectTableData() {
                                    var rows = tableBody.querySelectorAll('.data-row');
                                    var data = [];
                                    for (var r = 0; r < rows.length; r++) {
                                        var row = {};
                                        var cells = window.getRowTdCells(rows[r]);
                                        for (var c = 0; c < fields.length; c++) {
                                            var field = fields[c];
                                            var key = getFieldKey(field);
                                            var cell = cells[c];
                                            if (!cell) continue;
                                            var input = cell.querySelector('input, select, textarea');
                                            if (input) row[key] = input.value || '';
                                        }
                                        if (rows[r].previousElementSibling && rows[r].previousElementSibling.classList.contains('separator-row')) {
                                            row._after_separator = true;
                                        }
                                        data.push(row);
                                    }
                                    return data;
                                }

                                // Fallback saver for legacy/simple mode only.
                                // Do NOT override the multi-block saver above (it persists CTC/grand totals/by_submission).
                                if (typeof window.performSaveTableData !== 'function') {
                                    window.__wrapTemplateTableSave(function runPerformSaveTableDataSimple(opts) {
                                        opts = opts || {};
                                        var tableData = collectTableData();
                                        if (tableData.length === 0) {
                                            if (typeof window.showToast === 'function') window.showToast('notice', 'Add at least one row before saving.');
                                            window.__templateAutosaveFinish(opts);
                                            return;
                                        }
                                        var saveUrl = '{{ route("super-admin.templates.save-table-data", $template) }}';
                                        var token = document.querySelector('meta[name="csrf-token"]');
                                        token = token ? token.getAttribute('content') : '';
                                        fetch(saveUrl, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'Accept': 'application/json',
                                                'X-CSRF-TOKEN': token,
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'X-Draft-Autosave': '1'
                                            },
                                            body: JSON.stringify({ table_data: tableData })
                                        })
                                        .then(function(r) {
                                            if (!r.ok) {
                                                var e = new Error('Server returned ' + r.status);
                                                e.httpStatus = r.status;
                                                throw e;
                                            }
                                            return r.json();
                                        })
                                        .then(function(res) {
                                            if (res.success) {
                                                window.__templateAutosave.retryCount = 0;
                                                window.tableDataDirty = false;
                                                if (opts.onSuccess) opts.onSuccess();
                                                else if (typeof window.showToast === 'function') {
                                                    window.showToast('success', res.message || 'Saved successfully.');
                                                }
                                                window.__templateAutosaveFinish(opts);
                                            } else {
                                                if (typeof window.showToast === 'function') window.showToast('error', res.message || 'Save failed.');
                                                window.__templateAutosaveFinish(opts);
                                            }
                                        })
                                        .catch(function(err) {
                                            var httpStatus = err.httpStatus || 0;
                                            window.__templateAutosaveHandleFailure(opts, httpStatus, function() {
                                                if (typeof window.showToast === 'function') window.showToast('error', 'Failed to save table data.');
                                            });
                                        });
                                    });
                                }
                            });
                            @endif
                        </script>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No fields defined for this template.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('super-admin.templates.partials.show-audit-trail')
    @include('super-admin.templates.partials.show-back-to-top')
    @include('super-admin.templates.partials.show-quick-access-footer')

</x-app-layout>


