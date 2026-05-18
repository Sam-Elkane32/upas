<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Submission') }}
        </h2>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-full w-full sm:px-6 lg:px-8">
            {{-- success / error: layouts.flash-popup (toast, auto-dismiss ~3s) --}}

            @php
                $displayTemplate = $submission->template;
                $isTemplateLocked = $displayTemplate && $displayTemplate->is_locked;
            @endphp

            {{-- Lock banner --}}
            @if($isTemplateLocked)
            <div class="mb-6 flex items-center gap-3 px-5 py-4 bg-red-50 border border-red-300 rounded-xl shadow-sm">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-red-700">This template is locked</p>
                    <p class="text-xs text-red-600 mt-0.5">The administrator has locked this template. All inputs and submissions are disabled until it is unlocked.</p>
                </div>
            </div>
            @endif

            <form action="{{ route('campus-user.update-submission', $submission) }}" method="POST" id="submission-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="submission_id" value="{{ $submission->id }}">
                
                <!-- Template Information (match Super Admin: use linked template as source of truth) -->
                @php
                    $initialSubmissionQuarter = filled($submission->quarter) ? $submission->quarter : '1st Q';
                    $tdForQ = $submission->table_data;
                    if (is_string($tdForQ)) {
                        $tdForQ = json_decode($tdForQ, true);
                    }
                    if (is_array($tdForQ)) {
                        foreach ($tdForQ as $r) {
                            if (!is_array($r)) {
                                continue;
                            }
                            $meta = $r['_meta'] ?? [];
                            if (is_string($meta)) {
                                $meta = json_decode($meta, true) ?? [];
                            }
                            if (is_array($meta) && (($meta['row_type'] ?? 'data') === 'summary')) {
                                continue;
                            }
                            foreach ($r as $k => $v) {
                                if (strtolower((string) $k) === 'quarter' && trim((string) $v) !== '') {
                                    $initialSubmissionQuarter = trim((string) $v);
                                    break 2;
                                }
                            }
                        }
                    }
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Information</h3>
                        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Template Code</div>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                                {{ $displayTemplate ? $displayTemplate->template_code : $submission->template_code }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Strategic Goal</div>
                                        <div class="mt-1 text-sm font-medium text-gray-900">{{ $displayTemplate ? $displayTemplate->sg_code : $submission->sg_code }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">KPI Title</div>
                                        <div class="mt-1 text-sm text-gray-900 leading-relaxed whitespace-pre-wrap break-words">{{ $displayTemplate ? $displayTemplate->kpi_title : $submission->kpi_title }}</div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Key Result Area (KRA)</div>
                                        <div class="mt-1 text-sm text-gray-900">{{ $displayTemplate ? $displayTemplate->kra_title : $submission->kra_title }}</div>
                                    </div>
                                </div>
                            </div>
                            {{-- submission.quarter is derived from the first non-empty per-row Quarter on save (mixed Q rows supported) --}}
                            <input type="hidden" name="quarter" id="submission-quarter-hidden" value="{{ $initialSubmissionQuarter }}">
                        </div>
                        <input type="hidden" name="template_code" id="template_code" value="{{ $submission->template_code }}">
                    </div>
                </div>

                <!-- Accomplishment Data -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            Accomplishment Data
                        </h3>

                        <style>
                            .accomplishment-table-wrapper { overflow-x: auto; overflow-y: visible; max-height: none; -webkit-overflow-scrolling: touch; }
                            .accomplishment-data-table { table-layout: auto; width: 100%; }
                            .accomplishment-data-table th,
                            .accomplishment-data-table td { min-width: 10rem; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; vertical-align: top; }
                            #accomplishment-table-container td input,
                            #accomplishment-table-container td select,
                            #accomplishment-table-container td textarea {
                                background: transparent !important;
                                border: none !important;
                                box-shadow: none !important;
                                outline: none;
                                border-radius: 0;
                                min-width: 0;
                                width: 100%;
                                max-width: 100%;
                            }
                            #accomplishment-table-container td input:focus,
                            #accomplishment-table-container td select:focus,
                            #accomplishment-table-container td textarea:focus {
                                box-shadow: none !important;
                                outline: none;
                            }
                            #accomplishment-table-container td.cell-selected {
                                outline: none;
                                position: relative;
                            }
                            #accomplishment-table-container td.cell-selected::after {
                                content: '';
                                position: absolute;
                                inset: 0;
                                border: 2px solid rgb(99 102 241);
                                pointer-events: none;
                                z-index: 30;
                                box-sizing: border-box;
                            }
                            .accomplishment-data-table a { word-break: break-all; display: inline-block; max-width: 100%; }
                            #accomplishment-table-container.accomplishment-ctrl-drag-selecting {
                                user-select: none;
                                -webkit-user-select: none;
                            }
                        </style>

                        <!-- Dynamic Table -->
                        <div id="template-fields" class="hidden relative">
                            @php
                            // Planning Coordinator: omit Variance / Rate / Descriptive Rating so grid + blue row match editable columns (ComputeService::excludePerformanceMetricFieldsForPlanningCoordinator).
                            $pcSchema = $submission->template ? $submission->template->getSchemaFields() : [];
                            $pcSchema = \App\Services\ComputeService::excludePerformanceMetricFieldsForPlanningCoordinator($pcSchema);
                            $pcSchemaForGrid = \App\Models\Template::expandSchemaForDataGrid($pcSchema);
                            $accomplishmentColCount = count($pcSchemaForGrid);
                            @endphp
                            <div id="accomplishment-table-container" class="accomplishment-table-wrapper border border-gray-200 rounded-lg relative">
                                <table class="accomplishment-data-table min-w-full">
                                    <thead class="bg-gray-50" id="table-headers">
                                        {{-- Header row(s) generated in renderTemplateFields() --}}
                                    </thead>
                                    @if(!empty($otherCoordinatorRows))
                                    @php
                                        $schemaFields = $pcSchemaForGrid;
                                        $normalizeKey = function($label) {
                                            $k = is_string($label) ? $label : '';
                                            $k = str_replace(['"', "'"], '', $k);
                                            $k = strtolower(trim($k));
                                            $k = preg_replace('/\s+/', '_', $k);
                                            $k = preg_replace('/[^a-z0-9_]/', '_', $k);
                                            $k = preg_replace('/_+/', '_', $k);
                                            return preg_replace('/^_+|_+$/', '', $k);
                                        };
                                        $getCellValue = function($row, $field, $fieldIndex = null) use ($normalizeKey) {
                                            if (!is_array($row)) {
                                                return '';
                                            }
                                            $candidates = [];
                                            foreach (['key', 'name', 'label'] as $attr) {
                                                if (! isset($field[$attr]) || $field[$attr] === '') {
                                                    continue;
                                                }
                                                $candidates[] = $normalizeKey((string) $field[$attr]);
                                            }
                                            $candidates = array_values(array_unique(array_filter($candidates)));
                                            foreach ($candidates as $ck) {
                                                if ($ck !== '' && array_key_exists($ck, $row)) {
                                                    return $row[$ck];
                                                }
                                            }
                                            foreach (array_keys($row) as $rk) {
                                                if ($rk === '_meta' || $rk === '_after_separator') {
                                                    continue;
                                                }
                                                $nr = $normalizeKey((string) $rk);
                                                foreach ($candidates as $ck) {
                                                    if ($ck !== '' && ($nr === $ck || strtolower((string) $rk) === $ck)) {
                                                        return $row[$rk];
                                                    }
                                                }
                                            }

                                            // Never use row key *enumeration order* as column order — it shifts summary rows vs headers.
                                            return '';
                                        };
                                        $colCount = count($schemaFields);
                                    @endphp
                                    <tbody id="readonly-coordinators-body" class="bg-gray-50">
                                        @foreach($otherCoordinatorRows as $block)
                                        <tr class="bg-gray-100 border-t-2 border-gray-300">
                                            <td colspan="{{ $colCount }}" class="px-4 py-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">{{ $block['submitter_name'] }} (read-only)</td>
                                        </tr>
                                        @if(!empty($block['table_data']) && ($block['has_data'] ?? true))
                                        @foreach($block['table_data'] as $row)
                                        @php
                                            $meta = $row['_meta'] ?? null;
                                            if (is_string($meta)) { $meta = json_decode($meta, true); }
                                            $meta = is_array($meta) ? $meta : [];
                                            $isSummaryRow = ($meta['row_type'] ?? 'data') === 'summary';
                                        @endphp
                                        <tr class="{{ $isSummaryRow ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-100' }}" data-row-type="{{ $isSummaryRow ? 'summary' : 'readonly' }}">
                                            @foreach($schemaFields as $fieldIdx => $f)
                                            @php
                                                $val = $getCellValue($row, $f, $fieldIdx);
                                                if (is_array($val) || is_object($val)) $val = json_encode($val);
                                                $val = (string) $val;
                                                if ($isSummaryRow && strtolower(trim($val)) === 'summary') {
                                                    $val = '';
                                                }
                                                $isLink = $val !== '' && filter_var($val, FILTER_VALIDATE_URL);
                                            @endphp
                                            <td class="px-4 py-2 text-sm border-r border-gray-200 {{ $isSummaryRow ? 'bg-blue-100 text-gray-800 font-semibold' : 'text-gray-700' }} {{ !$isSummaryRow && $isLink ? 'text-blue-600' : '' }} break-words">
                                                @if($isLink)
                                                <a href="{{ $val }}" target="_blank" rel="noopener noreferrer" class="underline break-all">{{ $val }}</a>
                                                @else
                                                {{ $val ?: '—' }}
                                                @endif
                                            </td>
                                            @endforeach
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr class="hover:bg-gray-50" data-row-type="readonly">
                                            <td colspan="{{ $colCount }}" class="px-4 py-3 text-sm text-gray-500 italic">No data from this coordinator yet.</td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                    @endif
                                    <tbody class="bg-white" id="table-body">
                                        <!-- Your rows (editable) - continue below others -->
                                    </tbody>
                                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                                        <tr>
                                            <td colspan="{{ $accomplishmentColCount }}" class="px-4 py-3 align-top">
                                                <p class="text-xs text-gray-500">Click a row cell to select it, then use <strong>Add row below</strong> or <strong>Separate</strong> that appears next to the row. Hold <strong>Ctrl</strong> (or <strong>Cmd</strong>) and drag across cells to select a rectangle; <strong>Ctrl</strong>+click still toggles individual cells.</p>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center">
                            <button type="button" id="back-btn"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                                Back
                            </button>
                            <div class="flex items-center space-x-3">
                                @if($isTemplateLocked)
                                    <span class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-md cursor-not-allowed">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zM10 11V7a2 2 0 114 0v4"/>
                                        </svg>
                                        Template Locked — Submissions Disabled
                                    </span>
                                @else
                                    <span id="draft-save-status" class="text-xs text-gray-400" aria-live="polite"></span>
                                    <button type="submit" name="submit_action" value="draft" id="save-draft-btn" formnovalidate
                                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Save Draft
                                    </button>
                                    <button type="submit" name="submit_action" value="submit" id="save-submission-btn" formnovalidate
                                            class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                            aria-label="Submit template">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h11a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Submit Template
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const TEMPLATE_IS_LOCKED = {{ $isTemplateLocked ? 'true' : 'false' }};
        document.addEventListener('DOMContentLoaded', function() {
            const templateSelect = document.querySelector('input[name="template_code"]');
            const templateFields = document.getElementById('template-fields');
            const tableHeaders = document.getElementById('table-headers');
            const tableBody = document.getElementById('table-body');
            const addRowBtn = null; // Removed: Add Another Row now via popover on cell click (match Super Admin)
            const saveDraftBtn = document.getElementById('save-draft-btn');
            const submitBtn = document.getElementById('save-submission-btn');
            const form = document.getElementById('submission-form');
            const draftSaveStatus = document.getElementById('draft-save-status');

            // When template is locked, prevent all form submissions and disable the form
            if (TEMPLATE_IS_LOCKED) {
                if (form) {
                    form.addEventListener('submit', function(e) { e.preventDefault(); });
                    // Make all table inputs read-only
                    form.querySelectorAll('input, textarea, select').forEach(function(el) {
                        el.setAttribute('disabled', 'disabled');
                    });
                }
            }
            // Create popover and append to body so it's never clipped by overflow (planning coordinator row actions)
            let rowActionsPopover = document.getElementById('row-actions-popover');
            if (!rowActionsPopover) {
                rowActionsPopover = document.createElement('div');
                rowActionsPopover.id = 'row-actions-popover';
                rowActionsPopover.className = 'hidden fixed z-[99999] flex flex-col gap-1 p-1 bg-white border border-indigo-200 rounded-md shadow-md pointer-events-auto';
                rowActionsPopover.style.cssText = 'min-width:0;max-width:200px;position:fixed;z-index:99999;display:none;';
                rowActionsPopover.setAttribute('role', 'toolbar');
                rowActionsPopover.setAttribute('aria-label', 'Row actions');
                document.body.appendChild(rowActionsPopover);
            }
            // Create "three dots" trigger button (prevents auto-opening the popover)
            let rowActionsDotsBtn = document.getElementById('row-actions-dots-btn');
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
                    '<circle cx="12" cy="5" r="1.5" fill="currentColor" stroke="none"></circle>' +
                    '<circle cx="12" cy="12" r="1.5" fill="currentColor" stroke="none"></circle>' +
                    '<circle cx="12" cy="19" r="1.5" fill="currentColor" stroke="none"></circle>' +
                    '</svg>';
                document.body.appendChild(rowActionsDotsBtn);
            }
            // Always set correct popover buttons (handles cases where the div already exists)
            if (rowActionsPopover) {
                rowActionsPopover.innerHTML = ''
                    + '<button type="button" id="row-actions-add-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded border-0 shadow-sm whitespace-nowrap w-full justify-start" title="Add row below this row"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>Add row below</button>'
                    + '<button type="button" id="row-actions-add-rows-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded border border-gray-300 whitespace-nowrap w-full justify-start" title="Add multiple rows (enter count)"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h10"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17v4m-2-2h4"></path></svg>Add rows...</button>'
                    + '<div class="h-px bg-gray-200 my-0.5" aria-hidden="true"></div>'
                    + '<button type="button" id="row-actions-separate-btn" class="cursor-pointer inline-flex items-center gap-1.5 py-1.5 px-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 rounded border border-gray-300 whitespace-nowrap w-full justify-start" title="Add a gray section divider and start a new group of rows"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><line x1="3" y1="12" x2="21" y2="12" stroke-width="2" stroke-linecap="round"></line><line x1="3" y1="7" x2="9" y2="7" stroke-width="2" stroke-linecap="round"></line><line x1="3" y1="17" x2="9" y2="17" stroke-width="2" stroke-linecap="round"></line></svg>Separate</button>';
            }
            const tableContainer = document.getElementById('accomplishment-table-container');
            const scrollStateKey = `pc_edit_scroll:${window.location.pathname}`;
            let suppressScrollPersist = false;
            function persistScrollState() {
                if (suppressScrollPersist) return;
                try {
                    const payload = {
                        pageY: window.scrollY || window.pageYOffset || 0,
                        pageX: window.scrollX || window.pageXOffset || 0,
                        tableTop: tableContainer ? tableContainer.scrollTop : 0,
                        tableLeft: tableContainer ? tableContainer.scrollLeft : 0,
                        savedAt: Date.now(),
                    };
                    sessionStorage.setItem(scrollStateKey, JSON.stringify(payload));
                } catch (e) {}
            }
            function restoreScrollState(withRetry = false) {
                try {
                    const raw = sessionStorage.getItem(scrollStateKey);
                    if (!raw) return;
                    const payload = JSON.parse(raw || '{}');
                    if (!payload || typeof payload !== 'object') return;
                    suppressScrollPersist = true;
                    window.scrollTo(Number(payload.pageX || 0), Number(payload.pageY || 0));
                    if (tableContainer) {
                        tableContainer.scrollLeft = Number(payload.tableLeft || 0);
                        tableContainer.scrollTop = Number(payload.tableTop || 0);
                    }
                    setTimeout(() => { suppressScrollPersist = false; }, 50);
                    if (withRetry) {
                        requestAnimationFrame(() => {
                            window.scrollTo(Number(payload.pageX || 0), Number(payload.pageY || 0));
                            if (tableContainer) {
                                tableContainer.scrollLeft = Number(payload.tableLeft || 0);
                                tableContainer.scrollTop = Number(payload.tableTop || 0);
                            }
                        });
                    }
                } catch (e) {}
            }
            window.addEventListener('scroll', persistScrollState, { passive: true });
            if (tableContainer) tableContainer.addEventListener('scroll', persistScrollState, { passive: true });
            window.addEventListener('beforeunload', persistScrollState);
            
            let rawFieldsForHeader = @json($pcSchema ?? []);
            let currentTemplateSchema = @json($pcSchemaForGrid ?? []);
            let summaryRules = @json($submission->template?->getSummaryRules() ?? []);
            let summaryCellMappings = @json($submission->template?->getSummaryCellMappings() ?? []);
            let draftStatusTimeout = null;
            // Show "Draft saved" when arriving from redirect after save (e.g. fallback or non-AJAX path)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('saved')) {
                setTimeout(() => setDraftSaveStatus('saved'), 100);
                window.history.replaceState({}, '', window.location.pathname);
            }
            function setDraftSaveStatus(state) {
                if (!draftSaveStatus) return;
                if (draftStatusTimeout) {
                    clearTimeout(draftStatusTimeout);
                    draftStatusTimeout = null;
                }
                draftSaveStatus.classList.remove('hidden', 'text-gray-400', 'text-gray-500', 'text-green-700', 'text-red-600');
                if (state === 'saving_draft') {
                    draftSaveStatus.classList.add('text-gray-500');
                    draftSaveStatus.textContent = 'Saving draft...';
                    return;
                }
                if (state === 'saving_submit') {
                    draftSaveStatus.classList.add('text-gray-500');
                    draftSaveStatus.textContent = 'Saving submission...';
                    return;
                }
                if (state === 'saved') {
                    draftSaveStatus.classList.add('text-green-700');
                    draftSaveStatus.textContent = 'Draft saved';
                    draftStatusTimeout = setTimeout(() => {
                        draftSaveStatus.classList.remove('text-green-700');
                        draftSaveStatus.classList.add('text-gray-400');
                        draftSaveStatus.textContent = '';
                    }, 1800);
                    return;
                }
                if (state === 'error') {
                    draftSaveStatus.classList.add('text-red-600');
                    draftSaveStatus.textContent = 'Save failed';
                    draftStatusTimeout = setTimeout(() => {
                        draftSaveStatus.classList.remove('text-red-600');
                        draftSaveStatus.classList.add('text-gray-400');
                        draftSaveStatus.textContent = '';
                    }, 2500);
                    return;
                }
                draftSaveStatus.classList.add('text-gray-400');
                draftSaveStatus.textContent = state === 'saving_draft' ? 'Saving…' : (state === 'saving_submit' ? 'Submitting…' : '');
            }
            function hasAutoCalculatedColumns() {
                const hasCalc = currentTemplateSchema?.some((f) => f?.meta?.calc);
                const hasRules = Array.isArray(summaryRules) && summaryRules.some((r) => r?.enabled && Array.isArray(r?.outputs) && r.outputs.length > 0);
                return hasCalc || hasRules;
            }
            let rowCount = 0;
            // Use a global counter that increments for every field created - ensures absolute uniqueness
            let globalFieldIdCounter = 0;

            // Track unsaved changes for Back button and beforeunload
            window.tableDataDirty = false;
            window.draftSaveRedirecting = false;

            // Row selection for Add Another Row: declare early so setupRowSelectionForAdd (called from renderTemplateFields) can access
            let selectedRowForAdd = null;
            let rowSelectionSetupDone = false;
            let rowActionsHideTimeout = null;
            let rowActionsDotsRefCell = null;
            let rowActionsDotsTargetRow = null;

            // Copy/paste: OS clipboard (Excel TSV) + in-app grid copy via copy/paste events on #accomplishment-table-container (aligned with Super Admin).
            // Ctrl+click / Ctrl+drag: multi-cell selection for paste targets.
            const CELL_SELECT_CLASS = ['cell-selected'];
            let selectedCellsForPaste = [];
            /** In-app clipboard: v:3 grid from copy, or legacy { fieldKey, value } */
            let cellPasteClipboard = null;
            let lastClickedCellInput = null;
            let rangeSelectionAnchorInput = null;
            let pendingPointerCellInput = null;
            let pendingPointerCellTd = null;
            let ctrlDragSelectState = null;
            let suppressNextTableBodyClick = false;
            const CTRL_DRAG_THRESHOLD_PX = 6;
            const ROW_COPY_BUFFER_KEY = 'uaps_row_copy_v1';

            function isCellEmptyValue(v) {
                var s = (v === null || v === undefined) ? '' : String(v);
                s = s.trim();
                return s === '' || s === '—' || s === '-' || s.toLowerCase() === 'select...';
            }

            function clearCellSelection() {
                if (Array.isArray(selectedCellsForPaste)) {
                    selectedCellsForPaste.forEach((el) => {
                        if (!el) return;
                        const td = el.closest ? el.closest('td') : null;
                        if (td) {
                            try { td.classList.remove(...CELL_SELECT_CLASS); } catch (e) {}
                        }
                    });
                }
                selectedCellsForPaste = [];
            }

            function setCellSelection(el, additive) {
                if (!el) return;
                if (!tableBody || !tableBody.contains(el)) return;
                lastClickedCellInput = el;
                if (!rangeSelectionAnchorInput) rangeSelectionAnchorInput = el;

                const isAlready = selectedCellsForPaste.includes(el);
                if (!additive) {
                    clearCellSelection();
                    selectedCellsForPaste = [el];
                    const td = el.closest ? el.closest('td') : null;
                    if (td) {
                        try { td.classList.add(...CELL_SELECT_CLASS); } catch (e) {}
                    }
                    rangeSelectionAnchorInput = el;
                    return;
                }

                if (isAlready) {
                    selectedCellsForPaste = selectedCellsForPaste.filter(x => x !== el);
                    const td = el.closest ? el.closest('td') : null;
                    if (td) {
                        try { td.classList.remove(...CELL_SELECT_CLASS); } catch (e) {}
                    }
                } else {
                    selectedCellsForPaste.push(el);
                    const td = el.closest ? el.closest('td') : null;
                    if (td) {
                        try { td.classList.add(...CELL_SELECT_CLASS); } catch (e) {}
                    }
                }
            }

            function getEditableInputFromCellTarget(target) {
                if (!target) return null;
                const direct = target.closest('input, select, textarea');
                if (direct && tableBody && tableBody.contains(direct) && !direct.disabled && !direct.readOnly) return direct;
                const td = target.closest('td');
                if (!td || !tableBody || !tableBody.contains(td)) return null;
                const fallback = td.querySelector('input:not([type="hidden"]), select, textarea');
                if (!fallback || fallback.disabled || fallback.readOnly) return null;
                return fallback;
            }

            function getEditableInputFromPoint(clientX, clientY) {
                var el = document.elementFromPoint(clientX, clientY);
                if (!el) return null;
                if (tableContainer && !tableContainer.contains(el)) return null;
                return getEditableInputFromCellTarget(el);
            }

            function getInputGridPoint(inputEl) {
                if (!inputEl || !tableBody || !tableBody.contains(inputEl)) return null;
                const tr = inputEl.closest('tr[data-row-type="data"]');
                if (!tr) return null;
                const rows = Array.from(tableBody.querySelectorAll('tr[data-row-type="data"]'));
                const rowIndex = rows.indexOf(tr);
                if (rowIndex < 0) return null;
                const td = inputEl.closest('td');
                if (!td) return null;
                const colIndex = Array.from(tr.children).indexOf(td);
                if (colIndex < 0) return null;
                return { rowIndex, colIndex };
            }

            function getEditableInputAtGridPoint(rowIndex, colIndex) {
                if (!tableBody) return null;
                const rows = tableBody.querySelectorAll('tr[data-row-type="data"]');
                const tr = rows[rowIndex];
                if (!tr) return null;
                const td = tr.children[colIndex];
                if (!td) return null;
                const el = td.querySelector('input:not([type="hidden"]), select, textarea');
                if (!el || el.disabled || el.readOnly) return null;
                return el;
            }

            function selectCellRange(anchorInput, targetInput, additive) {
                const a = getInputGridPoint(anchorInput);
                const b = getInputGridPoint(targetInput);
                if (!a || !b) {
                    setCellSelection(targetInput, additive);
                    return;
                }
                const r0 = Math.min(a.rowIndex, b.rowIndex);
                const r1 = Math.max(a.rowIndex, b.rowIndex);
                const c0 = Math.min(a.colIndex, b.colIndex);
                const c1 = Math.max(a.colIndex, b.colIndex);
                if (!additive) clearCellSelection();
                for (let r = r0; r <= r1; r++) {
                    for (let c = c0; c <= c1; c++) {
                        const cellInput = getEditableInputAtGridPoint(r, c);
                        if (!cellInput) continue;
                        if (!selectedCellsForPaste.includes(cellInput)) {
                            selectedCellsForPaste.push(cellInput);
                            const td = cellInput.closest ? cellInput.closest('td') : null;
                            if (td) {
                                try { td.classList.add(...CELL_SELECT_CLASS); } catch (e) {}
                            }
                        }
                    }
                }
                lastClickedCellInput = targetInput;
            }

            function getFieldKeyFromCellInput(inputEl) {
                if (!inputEl) return '';
                const dk = inputEl.getAttribute && inputEl.getAttribute('data-field-key');
                if (dk) return String(dk);
                const name = inputEl.getAttribute && inputEl.getAttribute('name');
                if (name) return String(name);
                return '';
            }

            function readValueFromCellInput(inputEl) {
                if (!inputEl) return null;
                const fieldKey = getFieldKeyFromCellInput(inputEl);
                if (!fieldKey) return null;
                return { fieldKey, value: inputEl.value || '' };
            }

            function writeValueToCellInput(inputEl, value) {
                if (!inputEl) return false;
                if (inputEl.disabled || inputEl.readOnly) return false;
                inputEl.value = value || '';
                inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                inputEl.dispatchEvent(new Event('change', { bubbles: true }));
                return true;
            }

            function parsePlainTextToValueGrid(text) {
                if (text == null || String(text) === '') return null;
                var rows = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
                while (rows.length && rows[rows.length - 1] === '') rows.pop();
                if (!rows.length) return null;
                return rows.map(function(line) { return line.split('\t'); });
            }

            function isProbablyValueGrid(grid) {
                if (!grid || !grid.length) return false;
                if (grid.length > 1) return true;
                return grid[0].length > 1;
            }

            function rowOrderIndexDataRow(tr) {
                if (!tableBody || !tr) return 999999;
                var rows = tableBody.querySelectorAll('tr[data-row-type="data"]');
                return Array.prototype.indexOf.call(rows, tr);
            }

            function colIndexForCellInput(inputEl) {
                var td = inputEl && inputEl.closest ? inputEl.closest('td') : null;
                var tr = inputEl && inputEl.closest ? inputEl.closest('tr') : null;
                if (!td || !tr) return 999999;
                return Array.from(tr.children).indexOf(td);
            }

            function sortSelectedInputsRowMajor(inputs) {
                var arr = (inputs || []).filter(Boolean).slice();
                arr.sort(function(a, b) {
                    var tra = a.closest('tr[data-row-type="data"]');
                    var trb = b.closest('tr[data-row-type="data"]');
                    var ia = rowOrderIndexDataRow(tra);
                    var ib = rowOrderIndexDataRow(trb);
                    if (ia !== ib) return ia - ib;
                    return colIndexForCellInput(a) - colIndexForCellInput(b);
                });
                return arr;
            }

            function groupInputsIntoDataRows(sortedInputs) {
                var groups = [];
                var cur = [];
                var curTr = null;
                sortedInputs.forEach(function(inp) {
                    var tr = inp.closest('tr[data-row-type="data"]');
                    if (!tr) return;
                    if (tr !== curTr) {
                        if (cur.length) groups.push(cur);
                        cur = [];
                        curTr = tr;
                    }
                    cur.push(inp);
                });
                if (cur.length) groups.push(cur);
                return groups;
            }

            function buildClipboardRowsFromPcInputs(sourceInputs) {
                var sorted = sortSelectedInputsRowMajor(sourceInputs || []);
                var groups = groupInputsIntoDataRows(sorted);
                return groups.map(function(rowInputs) {
                    return rowInputs.map(function(inp) {
                        var read = readValueFromCellInput(inp);
                        return read ? String(read.value) : '';
                    });
                });
            }

            function getPasteableSelectedInputsSorted() {
                if (!selectedCellsForPaste || !selectedCellsForPaste.length || !tableBody) return [];
                var arr = selectedCellsForPaste.filter(function(el) {
                    if (!el || !tableBody.contains(el)) return false;
                    if (el.disabled || el.readOnly) return false;
                    return !!el.closest('tr[data-row-type="data"]');
                });
                return sortSelectedInputsRowMajor(arr);
            }

            function getPasteAnchorInput() {
                var active = document.activeElement;
                if (active && tableBody && tableBody.contains(active) && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT')) {
                    if (active.closest('tr[data-row-type="data"]') && !active.disabled && !active.readOnly) return active;
                }
                var sorted = getPasteableSelectedInputsSorted();
                if (sorted.length) return sorted[0];
                if (lastClickedCellInput && tableBody && tableBody.contains(lastClickedCellInput)) return lastClickedCellInput;
                return null;
            }

            function flattenValueGridForPaste(grid) {
                var out = [];
                if (!grid || !grid.length) return out;
                for (var ri = 0; ri < grid.length; ri++) {
                    var row = grid[ri] || [];
                    for (var cj = 0; cj < row.length; cj++) {
                        var cell = row[cj];
                        out.push(cell === null || cell === undefined ? '' : String(cell));
                    }
                }
                return out;
            }

            function pasteValueGridFromAnchorForPc(anchorInput, valueGrid) {
                if (!anchorInput || !valueGrid || !valueGrid.length || !tableBody) return 0;
                var pt = getInputGridPoint(anchorInput);
                if (!pt) return 0;
                var dataRows = Array.from(tableBody.querySelectorAll('tr[data-row-type="data"]'));
                var anchorRowIdx = pt.rowIndex;
                var anchorColIdx = pt.colIndex;
                var applied = 0;
                for (var ri = 0; ri < valueGrid.length; ri++) {
                    var srcRow = valueGrid[ri];
                    if (!srcRow) continue;
                    var tr = dataRows[anchorRowIdx + ri];
                    if (!tr) break;
                    var tds = tr.children;
                    for (var cj = 0; cj < srcRow.length; cj++) {
                        var v = srcRow[cj];
                        v = (v === null || v === undefined) ? '' : String(v);
                        var colIdx = anchorColIdx + cj;
                        if (colIdx < 0 || colIdx >= tds.length) continue;
                        var td = tds[colIdx];
                        if (!td) continue;
                        var inp = td.querySelector('input:not([type="hidden"]), select, textarea');
                        if (!inp || inp.disabled || inp.readOnly) continue;
                        if (writeValueToCellInput(inp, v)) applied++;
                    }
                }
                if (applied > 0) {
                    window.tableDataDirty = true;
                    if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                    if (typeof renderSummaryRows === 'function') renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                }
                return applied;
            }

            function pasteValueGridOntoMultiSelectionForPc(valueGrid) {
                if (!tableBody || !valueGrid || !valueGrid.length) return 0;
                var targets = getPasteableSelectedInputsSorted();
                if (targets.length < 2) return 0;
                var flat = flattenValueGridForPaste(valueGrid);
                if (!flat.length) return 0;
                var applied = 0;
                var n = Math.min(flat.length, targets.length);
                for (var i = 0; i < n; i++) {
                    if (writeValueToCellInput(targets[i], flat[i])) applied++;
                }
                if (applied > 0) {
                    window.tableDataDirty = true;
                    if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                    if (typeof renderSummaryRows === 'function') renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                }
                return applied;
            }

            function pastePlainTextToMultiSelectionForPc(text) {
                var targets = getPasteableSelectedInputsSorted();
                if (targets.length < 2) return 0;
                var raw = String(text || '').replace(/\r\n/g, '\n').replace(/\r/g, '\n');
                var lines = raw.split('\n');
                while (lines.length && lines[lines.length - 1] === '') lines.pop();
                if (!lines.length) return 0;
                var applied = 0;
                var n = targets.length;
                for (var i = 0; i < n; i++) {
                    var v = lines.length === 1 ? lines[0] : (lines[i] !== undefined ? lines[i] : '');
                    if (writeValueToCellInput(targets[i], v)) applied++;
                }
                if (applied > 0) {
                    window.tableDataDirty = true;
                    if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                    if (typeof renderSummaryRows === 'function') renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                }
                return applied;
            }

            function pasteSingleValueToMatchingColumnForPc(val, fieldKey) {
                if (!tableBody) return 0;
                var list = (selectedCellsForPaste && selectedCellsForPaste.length)
                    ? selectedCellsForPaste.slice()
                    : (lastClickedCellInput ? [lastClickedCellInput] : []);
                if (!list.length) return 0;
                var fk = String(fieldKey || '');
                var applied = 0;
                list.forEach(function(inp) {
                    if (!inp || !tableBody.contains(inp)) return;
                    if (inp.disabled || inp.readOnly) return;
                    var tr = inp.closest('tr[data-row-type="data"]');
                    if (!tr) return;
                    var read = readValueFromCellInput(inp);
                    if (!read || String(read.fieldKey) !== fk) return;
                    if (writeValueToCellInput(inp, String(val))) applied++;
                });
                if (applied > 0) {
                    window.tableDataDirty = true;
                    if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                    if (typeof renderSummaryRows === 'function') renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                }
                return applied;
            }

            function sortDataRowsByDomOrder(trs) {
                return trs.slice().sort(function(a, b) {
                    if (!a || !b) return 0;
                    if (a === b) return 0;
                    var pos = a.compareDocumentPosition(b);
                    if (pos & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
                    if (pos & Node.DOCUMENT_POSITION_PRECEDING) return 1;
                    return 0;
                });
            }

            function collectRowDataFromTrForRowCopy(tr, fieldKeysToCollect) {
                if (!tr) return null;
                const rowData = {};
                tr.querySelectorAll('input, select, textarea').forEach(function(el) {
                    if (!el) return;
                    if (el.disabled || el.readOnly) return;
                    const fk = getFieldKeyFromCellInput(el);
                    if (!fk) return;
                    if (fieldKeysToCollect && !fieldKeysToCollect.has(String(fk))) return;
                    const rawVal = el.value || '';
                    rowData[String(fk)] = isCellEmptyValue(rawVal) ? '' : String(rawVal);
                });
                return rowData;
            }

            function parseRowClipboardRows(raw) {
                let o = null;
                try { o = JSON.parse(raw || '{}'); } catch (e) { return []; }
                if (!o || typeof o !== 'object') return [];
                if (o.v === 2 && Array.isArray(o.rows)) {
                    return o.rows.filter(function(r) { return r && typeof r === 'object'; });
                }
                return [o];
            }

            function applyRowDataToTrPasteEmptyOnly(tr, rowData) {
                if (!tr || !rowData || typeof rowData !== 'object') return 0;
                let n = 0;
                tr.querySelectorAll('input, select, textarea').forEach(function(el) {
                    if (!el) return;
                    if (el.disabled || el.readOnly) return;
                    const fk = getFieldKeyFromCellInput(el);
                    if (!fk) return;
                    if (!Object.prototype.hasOwnProperty.call(rowData, String(fk))) return;
                    const targetVal = el.value || '';
                    if (!isCellEmptyValue(targetVal)) return;
                    const nextVal = rowData[String(fk)];
                    if (isCellEmptyValue(nextVal)) return;
                    if (writeValueToCellInput(el, nextVal)) n++;
                });
                return n;
            }

            // Load existing data - ensure it's properly formatted
            let existingData = @json($editableSeedData ?? ($submission->table_data ?? []));
            let hasPersistedSummaryRows = false;
            let lockSummaryToStored = false;
            
            // Handle case where table_data might be malformed
            if (!Array.isArray(existingData)) {
                if (typeof existingData === 'string') {
                    try {
                        existingData = JSON.parse(existingData);
                    } catch (e) {
                        console.error('Failed to parse existing data:', e);
                        existingData = [];
                    }
                } else if (existingData && typeof existingData === 'object') {
                    existingData = [existingData];
                } else {
                    existingData = [];
                }
            }
            
            // Convert objects to arrays and filter out invalid entries
            existingData = existingData.map((row, idx) => {
                if (Array.isArray(row)) {
                    const obj = {};
                    row.forEach((value, index) => {
                        obj[`field_${index}`] = value;
                    });
                    return obj;
                } else if (typeof row === 'object' && row !== null) {
                    return row;
                }
                return null;
            }).filter(row => row !== null);
            hasPersistedSummaryRows = Array.isArray(existingData)
                && existingData.some((row) => row && row._meta && row._meta.row_type === 'summary');
            // Always recompute blue summary rows from template summary_rules + current white-cell data (same idea as Super Admin).
            // Storing old summary numbers in table_data must not override live formulas — that caused wrong totals per campus/account.
            lockSummaryToStored = false;
            
            // Set rowCount to match existing data length
            rowCount = existingData.length;
            restoreScrollState(true);
            
            // Add hidden status field
            if (form) {
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.id = 'submission_status';
                form.appendChild(statusInput);
                
                // Set status based on button click - set draftSaveRedirecting immediately to prevent beforeunload dialog
                if (saveDraftBtn) {
                    saveDraftBtn.addEventListener('click', function() {
                        window.draftSaveRedirecting = true;
                        if (document.getElementById('submission_status')) {
                            document.getElementById('submission_status').value = 'Unpublished';
                        }
                        setDraftSaveStatus('saving_draft');
                    }, true);
                }
                
                if (submitBtn) {
                    submitBtn.addEventListener('click', function() {
                        if (document.getElementById('submission_status')) {
                            document.getElementById('submission_status').value = 'Pending Review';
                        }
                        setDraftSaveStatus('saving_submit');
                    });
                }

                // Back button: show custom confirmation dialog if unsaved changes, else navigate immediately
                const backBtn = document.getElementById('back-btn');
                if (backBtn) {
                    backBtn.addEventListener('click', function() {
                        const backUrl = '{{ route("campus-user.create-submission") }}';
                        if (window.tableDataDirty) {
                            if (typeof window.showConfirm === 'function') {
                                window.showConfirm({
                                    title: 'Unsaved Changes',
                                    message: 'You have unsaved changes. Are you sure you want to leave? Your changes will not be saved.',
                                    confirmText: 'Save Changes',
                                    cancelText: 'Leave',
                                    onConfirm: function() {
                                        if (typeof performDraftSave === 'function') {
                                            setDraftSaveStatus('saving_draft');
                                            performDraftSave({
                                                onSuccess: function() {
                                                    window.tableDataDirty = false;
                                                    setDraftSaveStatus('saved');
                                                    window.location.href = backUrl;
                                                },
                                                onDone: function() {}
                                            });
                                        }
                                    },
                                    onCancel: function() {
                                        window.tableDataDirty = false;
                                        window.location.href = backUrl;
                                    }
                                });
                            } else {
                                if (confirm('You have unsaved changes. Are you sure you want to leave? Your changes will not be saved.')) {
                                    window.tableDataDirty = false;
                                    window.location.href = backUrl;
                                }
                            }
                        } else {
                            window.location.href = backUrl;
                        }
                    });
                }
                
                // Re-index rows before form submission
                form.addEventListener('submit', function(e) {
                    const formDataForStatus = new FormData(form);
                    const statusAction = formDataForStatus.get('submit_action') || formDataForStatus.get('action');
                    const isDraft = statusAction === 'draft';
                    const isDraftAction = statusAction === 'draft';
                    if (isDraftAction) {
                        e.preventDefault();
                        window.draftSaveRedirecting = true;
                    }
                    setDraftSaveStatus(isDraft ? 'saving_draft' : 'saving_submit');
                    // Re-index all rows to ensure sequential indices
                    reindexRows();
                    
                    const formDataForAction = new FormData(form);
                    
                    const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                    if (!isDraftAction && dataRows.length === 0) {
                        e.preventDefault();
                        setDraftSaveStatus('error');
                        window.showAlert({ title: 'Notice', message: 'Please add at least one row of data.' });
                        return false;
                    }
                    
                    // Do not copy the top Quarter dropdown into every table row — rows can use different quarters (1st–4th Q).
                    
                    // Collect form data before submission
                    const formData = new FormData(form);
                    const tableDataByRow = {};
                    
                    // Collect all table_data entries and organize by row (supports table_data[n][_meta][row_type])
                    for (let [key, value] of formData.entries()) {
                        if (!key.startsWith('table_data[')) continue;
                        const metaMatch = key.match(/^table_data\[(\d+)\]\[_meta\]\[(.+)\]$/);
                        if (metaMatch) {
                            const rowIndex = metaMatch[1];
                            const metaKey = metaMatch[2];
                            if (!tableDataByRow[rowIndex]) tableDataByRow[rowIndex] = {};
                            if (!tableDataByRow[rowIndex]._meta) tableDataByRow[rowIndex]._meta = {};
                            tableDataByRow[rowIndex]._meta[metaKey] = value;
                            continue;
                        }
                        const match = key.match(/^table_data\[(\d+)\]\[([^\]]+)\]$/);
                        if (match) {
                            const rowIndex = match[1];
                            const fieldKey = match[2];
                            if (!tableDataByRow[rowIndex]) {
                                tableDataByRow[rowIndex] = {};
                            }
                            tableDataByRow[rowIndex][fieldKey] = value;
                        }
                    }
                    
                    // Also collect any inputs that might not be in FormData (e.g., disabled inputs)
                    // This ensures we capture ALL data, including empty fields
                    if (tableBody) {
                        dataRows.forEach((row) => {
                            const inputs = row.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                const name = input.name;
                                if (name && name.startsWith('table_data[')) {
                                    const metaM = name.match(/^table_data\[(\d+)\]\[_meta\]\[(.+)\]$/);
                                    if (metaM) {
                                        const matchedRowIndex = metaM[1];
                                        const metaKey = metaM[2];
                                        if (!tableDataByRow[matchedRowIndex]) tableDataByRow[matchedRowIndex] = {};
                                        if (!tableDataByRow[matchedRowIndex]._meta) tableDataByRow[matchedRowIndex]._meta = {};
                                        tableDataByRow[matchedRowIndex]._meta[metaKey] = input.value || '';
                                        return;
                                    }
                                    const match = name.match(/^table_data\[(\d+)\]\[([^\]]+)\]$/);
                                    if (match) {
                                        const matchedRowIndex = match[1];
                                        const fieldKey = match[2];
                                        
                                        // Ensure this field is in our collection
                                        if (!tableDataByRow[matchedRowIndex]) {
                                            tableDataByRow[matchedRowIndex] = {};
                                        }
                                        
                                        // Get value from input (handles disabled inputs, etc.)
                                        let inputValue = '';
                                        if (input.type === 'checkbox' || input.type === 'radio') {
                                            inputValue = input.checked ? input.value : '';
                                        } else {
                                            inputValue = input.value || '';
                                        }
                                        
                                        // Only update if not already set, or if the input has a value
                                        if (!tableDataByRow[matchedRowIndex][fieldKey] || inputValue) {
                                            tableDataByRow[matchedRowIndex][fieldKey] = inputValue;
                                        }
                                    }
                                }
                            });
                        });
                    }
                    
                    const tableDataEntries = Array.from(formData.entries()).filter(([k]) => k.startsWith('table_data['));
                    console.log('=== FORM DATA COLLECTION ===');
                    console.log('Table data entries count:', tableDataEntries.length);
                    console.log('Table data by row:', tableDataByRow);
                    console.log('Number of rows with data:', Object.keys(tableDataByRow).length);
                    console.log('Template code:', formData.get('template_code'));
                    console.log('Action:', formData.get('submit_action') || formData.get('action'));
                    console.log('Status:', formData.get('status'));
                    console.log('Quarter:', formData.get('quarter'));
                    
                    // Log each row's data
                    Object.keys(tableDataByRow).forEach(rowIndex => {
                        const rowData = tableDataByRow[rowIndex];
                        const nonEmptyFields = Object.entries(rowData).filter(([k, v]) => v && v.trim && v.trim() !== '').map(([k]) => k);
                        console.log(`Row ${rowIndex}:`, {
                            'all_fields': Object.keys(rowData),
                            'non_empty_fields': nonEmptyFields,
                            'field_count': Object.keys(rowData).length,
                            'non_empty_count': nonEmptyFields.length,
                            'all_data': rowData,
                        });
                    });
                    
                    // Verify we have table_data (skip for draft - allow saving empty)
                    if (!isDraft && Object.keys(tableDataByRow).length === 0) {
                        e.preventDefault();
                        setDraftSaveStatus('error');
                        window.showAlert({ title: 'Notice', message: 'No data found in the form. Please ensure you have filled in at least one field.' });
                        return false;
                    }
                    
                    // CRITICAL: Collect data from DATA rows and ensure schema (must run BEFORE removing inputs so we read current values)
                    if (tableBody && currentTemplateSchema) {
                        dataRows.forEach((row) => {
                            const rowIndex = row.getAttribute('data-row-index');
                            if (rowIndex === null || rowIndex === '') return;
                            const inputs = row.querySelectorAll('input, select, textarea');
                            
                            // Initialize row if it doesn't exist
                            if (!tableDataByRow[rowIndex]) {
                                tableDataByRow[rowIndex] = {};
                            }
                            // Preserve separator structure so sections restore correctly on load
                            if (row.getAttribute('data-after-separator') === 'true') {
                                tableDataByRow[rowIndex]._after_separator = '1';
                            }
                            const domUidSubmit = String(row.getAttribute('data-row-uid') || '').trim();
                            if (domUidSubmit) {
                                if (!tableDataByRow[rowIndex]._meta || typeof tableDataByRow[rowIndex]._meta !== 'object') {
                                    tableDataByRow[rowIndex]._meta = {};
                                }
                                tableDataByRow[rowIndex]._meta.row_uid = domUidSubmit;
                                tableDataByRow[rowIndex]._meta.row_type = 'data';
                            }

                            inputs.forEach(input => {
                                const name = input.name;
                                
                                // Skip if name is malformed or empty
                                if (!name || !name.startsWith('table_data[')) {
                                    // Try to fix malformed names
                                    const id = input.id || '';
                                    const idMatch = id.match(/^field_\d+_(.+?)_r\d+$/);
                                    if (idMatch && idMatch[1]) {
                                        // Reconstruct the name from the ID
                                        const fieldKey = idMatch[1];
                                        input.name = `table_data[${rowIndex}][${fieldKey}]`;
                                        console.log(`Fixed malformed input name using ID: ${input.name}`);
                                    } else {
                                        console.warn('Skipping input with invalid name:', name, 'ID:', id);
                                        return;
                                    }
                                }
                                
                                // Extract row index and field key (nested _meta supported)
                                const metaIn = input.name.match(/^table_data\[(\d+)\]\[_meta\]\[(.+)\]$/);
                                if (metaIn) {
                                    const matchedRowIndex = metaIn[1];
                                    const metaKey = metaIn[2];
                                    if (!tableDataByRow[matchedRowIndex]) tableDataByRow[matchedRowIndex] = {};
                                    if (!tableDataByRow[matchedRowIndex]._meta) tableDataByRow[matchedRowIndex]._meta = {};
                                    tableDataByRow[matchedRowIndex]._meta[metaKey] = input.value || '';
                                    return;
                                }
                                const match = input.name.match(/^table_data\[(\d+)\]\[([^\]]+)\]$/);
                                if (match && match[1] && match[2] && match[2].trim() !== '') {
                                    const matchedRowIndex = match[1];
                                    const fieldKey = match[2].trim();
                                        
                                    if (!tableDataByRow[matchedRowIndex]) {
                                        tableDataByRow[matchedRowIndex] = {};
                                    }
                                    
                                    // Get the actual value from the input
                                    let inputValue = '';
                                    if (input.type === 'checkbox' || input.type === 'radio') {
                                        inputValue = input.checked ? (input.value || '') : '';
                                    } else if (input.tagName === 'SELECT') {
                                        inputValue = input.value || '';
                                    } else {
                                        inputValue = input.value || '';
                                    }
                                    
                                    // Always update with the current value from the DOM (even if empty for drafts)
                                    tableDataByRow[matchedRowIndex][fieldKey] = inputValue;
                                    console.log(`Collected from DOM: table_data[${matchedRowIndex}][${fieldKey}] = "${String(inputValue).substring(0, 30)}"`);
                                }
                            });
                            
                            // CRITICAL: Ensure ALL fields from template schema are present
                            // This ensures we save ALL fields, even if they weren't rendered or have default values
                            if (currentTemplateSchema) {
                                currentTemplateSchema.forEach(field => {
                                    // Generate the expected field key using the SAME logic as renderField
                                    let expectedFieldKey = field.key;
                                    if (!expectedFieldKey || expectedFieldKey === '') {
                                        expectedFieldKey = field.label
                                            .replace(/"/g, '')
                                            .replace(/'/g, '')
                                            .toLowerCase()
                                            .trim()
                                            .replace(/\s+/g, '_')
                                            .replace(/[^a-z0-9_]/g, '_')
                                            .replace(/_+/g, '_')
                                            .replace(/^_|_$/g, '');
                                    }
                                    
                                    // Sanitize the key to match what's used in the name attribute
                                    const sanitizedKey = expectedFieldKey
                                        .replace(/[^a-z0-9_]/g, '_')
                                        .replace(/_+/g, '_')
                                        .replace(/^_|_$/g, '') || 'field_' + rowIndex;
                                    
                                    // Check if we already have this field from DOM collection
                                    const hasField = tableDataByRow[rowIndex] && (
                                        tableDataByRow[rowIndex].hasOwnProperty(sanitizedKey) ||
                                        tableDataByRow[rowIndex].hasOwnProperty(expectedFieldKey)
                                    );
                                    
                                    // If field is missing, add it with default value
                                    if (!hasField && sanitizedKey) {
                                        // Try to get value from DOM first
                                        const domInput = row.querySelector(`input[name*="[${sanitizedKey}]"], select[name*="[${sanitizedKey}]"], textarea[name*="[${sanitizedKey}]"]`);
                                        let fieldValue = '';
                                        
                                        if (domInput) {
                                            if (domInput.type === 'checkbox' || domInput.type === 'radio') {
                                                fieldValue = domInput.checked ? (domInput.value || '') : '';
                                            } else {
                                                fieldValue = domInput.value || '';
                                            }
                                        } else {
                                            // Use default value from field definition
                                            fieldValue = field.defaultValue || field.default_value || '';
                                        }
                                        
                                        tableDataByRow[rowIndex][sanitizedKey] = fieldValue;
                                        console.log(`SUCCESS: Ensured field from schema: table_data[${rowIndex}][${sanitizedKey}] = "${String(fieldValue).substring(0, 30)}"`);
                                    }
                                });
                            }
                        });
                    }
                    
                    // After all rows are collected: set DB-level quarter from first non-empty per-row Quarter (not from blasting top dropdown onto every row).
                    (function() {
                        let submissionQuarter = '';
                        const sortedRi = Object.keys(tableDataByRow).sort((a, b) => parseInt(a, 10) - parseInt(b, 10));
                        for (const ri of sortedRi) {
                            const row = tableDataByRow[ri];
                            if (!row || typeof row !== 'object') continue;
                            for (const k of Object.keys(row)) {
                                if (k === '_meta' || k === '_after_separator') continue;
                                if (k.toLowerCase() !== 'quarter') continue;
                                const v = row[k];
                                if (v != null && String(v).trim() !== '') {
                                    submissionQuarter = String(v).trim();
                                    break;
                                }
                            }
                            if (submissionQuarter) break;
                        }
                        if (!submissionQuarter) {
                            const hid = document.getElementById('submission-quarter-hidden');
                            submissionQuarter = (hid && hid.value && String(hid.value).trim()) ? String(hid.value).trim() : '1st Q';
                        }
                        const hidEl = document.getElementById('submission-quarter-hidden');
                        if (hidEl) hidEl.value = submissionQuarter;
                        sortedRi.forEach(rowIndex => {
                            const row = tableDataByRow[rowIndex];
                            if (!row || typeof row !== 'object') return;
                            const qKeys = Object.keys(row).filter(k => k.toLowerCase() === 'quarter');
                            if (qKeys.length === 0) {
                                tableDataByRow[rowIndex]['quarter'] = submissionQuarter;
                                return;
                            }
                            qKeys.forEach(qk => {
                                if (row[qk] == null || String(row[qk]).trim() === '') {
                                    tableDataByRow[rowIndex][qk] = submissionQuarter;
                                }
                            });
                        });
                    })();
                    
                    // Remove ALL existing table_data inputs only AFTER we have finished collecting (so payload is correct)
                    const existingTableDataInputs = form.querySelectorAll('input[name^="table_data"], select[name^="table_data"], textarea[name^="table_data"]');
                    existingTableDataInputs.forEach(input => input.remove());
                    
                    // Add hidden inputs for all collected data (include _after_separator for section structure)
                    Object.keys(tableDataByRow).sort((a, b) => parseInt(a) - parseInt(b)).forEach(rowIndex => {
                        const rowData = tableDataByRow[rowIndex];
                        const keysToAdd = Object.keys(rowData);
                        keysToAdd.forEach(fieldKey => {
                            if (fieldKey === '_meta' && rowData[fieldKey] != null && typeof rowData[fieldKey] === 'object' && !Array.isArray(rowData[fieldKey])) {
                                Object.entries(rowData[fieldKey]).forEach(([mk, mv]) => {
                                    const hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = `table_data[${rowIndex}][_meta][${mk}]`;
                                    hiddenInput.value = String(mv ?? '');
                                    form.appendChild(hiddenInput);
                                });
                                return;
                            }
                            if (fieldKey === '_meta') return;
                            const value = rowData[fieldKey] || '';
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = `table_data[${rowIndex}][${fieldKey}]`;
                            hiddenInput.value = String(value);
                            form.appendChild(hiddenInput);
                        });
                    });
                    
                    // For submissions (not drafts), verify at least one row has data and required fields are filled
                    const action = formData.get('submit_action') || formData.get('action');
                    if (action === 'submit') {
                        let hasDataInAnyRow = false;
                        Object.keys(tableDataByRow).forEach(rowIndex => {
                            const rowData = tableDataByRow[rowIndex];
                            const hasNonEmpty = Object.values(rowData).some(v => v && v.trim && v.trim() !== '');
                            if (hasNonEmpty) {
                                hasDataInAnyRow = true;
                            }
                        });
                        
                        if (!hasDataInAnyRow) {
                            e.preventDefault();
                            setDraftSaveStatus('error');
                            window.showAlert({ title: 'Notice', message: 'Please fill in at least one field before submitting for review.' });
                            return false;
                        }
                        
                        // Validate required fields (e.g. MAJOR NAME) before submit - match key normalization used in renderField
                        if (currentTemplateSchema && currentTemplateSchema.length > 0) {
                            const norm = (s) => String(s ?? '').toLowerCase().trim().replace(/[^a-z0-9]+/gi, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                            const requiredFields = currentTemplateSchema.filter(f => f.required);
                            const emptyRequired = [];
                            Object.keys(tableDataByRow).sort((a,b)=>parseInt(a)-parseInt(b)).forEach(rowIndex => {
                                const rowData = tableDataByRow[rowIndex];
                                const hasAnyData = Object.values(rowData).some(v => v && String(v).trim && String(v).trim() !== '');
                                if (!hasAnyData) return;
                                requiredFields.forEach(field => {
                                    let fieldKey = field.key ?? field.name ?? '';
                                    if (!fieldKey) {
                                        const label = (field.label ?? '').toString();
                                        fieldKey = label.replace(/"/g,'').replace(/'/g,'').toLowerCase().trim().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'_').replace(/_+/g,'_').replace(/^_|_$/g,'');
                                    }
                                    const targetNorm = norm(fieldKey);
                                    let val = '';
                                    for (const k of Object.keys(rowData)) {
                                        if (k === '_after_separator') continue;
                                        if (norm(k) === targetNorm || norm(k).replace(/_/g,'') === targetNorm.replace(/_/g,'')) {
                                            val = rowData[k];
                                            break;
                                        }
                                    }
                                    if (!val || String(val).trim() === '') {
                                        emptyRequired.push({ row: parseInt(rowIndex) + 1, label: field.label || field.key || 'Field' });
                                    }
                                });
                            });
                            if (emptyRequired.length > 0) {
                                e.preventDefault();
                                setDraftSaveStatus('error');
                                const msg = 'Please fill in all required fields before submitting.\n\nMissing:\n' +
                                    emptyRequired.slice(0, 5).map(x => '• Row ' + x.row + ': ' + x.label).join('\n') +
                                    (emptyRequired.length > 5 ? '\n... and ' + (emptyRequired.length - 5) + ' more' : '');
                                window.showAlert({ title: 'Required fields empty', message: msg });
                                return false;
                            }
                        }
                    }
                    
                    // Draft save: use AJAX - saves automatically on click, no dialogs
                    if (isDraftAction) {
                        const submitFormData = new FormData(form);
                        submitFormData.set('action', 'draft');
                        submitFormData.set('status', 'Unpublished');
                        fetch((form && form.getAttribute('action')) || '', {
                            method: 'POST',
                            body: submitFormData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        }).then(r => {
                            const contentType = r.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return r.json().then(data => ({ ok: r.ok, status: r.status, data }));
                            }
                            return { ok: r.ok, status: r.status, data: { message: r.statusText } };
                        }).then(({ ok, data }) => {
                            if (ok && data.success) {
                                window.tableDataDirty = false;
                                setDraftSaveStatus('saved');
                            } else {
                                window.draftSaveRedirecting = false;
                                setDraftSaveStatus('error');
                                window.showAlert({ title: 'Save failed', message: data.message || 'Could not save draft.' });
                            }
                        }).catch(err => {
                            window.draftSaveRedirecting = false;
                            setDraftSaveStatus('error');
                            window.showAlert({ title: 'Save failed', message: err.message || 'Could not save draft.' });
                        });
                        return false;
                    }
                    
                    return true;
                });
            }

            // Get template schema directly from the submission
            const templateSchema = @json($submission->template->fields_json ?? null);
            const canEditEvidenceVerifiedByQA = {{ auth()->user()->isQACoordinator() ? 'true' : 'false' }};
            function isEvidenceVerifiedByQAColumn(field) {
                const s = ((field.label || '') + ' ' + (field.key || '')).toLowerCase();
                return s.includes('evidence') && s.includes('verified') && s.includes('qa');
            }
            /** Align with PHP excludePerformanceMetricFieldsForPlanningCoordinator — PC edit view does not show those columns. */
            function ensureSchemaMatchesSuperAdmin(fields) {
                if (!Array.isArray(fields)) return fields;
                return fields.filter((f) => !isPerformanceMetricField(f));
            }
            function isPerformanceMetricField(field) {
                const t = ((field?.key ?? '') + '_' + (field?.label ?? '')).toLowerCase().replace(/[^a-z0-9]+/g, '_');
                return t.includes('variance') || (t.includes('rate') && (t.includes('accomp') || t.includes('accomplishment'))) || t.includes('descriptive') || t.includes('rating');
            }
            function getSchemaFieldKeyForExpand(f) {
                const k = f?.key ?? f?.name;
                if (k != null && String(k).trim() !== '') return String(k);
                const label = String(f?.label ?? '').trim();
                return label.toLowerCase().replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '');
            }
            function normalizeKeyFromLabelSub(s) {
                let x = String(s ?? '').trim().toLowerCase().replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '');
                return x || 'col';
            }
            /** 2+ subheaders ⇒ separate data columns (must match App\Support\TemplateTableGrid). */
            function expandSubheaderGroupsForGrid(fields) {
                if (!Array.isArray(fields)) return [];
                const expanded = [];
                fields.forEach((f) => {
                    const subs = Array.isArray(f.subheaders)
                        ? f.subheaders.map((x) => String(x).trim()).filter(Boolean)
                        : [];
                    if (subs.length >= 2) {
                        const parentKey = getSchemaFieldKeyForExpand(f);
                        const used = Object.create(null);
                        subs.forEach((subLabel, si) => {
                            let base = parentKey + '_' + normalizeKeyFromLabelSub(subLabel);
                            let key = base;
                            let n = 2;
                            while (used[key]) {
                                key = base + '_' + n;
                                n++;
                            }
                            used[key] = true;
                            const child = { ...f };
                            delete child.subheaders;
                            child.key = key;
                            child.label = subLabel;
                            child._grid_parent_label = f.label ?? '';
                            child._grid_parent_key = parentKey;
                            child._grid_is_subcolumn = true;
                            child._grid_subcolumn_index = si;
                            expanded.push(child);
                        });
                    } else {
                        expanded.push(f);
                    }
                });
                return expanded;
            }
            function tableHeaderUsesTwoRows(rawFields) {
                if (!Array.isArray(rawFields)) return false;
                return rawFields.some((f) => {
                    const subs = Array.isArray(f?.subheaders)
                        ? f.subheaders.map((x) => String(x).trim()).filter(Boolean)
                        : [];
                    return subs.length >= 2;
                });
            }
            function getLastVisibleColIndexForDelete(schema) {
                let idx = -1;
                (schema || []).forEach((f, i) => {
                    if (!isPerformanceMetricField(f)) idx = i;
                });
                return idx < 0 ? (schema?.length ?? 1) - 1 : idx;
            }
            
            // Handle different template schema structures
            let schemaFields = null;
            if (templateSchema) {
                if (templateSchema.fields && Array.isArray(templateSchema.fields)) {
                    schemaFields = templateSchema.fields;
                } else if (Array.isArray(templateSchema)) {
                    schemaFields = templateSchema;
                } else if (typeof templateSchema === 'string') {
                    try {
                        const parsed = JSON.parse(templateSchema);
                        if (parsed.fields && Array.isArray(parsed.fields)) {
                            schemaFields = parsed.fields;
                        } else if (Array.isArray(parsed)) {
                            schemaFields = parsed;
                        }
                    } catch (e) {
                        console.error('Failed to parse template schema:', e);
                    }
                }
            }
            
            if (schemaFields && schemaFields.length > 0) {
                rawFieldsForHeader = ensureSchemaMatchesSuperAdmin(schemaFields);
                currentTemplateSchema = expandSubheaderGroupsForGrid(rawFieldsForHeader);
                renderTemplateFields();
            } else {
                const currentTemplateCode = '{{ $submission->template_code }}';
                if (currentTemplateCode) {
                    loadTemplateSchema(currentTemplateCode);
                } else {
                    window.showAlert({ title: 'Notice', message: 'Template information not found. Please refresh the page and try again.' });
                }
            }

            templateSelect.addEventListener('change', function() {
                const templateCode = this.value;
                if (templateCode) {
                    loadTemplateSchema(templateCode);
                } else {
                    templateFields.classList.add('hidden');
                }
            });

            // Load template schema
            function loadTemplateSchema(templateCode) {
                console.log('Loading template schema for:', templateCode);
                // Try to get template schema from the server
                fetch(`{{ route('campus-user.get-template-details') }}?template_code=${templateCode}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Server response:', data);
                        let schemaFields = null;
                        
                        if (data.template_schema) {
                            // Handle different structures
                            if (data.template_schema.fields && Array.isArray(data.template_schema.fields)) {
                                schemaFields = data.template_schema.fields;
                            } else if (Array.isArray(data.template_schema)) {
                                schemaFields = data.template_schema;
                            } else {
                                schemaFields = data.template_schema;
                            }
                        }
                        
                        if (schemaFields && Array.isArray(schemaFields) && schemaFields.length > 0) {
                            rawFieldsForHeader = ensureSchemaMatchesSuperAdmin(schemaFields);
                            currentTemplateSchema = expandSubheaderGroupsForGrid(rawFieldsForHeader);
                            summaryRules = Array.isArray(data.summary_rules) ? data.summary_rules : (summaryRules || []);
                            summaryCellMappings = Array.isArray(data.summary_cell_mappings) ? data.summary_cell_mappings : (summaryCellMappings || []);
                            console.log('Template schema loaded from server:', currentTemplateSchema);
                            console.log('Number of fields:', currentTemplateSchema.length);
                            renderTemplateFields();
                        } else {
                            console.log('No template schema in server response, trying fallback...');
                            // Fallback: try to get template from the current submission
                            const template = @json($submission->template ?? null);
                            if (template && template.fields_json) {
                                let fallbackFields = null;
                                if (template.fields_json.fields && Array.isArray(template.fields_json.fields)) {
                                    fallbackFields = template.fields_json.fields;
                                } else if (Array.isArray(template.fields_json)) {
                                    fallbackFields = template.fields_json;
                                }
                                
                                if (fallbackFields && fallbackFields.length > 0) {
                                    rawFieldsForHeader = ensureSchemaMatchesSuperAdmin(fallbackFields);
                                    currentTemplateSchema = expandSubheaderGroupsForGrid(rawFieldsForHeader);
                                    console.log('Template schema loaded from fallback:', currentTemplateSchema);
                                    renderTemplateFields();
                                } else {
                                    console.error('No valid template schema found in fallback');
                                    window.showAlert({ title: 'Notice', message: 'Template schema could not be loaded. Please refresh the page and try again.' });
                                }
                            } else {
                                console.error('No template schema found in fallback either');
                                window.showAlert({ title: 'Notice', message: 'Template schema could not be loaded. Please refresh the page and try again.' });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading template schema:', error);
                        // Fallback: try to get template from the current submission
                        const template = @json($submission->template ?? null);
                        if (template && template.fields_json) {
                            let fallbackFields = null;
                            if (template.fields_json.fields && Array.isArray(template.fields_json.fields)) {
                                fallbackFields = template.fields_json.fields;
                            } else if (Array.isArray(template.fields_json)) {
                                fallbackFields = template.fields_json;
                            }
                            
                            if (fallbackFields && fallbackFields.length > 0) {
                                rawFieldsForHeader = ensureSchemaMatchesSuperAdmin(fallbackFields);
                                currentTemplateSchema = expandSubheaderGroupsForGrid(rawFieldsForHeader);
                                console.log('Template schema loaded from error fallback:', currentTemplateSchema);
                                renderTemplateFields();
                            } else {
                                console.error('No valid template schema found in error fallback');
                                window.showAlert({ title: 'Notice', message: 'Template schema could not be loaded. Please refresh the page and try again.' });
                            }
                        } else {
                            console.error('No template schema found in error fallback either');
                            window.showAlert({ title: 'Notice', message: 'Template schema could not be loaded. Please refresh the page and try again.' });
                        }
                    });
            }

            // Render template fields
            function renderTemplateFields() {
                try {
            // Add Row now via popover on cell click (no standalone button)
                
                if (!currentTemplateSchema || !Array.isArray(currentTemplateSchema) || currentTemplateSchema.length === 0) {
                    templateFields.classList.remove('hidden');
                    if (tableBody) tableBody.innerHTML = '<tr><td colspan="10" class="px-4 py-4 text-amber-600">No template schema found. Please refresh the page.</td></tr>';
                    return;
                }

                // Render headers — 2+ subheaders ⇒ parent colspan + sub-column row (matches Super Admin grid).
                tableHeaders.innerHTML = '';
                const lastVisibleColIdx = getLastVisibleColIndexForDelete(currentTemplateSchema);
                const rawH = Array.isArray(rawFieldsForHeader) ? rawFieldsForHeader : [];
                const twoRow = tableHeaderUsesTwoRows(rawH);
                function headerThClass(field) {
                    const isPerf = isPerformanceMetricField(field);
                    const stickyClass = isPerf ? ' sticky-perf' : '';
                    const isNumOrCalc = (field?.type === 'number') || (field?.meta?.calc);
                    const labelNorm = (field?.label ?? '').toString().toLowerCase().replace(/[^a-z0-9]+/g, '');
                    const isNoCol = labelNorm === 'no';
                    const alignClass = isNumOrCalc && !isNoCol ? ' text-right' : ' text-center';
                    return 'px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200' + alignClass + stickyClass;
                }
                if (twoRow) {
                    const tr1 = document.createElement('tr');
                    const tr2 = document.createElement('tr');
                    let expIdx = 0;
                    rawH.forEach((rf) => {
                        const subs = Array.isArray(rf?.subheaders)
                            ? rf.subheaders.map((x) => String(x).trim()).filter(Boolean)
                            : [];
                        if (subs.length >= 2) {
                            const thg = document.createElement('th');
                            thg.colSpan = subs.length;
                            thg.className = 'px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-b border-gray-300 text-center align-middle';
                            const sp = document.createElement('span');
                            sp.className = 'block';
                            sp.textContent = rf?.label ?? '';
                            thg.appendChild(sp);
                            tr1.appendChild(thg);
                            subs.forEach(() => {
                                const field = currentTemplateSchema[expIdx];
                                expIdx++;
                                const th = document.createElement('th');
                                th.setAttribute('scope', 'col');
                                const isPerf = isPerformanceMetricField(field);
                                const stickyClass = isPerf ? ' sticky-perf' : '';
                                th.className = 'px-4 py-2 text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 text-center' + stickyClass;
                                const span = document.createElement('span');
                                span.className = 'block';
                                span.textContent = field?.label ?? field?.key ?? '';
                                th.appendChild(span);
                                tr2.appendChild(th);
                            });
                        } else {
                            const field = currentTemplateSchema[expIdx];
                            expIdx++;
                            const th = document.createElement('th');
                            th.rowSpan = 2;
                            th.setAttribute('scope', 'col');
                            const rawSubs = rf?.subheaders ?? rf?.sub_headers;
                            const oneSub = Array.isArray(rawSubs) && rawSubs.length === 1 && String(rawSubs[0] ?? '').trim() !== '';
                            if (oneSub) {
                                const isPerfOne = isPerformanceMetricField(field);
                                const stickyOne = isPerfOne ? ' sticky-perf' : '';
                                th.className = 'p-0 align-top text-xs font-semibold text-gray-700 uppercase tracking-wider border-r border-gray-200 text-center' + stickyOne;
                                const topWrap = document.createElement('div');
                                topWrap.className = 'border-b border-gray-300 px-4 py-2 text-center';
                                const span = document.createElement('span');
                                span.className = 'block';
                                span.textContent = field?.label ?? field?.key ?? '';
                                topWrap.appendChild(span);
                                th.appendChild(topWrap);
                                const botWrap = document.createElement('div');
                                botWrap.className = 'px-4 py-2 text-center';
                                const subEl = document.createElement('span');
                                subEl.className = 'block text-[10px] font-normal normal-case text-gray-600 leading-tight';
                                subEl.textContent = String(rawSubs[0]).trim();
                                botWrap.appendChild(subEl);
                                th.appendChild(botWrap);
                            } else {
                                th.className = headerThClass(field) + ' align-middle';
                                const span = document.createElement('span');
                                span.className = 'block';
                                span.textContent = field?.label ?? field?.key ?? '';
                                th.appendChild(span);
                            }
                            tr1.appendChild(th);
                        }
                    });
                    tableHeaders.appendChild(tr1);
                    tableHeaders.appendChild(tr2);
                } else {
                    const tr = document.createElement('tr');
                    currentTemplateSchema.forEach((field) => {
                        const th = document.createElement('th');
                        th.setAttribute('scope', 'col');
                        th.className = headerThClass(field);
                        const span = document.createElement('span');
                        span.className = 'block';
                        span.textContent = field?.label ?? field?.key ?? '';
                        th.appendChild(span);
                        const rawSubs = field?.subheaders ?? field?.sub_headers;
                        if (Array.isArray(rawSubs) && rawSubs.length === 1 && String(rawSubs[0] ?? '').trim() !== '') {
                            const subEl = document.createElement('span');
                            subEl.className = 'block text-[10px] font-normal normal-case text-gray-600 mt-0.5 leading-tight';
                            subEl.textContent = String(rawSubs[0]).trim();
                            th.appendChild(subEl);
                        }
                        tr.appendChild(th);
                    });
                    tableHeaders.appendChild(tr);
                }

                // Clear existing rows
                tableBody.innerHTML = '';
                
                // Render existing data or add initial row
                if (existingData.length > 0) {
                    // Set rowCount before rendering to ensure proper indexing
                    rowCount = existingData.length;
                    
                    // Render each row; summary rows from server match Super Admin structure exactly
                    let lastInsertedElement = null;
                    const normKey = (s) => String(s ?? '').toLowerCase().trim().replace(/[^a-z0-9]+/gi, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
                    const getSchemaFieldKey = (f) => {
                        const k = f?.key ?? f?.name ?? null;
                        if (k !== null && k !== '') return String(k);
                        const label = (f?.label ?? '').toString().trim();
                        return normKey(label) || '';
                    };
                    const getValFromSummaryRow = (row, field) => {
                        if (!row || typeof row !== 'object') return '—';
                        const canonicalKey = getSchemaFieldKey(field);
                        const targetKeys = [canonicalKey, field.key, field.name, (field.label ?? '').toString()].filter(Boolean);
                        const targetNorms = [...new Set([...targetKeys, normKey(field.key), normKey(field.name), normKey(field.label)].filter(Boolean))];
                        let v = '';
                        for (const tk of targetKeys) {
                            if (row.hasOwnProperty(tk)) { v = row[tk]; break; }
                        }
                        if (v === '' || v == null) {
                            const rowNormMap = {};
                            for (const rk of Object.keys(row)) {
                                if (rk === '_meta') continue;
                                rowNormMap[normKey(rk)] = row[rk];
                            }
                            for (const tn of targetNorms) {
                                if (rowNormMap.hasOwnProperty(tn)) { v = rowNormMap[tn]; break; }
                            }
                        }
                        if (v === '' || v == null) {
                            for (const rk of Object.keys(row)) {
                                if (rk === '_meta') continue;
                                const rn = normKey(rk);
                                if (targetNorms.some(tn => rn === tn || (rn.length >= 4 && tn.length >= 4 && (rn.includes(tn) || tn.includes(rn))))) {
                                    v = row[rk]; break;
                                }
                            }
                        }
                        v = String(v ?? '').trim();
                        if (v === '' || String(v).toLowerCase() === 'summary') return '—';
                        return v;
                    };
                    existingData.forEach((rowData, index) => {
                        let meta = rowData && rowData._meta;
                        if (typeof meta === 'string') {
                            try { meta = JSON.parse(meta); } catch (e) { meta = {}; }
                        }
                        if (!meta || typeof meta !== 'object') meta = {};
                        // Skip summary rows on load - renderSummaryRows will add them in correct position (below fixed)
                        if ((meta.row_type || 'data') === 'summary') return;
                        // Preserve section separators for any data type (with or without auto-calculated columns)
                        const hasAfterSeparator = !!(rowData?._after_separator) || rowData?._after_separator === 'true' || rowData?._after_separator === 1 || rowData?._after_separator === '1';
                        const uidForDom = (meta.row_uid != null && String(meta.row_uid).trim() !== '')
                            ? String(meta.row_uid).trim() : '';
                        const cleanRowData = rowData && typeof rowData === 'object' ? { ...rowData } : {};
                        delete cleanRowData._after_separator;
                        delete cleanRowData._meta;
                        if (hasAfterSeparator) {
                            const separatorRow = document.createElement('tr');
                            separatorRow.setAttribute('data-row-type', 'separator');
                            const colCount = currentTemplateSchema ? currentTemplateSchema.length : 5;
                            const sepClass = 'h-4 min-h-[1rem] px-4 py-2 bg-gray-200 border-t-2 border-b-2 border-gray-300';
                            separatorRow.innerHTML = `<td colspan="${colCount}" class="${sepClass}"></td>`;
                            if (lastInsertedElement && lastInsertedElement.parentNode === tableBody) {
                                lastInsertedElement.insertAdjacentElement('afterend', separatorRow);
                            } else if (tableBody) {
                                tableBody.appendChild(separatorRow);
                            }
                            lastInsertedElement = separatorRow;
                            editAddRow(index, cleanRowData, separatorRow, true, uidForDom);
                            const newRow = separatorRow.nextElementSibling;
                            if (newRow && newRow.getAttribute('data-row-type') === 'data') {
                                newRow.setAttribute('data-after-separator', 'true');
                                lastInsertedElement = newRow;
                            }
                        } else {
                            editAddRow(index, cleanRowData, lastInsertedElement, true, uidForDom);
                            const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                            lastInsertedElement = dataRows[dataRows.length - 1] || null;
                        }
                    });
                    
                } else {
                    rowCount = 0;
                    // Add 5 empty rows by default (matching the Super Admin template details view)
                    for (let i = 0; i < 5; i++) {
                        editAddRow(i, {}, null, true);
                    }
                    rowCount = 5;
                }

                // Show the table
                templateFields.classList.remove('hidden');
                
                // Re-index rows after initial load to ensure proper sequential indices
                reindexRows();
                
                // Update rowCount after reindexing
                const finalRowCount = tableBody ? tableBody.querySelectorAll('tr').length : 0;
                rowCount = finalRowCount;
                
                // Row actions (Add/Separate) via popover on cell click - no standalone buttons
                
                // Re-setup remove row handlers
                setupRemoveRowHandlers();
                
                // Row selection for Add Another Row (insert below clicked row)
                setupRowSelectionForAdd();

                if (hasPersistedSummaryRows) {
                    // Keep saved blue rows exactly as persisted on initial load.
                    renderSummaryRows();
                } else {
                    // No persisted summary rows yet, so synthesize them for first render.
                    computeCalculatedFields();
                    renderSummaryRows();
                }
                restoreScrollState(true);

                // Delegate input/change events to recalculate when user edits source fields (blue row updates live)
                tableBody.addEventListener('input', debounceCompute);
                tableBody.addEventListener('change', debounceCompute);
                // Mark dirty on input/change for Back button and beforeunload
                tableBody.addEventListener('input', function(e) { if (e.target.matches('input, select, textarea')) markTableDirty(); });
                tableBody.addEventListener('change', function(e) { if (e.target.matches('input, select, textarea')) markTableDirty(); });
                } catch (err) {
                    console.error('Error rendering template fields:', err);
                    templateFields.classList.remove('hidden');
                    const colSpan = (currentTemplateSchema?.length ?? 6) + 1;
                    if (tableBody) tableBody.innerHTML = `<tr><td colspan="${colSpan}" class="px-4 py-4 text-red-600">Failed to load table. Please refresh the page.</td></tr>`;
                }
            }

            let computeTimeout = null;
            function debounceCompute() {
                if (lockSummaryToStored) {
                    computeTimeout = null;
                    return;
                }
                if (computeTimeout) clearTimeout(computeTimeout);
                computeTimeout = setTimeout(() => {
                    computeCalculatedFields();
                    renderSummaryRows();
                    computeTimeout = null;
                }, 150);
            }
            // Event delegation on container: ensures blue row updates when Planning Coordinator edits source cells
            // (works even if table body is rebuilt; only trigger for data row edits, not summary row)
            function onSourceCellEdit(e) {
                if (!e.target.matches('input, select, textarea')) return;
                if (e.target.closest('tr[data-row-type="summary"]')) return;
                if (lockSummaryToStored) return;
                debounceCompute();
            }
            if (tableContainer) {
                tableContainer.addEventListener('input', onSourceCellEdit);
                tableContainer.addEventListener('change', onSourceCellEdit);
                tableContainer.addEventListener('blur', onSourceCellEdit, true);
            }

            let draftAutosaveTimer = null;
            let draftAutosaveInFlight = false;
            let draftAutosaveQueued = false;
            /** Prevents duplicate keepalive draft POST in the same leave/hidden cycle; reset when table becomes dirty again. */
            let draftUnloadFlushSent = false;

            function buildDraftSaveFormData() {
                const tableDataByRow = collectTableDataForSave();
                let rowKeys = Object.keys(tableDataByRow).sort((a, b) => parseInt(a, 10) - parseInt(b, 10));
                const quarterVal = deriveSubmissionQuarterFromTableDataByRow(tableDataByRow);
                if (rowKeys.length === 0) {
                    rowKeys = ['0'];
                    tableDataByRow['0'] = { quarter: quarterVal };
                }
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form?.querySelector('input[name="_token"]')?.value || '';
                const fd = new FormData();
                fd.append('_method', 'PUT');
                fd.append('_token', token);
                fd.append('template_code', form?.querySelector('input[name="template_code"]')?.value || '');
                fd.append('quarter', quarterVal);
                fd.append('action', 'draft');
                fd.append('status', 'Unpublished');
                rowKeys.forEach(ri => {
                    const row = tableDataByRow[ri];
                    if (!row || typeof row !== 'object') return;
                    Object.entries(row).forEach(([k, v]) => {
                        if (k === '_meta' && v != null && typeof v === 'object' && !Array.isArray(v)) {
                            Object.entries(v).forEach(([mk, mv]) => {
                                fd.append(`table_data[${ri}][_meta][${mk}]`, String(mv ?? ''));
                            });
                        } else if (k !== '_meta') {
                            fd.append(`table_data[${ri}][${k}]`, String(v ?? ''));
                        }
                    });
                });
                return fd;
            }

            /**
             * Best-effort persist when the page is hidden, closed, or refreshed (keepalive fetch),
             * so pasted/edited cells are not lost if autosave debounce has not fired yet.
             */
            function flushDraftSaveOnPageLeave(ev) {
                if (TEMPLATE_IS_LOCKED || window.draftSaveRedirecting || !form) return;
                var hadDirty = !!window.tableDataDirty;
                if (hadDirty && !draftUnloadFlushSent) {
                    draftUnloadFlushSent = true;
                    if (draftAutosaveTimer) {
                        clearTimeout(draftAutosaveTimer);
                        draftAutosaveTimer = null;
                    }
                    try {
                        const fd = buildDraftSaveFormData();
                        const action = form.getAttribute('action') || '';
                        if (action) {
                            fetch(action, {
                                method: 'POST',
                                body: fd,
                                keepalive: true,
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin'
                            });
                        }
                    } catch (err) {}
                    window.tableDataDirty = false;
                }
                if (ev && ev.type === 'beforeunload' && hadDirty) {
                    ev.preventDefault();
                    ev.returnValue = '';
                }
            }

            function queueDraftAutosave() {
                if (TEMPLATE_IS_LOCKED) return;
                if (!form) return;
                if (window.draftSaveRedirecting) return;
                if (draftAutosaveTimer) clearTimeout(draftAutosaveTimer);
                draftAutosaveTimer = setTimeout(() => {
                    draftAutosaveTimer = null;
                    if (!window.tableDataDirty) return;
                    if (draftAutosaveInFlight) {
                        draftAutosaveQueued = true;
                        return;
                    }
                    draftAutosaveInFlight = true;
                    setDraftSaveStatus('saving_draft');
                    performDraftSave({
                        onSuccess: function() {
                            setDraftSaveStatus('saved');
                        },
                        onDone: function() {
                            draftAutosaveInFlight = false;
                            if (draftAutosaveQueued) {
                                draftAutosaveQueued = false;
                                queueDraftAutosave();
                            }
                        }
                    });
                }, 1200);
            }

            function markTableDirty() {
                window.tableDataDirty = true;
                draftUnloadFlushSent = false;
                queueDraftAutosave();
            }

            function deriveSubmissionQuarterFromTableDataByRow(tableDataByRow) {
                const sortedRi = Object.keys(tableDataByRow).sort((a, b) => parseInt(a, 10) - parseInt(b, 10));
                for (const ri of sortedRi) {
                    const row = tableDataByRow[ri];
                    if (!row || typeof row !== 'object') continue;
                    for (const k of Object.keys(row)) {
                        if (k === '_meta' || k === '_after_separator') continue;
                        if (k.toLowerCase() !== 'quarter') continue;
                        const v = row[k];
                        if (v != null && String(v).trim() !== '') return String(v).trim();
                    }
                }
                const hid = document.getElementById('submission-quarter-hidden');
                return (hid && hid.value && String(hid.value).trim()) ? String(hid.value).trim() : '1st Q';
            }

            function collectTableDataForSave() {
                if (!tableBody || !currentTemplateSchema) return {};
                reindexRows();
                const dataRows = tableBody.querySelectorAll('tr[data-row-type="data"]');
                const tableDataByRow = {};
                dataRows.forEach((row) => {
                    const inputs = row.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        const name = input.name;
                        if (!name || !name.startsWith('table_data[')) return;
                        const match = name.match(/table_data\[(\d+)\]\[(.+)\]/);
                        if (!match || !match[1] || !match[2] || match[2].trim() === '') return;
                        const rowIndex = match[1];
                        const fieldKey = match[2].trim();
                        if (!tableDataByRow[rowIndex]) tableDataByRow[rowIndex] = {};
                        let val = '';
                        if (input.type === 'checkbox' || input.type === 'radio') val = input.checked ? (input.value || '') : '';
                        else if (input.tagName === 'SELECT') val = input.value || '';
                        else val = input.value || '';
                        tableDataByRow[rowIndex][fieldKey] = val;
                    });
                    const firstInput = row.querySelector('input, select, textarea');
                    const rowIdx = firstInput && firstInput.name ? (firstInput.name.match(/table_data\[(\d+)\]/) || [])[1] : row.getAttribute('data-row-index');
                    if (rowIdx != null && tableDataByRow[rowIdx]) {
                        if (row.getAttribute('data-after-separator') === 'true') tableDataByRow[rowIdx]._after_separator = '1';
                        const domUid = String(row.getAttribute('data-row-uid') || '').trim();
                        if (domUid) {
                            if (!tableDataByRow[rowIdx]._meta || typeof tableDataByRow[rowIdx]._meta !== 'object') {
                                tableDataByRow[rowIdx]._meta = {};
                            }
                            tableDataByRow[rowIdx]._meta.row_uid = domUid;
                            tableDataByRow[rowIdx]._meta.row_type = 'data';
                        }
                        currentTemplateSchema.forEach(field => {
                            let expectedKey = field.key || (field.label || '').toString().toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                            if (!expectedKey) expectedKey = 'field_' + rowIdx;
                            const sanitized = expectedKey.replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || expectedKey;
                            if (!tableDataByRow[rowIdx].hasOwnProperty(sanitized) && !tableDataByRow[rowIdx].hasOwnProperty(expectedKey)) {
                                const domInput = row.querySelector(`input[name*="[${sanitized}]"], select[name*="[${sanitized}]"], textarea[name*="[${sanitized}]"]`);
                                tableDataByRow[rowIdx][sanitized] = domInput ? (domInput.value || '') : (field.defaultValue || field.default_value || '');
                            }
                        });
                    }
                });
                const quarterVal = deriveSubmissionQuarterFromTableDataByRow(tableDataByRow);
                Object.keys(tableDataByRow).forEach(ri => {
                    const row = tableDataByRow[ri];
                    if (!row || typeof row !== 'object') return;
                    const qKeys = Object.keys(row).filter(k => k.toLowerCase() === 'quarter');
                    if (qKeys.length === 0) {
                        tableDataByRow[ri]['quarter'] = quarterVal;
                    } else {
                        qKeys.forEach(qk => {
                            if (row[qk] == null || String(row[qk]).trim() === '') {
                                tableDataByRow[ri][qk] = quarterVal;
                            }
                        });
                    }
                });
                return tableDataByRow;
            }

            function performDraftSave(opts) {
                opts = opts || {};
                const fd = buildDraftSaveFormData();
                fetch((form && form.getAttribute('action')) || '', {
                    method: 'POST',
                    body: fd,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin'
                }).then(r => {
                    if (r.headers.get('content-type')?.includes('application/json')) return r.json().then(d => ({ ok: r.ok, data: d }));
                    return { ok: r.ok, data: {} };
                }).then(({ ok, data }) => {
                    if (opts.onDone) opts.onDone();
                    if (ok && data.success) {
                        window.tableDataDirty = false;
                        draftUnloadFlushSent = false;
                        if (opts.onSuccess) opts.onSuccess();
                        else setDraftSaveStatus('saved');
                    } else {
                        setDraftSaveStatus('error');
                    }
                }).catch(err => {
                    if (opts.onDone) opts.onDone();
                    setDraftSaveStatus('error');
                });
            }

            // Keepalive draft save on refresh/close/tab hide (same idea as Super Admin) + optional leave warning
            window.addEventListener('beforeunload', function(e) {
                if (window.draftSaveRedirecting) return;
                flushDraftSaveOnPageLeave(e);
            });
            window.addEventListener('pagehide', function(e) {
                if (window.draftSaveRedirecting) return;
                flushDraftSaveOnPageLeave(e);
            });
            document.addEventListener('visibilitychange', function() {
                if (window.draftSaveRedirecting || TEMPLATE_IS_LOCKED) return;
                if (document.visibilityState !== 'hidden') return;
                flushDraftSaveOnPageLeave(null);
            });

            // Add new row - exactly like create submission
            // insertAfterElement: optional - if provided, insert after this element instead of appending
            // skipMarkDirty: when true (e.g. initial render), do not mark table as dirty
            function editAddRow(rowIndex = null, rowData = null, insertAfterElement = null, skipMarkDirty = false, domRowUid = null) {
                if (!currentTemplateSchema) {
                    console.warn('Cannot add row: template schema not loaded');
                    return;
                }
                
                // For new rows, use the current number of data rows as the index (reindexRows will fix)
                let actualRowIndex;
                if (rowIndex !== null && rowIndex !== undefined) {
                    actualRowIndex = parseInt(rowIndex);
                } else {
                    const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                    actualRowIndex = dataRows.length;
                }
                
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 group';
                row.setAttribute('data-row-index', actualRowIndex);
                row.setAttribute('data-row-type', 'data');
                const stableUid = (domRowUid != null && String(domRowUid).trim() !== '') ? String(domRowUid).trim() : '';
                if (stableUid) {
                    row.setAttribute('data-row-uid', stableUid);
                } else if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
                    row.setAttribute('data-row-uid', crypto.randomUUID());
                }
                
                const lastVisibleColIdx = getLastVisibleColIndexForDelete(currentTemplateSchema);
                const rowFields = currentTemplateSchema.map((field, fieldIndex) => {
                    const includeDelete = (fieldIndex === lastVisibleColIdx);
                    const isPerf = isPerformanceMetricField(field);
                    const stickyClass = isPerf ? ' sticky-perf' : '';
                    return renderField(field, actualRowIndex, rowData, { includeDeleteButton: includeDelete, stickyClass, isPerformanceMetricCol: isPerf, fieldIndex });
                }).join('');
                row.innerHTML = rowFields;
                
                if (tableBody) {
                    if (insertAfterElement && insertAfterElement.parentNode === tableBody) {
                        insertAfterElement.insertAdjacentElement('afterend', row);
                    } else {
                        tableBody.appendChild(row);
                    }
                    if (!skipMarkDirty && typeof markTableDirty === 'function') markTableDirty();
                } else {
                    console.error('Cannot add row: tableBody not found');
                }
            }
            
            // Re-index all rows to ensure sequential indices (data rows + blue summary rows, in DOM order).
            // Summary rows must participate so Submit sends _meta.row_type=summary and QA sees section breaks.
            function reindexRows() {
                if (!tableBody) {
                    console.warn('reindexRows: tableBody not found');
                    return;
                }

                const rows = Array.from(tableBody.querySelectorAll('tr'));
                let newIndex = 0;

                rows.forEach((row) => {
                    const type = row.getAttribute('data-row-type');
                    if (type === 'separator' || type === 'section-actions') {
                        return;
                    }
                    if (type !== 'data' && type !== 'summary') {
                        return;
                    }

                    row.setAttribute('data-row-index', String(newIndex));
                    const inputs = row.querySelectorAll('input, select, textarea');
                    inputs.forEach((input) => {
                        const name = input.name;
                        if (name && name.startsWith('table_data[')) {
                            input.name = name.replace(/^table_data\[\d+]/, `table_data[${newIndex}]`);

                            const existingId = input.id || '';
                            const idMatch = existingId.match(/^field_(\d+)_(.+?)_r(\d+)$/);
                            if (idMatch) {
                                const counter = idMatch[1];
                                const existingFieldKey = idMatch[2];
                                input.id = `field_${counter}_${existingFieldKey}_r${newIndex}`;
                            }
                            if (input.getAttribute('aria-label')) {
                                const ariaLabel = input.getAttribute('aria-label');
                                input.setAttribute('aria-label', ariaLabel.replace(/row \d+/, `row ${newIndex + 1}`));
                            }
                        }
                    });
                    newIndex++;
                });

                rowCount = tableBody.querySelectorAll('tr[data-row-type="data"]').length;
            }

            // Render individual field - match Super Admin structure (delete in last visible col, perf cols read-only)
            function renderField(field, rowIndex, existingRowData = null, opts = {}) {
                const includeDelete = !!opts.includeDeleteButton;
                const stickyClass = opts.stickyClass || '';
                const isPerformanceMetricCol = !!opts.isPerformanceMetricCol;
                const fieldIndex = Number.isInteger(opts.fieldIndex) ? opts.fieldIndex : null;
                const schemaColAttr = (fieldIndex !== null && fieldIndex >= 0) ? ` data-schema-col-index="${fieldIndex}"` : '';
                const buildTd = (innerHtml, extraClass = '') => {
                    const rel = includeDelete ? ' relative' : '';
                    const base = 'px-4 py-1.5 border-r border-gray-200' + rel + ' ' + stickyClass + extraClass;
                    if (includeDelete) {
                        return `<td${schemaColAttr} class="${base}"><div class="flex items-center gap-2 relative pr-8 min-h-[28px]">${innerHtml}<button type="button" class="edit-remove-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none" data-action="remove" title="Delete row">×</button></div></td>`;
                    }
                    return `<td${schemaColAttr} class="${base}">${innerHtml}</td>`;
                };
                if (isPerformanceMetricCol) {
                    const val = (existingRowData && typeof existingRowData === 'object') ? (existingRowData[field?.key ?? ''] ?? existingRowData[field?.name ?? ''] ?? '') : '';
                    const safe = (t) => String(t || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return buildTd(`<span class="text-sm text-gray-500 select-none">${safe(String(val || '').trim()) || '—'}</span>`);
                }
                // Use the field key instead of label for consistent mapping
                // IMPORTANT: This must match exactly how fields are created in create-submission
                // Remove quotes, special characters, and normalize the key
                let fieldKey = field.key ?? field.name ?? '';
                if (!fieldKey || fieldKey === '') {
                    const label = (field.label ?? '').toString();
                    fieldKey = label.replace(/"/g, '').replace(/'/g, '').toLowerCase().trim()
                        .replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                }
                
                // Ensure fieldKey is never empty - use a fallback
                if (!fieldKey || fieldKey === '') {
                    fieldKey = 'field_' + (field.label || 'unknown').toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_');
                    console.warn(`WARNING: Field key was empty for "${field.label}", using fallback: "${fieldKey}"`);
                }
                const required = field.required ? 'required' : '';
                const requiredClass = field.required ? 'border-red-300' : 'border-gray-300';
                
                // Try to get field value using different possible keys
                let fieldValue = '';
                
                if (existingRowData && typeof existingRowData === 'object' && existingRowData !== null) {
                    // Robust key matching: align with backend getSchemaFieldKey and handle all key formats
                    const norm = (s) => String(s ?? '').toLowerCase().trim().replace(/[^a-z0-9]+/gi, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                    const normalizedFieldKey = norm(fieldKey);
                    const fieldLabel = (field.label ?? '').toString();
                    const labelNorm = norm(fieldLabel);
                    
                    // Target keys to try (backend uses key|name|normalized label)
                    const targetKeys = [
                        field.key,
                        field.name,
                        fieldKey,
                        norm(field.key),
                        norm(field.name),
                        labelNorm,
                        fieldLabel.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/^_|_$/g, ''),
                    ].filter(Boolean);
                    
                    // Build normalized map from row keys (backend stores with these keys)
                    const rowNormToValue = {};
                    for (const k of Object.keys(existingRowData)) {
                        if (k === '_meta') continue;
                        const nk = norm(k);
                        if (!rowNormToValue[nk] || existingRowData[k] !== '' && existingRowData[k] != null) {
                            rowNormToValue[nk] = existingRowData[k];
                        }
                    }
                    
                    // 1. Exact key match (accept empty values)
                    if (existingRowData.hasOwnProperty(fieldKey)) {
                        fieldValue = String(existingRowData[fieldKey] ?? '');
                    }
                    if (fieldValue === '' && field.key && existingRowData.hasOwnProperty(field.key)) {
                        fieldValue = String(existingRowData[field.key] ?? '');
                    }
                    
                    // 2. Normalized match for each target key
                    if (fieldValue === '') {
                        for (const tk of targetKeys) {
                            const ntk = norm(tk);
                            if (rowNormToValue.hasOwnProperty(ntk)) {
                                fieldValue = String(rowNormToValue[ntk] ?? '');
                                break;
                            }
                        }
                    }
                    
                    // 3. Reverse scan: find row key whose normalized form matches our target
                    if (fieldValue === '') {
                        for (const rowKey of Object.keys(existingRowData)) {
                            if (rowKey === '_meta') continue;
                            const nRow = norm(rowKey);
                            if (nRow === normalizedFieldKey || nRow === labelNorm) {
                                fieldValue = String(existingRowData[rowKey] ?? '');
                                break;
                            }
                            // Loose match — but never bind a parent key (e.g. 4th_year) to a sub-column (4th_year_m),
                            // or values shift columns and the blue summary row becomes wrong vs Super Admin.
                            if (nRow.length >= 4 && normalizedFieldKey.length >= 4) {
                                if (nRow.includes(normalizedFieldKey)) {
                                    fieldValue = String(existingRowData[rowKey] ?? '');
                                    break;
                                }
                                if (normalizedFieldKey.includes(nRow)) {
                                    const shorter = nRow.length <= normalizedFieldKey.length ? nRow : normalizedFieldKey;
                                    const longer = nRow.length > normalizedFieldKey.length ? nRow : normalizedFieldKey;
                                    if (longer.startsWith(shorter + '_')) {
                                        continue;
                                    }
                                    fieldValue = String(existingRowData[rowKey] ?? '');
                                    break;
                                }
                            }
                        }
                    }
                    if (fieldValue === '' && field._grid_is_subcolumn && Number(field._grid_subcolumn_index) === 0) {
                        const pk = field._grid_parent_key;
                        if (pk && existingRowData.hasOwnProperty(pk)) {
                            fieldValue = String(existingRowData[pk] ?? '');
                        }
                    }
                }
                    
                // If no existing value, use default value from field definition (set by campus admin)
                if (!fieldValue) {
                    fieldValue = field.defaultValue || field.default_value || '';
                }
                // Last-resort column-order fallback (only when not using expanded M/F-style grids — key order then rarely matches columns).
                if ((fieldValue === '' || fieldValue === null) && existingRowData && typeof existingRowData === 'object' && fieldIndex !== null) {
                    const skipOrderFallback = Array.isArray(currentTemplateSchema) && currentTemplateSchema.some((f) => f && f._grid_is_subcolumn);
                    if (!skipOrderFallback) {
                        const orderedKeys = Object.keys(existingRowData).filter((k) => k !== '_meta' && k !== '_after_separator');
                        if (fieldIndex >= 0 && fieldIndex < orderedKeys.length) {
                            const candidate = existingRowData[orderedKeys[fieldIndex]];
                            if (candidate !== null && candidate !== undefined && String(candidate).trim() !== '') {
                                fieldValue = String(candidate);
                            }
                        }
                    }
                }
                    
                
                let inputHtml = '';
                
                // Escape HTML to prevent XSS
                const escapeHtml = (text) => {
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                };
                
                // Ensure fieldKey is safe for use in HTML attributes
                // CRITICAL: This must match exactly how keys are generated in create-submission
                // Remove any remaining special characters that could break HTML
                let safeFieldKey = fieldKey
                    .replace(/[^a-z0-9_]/g, '_') // Replace any non-alphanumeric/underscore with underscore
                    .replace(/_+/g, '_') // Replace multiple underscores with single
                    .replace(/^_|_$/g, ''); // Remove leading/trailing underscores
                
                // Normalized key for data-field-key so summary rules (target_field/sourceA from server use lowercase) find inputs
                const dataFieldKeyAttr = (fieldKey || '').toString().toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || safeFieldKey;
                const labelLower = (field.label || '').toString().toLowerCase();
                const isGroupField = ['responsible_work_units', 'responsible_work_unit', 'campus'].includes(dataFieldKeyAttr) ||
                    (labelLower.includes('responsible') && labelLower.includes('work'));
                
                // If still empty after sanitization, create a fallback key
                if (!safeFieldKey || safeFieldKey === '') {
                    safeFieldKey = 'field_' + globalFieldIdCounter;
                    console.warn(`WARNING: Field key was empty after sanitization for "${field.label}", using fallback: "${safeFieldKey}"`);
                }
                
                // Create truly unique ID for this field - increment global counter for absolute uniqueness
                globalFieldIdCounter++;
                const fieldId = `field_${globalFieldIdCounter}_${safeFieldKey}_r${rowIndex}`;
                const escapedValue = escapeHtml(fieldValue || '');
                const escapedLabel = escapeHtml(field?.label ?? '');
                
                // Use safeFieldKey for the name attribute to ensure it's valid HTML
                // But we need to match the original fieldKey when loading data
                const nameFieldKey = safeFieldKey;
                
                
                // Add aria-label for accessibility (since we're in a table)
                const ariaLabel = `${escapedLabel}${field.required ? ' (required)' : ''} row ${rowIndex + 1}`;
                
                // NO. column: always editable so coordinators can input row numbers (ignore template calc/dropdown for this column in data rows)
                const labelNorm = (field.label || '').toString().toLowerCase().replace(/[\s._]/g, '').trim();
                const keyNorm = (field.key || '').toString().toLowerCase().replace(/[\s._]/g, '').trim();
                const isNoColumn = safeFieldKey === 'no' || safeFieldKey === 'number'
                    || labelNorm === 'no' || labelNorm === 'number'
                    || keyNorm === 'no' || keyNorm === 'number'
                    || (labelNorm.length <= 3 && labelNorm.includes('no'));
                if (isNoColumn) {
                    inputHtml = `<input type="number" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" min="0" step="1"
                        class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none" data-field-key="${dataFieldKeyAttr}"
                        placeholder="" aria-label="${ariaLabel}">`;
                    return buildTd(inputHtml);
                }
                
                // Check if this is a calculated field (unique, sum, countif, avg_percentage, formula)
                // Exception: unique/countif — user fills source values in data rows; result (count) shows in summary row below
                // Sum and Average are fillable — user can enter or override values
                const meta = field.meta || {};
                const isCalculated = meta.calc;
                const sourceA = (meta.sourceA || '').toString().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_');
                const fieldKeyNorm = (fieldKey || field.key || '').toString().toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_');
                const isSourceForSummaryOnly = isCalculated && ['unique', 'countif'].includes(meta.calc) &&
                    (meta.outputMode === 'count' || sourceA === fieldKeyNorm || sourceA === '');
                const isFillableCalc = isCalculated && ['sum', 'avg_percentage'].includes(meta.calc);
                if (isCalculated && !isSourceForSummaryOnly && !isFillableCalc) {
                    inputHtml = `<input type="text" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" readonly
                        class="w-full text-sm text-gray-700 border-0 focus:ring-0 calc-field" data-field-key="${dataFieldKeyAttr}"
                        placeholder="Auto-calculated" aria-label="${ariaLabel}">`;
                    return buildTd(inputHtml);
                }
                if (isFillableCalc) {
                    inputHtml = `<input type="number" step="0.01" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required}
                        class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none calc-field fillable-calc" data-field-key="${dataFieldKeyAttr}"
                        placeholder="0.00" aria-label="${ariaLabel}">`;
                    return buildTd(inputHtml);
                }
                
                switch (field.type) {
                    case 'text':
                        const textPlaceholder = (isGroupField && !fieldValue) ? '—' : '';
                        inputHtml = `<input type="text" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required} 
                                         class="w-full min-h-[28px] text-sm leading-5 text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent py-0.5" data-field-key="${dataFieldKeyAttr}"
                                         placeholder="${textPlaceholder}" aria-label="${ariaLabel}">`;
                        break;
                    case 'date':
                        inputHtml = `<input type="date" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required} 
                                         class="w-full min-h-[28px] text-sm leading-5 text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent py-0.5" data-field-key="${dataFieldKeyAttr}"
                                         aria-label="${ariaLabel}">`;
                        break;
                    case 'dropdown':
                        if (isEvidenceVerifiedByQAColumn(field) && !canEditEvidenceVerifiedByQA) {
                            const displayVal = fieldValue || '—';
                            inputHtml = `<span class="w-full text-sm text-gray-600 bg-gray-100 border-0 py-1 block" aria-label="${ariaLabel}">${escapeHtml(displayVal)}</span>
                                         <input type="hidden" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapeHtml(fieldValue || '')}" data-field-key="${dataFieldKeyAttr}">`;
                        } else {
                            const selectedValue = fieldValue || (field.defaultValue || field.default_value || '');
                            const options = (field.options || []).map(option => {
                                const escapedOption = escapeHtml(option);
                                return `<option value="${escapedOption}" ${selectedValue === option ? 'selected' : ''}>${escapedOption}</option>`;
                            }).join('');
                            const emptyOptionText = isGroupField ? '—' : 'Select...';
                            inputHtml = `<select id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" ${required} 
                                             class="w-full min-h-[28px] text-sm leading-5 text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent py-0.5" data-field-key="${dataFieldKeyAttr}"
                                             aria-label="${ariaLabel}">
                                        <option value="">${emptyOptionText}</option>
                                        ${options}
                                        </select>`;
                        }
                        break;
                    case 'link':
                        // Same outer box as other cells (buildTd); same vertical metrics as text inputs
                        {
                            inputHtml = `<input type="text" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required}
                                class="w-full min-w-0 min-h-[28px] text-sm leading-5 text-blue-700 font-medium underline decoration-blue-600 decoration-2 underline-offset-2 bg-transparent border-0 focus:ring-0 focus:outline-none py-0.5" data-field-key="${dataFieldKeyAttr}"
                                placeholder="Paste or type link" aria-label="${ariaLabel}"
                                title="Double-click to open in a new tab"
                                ondblclick="(function(el){var v=String(el.value||'').trim();if(v.indexOf('http://')===0||v.indexOf('https://')===0)window.open(v,'_blank','noopener,noreferrer');})(this)">`;
                        }
                        break;
                    case 'textarea':
                        inputHtml = `<textarea id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" ${required} rows="2"
                                         class="w-full text-sm text-gray-900 border-0 focus:ring-0 focus:outline-none resize-none" data-field-key="${dataFieldKeyAttr}"
                                    placeholder="" aria-label="${ariaLabel}">${escapedValue}</textarea>`;
                            break;
                        case 'number':
                        inputHtml = `<input type="number" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required} 
                                         class="w-full min-h-[28px] text-sm leading-5 text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent py-0.5" data-field-key="${dataFieldKeyAttr}"
                                         placeholder="" aria-label="${ariaLabel}">`;
                        break;
                    default:
                        // Legacy Google Drive / supporting document columns: same highlighted editable URL as link type
                        const defLbl = (field.label || '').toString().toLowerCase();
                        const defIsGoogleDriveLink = (defLbl.includes('google') && defLbl.includes('drive')) || (defLbl.includes('supporting') && defLbl.includes('document'));
                        if (defIsGoogleDriveLink) {
                            inputHtml = `<input type="text" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required}
                                class="w-full min-w-0 min-h-[28px] text-sm leading-5 text-blue-700 font-medium underline decoration-blue-600 decoration-2 underline-offset-2 bg-transparent border-0 focus:ring-0 focus:outline-none py-0.5" data-field-key="${dataFieldKeyAttr}"
                                placeholder="Paste or type link" aria-label="${ariaLabel}"
                                title="Double-click to open in a new tab"
                                ondblclick="(function(el){var v=String(el.value||'').trim();if(v.indexOf('http://')===0||v.indexOf('https://')===0)window.open(v,'_blank','noopener,noreferrer');})(this)">`;
                        } else {
                            const defPlaceholder = isGroupField && !fieldValue ? '—' : '';
                            inputHtml = `<input type="text" id="${fieldId}" name="table_data[${rowIndex}][${nameFieldKey}]" value="${escapedValue}" ${required} 
                                         class="w-full min-h-[28px] text-sm leading-5 text-gray-900 border-0 focus:ring-0 focus:outline-none bg-transparent py-0.5" data-field-key="${dataFieldKeyAttr}"
                                         placeholder="${defPlaceholder}" aria-label="${ariaLabel}">`;
                        }
                        break;
                }
                
                // Store the original fieldKey in a data attribute for matching when loading
                // This allows us to match saved data even if the key was normalized
                if (inputHtml && fieldKey) {
                    const originalKeyEscaped = escapeHtml(fieldKey);
                    inputHtml = inputHtml.replace(/(<[^>]+)/, `$1 data-original-key="${originalKeyEscaped}"`);
                }
                
                return buildTd(inputHtml);
            }

            // Calculate a single field's value based on its input (for calculated fields)
            function calculateFieldValue(inputValue, calcType, meta, row = null, allRows = []) {
                if (calcType === 'unique') {
                    if (!inputValue || String(inputValue).trim() === '') return '';
                    const values = String(inputValue).split(/[,;\n|]/).map(v => v.trim()).filter(v => v);
                    const uniqueMap = new Map();
                    values.forEach(val => {
                        const displayValue = val.trim().replace(/\s+/g, ' ');
                        const normalized = displayValue.toLowerCase();
                        if (normalized && !uniqueMap.has(normalized)) uniqueMap.set(normalized, displayValue);
                    });
                    return uniqueMap.size.toString();
                } else if (calcType === 'sum') {
                    if (!inputValue || String(inputValue).trim() === '') return '0';
                    const values = String(inputValue).split(/[,;\n|]/).map(v => v.trim()).filter(v => v);
                    const sum = values.reduce((acc, val) => acc + (parseFloat(val) || 0), 0);
                    return sum.toString();
                } else if (calcType === 'countif') {
                    if (!inputValue || String(inputValue).trim() === '') return '0';
                    const values = String(inputValue).split(/[,;\n|]/).map(v => v.trim().replace(/\s+/g, ' ')).filter(v => v);
                    const frequency = new Map();
                    values.forEach((val) => {
                        const key = val.toLowerCase();
                        frequency.set(key, (frequency.get(key) || 0) + 1);
                    });
                    let duplicateCount = 0;
                    frequency.forEach((count) => { if (count > 1) duplicateCount += count; });
                    return duplicateCount.toString();
                } else if (calcType === 'avg_percentage') {
                    const parsed = parseFloat(String(inputValue || '').replace(/[^0-9.\-]/g, ''));
                    return Number.isFinite(parsed) ? Number(parsed).toFixed(2) : '0.00';
                } else if (calcType === 'formula') {
                    const operation = meta.operation || 'sum';
                    const sourceA = meta.sourceA || '';
                    const sourceB = meta.sourceB || '';
                    const scope = meta.scope || 'row';
                    const getNumericFromRow = (targetRow, key) => {
                        if (!targetRow || !key) return 0;
                        const sourceInput = targetRow.querySelector(`[data-field-key="${key}"]`);
                        const value = sourceInput ? sourceInput.value : '';
                        const parsed = parseFloat(String(value).replace(/[^0-9.\-]/g, ''));
                        return Number.isFinite(parsed) ? parsed : 0;
                    };
                    const getScopedValue = (key) => {
                        if (!key) return 0;
                        if (scope === 'all_rows') {
                            return Array.from(allRows || []).reduce((sum, tr) => sum + getNumericFromRow(tr, key), 0);
                        }
                        return getNumericFromRow(row, key);
                    };
                    const a = getScopedValue(sourceA);
                    const b = getScopedValue(sourceB);
                    let result = 0;
                    switch (operation) {
                        case 'sum': result = a + b; break;
                        case 'subtract': result = a - b; break;
                        case 'multiply': result = a * b; break;
                        case 'divide': result = b !== 0 ? a / b : 0; break;
                        case 'percent_of': result = b !== 0 ? (a / b) * 100 : 0; break;
                        case 'sum_over_b_percent': result = b !== 0 ? ((a + b) / b) * 100 : 0; break;
                        case 'diff_over_b_percent': result = b !== 0 ? ((a - b) / b) * 100 : 0; break;
                        default: result = 0;
                    }
                    return Number(result).toFixed(2);
                }
                return inputValue;
            }

            function computeCalculatedFields() {
                if (lockSummaryToStored) return;
                if (!currentTemplateSchema) return;
                const rows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                currentTemplateSchema.forEach(field => {
                    if (!field.meta || !field.meta.calc) return;
                    const fieldKey = field.key || field.label.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
                    const calcType = field.meta.calc;
                    // Sum and Average are fillable — skip auto-calculation so user values persist
                    if (calcType === 'sum' || calcType === 'avg_percentage') return;
                    const applyAllRows = field.meta.applyAllRows;
                    if (calcType === 'formula') {
                        rows.forEach((row) => {
                            const calcInput = row.querySelector(`input.calc-field[data-field-key="${fieldKey}"]`);
                            if (calcInput) calcInput.value = calculateFieldValue('', calcType, field.meta, row, rows);
                        });
                    } else if (calcType === 'avg_percentage' && (field.meta.scope || 'all_rows') === 'all_rows') {
                        const sourceKey = field.meta.sourceA || fieldKey;
                        const numericValues = [];
                        rows.forEach((row) => {
                            const sourceInput = row.querySelector(`[data-field-key="${sourceKey}"]`);
                            const raw = sourceInput ? sourceInput.value : '';
                            const parsed = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
                            if (Number.isFinite(parsed)) numericValues.push(parsed);
                        });
                        const avg = numericValues.length > 0 ? (numericValues.reduce((a, b) => a + b, 0) / numericValues.length) : 0;
                        const formattedAvg = Number(avg).toFixed(2);
                        rows.forEach((row) => {
                            const calcInput = row.querySelector(`input.calc-field[data-field-key="${fieldKey}"]`);
                            if (calcInput) calcInput.value = formattedAvg;
                        });
                    } else if (applyAllRows) {
                        const allValues = [];
                        rows.forEach((row) => {
                            const input = row.querySelector(`input.calc-field[data-field-key="${fieldKey}"]`);
                            if (input && input.value) allValues.push(input.value);
                        });
                        const combinedValue = allValues.join(', ');
                        const calculatedValue = calculateFieldValue(combinedValue, calcType, field.meta);
                        rows.forEach((row) => {
                            const calcInput = row.querySelector(`input.calc-field[data-field-key="${fieldKey}"]`);
                            if (calcInput) calcInput.value = calculatedValue;
                        });
                    } else {
                        rows.forEach((row) => {
                            const calcInput = row.querySelector(`input.calc-field[data-field-key="${fieldKey}"]`);
                            if (calcInput) {
                                const sourceKey = field.meta.sourceA || fieldKey;
                                const sourceInput = row.querySelector(`[data-field-key="${sourceKey}"]`);
                                const inputValue = sourceInput ? (sourceInput.value || '') : (calcInput.value || '');
                                calcInput.value = calculateFieldValue(inputValue, calcType, field.meta);
                            }
                        });
                    }
                });
            }

            function normKeyForDataField(s) {
                return (s || '').toString().toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || '';
            }
            /** Super Admin `normalizeMetricTokenForMatch` — keep summary source/target resolution aligned with templates/show.blade.php */
            function normalizeMetricTokenForMatch(v) {
                return String(v || '').toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
            }
            function getSchemaFieldKeyLoose(f) {
                if (!f || typeof f !== 'object') return '';
                const k = f.key ?? f.name ?? null;
                if (k != null && String(k).trim() !== '') return String(k).trim();
                const label = (f.label ?? '').toString().trim();
                return label.toLowerCase().replace(/[^a-z0-9]+/gi, '_').replace(/^_+|_+$/g, '') || '';
            }
            /**
             * Map rule keys (sourceA, target_field, source_columns) to a schema column index.
             * IMPORTANT: Do not use label-only match when multiple columns share the same sub-label (M/P/F under each year),
             * or the first matching column wins and totals + percentages shift into the wrong cells.
             */
            function resolveSchemaColumnIndexFlexible(fieldKey) {
                if (!currentTemplateSchema || !Array.isArray(currentTemplateSchema) || fieldKey == null) return -1;
                const raw = String(fieldKey || '').trim();
                if (!raw) return -1;
                const n = currentTemplateSchema.length;
                let i;
                for (i = 0; i < n; i++) {
                    if (getSchemaFieldKeyLoose(currentTemplateSchema[i]) === raw) return i;
                }
                const targetNorm = normalizeMetricTokenForMatch(raw);
                const targetNormData = normKeyForDataField(raw);
                if (!targetNorm && !targetNormData) return -1;
                const keyNormHits = [];
                for (i = 0; i < n; i++) {
                    const f = currentTemplateSchema[i];
                    const keyLoose = getSchemaFieldKeyLoose(f);
                    const keyNorm = normalizeMetricTokenForMatch(keyLoose);
                    const keyData = normKeyForDataField(keyLoose);
                    let hit = false;
                    if (targetNorm && (keyNorm === targetNorm || keyData === targetNorm)) hit = true;
                    if (targetNormData && (keyNorm === targetNormData || keyData === targetNormData)) hit = true;
                    if (hit) keyNormHits.push(i);
                }
                const uniqKeyHits = [...new Set(keyNormHits)];
                if (uniqKeyHits.length === 1) return uniqKeyHits[0];
                if (uniqKeyHits.length > 1) return -1;
                const bothHits = [];
                for (i = 0; i < n; i++) {
                    const f = currentTemplateSchema[i];
                    const both = normalizeMetricTokenForMatch((getSchemaFieldKeyLoose(f) || '') + '_' + (f.label || ''));
                    const bothData = normKeyForDataField((getSchemaFieldKeyLoose(f) || '') + '_' + (f.label || ''));
                    let bothHit = false;
                    if (targetNorm && both === targetNorm) bothHit = true;
                    if (targetNormData && bothData === targetNormData) bothHit = true;
                    if (bothHit) bothHits.push(i);
                }
                const uniqBoth = [...new Set(bothHits)];
                if (uniqBoth.length === 1) return uniqBoth[0];
                if (uniqBoth.length > 1) return -1;
                const labelHits = [];
                for (i = 0; i < n; i++) {
                    const labelNorm = normalizeMetricTokenForMatch(currentTemplateSchema[i].label || '');
                    if (!labelNorm) continue;
                    if (targetNorm === labelNorm || targetNormData === labelNorm) labelHits.push(i);
                }
                if (labelHits.length === 1) return labelHits[0];
                return -1;
            }
            /** Same canonical key as data-row inputs (renderField / data-field-key) — aligns summary rules with table columns. */
            function canonicalFieldKeyForSchema(field) {
                if (!field || typeof field !== 'object') return '';
                let fk = field.key ?? field.name ?? '';
                if (fk == null || String(fk).trim() === '') {
                    const label = (field.label ?? '').toString();
                    fk = label.replace(/"/g, '').replace(/'/g, '').toLowerCase().trim()
                        .replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
                }
                return normKeyForDataField(String(fk));
            }
            /** Map summary rule target_field to expanded grid column index (match Super Admin flexible key match). */
            function resolveSummaryOutputColumnIndex(targetField) {
                const flex = resolveSchemaColumnIndexFlexible(targetField);
                if (flex >= 0) return flex;
                if (!targetField || !currentTemplateSchema || !Array.isArray(currentTemplateSchema)) return -1;
                const tNorm = normKeyForDataField(targetField);
                if (!tNorm) return -1;
                for (let i = 0; i < currentTemplateSchema.length; i++) {
                    if (canonicalFieldKeyForSchema(currentTemplateSchema[i]) === tNorm) return i;
                }
                return -1;
            }
            /** Match ComputeService::getRowValueByKey — summary math must use the same keys as saved table_data, not TD column index. */
            function phpKeyNormForRow(s) {
                let x = String(s ?? '').trim().toLowerCase().replace(/[^a-z0-9]+/gi, '_');
                return x.replace(/^_+|_+$/g, '');
            }
            function collectRowKeyValues(tr) {
                const map = Object.create(null);
                if (!tr || tr.getAttribute('data-row-type') !== 'data') return map;
                tr.querySelectorAll('input, select, textarea').forEach((el) => {
                    const name = el.getAttribute('name') || '';
                    const m = name.match(/^table_data\[\d+\]\[(.+)\]$/);
                    if (!m || !m[1]) return;
                    const fk = m[1].trim();
                    let v = '';
                    if (el.tagName === 'SELECT') {
                        const opt = el.options[el.selectedIndex];
                        v = ((opt && opt.value) || '').toString().trim();
                    } else {
                        v = String(el.value ?? '').trim();
                    }
                    map[fk] = v;
                });
                return map;
            }
            function getRowValueByKeyFromTr(tr, key) {
                const map = collectRowKeyValues(tr);
                const k = String(key || '').trim();
                if (!k) return '';
                if (Object.prototype.hasOwnProperty.call(map, k)) return String(map[k] ?? '').trim();
                const keyNorm = phpKeyNormForRow(k);
                if (!keyNorm) return '';
                for (const rk of Object.keys(map)) {
                    if (rk === '_meta' || rk === '_after_separator') continue;
                    if (phpKeyNormForRow(rk) === keyNorm) return String(map[rk] ?? '').trim();
                }
                return '';
            }
            /**
             * Match ComputeService::calculateSummaryOperation — restrict DOM rows by row_uids then row_indices (0-based within this section).
             * When an output omits row_indices/row_uids (common if Super Admin saved scope on only one column), inherit from any sibling output in the same rule
             * so every blue cell uses the same row selection unless explicitly overridden.
             */
            function filterSectionDomRowsForSummaryOutput(sectionRows, output, inheritedRowIndices, inheritedRowUids) {
                const rows = Array.isArray(sectionRows) ? sectionRows.slice() : [];
                if (!output || typeof output !== 'object') return rows;
                if (String(output.ui_calc_type || '').trim() === 'aggregate_chain') {
                    return rows;
                }
                const outHasIdx = Array.isArray(output.row_indices) && output.row_indices.length > 0;
                const outHasUid = Array.isArray(output.row_uids) && output.row_uids.length > 0;
                let uidList = outHasUid ? output.row_uids : [];
                let idxList = outHasIdx ? output.row_indices : [];
                if (!outHasIdx && inheritedRowIndices != null && Array.isArray(inheritedRowIndices) && inheritedRowIndices.length > 0) {
                    idxList = inheritedRowIndices;
                }
                if (!outHasUid && (!idxList || idxList.length === 0) && inheritedRowUids != null && Array.isArray(inheritedRowUids) && inheritedRowUids.length > 0) {
                    uidList = inheritedRowUids;
                }
                let scoped = rows;
                if (uidList.length > 0) {
                    const uidSet = Object.create(null);
                    uidList.forEach((u) => {
                        const s = String(u || '').trim();
                        if (s) uidSet[s] = true;
                    });
                    if (Object.keys(uidSet).length > 0) {
                        const byUid = scoped.filter((tr) => {
                            const uid = String(tr.getAttribute('data-row-uid') || '').trim();
                            return uid && uidSet[uid];
                        });
                        if (byUid.length > 0) {
                            scoped = byUid;
                        } else if (idxList.length > 0) {
                            // Template row_uids came from Super Admin; Planning rows often have new UUIDs if row_uid
                            // was not stored or was regenerated. Summing "no matching uid" used to mean "all rows"
                            // and inflated totals. Prefer row_indices (0-based within this section) instead.
                            scoped = rows;
                        }
                    }
                }
                if (idxList.length > 0) {
                    const picked = [];
                    idxList.forEach((idx) => {
                        const n = parseInt(idx, 10);
                        if (!Number.isNaN(n) && n >= 0 && n < scoped.length) picked.push(scoped[n]);
                    });
                    return picked;
                }
                return scoped;
            }

            /** Section-scoped mapping (matches ComputeService::pickSummaryCellMappingForSection). */
            function pickPcSummaryCellMappingForSection(targetField, sectionOrdinal, totalSections, sectionRowUidSet) {
                const tf = String(targetField || '').trim();
                if (!tf || !Array.isArray(summaryCellMappings) || summaryCellMappings.length === 0) return null;
                const hasUidOverlap = (uids) => {
                    if (!Array.isArray(uids) || uids.length === 0) return false;
                    for (const u of uids) {
                        const s = String(u || '').trim();
                        if (s && sectionRowUidSet && sectionRowUidSet[s]) return true;
                    }
                    return false;
                };
                const mappingMatchesSection = (m) => {
                    const ref = String(m.section_ref || '').trim();
                    if (ref === 'grand_total') return false;
                    if (hasUidOverlap(m.row_uids) || hasUidOverlap(m.base_row_uids)) return true;
                    if (!ref) return totalSections <= 1 || sectionOrdinal === 1;
                    const sm = ref.match(/(?:^|\|)sec:(\d+)(?:\||$)/);
                    if (sm && sm[1]) {
                        const n = parseInt(sm[1], 10);
                        if (!Number.isNaN(n)) return n === sectionOrdinal;
                    }
                    return totalSections <= 1 || sectionOrdinal === 1;
                };
                const candidates = [];
                for (let i = 0; i < summaryCellMappings.length; i++) {
                    const m = summaryCellMappings[i];
                    if (!m || typeof m !== 'object') continue;
                    if (String(m.target_field || '').trim() !== tf) continue;
                    if (!mappingMatchesSection(m)) continue;
                    candidates.push({ mapping: m, index: i });
                }
                if (candidates.length === 0) return null;
                candidates.sort((a, b) => {
                    const aCols = Array.isArray(a.mapping.source_columns) ? a.mapping.source_columns.length : 0;
                    const bCols = Array.isArray(b.mapping.source_columns) ? b.mapping.source_columns.length : 0;
                    if (aCols !== bCols) return bCols - aCols;
                    const aHasB = String(a.mapping.sourceB || '').trim() !== '' ? 1 : 0;
                    const bHasB = String(b.mapping.sourceB || '').trim() !== '' ? 1 : 0;
                    if (aHasB !== bHasB) return bHasB - aHasB;
                    return b.index - a.index;
                });
                return candidates[0].mapping;
            }
            function mergePcOutputWithCellMapping(output, sectionOrdinal, totalSections, sectionRowUidSet) {
                if (!output || typeof output !== 'object') return output;
                const tf = String(output.target_field || '').trim();
                if (!tf || !Array.isArray(summaryCellMappings) || summaryCellMappings.length === 0) {
                    return { ...output };
                }
                const match = pickPcSummaryCellMappingForSection(tf, sectionOrdinal || 1, totalSections || 1, sectionRowUidSet || null);
                if (!match) return { ...output };
                const out = { ...output };
                Object.keys(match).forEach((k) => {
                    if (k === 'target_field') return;
                    const mv = match[k];
                    if (mv === null || mv === undefined || mv === '') return;
                    if (k === 'chain' && Array.isArray(mv)) {
                        out.chain = mv.map((step) => (step && typeof step === 'object' ? { ...step } : step));
                        return;
                    }
                    if (Array.isArray(mv) && mv.length === 0) return;
                    if (k === 'base_row_indices' && Array.isArray(mv)) {
                        out.base_row_indices = mv.map((x) => parseInt(x, 10)).filter((n) => !Number.isNaN(n) && n >= 0);
                        return;
                    }
                    if (k === 'row_indices' && Array.isArray(mv)) {
                        out.row_indices = mv.map((x) => parseInt(x, 10)).filter((n) => !Number.isNaN(n) && n >= 0);
                    } else if (k === 'row_uids' && Array.isArray(mv)) {
                        out.row_uids = mv.map((u) => String(u || '').trim()).filter(Boolean);
                    } else if (k === 'source_columns' && Array.isArray(mv)) {
                        out.source_columns = mv.map((x) => String(x || '').trim()).filter(Boolean);
                    } else if (k === 'count_adjust') {
                        out.count_adjust = mv;
                    } else {
                        out[k] = mv;
                    }
                });
                return out;
            }

            function calculateAggregateChainSummaryValue(output, sectionRows) {
                const baseSource = String(output?.base_source || output?.sourceA || '').trim();
                if (!baseSource) return null;
                const baseAgg = String(output?.base_aggregate || 'sum').toLowerCase() === 'avg' ? 'avg' : 'sum';
                const baseRowUids = Array.isArray(output?.base_row_uids) ? output.base_row_uids : [];
                const baseRowIndices = Array.isArray(output?.base_row_indices) ? output.base_row_indices : [];
                const chain = Array.isArray(output?.chain) ? output.chain : [];
                const whiteRows = (Array.isArray(sectionRows) ? sectionRows : []).filter((tr) => tr && !tr.classList.contains('bg-blue-100'));
                const uidToTr = Object.create(null);
                whiteRows.forEach((tr) => {
                    const uid = String(tr.getAttribute('data-row-uid') || '').trim();
                    if (uid) uidToTr[uid] = tr;
                });
                const uidMissing = baseRowUids.some((uid) => !uidToTr[String(uid || '').trim()]);
                const useIdxFallback = uidMissing && baseRowUids.length > 0
                    && baseRowIndices.length === baseRowUids.length;
                const anyUidResolved = baseRowUids.some((uid) => !!uidToTr[String(uid || '').trim()]);
                const allBaseUidsAbsent = baseRowUids.length > 0 && !anyUidResolved;
                const usePositionalFallback = !useIdxFallback && allBaseUidsAbsent
                    && baseRowUids.length === whiteRows.length && whiteRows.length > 0;
                const baseVals = [];
                if (useIdxFallback) {
                    baseRowIndices.forEach((ix) => {
                        const n = parseInt(ix, 10);
                        if (Number.isNaN(n) || n < 0 || n >= whiteRows.length) return;
                        const tr = whiteRows[n];
                        if (!tr) return;
                        const raw = getRowValueByKeyFromTr(tr, baseSource);
                        const v = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
                        if (Number.isFinite(v)) baseVals.push(v);
                    });
                } else if (usePositionalFallback) {
                    whiteRows.forEach((tr) => {
                        if (!tr) return;
                        const raw = getRowValueByKeyFromTr(tr, baseSource);
                        const v = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
                        if (Number.isFinite(v)) baseVals.push(v);
                    });
                } else {
                    baseRowUids.forEach((uid) => {
                        const tr = uidToTr[String(uid || '').trim()];
                        if (!tr) return;
                        const raw = getRowValueByKeyFromTr(tr, baseSource);
                        const n = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
                        if (Number.isFinite(n)) baseVals.push(n);
                    });
                }
                if (baseVals.length === 0) return null;
                let result = baseAgg === 'avg'
                    ? baseVals.reduce((a, b) => a + b, 0) / baseVals.length
                    : baseVals.reduce((a, b) => a + b, 0);
                chain.forEach((step) => {
                    if (!step || typeof step !== 'object') return;
                    let op = String(step.op || '-').trim();
                    if (op === '÷') op = '/';
                    if (op === '×') op = '*';
                    if (!['+', '-', '*', '/'].includes(op)) return;
                    let tr = uidToTr[String(step.row_uid || '').trim()];
                    if (!tr && step.row_index != null && step.row_index !== '') {
                        const ri = parseInt(step.row_index, 10);
                        if (!Number.isNaN(ri) && ri >= 0 && ri < whiteRows.length) tr = whiteRows[ri];
                    }
                    if (!tr) return;
                    const sk = String(step.source || baseSource).trim() || baseSource;
                    const raw = getRowValueByKeyFromTr(tr, sk);
                    const v = parseFloat(String(raw).replace(/[^0-9.\-]/g, ''));
                    if (!Number.isFinite(v)) return;
                    if (op === '+') result += v;
                    else if (op === '-') result -= v;
                    else if (op === '*') result *= v;
                    else if (op === '/') result = v !== 0 ? result / v : result;
                });
                return result.toFixed(2);
            }

            function calculateSummaryValueByOperation(output, rows) {
                // Match Super Admin / show.blade: ui_formula_operation is the user's chosen formula; operation may stay legacy "sum".
                const uiCtAgg = String(output?.ui_calc_type || '').trim();
                if (uiCtAgg === 'aggregate_chain') {
                    return calculateAggregateChainSummaryValue(output, rows);
                }
                const operation = String((output && (output.ui_formula_operation || output.operation)) || '').trim() || 'sum';
                if (operation === 'count_rows') {
                    return String(Array.from(rows).length);
                }
                const sourceA = (output && output.sourceA) ? output.sourceA : '';
                const sourceB = (output && output.sourceB) ? output.sourceB : '';
                const sourceCols = Array.isArray(output && output.source_columns) ? output.source_columns : [];
                const suffix = (output && output.suffix) ? output.suffix : '';
                const keysToUse = (operation === 'count_total' && sourceCols.length > 0) ? sourceCols : [(sourceA || (sourceCols[0] || ''))];
                /** One sample per (row × source key), same order as ComputeService textValues / numericValues */
                const textVals = [];
                rows.forEach((row) => {
                    keysToUse.forEach((key) => {
                        textVals.push(getRowValueByKeyFromTr(row, key));
                    });
                });
                const numericValues = textVals
                    .map((v) => parseFloat(String(v).replace(/[^0-9.\-]/g, '')))
                    .filter((v) => Number.isFinite(v));

                // ratio / ratio_percent: sum(column A) / sum(column B) for summary (matches backend ComputeService)
                if ((operation === 'ratio' || operation === 'ratio_percent') && sourceA && sourceB) {
                    let sumA = 0, sumB = 0;
                    let sawNumeric = false;
                    rows.forEach((row) => {
                        const rawA = getRowValueByKeyFromTr(row, sourceA);
                        const rawB = getRowValueByKeyFromTr(row, sourceB);
                        const nA = parseFloat(String(rawA).replace(/[^0-9.\-]/g, ''));
                        const nB = parseFloat(String(rawB).replace(/[^0-9.\-]/g, ''));
                        const fa = Number.isFinite(nA);
                        const fb = Number.isFinite(nB);
                        if (!fa && !fb) return;
                        if (fa) sumA += nA;
                        if (fb) sumB += nB;
                        sawNumeric = true;
                    });
                    if (!sawNumeric) return null;
                    if (sumB === 0) return null;
                    const ratio = sumA / sumB;
                    return (operation === 'ratio_percent' ? (ratio * 100) : ratio).toFixed(2);
                }

                switch (operation) {
                    case 'sum_with_suffix':
                        if (numericValues.length === 0) return null;
                        return (Number(numericValues.reduce((a, b) => a + b, 0)).toFixed(2)) + suffix;
                    case 'avg_with_suffix':
                        if (numericValues.length === 0) return null;
                        return (Number(numericValues.reduce((a, b) => a + b, 0) / numericValues.length).toFixed(2)) + suffix;
                    case 'avg':
                    case 'avg_number':
                        if (numericValues.length === 0) return null;
                        return Number(numericValues.reduce((a, b) => a + b, 0) / numericValues.length).toFixed(2);
                    case 'avg_percentage':
                        if (numericValues.length === 0) return null;
                        return (Number(numericValues.reduce((a, b) => a + b, 0) / numericValues.length).toFixed(2)) + '%';
                    case 'count_unique': {
                        const set = Object.create(null);
                        textVals.forEach((tv) => {
                            const v = String(tv || '').trim();
                            if (v === '') return;
                            const k = v.replace(/\s+/g, ' ').toLowerCase();
                            set[k] = true;
                        });
                        const base = Object.keys(set).length;
                        const adjust = parseInt(String(output.count_adjust ?? ''), 10);
                        const adj = Number.isFinite(adjust) ? adjust : 0;
                        return String(Math.max(0, base + adj));
                    }
                    case 'count_duplicates': {
                        const norm = textVals
                            .filter((v) => String(v || '').trim() !== '')
                            .map((v) => String(v).trim().replace(/\s+/g, ' ').toLowerCase());
                        const freq = {};
                        norm.forEach((n) => { freq[n] = (freq[n] || 0) + 1; });
                        let duplicateCount = 0;
                        Object.keys(freq).forEach((k) => {
                            const c = freq[k];
                            if (c > 1) duplicateCount += c;
                        });
                        return String(duplicateCount);
                    }
                    case 'count_total':
                        return String(textVals.filter((v) => String(v || '').trim() !== '' && String(v || '').trim() !== '0').length);
                    case 'sum':
                    default:
                        if (numericValues.length === 0) return null;
                        return Number(numericValues.reduce((a, b) => a + b, 0)).toFixed(2);
                }
            }

            /** Super Admin "Formula (A & B)" — same as show.blade ui_calc_type blue-row-formula* (reads other cells in the blue row, not data-row aggregation). */
            function isPcBlueRowSameRowFormulaOutput(out) {
                const u = String(out?.ui_calc_type || '').trim();
                return u === 'blue-row-formula' || u === 'blue-row-formula-multi' || u === 'blue-row-formula-custom';
            }
            function parsePcSummaryBlueCellNumber(val) {
                const s = String(val ?? '').trim().replace(/[^0-9.\-]/g, '');
                const n = parseFloat(s);
                return Number.isFinite(n) ? n : 0;
            }
            function formatPcBlueSummaryPercentWhole(n) {
                const x = Number(n);
                if (!Number.isFinite(x)) return '0%';
                return String(Math.round(x)) + '%';
            }
            function isPcPercentBlueOp(op, customExpr) {
                const o = String(op || '').trim();
                if (o === 'percent_of' || o === 'ratio_percent' || o === 'sum_over_b_percent' || o === 'diff_over_b_percent') return true;
                if (o === 'custom' && customExpr && /%/.test(String(customExpr))) return true;
                return false;
            }
            /**
             * Mirror reapplyBlueRowSameRowFormulaFromMapping (show.blade.php): A/B uses values already computed
             * for other columns in this blue row (summaryByColIndex), not sums over data rows.
             */
            function calculateBlueRowSameRowSummaryValue(output, summaryByColIndex) {
                if (!output || typeof output !== 'object') return null;
                const uiCalc = String(output.ui_calc_type || '').trim();
                const op = String((output.ui_formula_operation || output.operation || '')).trim();
                /** Missing / cleared blue cells must not be treated as 0 — that produced fake % and totals over empty columns. */
                const parseN = (idx) => {
                    if (idx < 0 || !summaryByColIndex || !Object.prototype.hasOwnProperty.call(summaryByColIndex, idx)) return NaN;
                    const raw = summaryByColIndex[idx];
                    const s = String(raw ?? '').trim();
                    if (s === '' || s === '—' || s === '-' || s === '–') return NaN;
                    const n = parsePcSummaryBlueCellNumber(raw);
                    return Number.isFinite(n) ? n : NaN;
                };
                if (uiCalc === 'blue-row-formula-custom') {
                    const sourceA = String(output.sourceA || '').trim();
                    const sourceB = String(output.sourceB || '').trim();
                    const customExpr = String(output.custom_expr || '').trim();
                    const idxA = resolveSchemaColumnIndexFlexible(sourceA);
                    const idxB = sourceB ? resolveSchemaColumnIndexFlexible(sourceB) : -1;
                    if (idxA < 0 || !customExpr) return null;
                    const valA = parseN(idxA);
                    const valB = idxB >= 0 ? parseN(idxB) : 0;
                    if (!Number.isFinite(valA)) return null;
                    if (idxB >= 0 && !Number.isFinite(valB)) return null;
                    const safeEvalCustom = (e, a, b) => {
                        const s = String(e).replace(/[x×]/g, '*').replace(/÷/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                        if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                        try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                    };
                    const result = safeEvalCustom(customExpr, valA, valB);
                    if (!Number.isFinite(result)) return null;
                    const isPct = isPcPercentBlueOp(op, customExpr);
                    return isPct ? formatPcBlueSummaryPercentWhole(result) : result.toFixed(2);
                }
                if (uiCalc === 'blue-row-formula') {
                    const sourceA = String(output.sourceA || '').trim();
                    const sourceB = String(output.sourceB || '').trim();
                    if (!sourceA) return null;
                    const needB = ['divide', 'percent_of', 'ratio', 'ratio_percent', 'sum_over_b_percent', 'diff_over_b_percent', 'subtract', 'multiply'].indexOf(op) !== -1;
                    if (needB && !sourceB) return null;
                    const idxAf = resolveSchemaColumnIndexFlexible(sourceA);
                    const idxBf = sourceB ? resolveSchemaColumnIndexFlexible(sourceB) : -1;
                    if (idxAf < 0) return null;
                    const valAf = parseN(idxAf);
                    const valBf = idxBf >= 0 ? parseN(idxBf) : 0;
                    if (!Number.isFinite(valAf)) return null;
                    if (idxBf >= 0 && !Number.isFinite(valBf)) return null;
                    let resF = 0;
                    if (op === 'custom' || (typeof op === 'string' && op.indexOf('custom:') === 0)) {
                        const customExprForOp = String(output.custom_expr || (op.indexOf('custom:') === 0 ? op.substring(7) : '')).trim();
                        if (!customExprForOp) return null;
                        const safeEvalCustom = (e, a, b) => {
                            const s = String(e).replace(/[x×]/g, '*').replace(/÷/g, '/').replace(/\bA\b/g, String(a)).replace(/\bB\b/g, String(b));
                            if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                            try { return (new Function('return (' + s + ')'))(); } catch (err) { return NaN; }
                        };
                        resF = safeEvalCustom(customExprForOp, valAf, valBf);
                        if (!Number.isFinite(resF)) return null;
                    } else {
                        switch (op) {
                            case 'sum': resF = valAf + valBf; break;
                            case 'subtract': resF = valAf - valBf; break;
                            case 'multiply': resF = valAf * valBf; break;
                            case 'divide': resF = valBf !== 0 ? (valAf / valBf) : NaN; break;
                            case 'percent_of':
                            case 'ratio_percent':
                                resF = valBf !== 0 ? ((valAf / valBf) * 100) : NaN; break;
                            case 'ratio':
                                resF = valBf !== 0 ? (valAf / valBf) : NaN; break;
                            case 'sum_over_b_percent': resF = valBf !== 0 ? (((valAf + valBf) / valBf) * 100) : NaN; break;
                            case 'diff_over_b_percent': resF = valBf !== 0 ? (((valAf - valBf) / valBf) * 100) : NaN; break;
                            default: resF = valAf + valBf;
                        }
                    }
                    if (!Number.isFinite(resF)) return null;
                    const isPct = isPcPercentBlueOp(op, output.custom_expr);
                    return isPct ? formatPcBlueSummaryPercentWhole(resF) : resF.toFixed(2);
                }
                if (uiCalc === 'blue-row-formula-multi') {
                    const sourceKeys = Array.isArray(output.source_keys) && output.source_keys.length > 0 ? output.source_keys.slice() : [];
                    if (sourceKeys.length === 0) {
                        if (output.sourceA) sourceKeys.push(output.sourceA);
                        if (output.sourceB) sourceKeys.push(output.sourceB);
                    }
                    if (sourceKeys.length === 0) return null;
                    const multiOpRequiresB = (op === 'divide' || op === 'percent_of' || op === 'ratio' || op === 'ratio_percent' || op === 'sum_over_b_percent' || op === 'diff_over_b_percent');
                    const sourceAKeyM = String(output.sourceA || sourceKeys[0] || '').trim();
                    const sourceBKeyM = String(output.sourceB || sourceKeys[1] || '').trim();
                    if (multiOpRequiresB && !sourceBKeyM) return null;
                    const useABOnlyMulti = op !== 'sum' && op !== 'avg';
                    let resultM;
                    if (useABOnlyMulti) {
                        const idxAMulti = resolveSchemaColumnIndexFlexible(sourceAKeyM);
                        const idxBMulti = sourceBKeyM ? resolveSchemaColumnIndexFlexible(sourceBKeyM) : -1;
                        if (idxAMulti < 0 || (multiOpRequiresB && idxBMulti < 0)) return null;
                        const valAm = parseN(idxAMulti);
                        const valBm = idxBMulti >= 0 ? parseN(idxBMulti) : 0;
                        if (!Number.isFinite(valAm)) return null;
                        if (multiOpRequiresB && idxBMulti >= 0 && !Number.isFinite(valBm)) return null;
                        if (op === 'custom' && String(output.custom_expr || '').trim()) {
                            const multiCustomExpr = String(output.custom_expr);
                            const safeEvalMulti = (e, vals) => {
                                let s = String(e).replace(/[x×]/g, '*').replace(/÷/g, '/');
                                const letters = 'ABCDEFGHIJ';
                                for (let vi = 0; vi < vals.length && vi < letters.length; vi++) {
                                    const re = new RegExp('\\b' + letters[vi] + '\\b', 'g');
                                    s = s.replace(re, String(vals[vi]));
                                }
                                if (!/^[\d\s+\-*/().]+$/.test(s)) return NaN;
                                try { return (new Function('return (' + s + ')'))(); } catch (err2) { return NaN; }
                            };
                            const sourceIdxs = sourceKeys.map((k) => resolveSchemaColumnIndexFlexible(k));
                            const valsM = sourceIdxs.map((idx) => (idx >= 0 ? parseN(idx) : 0));
                            resultM = safeEvalMulti(multiCustomExpr, valsM);
                        } else {
                            switch (op) {
                                case 'subtract': resultM = valAm - valBm; break;
                                case 'multiply': resultM = valAm * valBm; break;
                                case 'divide': resultM = valBm !== 0 ? (valAm / valBm) : NaN; break;
                                case 'percent_of':
                                case 'ratio_percent':
                                    resultM = valBm !== 0 ? ((valAm / valBm) * 100) : NaN; break;
                                case 'ratio':
                                    resultM = valBm !== 0 ? (valAm / valBm) : NaN; break;
                                case 'sum_over_b_percent': resultM = valBm !== 0 ? (((valAm + valBm) / valBm) * 100) : NaN; break;
                                case 'diff_over_b_percent': resultM = valBm !== 0 ? (((valAm - valBm) / valBm) * 100) : NaN; break;
                                default: resultM = valAm + valBm;
                            }
                        }
                    } else {
                        let totalM = 0;
                        let finiteCount = 0;
                        sourceKeys.forEach((key) => {
                            const idx = resolveSchemaColumnIndexFlexible(key);
                            if (idx < 0) return;
                            const v = parseN(idx);
                            if (!Number.isFinite(v)) return;
                            totalM += v;
                            finiteCount++;
                        });
                        resultM = finiteCount === 0 ? NaN : (op === 'avg' ? totalM / finiteCount : totalM);
                    }
                    if (!Number.isFinite(resultM)) return null;
                    const isPct = isPcPercentBlueOp(op, output.custom_expr);
                    return isPct ? formatPcBlueSummaryPercentWhole(resultM) : resultM.toFixed(2);
                }
                return null;
            }

            function renderSummaryRows() {
                if (!tableBody) return;
                tableBody.querySelectorAll('tr[data-row-type="summary"]').forEach((row) => row.remove());
                tableBody.querySelectorAll('tr[data-row-type="section-actions"]').forEach((row) => row.remove());
                // Stored summary rows from server (Super Admin may have cleared cells to "—") — use all so reflection matches
                const storedSummaryRows = Array.isArray(existingData) ? existingData.filter(r => r && r._meta && r._meta.row_type === 'summary') : [];
                if (lockSummaryToStored && storedSummaryRows.length === 0) return;
                const normRowKeyForSummary = (s) => String(s || '').toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
                /**
                 * Read one summary cell from saved row_data. Prefer exact keys (PHP/table_data);
                 * only use normalized key match when exactly ONE row key matches (avoids values sliding into wrong columns).
                 */
                const getStoredSummaryValue = (field, getNormKeyFn, storedRow) => {
                    const row = storedRow || storedSummaryRows[0] || {};
                    if (!row || typeof row !== 'object' || Object.keys(row).length === 0) return null;
                    const loose = getSchemaFieldKeyLoose(field);
                    const canon = typeof canonicalFieldKeyForSchema === 'function' ? canonicalFieldKeyForSchema(field) : '';
                    const normKeyFromFn = getNormKeyFn(field);
                    const exactKeys = [...new Set([loose, field.key, field.name, normKeyFromFn, canon].filter((x) => x != null && String(x).trim() !== ''))].map(String);
                    for (const ek of exactKeys) {
                        if (Object.prototype.hasOwnProperty.call(row, ek)) {
                            const raw = row[ek];
                            const v = typeof raw !== 'string' ? String(raw ?? '') : raw;
                            const t = v.trim();
                            if (t !== '') return t;
                        }
                    }
                    const norm = normRowKeyForSummary;
                    const targetNorms = [...new Set([norm(normKeyFromFn), norm(loose), norm(field.key || ''), norm(field.name || ''), norm(canon)].filter(Boolean))];
                    for (const tn of targetNorms) {
                        let hitVal = null;
                        let hitCount = 0;
                        for (const rk of Object.keys(row)) {
                            if (rk === '_meta') continue;
                            if (norm(rk) === tn) {
                                hitVal = row[rk];
                                hitCount++;
                            }
                        }
                        if (hitCount !== 1) continue;
                        const v = typeof hitVal !== 'string' ? String(hitVal ?? '') : hitVal;
                        const t = v.trim();
                        if (t !== '') return t;
                    }
                    return null;
                };
                const getStoredSummaryValueRaw = (field, getNormKeyFn, storedRow) => {
                    const row = storedRow || storedSummaryRows[0] || {};
                    if (!row || typeof row !== 'object') return null;
                    const loose = getSchemaFieldKeyLoose(field);
                    const canon = typeof canonicalFieldKeyForSchema === 'function' ? canonicalFieldKeyForSchema(field) : '';
                    const normKeyFromFn = getNormKeyFn(field);
                    const exactKeys = [...new Set([loose, field.key, field.name, normKeyFromFn, canon].filter((x) => x != null && String(x).trim() !== ''))].map(String);
                    for (const ek of exactKeys) {
                        if (Object.prototype.hasOwnProperty.call(row, ek)) return String(row[ek] ?? '').trim();
                    }
                    const norm = normRowKeyForSummary;
                    const targetNorms = [...new Set([norm(normKeyFromFn), norm(loose), norm(canon)].filter(Boolean))];
                    for (const tn of targetNorms) {
                        let hitVal = null;
                        let hitCount = 0;
                        for (const rk of Object.keys(row)) {
                            if (rk === '_meta') continue;
                            if (norm(rk) === tn) {
                                hitVal = row[rk];
                                hitCount++;
                            }
                        }
                        if (hitCount === 1) return String(hitVal ?? '').trim();
                    }
                    return null;
                };
                // Match Super Admin: prefer summary_rules; if admin only used summary_cell_mappings, fall back so blue rows still render from server + mappings.
                let rules = Array.isArray(summaryRules) ? [...summaryRules] : [];
                const enabledRules = rules.filter((r) => r && r.enabled && r.placement === 'after_group' && Array.isArray(r.group_by) && r.group_by.length > 0);
                let rule;
                let mergedOutputsList;
                const scmFallback = Array.isArray(summaryCellMappings) ? summaryCellMappings : [];
                if (enabledRules.length > 0) {
                    rule = enabledRules[0];
                } else {
                    const canRenderFallback = storedSummaryRows.length > 0 || scmFallback.length > 0;
                    if (!canRenderFallback) return;
                    const firstF = currentTemplateSchema && currentTemplateSchema[0] ? currentTemplateSchema[0] : null;
                    const gk = firstF
                        ? String(firstF.key || firstF.name || (typeof canonicalFieldKeyForSchema === 'function' ? canonicalFieldKeyForSchema(firstF) : '') || 'field').trim()
                        : 'field';
                    rule = { enabled: true, placement: 'after_group', group_by: [gk], outputs: scmFallback.slice() };
                }
                const baseOutputsList = Array.isArray(rule.outputs) ? rule.outputs : [];
                const groupField = rule.group_by[0];
                const getNormKeyForSummary = (f) => (f && (f.key || f.label || f.name || '')).toString().toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
                const normalizeOutputForNo = (out) => {
                    if (!out?.target_field) return out;
                    const noNorm = (out.target_field || '').toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
                    const effOp = String((out.ui_formula_operation || out.operation || '')).trim();
                    if ((noNorm === 'no' || noNorm === 'no_') && effOp === 'count_total') {
                        return { ...out, operation: 'count_rows' };
                    }
                    return out;
                };
                // Group by section (separator); sections may have zero data rows after Separate (gray bar + blue only)
                const rowsForGroups = Array.from(tableBody.querySelectorAll('tr'));
                const groups = [];
                let currentSection = [];
                let leadingSep = null;
                rowsForGroups.forEach((row) => {
                    const type = row.getAttribute('data-row-type');
                    if (type === 'separator') {
                        if (currentSection.length > 0 || groups.length > 0) {
                            groups.push({ rows: [...currentSection], leadingSep });
                        }
                        currentSection = [];
                        leadingSep = row;
                    } else if (type === 'data') {
                        currentSection.push(row);
                    }
                });
                groups.push({ rows: [...currentSection], leadingSep });
                const dataRows = groups.flatMap((g) => g.rows);
                if (dataRows.length === 0) return;
                const totalSectionCount = groups.length;
                const outputMatchesGroupSection = (output, sectionOrdinal, sectionRowUidSet) => {
                    if (!output || typeof output !== 'object') return false;
                    const ref = String(output.section_ref || '').trim();
                    if (ref === 'grand_total') return false;
                    const hasUidOverlap = (uids) => {
                        if (!Array.isArray(uids) || uids.length === 0) return false;
                        for (const u of uids) {
                            const s = String(u || '').trim();
                            if (s && sectionRowUidSet && sectionRowUidSet[s]) return true;
                        }
                        return false;
                    };
                    // Prefer stable row identity when available; this survives section reorder/insert better than sec:N.
                    if (hasUidOverlap(output.row_uids) || hasUidOverlap(output.base_row_uids)) return true;
                    if (!ref) {
                        // Unscoped output is ambiguous in multi-section views.
                        // Keep backward compatibility by applying it only to the first section.
                        return totalSectionCount <= 1 || sectionOrdinal === 1;
                    }
                    const m = ref.match(/(?:^|\|)sec:(\d+)(?:\||$)/);
                    if (m && m[1]) {
                        const n = parseInt(m[1], 10);
                        if (!Number.isNaN(n)) return n === sectionOrdinal;
                    }
                    // Legacy ref without explicit section ordinal: only section 1 when multiple sections exist.
                    return totalSectionCount <= 1 || sectionOrdinal === 1;
                };
                // Add one blue row per section (anchor: last data row, or separator when section has no data yet)
                groups.forEach((group, groupIndex) => {
                    const storedRowForGroup = storedSummaryRows[groupIndex] || null;
                    if (lockSummaryToStored && !storedRowForGroup) return;
                    const anchor = group.rows.length > 0 ? group.rows[group.rows.length - 1] : group.leadingSep;
                    if (!anchor) return;
                    const sectionHasDataRows = Array.isArray(group.rows) && group.rows.length > 0;

                    const summaryRow = document.createElement('tr');
                    summaryRow.className = 'bg-blue-100 font-semibold';
                    summaryRow.setAttribute('data-row-type', 'summary');
                    const sectionOrdinal = groupIndex + 1;
                    const sectionRowUidSet = Object.create(null);
                    (Array.isArray(group.rows) ? group.rows : []).forEach((tr) => {
                        const uid = String(tr?.getAttribute?.('data-row-uid') || '').trim();
                        if (uid) sectionRowUidSet[uid] = true;
                    });
                    const mergedOutputsList = baseOutputsList
                        .map((o) => mergePcOutputWithCellMapping(o, sectionOrdinal, totalSectionCount, sectionRowUidSet))
                        .filter((o) => o && o.target_field);
                    const scopedOutputsList = mergedOutputsList.filter((o) => o && outputMatchesGroupSection(o, sectionOrdinal, sectionRowUidSet));
                    let inheritedRowIndicesRule = null;
                    for (let ir = 0; ir < scopedOutputsList.length; ir++) {
                        const o = scopedOutputsList[ir];
                        if (o && Array.isArray(o.row_indices) && o.row_indices.length > 0) {
                            inheritedRowIndicesRule = o.row_indices.map((x) => parseInt(x, 10)).filter((n) => !Number.isNaN(n));
                            break;
                        }
                    }
                    let inheritedRowUidsRule = null;
                    if (!inheritedRowIndicesRule || inheritedRowIndicesRule.length === 0) {
                        for (let iu = 0; iu < scopedOutputsList.length; iu++) {
                            const o = scopedOutputsList[iu];
                            if (o && Array.isArray(o.row_uids) && o.row_uids.length > 0) {
                                inheritedRowUidsRule = o.row_uids.slice();
                                break;
                            }
                        }
                    }
                    const summaryValues = {};
                    const summaryByColIndex = Object.create(null);
                    const applySummaryOutputToMaps = (output, val) => {
                        if (val === null || val === undefined || val === '') return;
                        const targetNorm = normKeyForDataField(output.target_field);
                        summaryValues[output.target_field] = val;
                        if (targetNorm) summaryValues[targetNorm] = val;
                        const colIdx = resolveSummaryOutputColumnIndex(output.target_field);
                        if (colIdx >= 0) summaryByColIndex[colIdx] = val;
                    };
                    // Phase 1: aggregate from data rows (summary-formula, row-wise ratio, etc.)
                    scopedOutputsList.forEach((output) => {
                        if (!sectionHasDataRows) return;
                        if (!output?.target_field) return;
                        if (isPcBlueRowSameRowFormulaOutput(output)) return;
                        const normOutBase = normalizeOutputForNo(output);
                        const op = String((normOutBase && (normOutBase.ui_formula_operation || normOutBase.operation)) || '').trim() || 'sum';
                        let normOut;
                        if (op === 'count_rows') {
                            normOut = normOutBase;
                        } else {
                            let srcA = (output.sourceA || (Array.isArray(output.source_columns) && output.source_columns.length > 0 ? output.source_columns[0] : '') || '').trim();
                            if (String(normOutBase?.ui_calc_type || '').trim() === 'aggregate_chain') {
                                srcA = String(normOutBase.base_source || normOutBase.sourceA || '').trim();
                            }
                            if (!srcA) return;
                            normOut = { ...normOutBase, sourceA: srcA };
                        }
                        const rowsForThisOutput = filterSectionDomRowsForSummaryOutput(group.rows, output, inheritedRowIndicesRule, inheritedRowUidsRule);
                        const val = calculateSummaryValueByOperation(normOut, rowsForThisOutput);
                        applySummaryOutputToMaps(output, val);
                    });
                    // Phase 2: Super Admin "Formula (A & B)" — uses other blue cells in this row (totals), not data-row sums
                    const blueSameRowOutputs = scopedOutputsList.filter((o) => o && o.target_field && isPcBlueRowSameRowFormulaOutput(o));
                    blueSameRowOutputs.sort((a, b) => {
                        const ia = resolveSummaryOutputColumnIndex(a.target_field);
                        const ib = resolveSummaryOutputColumnIndex(b.target_field);
                        if (ia < 0 && ib < 0) return 0;
                        if (ia < 0) return 1;
                        if (ib < 0) return -1;
                        return ia - ib;
                    });
                    const brPasses = Math.max(1, blueSameRowOutputs.length * 2);
                    for (let brPass = 0; brPass < brPasses; brPass++) {
                        blueSameRowOutputs.forEach((output) => {
                            const val = calculateBlueRowSameRowSummaryValue(output, summaryByColIndex);
                            applySummaryOutputToMaps(output, val);
                        });
                    }
                    /** Map schema column index → summary rule outputs that target this column (resolved; no loose string-key matching). */
                    const outputsByColIdx = Object.create(null);
                    if (sectionHasDataRows && Array.isArray(scopedOutputsList)) {
                        scopedOutputsList.forEach((o) => {
                            if (!o?.target_field) return;
                            const idx = resolveSummaryOutputColumnIndex(o.target_field);
                            if (idx < 0) return;
                            if (!outputsByColIdx[idx]) outputsByColIdx[idx] = [];
                            outputsByColIdx[idx].push(o);
                        });
                    }
                    const manualOverrideFields = Array.isArray(storedRowForGroup?._meta?.manual_override_fields) ? storedRowForGroup._meta.manual_override_fields : [];
                    const isClearedValue = (v) => { const s = String(v ?? '').trim(); return s === '' || s === '—' || s === '-' || s === '–'; };
                    const isManualSummaryOverride = (field, fk, normK, canonK) => {
                        if (!manualOverrideFields.length) return false;
                        const candidates = new Set([fk, normK, canonK, field?.key, field?.name, field?.label].filter(Boolean).map((x) => String(x).trim()));
                        for (const m of manualOverrideFields) {
                            const ms = String(m || '').trim();
                            if (!ms) continue;
                            if (candidates.has(ms)) return true;
                            if (normRowKeyForSummary(ms) === normK) return true;
                        }
                        return false;
                    };
                    const lastSummaryCol = currentTemplateSchema.length - 1;
                    currentTemplateSchema.forEach((field, fieldIndex) => {
                        const td = document.createElement('td');
                        td.className = 'px-4 py-1.5 border-r border-gray-200 text-sm text-gray-800';
                        const fieldKey = field.key || (field.label || '').toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '') || 'field';
                        const normKey = getNormKeyForSummary(field);
                        const canonKey = canonicalFieldKeyForSchema(field);
                        const fromColMap = Object.prototype.hasOwnProperty.call(summaryByColIndex, fieldIndex);
                        const outsForThisCol = outputsByColIdx[fieldIndex] || [];
                        const hasResolvedFormula = sectionHasDataRows && outsForThisCol.length > 0;
                        let value;
                        const storedRaw = getStoredSummaryValueRaw(field, getNormKeyForSummary, storedRowForGroup);
                        const storedVal = getStoredSummaryValue(field, getNormKeyForSummary, storedRowForGroup);
                        // Prefer live formula output (template rules + current rows) so Planning matches Super Admin across accounts.
                        // Use stored snapshot only when there is no formula for this column, or when this cell was explicitly manual-overridden.
                        if (storedRaw !== null && isClearedValue(storedRaw)) {
                            value = '—';
                        } else if (isManualSummaryOverride(field, fieldKey, normKey, canonKey) && storedVal !== null && String(storedVal).trim() !== '') {
                            value = storedVal;
                        } else if (fromColMap || hasResolvedFormula) {
                            let liveVal = '';
                            if (fromColMap) {
                                liveVal = String(summaryByColIndex[fieldIndex] ?? '');
                            } else {
                                const oLast = outsForThisCol[outsForThisCol.length - 1];
                                const tf = String(oLast?.target_field || '').trim();
                                if (tf) {
                                    const ntf = normKeyForDataField(tf);
                                    liveVal = String(summaryValues[tf] ?? summaryValues[ntf] ?? '');
                                }
                            }
                            value = (liveVal === '' || liveVal === undefined) ? '—' : liveVal;
                        } else {
                            // Do not resurrect stale persisted summary numbers when a formula is removed.
                            // Keep only explicit manual overrides; otherwise show dash until a live formula computes this cell.
                            value = '—';
                        }
                        if (fieldIndex === 0 && (value === '' || value === null)) {
                            value = '—';
                        }
                        if (String(value || '').trim().toLowerCase() === 'summary') value = '—';
                        const escapedValue = String(value ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        td.className = 'px-4 py-1.5 border-r border-gray-200 text-sm font-semibold text-gray-800 bg-blue-100' + (fieldIndex === lastSummaryCol ? ' relative group' : '');
                        const metaHidden = fieldIndex === 0 ? '<input type="hidden" name="table_data[0][_meta][row_type]" value="summary">' : '';
                        const inputHtml = `${metaHidden}<input type="text" name="table_data[0][${fieldKey}]" value="${escapedValue}" readonly class="w-full text-sm text-gray-800 bg-blue-100 border-0 focus:ring-0 font-semibold" aria-label="Summary ${field.label || fieldKey}">`;
                        if (fieldIndex === lastSummaryCol) {
                            td.innerHTML = `<div class="flex items-center gap-2 relative pr-8 min-h-[28px]">${inputHtml}<button type="button" class="edit-remove-btn absolute right-0 top-1/2 -translate-y-1/2 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 hover:text-red-800 opacity-0 group-hover:opacity-100 transition-opacity text-lg font-bold leading-none" data-action="remove-summary" title="Delete summary row">×</button></div>`;
                        } else {
                            td.innerHTML = inputHtml;
                        }
                        summaryRow.appendChild(td);
                    });
                    anchor.insertAdjacentElement('afterend', summaryRow);
                });
                // After summaries exist, drop separators that still have no data or summary following
                const allTrSweep = Array.from(tableBody.querySelectorAll('tr'));
                for (let si = allTrSweep.length - 1; si >= 0; si--) {
                    const srow = allTrSweep[si];
                    if (srow.getAttribute('data-row-type') === 'separator') {
                        const nx = srow.nextElementSibling;
                        const nt = nx ? nx.getAttribute('data-row-type') : null;
                        if (!nx || (nt !== 'data' && nt !== 'summary')) srow.remove();
                    }
                }
                reindexRows();
            }

            // Remove row functionality using event delegation - more reliable
            function setupRemoveRowHandlers() {
                
                // Wait for DOM to be ready
                const waitForTableBody = () => {
                    const tableBody = document.getElementById('table-body');
                    if (tableBody) {
                        try {
                            tableBody.removeEventListener('click', handleRemoveRowClick);
                            tableBody.addEventListener('click', handleRemoveRowClick);
                            return true;
                        } catch (error) {
                            console.error('Error setting up table body event listener:', error);
                            return false;
                        }
                    }
                    return false;
                };
                
                // Try immediately
                if (!waitForTableBody()) {
                    console.log('Table body not found, waiting for DOM...');
                    // Try multiple times with increasing delays
                    let attempts = 0;
                    const maxAttempts = 10;
                    const tryAgain = () => {
                        attempts++;
                        if (waitForTableBody()) {
                            return;
                        }
                        if (attempts < maxAttempts) {
                            setTimeout(tryAgain, 100 * attempts);
                        } else {
                            console.error('Table body still not found after', maxAttempts, 'attempts');
                        }
                    };
                    setTimeout(tryAgain, 50);
                }
            }
            
            function handleRemoveRowClick(event) {
                const removeBtn = event.target.closest('.edit-remove-btn, [data-action="remove"], [data-action="remove-summary"]');
                if (!removeBtn) return;
                event.preventDefault();
                event.stopPropagation();
                const rowToRemove = removeBtn.closest('tr');
                if (!rowToRemove) return;
                const rowType = rowToRemove.getAttribute('data-row-type');
                if (rowType === 'summary' || removeBtn.getAttribute('data-action') === 'remove-summary') {
                    const prevS = rowToRemove.previousElementSibling;
                    const nextS = rowToRemove.nextElementSibling;
                    rowToRemove.remove();
                    if (prevS && prevS.getAttribute('data-row-type') === 'separator') prevS.remove();
                    if (nextS && nextS.getAttribute('data-row-type') === 'separator') nextS.remove();
                    reindexRows();
                    computeCalculatedFields();
                    renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                    return;
                }
                if (rowType !== 'data') return;
                const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                if (dataRows.length <= 1) {
                    window.showAlert({ title: 'Notice', message: 'You must have at least one row of data.' });
                    return;
                }
                rowToRemove.remove();
                reindexRows();
                computeCalculatedFields();
                renderSummaryRows();
                if (typeof markTableDirty === 'function') markTableDirty();
                // Remove orphaned separators (nothing valid after)
                if (tableBody) {
                    const tableRows = Array.from(tableBody.querySelectorAll('tr'));
                    for (let i = tableRows.length - 1; i >= 0; i--) {
                        const row = tableRows[i];
                        if (row.getAttribute('data-row-type') === 'separator') {
                            const next = row.nextElementSibling;
                            const nt = next ? next.getAttribute('data-row-type') : null;
                            if (!next || (nt !== 'data' && nt !== 'summary')) {
                                row.remove();
                            }
                        }
                    }
                }
            }

            // Track selected row for "Add Another Row" - insert below clicked row (match Super Admin)
            const ROW_SELECT_CLASS = [];
            function clearRowSelection() {
                if (selectedRowForAdd) {
                    selectedRowForAdd.classList.remove(...ROW_SELECT_CLASS);
                    selectedRowForAdd = null;
                }
                rowActionsDotsRefCell = null;
                if (rowActionsPopover) {
                    rowActionsPopover.classList.add('hidden');
                    rowActionsPopover.style.display = 'none';
                }
                if (rowActionsDotsBtn) {
                    rowActionsDotsBtn.classList.add('hidden');
                    rowActionsDotsBtn.style.display = 'none';
                }
            }
            function positionRowActionsPopover(tr) {
                if (!rowActionsPopover || !tr) return;
                const trRect = tr.getBoundingClientRect();
                const popW = rowActionsPopover.offsetWidth || 200;
                const popH = rowActionsPopover.offsetHeight || 44;
                const gap = 8;
                let top = trRect.bottom + gap;
                if (top + popH > window.innerHeight - gap) top = trRect.top - popH - gap;
                if (top < gap) top = gap;
                let left = trRect.left;
                if (left + popW > window.innerWidth - gap) left = window.innerWidth - popW - gap;
                if (left < gap) left = gap;
                rowActionsPopover.style.left = left + 'px';
                rowActionsPopover.style.top = top + 'px';
            }
            function showRowActionsPopover(tr) {
                if (!rowActionsPopover || !tr) return;
                rowActionsPopover.classList.remove('hidden');
                rowActionsPopover.style.display = 'flex';
                rowActionsPopover.style.visibility = 'visible';
                rowActionsPopover.style.pointerEvents = 'auto';
                requestAnimationFrame(() => {
                    positionRowActionsPopover(tr);
                    rowActionsPopover.style.display = 'flex';
                });
            }

            function positionRowActionsDots(tr, refCell = null) {
                if (!rowActionsDotsBtn || !tr) return;
                const anchorCell = refCell || rowActionsDotsRefCell || null;
                const rowRect = tr.getBoundingClientRect();
                let anchorRect = anchorCell ? anchorCell.getBoundingClientRect() : rowRect;
                const gap = 8;
                const btnSize = 32; // ~w-8 h-8
                const winRect = { top: 0, left: 0, right: window.innerWidth, bottom: window.innerHeight };
                const rowVisible = !(rowRect.bottom < winRect.top || rowRect.top > winRect.bottom || rowRect.right < winRect.left || rowRect.left > winRect.right);
                if (!rowVisible) {
                    hideRowActionsDots();
                    return;
                }
                const cellVisible = anchorCell
                    ? !(anchorRect.bottom < winRect.top || anchorRect.top > winRect.bottom || anchorRect.right < winRect.left || anchorRect.left > winRect.right)
                    : true;
                if (!cellVisible) {
                    // Keep actions available even when selected cell is off-screen.
                    anchorRect = rowRect;
                }
                let left = anchorRect.right - btnSize - gap;
                if (left < gap) left = gap;
                if (left + btnSize > window.innerWidth - gap) left = window.innerWidth - btnSize - gap;
                let top = anchorRect.top + (anchorRect.height / 2) - (btnSize / 2);
                if (top < gap) top = gap;
                if (top + btnSize > window.innerHeight - gap) top = window.innerHeight - gap - btnSize;
                rowActionsDotsBtn.style.left = left + 'px';
                rowActionsDotsBtn.style.top = top + 'px';
            }

            function scheduleRepositionRowActionsDots() {
                if (!rowActionsDotsBtn || !rowActionsDotsTargetRow) return;
                if (scheduleRepositionRowActionsDots._raf) {
                    cancelAnimationFrame(scheduleRepositionRowActionsDots._raf);
                }
                scheduleRepositionRowActionsDots._raf = requestAnimationFrame(() => {
                    scheduleRepositionRowActionsDots._raf = null;
                    positionRowActionsDots(rowActionsDotsTargetRow, rowActionsDotsRefCell || null);
                });
            }

            function showRowActionsDots(tr, refCell = null) {
                if (!rowActionsDotsBtn || !tr) return;
                rowActionsDotsTargetRow = tr;
                rowActionsDotsRefCell = refCell || rowActionsDotsRefCell;
                rowActionsDotsBtn.classList.remove('hidden');
                rowActionsDotsBtn.style.display = 'flex';
                rowActionsDotsBtn.style.visibility = 'visible';
                requestAnimationFrame(() => {
                    positionRowActionsDots(tr, rowActionsDotsRefCell);
                });
            }

            function hideRowActionsDots() {
                if (!rowActionsDotsBtn) return;
                rowActionsDotsTargetRow = null;
                rowActionsDotsBtn.classList.add('hidden');
                rowActionsDotsBtn.style.display = 'none';
            }

            // Clicking the dots opens the row actions popover
            if (rowActionsDotsBtn) {
                rowActionsDotsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!selectedRowForAdd) return;
                    showRowActionsPopover(selectedRowForAdd);
                });
            }
            function setupRowSelectionForAdd() {
                if (rowSelectionSetupDone) return;
                rowSelectionSetupDone = true;
                // Use document-level delegation so it works regardless of when table is rendered
                document.addEventListener('pointerdown', function rowPointerDownHandler(e) {
                    const tbody = document.getElementById('table-body');
                    if (!tbody) return;
                    const tr = e.target.closest('tr[data-row-type="data"]');
                    if (!tr || !tbody.contains(tr)) {
                        ctrlDragSelectState = null;
                        return;
                    }
                    pendingPointerCellTd = e.target.closest('td');
                    pendingPointerCellInput = getEditableInputFromCellTarget(e.target);
                    ctrlDragSelectState = null;
                    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && pendingPointerCellInput && tr.contains(pendingPointerCellInput)) {
                        ctrlDragSelectState = {
                            anchorInput: pendingPointerCellInput,
                            startX: e.clientX,
                            startY: e.clientY,
                            active: false,
                            pointerId: e.pointerId
                        };
                    }
                }, true);

                document.addEventListener('pointermove', function rowCtrlDragMove(e) {
                    if (!ctrlDragSelectState || e.pointerId !== ctrlDragSelectState.pointerId) return;
                    const st = ctrlDragSelectState;
                    const dx = e.clientX - st.startX;
                    const dy = e.clientY - st.startY;
                    const th = CTRL_DRAG_THRESHOLD_PX;
                    if (!st.active) {
                        if (dx * dx + dy * dy < th * th) return;
                        st.active = true;
                        if (tableContainer) {
                            tableContainer.classList.add('accomplishment-ctrl-drag-selecting');
                            try { tableContainer.setPointerCapture(e.pointerId); } catch (err) {}
                        }
                    }
                    e.preventDefault();
                    const cur = getEditableInputFromPoint(e.clientX, e.clientY);
                    if (cur && tableBody && tableBody.contains(cur)) {
                        selectCellRange(st.anchorInput, cur, false);
                        rangeSelectionAnchorInput = st.anchorInput;
                    }
                }, { capture: true, passive: false });

                function finishCtrlDragPointer(e) {
                    if (!ctrlDragSelectState || e.pointerId !== ctrlDragSelectState.pointerId) return;
                    const wasActive = ctrlDragSelectState.active;
                    if (wasActive) suppressNextTableBodyClick = true;
                    if (tableContainer) {
                        try { tableContainer.releasePointerCapture(e.pointerId); } catch (err) {}
                        tableContainer.classList.remove('accomplishment-ctrl-drag-selecting');
                    }
                    if (wasActive && lastClickedCellInput && tableBody && tableBody.contains(lastClickedCellInput)) {
                        const trEnd = lastClickedCellInput.closest('tr[data-row-type="data"]');
                        if (trEnd) {
                            selectedRowForAdd = trEnd;
                            rowActionsDotsRefCell = lastClickedCellInput.closest('td');
                            showRowActionsDots(trEnd, rowActionsDotsRefCell);
                        }
                    }
                    ctrlDragSelectState = null;
                }
                document.addEventListener('pointerup', finishCtrlDragPointer, true);
                document.addEventListener('pointercancel', finishCtrlDragPointer, true);

                document.addEventListener('click', function rowClickHandler(e) {
                    const tbody = document.getElementById('table-body');
                    if (!tbody) return;
                    if (suppressNextTableBodyClick) {
                        suppressNextTableBodyClick = false;
                        pendingPointerCellInput = null;
                        pendingPointerCellTd = null;
                        if (tableContainer && e.target && tableContainer.contains(e.target)) {
                            return;
                        }
                    }
                    const tr = e.target.closest('tr[data-row-type="data"]');
                    if (tr && tbody.contains(tr)) {
                        const clickedCell = (pendingPointerCellTd && tr.contains(pendingPointerCellTd)) ? pendingPointerCellTd : e.target.closest('td');
                        const cellInput = (pendingPointerCellInput && tr.contains(pendingPointerCellInput))
                            ? pendingPointerCellInput
                            : getEditableInputFromCellTarget(e.target);
                        const additive = !!(e.ctrlKey || e.metaKey);
                        const isRange = !!e.shiftKey;

                        // Ctrl+click: multi-select cells for later paste.
                        // Plain click: select a single cell for copy/paste column reference.
                        if (cellInput) {
                            if (isRange && rangeSelectionAnchorInput) {
                                selectCellRange(rangeSelectionAnchorInput, cellInput, additive);
                            } else {
                                if (!additive) clearCellSelection();
                                setCellSelection(cellInput, additive);
                                rangeSelectionAnchorInput = cellInput;
                            }
                        } else if (!additive) {
                            clearCellSelection();
                        }

                        // Keep row highlight only for plain single-click.
                        // Multi-cell gestures (Ctrl/Cmd/Shift) should focus on cell selection, not whole-row selection.
                        if (!additive && !isRange) {
                            clearRowSelection();
                            selectedRowForAdd = tr;
                            tr.classList.add(...ROW_SELECT_CLASS);
                        } else {
                            selectedRowForAdd = tr;
                        }
                        rowActionsDotsRefCell = clickedCell || null;
                        // Show dots near current cell; popover still opens only when dots are clicked.
                        showRowActionsDots(tr, rowActionsDotsRefCell);
                    }
                    pendingPointerCellInput = null;
                    pendingPointerCellTd = null;
                }, true);
                document.addEventListener('focusin', function rowFocusHandler(e) {
                    const tbody = document.getElementById('table-body');
                    if (!tbody) return;
                    const input = e.target.closest('input, select, textarea');
                    if (input) {
                        // Do not reset multi-cell selection on focus changes.
                        const tr = input.closest('tr[data-row-type="data"]');
                        if (tr && tbody.contains(tr)) {
                            const td = input.closest('td');
                            if (!selectedCellsForPaste || selectedCellsForPaste.length <= 1) {
                                clearRowSelection();
                                selectedRowForAdd = tr;
                                tr.classList.add(...ROW_SELECT_CLASS);
                            } else {
                                selectedRowForAdd = tr;
                            }
                            rowActionsDotsRefCell = td || null;
                            // Focus should show dots, not auto-open popover.
                            showRowActionsDots(tr, rowActionsDotsRefCell);
                        }
                    }
                }, true);
                if (rowActionsPopover) {
                    rowActionsPopover.addEventListener('mouseenter', function() {
                        if (rowActionsHideTimeout) { clearTimeout(rowActionsHideTimeout); rowActionsHideTimeout = null; }
                    });
                    rowActionsPopover.addEventListener('mouseleave', function(e) {
                        const related = e.relatedTarget;
                        if (related && tableContainer && tableContainer.contains(related)) return;
                        if (related && rowActionsPopover.contains(related)) return;
                        if (rowActionsHideTimeout) clearTimeout(rowActionsHideTimeout);
                        rowActionsHideTimeout = setTimeout(function() {
                            rowActionsHideTimeout = null;
                            if (rowActionsPopover) rowActionsPopover.classList.add('hidden');
                        }, 200);
                    });
                }
                document.addEventListener('click', function(e) {
                    if (!rowActionsPopover || !selectedRowForAdd) return;
                    if (rowActionsPopover.contains(e.target) || (selectedRowForAdd && selectedRowForAdd.contains(e.target))) return;
                    if (rowActionsDotsBtn && rowActionsDotsBtn.contains(e.target)) return;
                    if (tableContainer && tableContainer.contains(e.target)) return;
                    clearRowSelection();
                }, false);
                if (tableContainer) {
                    tableContainer.addEventListener('scroll', scheduleRepositionRowActionsDots, { passive: true });
                }
                window.addEventListener('scroll', scheduleRepositionRowActionsDots, { passive: true });
                window.addEventListener('resize', scheduleRepositionRowActionsDots, { passive: true });

                if (tableContainer && !TEMPLATE_IS_LOCKED) {
                    tableContainer.addEventListener('copy', function(e) {
                        if (!tableBody || !tableBody.contains(e.target)) return;
                        var sourceInputs = [];
                        if (selectedCellsForPaste && selectedCellsForPaste.length > 0) {
                            sourceInputs = selectedCellsForPaste.slice();
                        } else {
                            var tCopy = e.target;
                            if (tCopy && (tCopy.tagName === 'INPUT' || tCopy.tagName === 'TEXTAREA')) {
                                try {
                                    if (tCopy.selectionStart != null && tCopy.selectionEnd != null && tCopy.selectionStart !== tCopy.selectionEnd) return;
                                } catch (scErr) {}
                            }
                            var anchorIn = getPasteAnchorInput();
                            if (anchorIn) sourceInputs = [anchorIn];
                        }
                        if (!sourceInputs.length) return;

                        var clipRows = buildClipboardRowsFromPcInputs(sourceInputs);
                        if (!clipRows.length) return;

                        var hasAny = false;
                        for (var hi = 0; hi < clipRows.length && !hasAny; hi++) {
                            for (var hj = 0; hj < clipRows[hi].length; hj++) {
                                if (!isCellEmptyValue(clipRows[hi][hj])) { hasAny = true; break; }
                            }
                        }
                        if (!hasAny) {
                            if (typeof window.showToast === 'function') window.showToast('notice', 'Selection is empty.');
                            else if (typeof window.showAlert === 'function') window.showAlert({ title: 'Notice', message: 'Selection is empty.' });
                            e.preventDefault();
                            return;
                        }

                        var sortedOne = sortSelectedInputsRowMajor(sourceInputs);
                        var firstRead = sortedOne[0] ? readValueFromCellInput(sortedOne[0]) : null;
                        cellPasteClipboard = {
                            v: 3,
                            rows: clipRows.map(function(r) { return r.map(function(v) { return { v: v }; }); }),
                            primaryFieldKey: firstRead ? String(firstRead.fieldKey) : ''
                        };

                        var tsv = clipRows.map(function(row) {
                            return row.map(function(c) { return String(c).replace(/\r|\n|\t/g, ' '); }).join('\t');
                        }).join('\n');
                        try {
                            e.clipboardData.setData('text/plain', tsv);
                        } catch (cbErr) {}
                        e.preventDefault();

                        var ncells = clipRows.reduce(function(a, r) { return a + r.length; }, 0);
                        var msg = ncells === 1 ? 'Copied cell value.' : ('Copied ' + ncells + ' cells.');
                        if (typeof window.showToast === 'function') window.showToast('notice', msg);
                    }, true);

                    tableContainer.addEventListener('paste', function(e) {
                        if (!tableBody) return;

                        var text = '';
                        try {
                            text = (e.clipboardData || window.clipboardData).getData('text/plain') || '';
                        } catch (pe) { text = ''; }

                        var osGrid = text ? parsePlainTextToValueGrid(text) : null;
                        var osIsGrid = osGrid && isProbablyValueGrid(osGrid);

                        var internalGrid = null;
                        if (cellPasteClipboard && cellPasteClipboard.v === 3 && Array.isArray(cellPasteClipboard.rows)) {
                            internalGrid = cellPasteClipboard.rows.map(function(r) {
                                return r.map(function(c) { return String((c && c.v !== undefined) ? c.v : ''); });
                            });
                        }
                        var internalIsGrid = internalGrid && isProbablyValueGrid(internalGrid);

                        function pasteDone(applied, emptyMsg) {
                            if (applied > 0) {
                                if (typeof window.showToast === 'function') {
                                    window.showToast('notice', applied === 1 ? 'Pasted into 1 cell.' : ('Pasted into ' + applied + ' cells.'));
                                }
                            } else if (emptyMsg && typeof window.showToast === 'function') {
                                window.showToast('notice', emptyMsg);
                            }
                        }

                        var tagPaste = e.target && e.target.tagName ? String(e.target.tagName).toUpperCase() : '';
                        if (String(text).replace(/^\s+|\s+$/g, '') !== '' && !osIsGrid && (tagPaste === 'INPUT' || tagPaste === 'TEXTAREA' || tagPaste === 'SELECT')) {
                            return;
                        }

                        var pasteableMultiSel = getPasteableSelectedInputsSorted();
                        var multiHint = 'Could not paste into selection (editable data cells only).';
                        if (pasteableMultiSel.length >= 2) {
                            if (osIsGrid) {
                                e.preventDefault();
                                e.stopPropagation();
                                pasteDone(pasteValueGridOntoMultiSelectionForPc(osGrid), multiHint);
                                return;
                            }
                            if (internalIsGrid) {
                                e.preventDefault();
                                e.stopPropagation();
                                pasteDone(pasteValueGridOntoMultiSelectionForPc(internalGrid), multiHint);
                                return;
                            }
                            if (String(text).replace(/^\s+|\s+$/g, '') !== '') {
                                e.preventDefault();
                                e.stopPropagation();
                                pasteDone(pastePlainTextToMultiSelectionForPc(text), multiHint);
                                return;
                            }
                        }

                        if (osIsGrid) {
                            e.preventDefault();
                            e.stopPropagation();
                            var anchorOs = getPasteAnchorInput();
                            if (!anchorOs) return;
                            pasteDone(pasteValueGridFromAnchorForPc(anchorOs, osGrid), 'No cells updated from paste.');
                            return;
                        }
                        if (internalIsGrid) {
                            e.preventDefault();
                            e.stopPropagation();
                            var anchorIn = getPasteAnchorInput();
                            if (!anchorIn) return;
                            pasteDone(pasteValueGridFromAnchorForPc(anchorIn, internalGrid), 'No cells updated from paste.');
                            return;
                        }
                        if (internalGrid && internalGrid.length === 1 && internalGrid[0].length === 1 && cellPasteClipboard && cellPasteClipboard.primaryFieldKey) {
                            var vOne = internalGrid[0][0];
                            if (!isCellEmptyValue(vOne)) {
                                e.preventDefault();
                                e.stopPropagation();
                                pasteDone(pasteSingleValueToMatchingColumnForPc(vOne, cellPasteClipboard.primaryFieldKey), 'No matching cells to paste into.');
                            }
                            return;
                        }
                        if (cellPasteClipboard && !cellPasteClipboard.v && cellPasteClipboard.fieldKey !== undefined && cellPasteClipboard.value !== undefined) {
                            if (!isCellEmptyValue(cellPasteClipboard.value)) {
                                e.preventDefault();
                                e.stopPropagation();
                                pasteDone(pasteSingleValueToMatchingColumnForPc(cellPasteClipboard.value, cellPasteClipboard.fieldKey), 'No matching cells to paste into.');
                            }
                        }
                    }, true);
                }

                // Ctrl+Shift+C / Ctrl+Shift+V: row copy via localStorage (unchanged)
                document.addEventListener('keydown', function(e) {
                    const ctrl = !!(e.ctrlKey || e.metaKey);
                    if (!ctrl) return;
                    const key = String(e.key || '').toLowerCase();
                    if (key !== 'c' && key !== 'v') return;

                    const srcInput = lastClickedCellInput && tableBody && tableBody.contains(lastClickedCellInput)
                        ? lastClickedCellInput
                        : (document.activeElement ? document.activeElement.closest('input, select, textarea') : null);

                    if (!srcInput || !tableBody || !tableBody.contains(srcInput)) return;

                    // Ctrl+Shift+C = copy one or more full rows (cross-template) to localStorage
                    // - Ctrl+click cells in multiple rows → copies each of those rows (top-to-bottom order)
                    // - Otherwise copies the focused / row-actions-selected row
                    if (e.shiftKey && key === 'c') {
                        var fieldKeysToCollect = null;
                        if (selectedCellsForPaste && selectedCellsForPaste.length > 0) {
                            var keysSet = new Set();
                            selectedCellsForPaste.forEach(function(el) {
                                if (!el) return;
                                var fk = getFieldKeyFromCellInput(el);
                                if (fk) keysSet.add(String(fk));
                            });
                            if (keysSet.size > 0) fieldKeysToCollect = keysSet;
                        }

                        let sourceTrs = [];
                        if (selectedCellsForPaste && selectedCellsForPaste.length > 0) {
                            const seen = new Set();
                            selectedCellsForPaste.forEach(function(el) {
                                if (!el) return;
                                const tr = el.closest('tr[data-row-type="data"]');
                                if (!tr || !tableBody || !tableBody.contains(tr)) return;
                                if (seen.has(tr)) return;
                                seen.add(tr);
                                sourceTrs.push(tr);
                            });
                            sourceTrs = sortDataRowsByDomOrder(sourceTrs);
                        } else if (selectedRowForAdd && selectedRowForAdd.getAttribute('data-row-type') === 'data') {
                            sourceTrs = [selectedRowForAdd];
                        } else {
                            const srcTr = srcInput.closest('tr[data-row-type="data"]');
                            if (srcTr) sourceTrs = [srcTr];
                        }
                        if (sourceTrs.length === 0) return;
                        const rowsPayload = sourceTrs.map(function(tr) { return collectRowDataFromTrForRowCopy(tr, fieldKeysToCollect); }).filter(Boolean);
                        if (rowsPayload.length === 0) return;
                        try {
                            localStorage.setItem(ROW_COPY_BUFFER_KEY, JSON.stringify({ v: 2, rows: rowsPayload }));
                            const msg = rowsPayload.length === 1
                                ? 'Copied 1 row to clipboard.'
                                : ('Copied ' + rowsPayload.length + ' rows to clipboard.');
                            if (typeof window.showToast === 'function') window.showToast('notice', msg);
                        } catch (err) {
                            if (typeof window.showAlert === 'function') window.showAlert({ title: 'Notice', message: 'Row copy failed (storage blocked).' });
                        }
                        e.preventDefault();
                        return;
                    }

                    // Ctrl+Shift+V = paste copied row(s) into selected target row(s)
                    // - 1 copied row → same data into every selected destination row
                    // - N copied rows → row 1 → dest 1, row 2 → dest 2, … (table order)
                    if (e.shiftKey && key === 'v') {
                        let raw = null;
                        try { raw = localStorage.getItem(ROW_COPY_BUFFER_KEY); } catch (err) {}
                        if (!raw) return;
                        const clipboardRows = parseRowClipboardRows(raw);
                        if (!clipboardRows.length) return;

                        let targetRows = [];
                        if (selectedRowForAdd && selectedRowForAdd.getAttribute('data-row-type') === 'data') {
                            targetRows = [selectedRowForAdd];
                        } else if (selectedCellsForPaste && selectedCellsForPaste.length > 0) {
                            var unique = new Set();
                            selectedCellsForPaste.forEach(function(el) {
                                if (!el) return;
                                if (!isCellEmptyValue(el.value || '')) return;
                                var tr = el.closest('tr[data-row-type="data"]');
                                if (!tr) return;
                                unique.add(tr);
                            });
                            targetRows = sortDataRowsByDomOrder(Array.from(unique));
                            if (targetRows.length === 0) {
                                if (typeof window.showToast === 'function') window.showToast('notice', 'Select empty cells in the destination rows.');
                                return;
                            }
                        } else {
                            var srcTr2 = srcInput.closest('tr[data-row-type="data"]');
                            targetRows = srcTr2 ? [srcTr2] : [];
                        }

                        var applied = 0;
                        for (var ri = 0; ri < targetRows.length; ri++) {
                            var tr = targetRows[ri];
                            if (!tr || !tableBody || !tableBody.contains(tr)) continue;
                            var srcRow = clipboardRows.length === 1 ? clipboardRows[0] : clipboardRows[ri];
                            if (!srcRow) break;
                            applied += applyRowDataToTrPasteEmptyOnly(tr, srcRow);
                        }

                        if (applied > 0) {
                            window.tableDataDirty = true;
                            if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                            if (typeof renderSummaryRows === 'function') renderSummaryRows();
                            if (typeof markTableDirty === 'function') markTableDirty();
                        }
                        e.preventDefault();
                        return;
                    }
                }, true);
            }

            // Backspace/Delete: clear values of selected (multi) cells
            document.addEventListener('keydown', function(e) {
                var key = String(e.key || '');
                if (key !== 'Backspace' && key !== 'Delete') return;
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                if (!Array.isArray(selectedCellsForPaste) || selectedCellsForPaste.length === 0) return;
                if (!tableBody) return;

                var active = document.activeElement;
                if (!active || !tableBody.contains(active)) return;

                // If user is editing a single focused cell, let Backspace behave normally.
                if (selectedCellsForPaste.length === 1) {
                    var only = selectedCellsForPaste[0];
                    if (only && active === only) return;
                    // Also allow normal backspace when the caret is inside the same cell input.
                    if (only && active && active.closest && active.closest('input, select, textarea') === only) return;
                    // Only clear instantly when multiple cells are selected.
                    return;
                }

                var applied = 0;
                selectedCellsForPaste.forEach(function(el) {
                    if (!el || !tableBody.contains(el)) return;
                    var cur = el.value || '';
                    if (!cur || isCellEmptyValue(cur)) return;
                    if (writeValueToCellInput(el, '')) applied++;
                });

                if (applied > 0) {
                    if (typeof computeCalculatedFields === 'function') computeCalculatedFields();
                    if (typeof renderSummaryRows === 'function') renderSummaryRows();
                    if (typeof markTableDirty === 'function') markTableDirty();
                    e.preventDefault();
                }
            }, true);
            
            function handleAddRowClick(e) {
                e.preventDefault();
                e.stopPropagation();
                // Insert below selected row if user clicked a cell; otherwise append at end (match Super Admin)
                const insertAfter = (selectedRowForAdd && selectedRowForAdd.parentNode === tableBody) ? selectedRowForAdd : null;
                editAddRow(null, null, insertAfter);
                const defaultQ = (function() {
                    const h = document.getElementById('submission-quarter-hidden');
                    return (h && h.value && String(h.value).trim()) ? String(h.value).trim() : '1st Q';
                })();
                const newDataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                const newRow = newDataRows[newDataRows.length - 1];
                if (newRow && defaultQ) {
                    const quarterInput = newRow.querySelector('input[name*="[quarter]"], select[name*="[quarter]"]');
                    if (quarterInput) quarterInput.value = defaultQ;
                }
                reindexRows();
                const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                rowCount = dataRows.length;
                computeCalculatedFields();
                renderSummaryRows();
                if (typeof markTableDirty === 'function') markTableDirty();
                // Move selection to the new row (match Super Admin)
                if (insertAfter) {
                    const addedRow = insertAfter.nextElementSibling;
                    if (addedRow && addedRow.getAttribute('data-row-type') === 'data') {
                        if (selectedRowForAdd) selectedRowForAdd.classList.remove(...ROW_SELECT_CLASS);
                        selectedRowForAdd = addedRow;
                        addedRow.classList.add(...ROW_SELECT_CLASS);
                        showRowActionsPopover(addedRow);
                    }
                }
            }

            function handleGlobalSeparateClick(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!tableBody || !currentTemplateSchema) {
                    console.warn('Separate: tableBody or currentTemplateSchema not ready');
                    return;
                }
                // Anchor on a data row only (match Super Admin — never insert after summary/separator)
                let insertAfterRow = null;
                if (selectedRowForAdd && selectedRowForAdd.parentNode === tableBody && selectedRowForAdd.getAttribute('data-row-type') === 'data') {
                    insertAfterRow = selectedRowForAdd;
                } else {
                    const dataOnly = tableBody.querySelectorAll('tr[data-row-type="data"]');
                    insertAfterRow = dataOnly.length ? dataOnly[dataOnly.length - 1] : null;
                }
                if (!insertAfterRow) return;
                const separatorRow = document.createElement('tr');
                separatorRow.setAttribute('data-row-type', 'separator');
                const sepToken = `sep_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
                separatorRow.setAttribute('data-sep-token', sepToken);
                const colCount = currentTemplateSchema.length;
                const sepClass = 'h-4 min-h-[1rem] px-4 py-2 bg-gray-200 border-t-2 border-b-2 border-gray-300';
                separatorRow.innerHTML = `<td colspan="${colCount}" class="${sepClass}"></td>`;
                insertAfterRow.insertAdjacentElement('afterend', separatorRow);
                // If the old block summary sat right under the data rows, it is now below the separator — move it above (same as Super Admin multi)
                const imm = separatorRow.nextElementSibling;
                if (imm && (imm.getAttribute('data-row-type') === 'summary' || imm.classList.contains('bg-blue-100'))) {
                    separatorRow.parentNode.insertBefore(imm, separatorRow);
                }
                // Auto-create a clean first row for the new section (no extra click needed).
                editAddRow(null, {}, separatorRow, true);
                const firstRowInNewSection = separatorRow.nextElementSibling;
                if (firstRowInNewSection && firstRowInNewSection.getAttribute('data-row-type') === 'data') {
                    firstRowInNewSection.setAttribute('data-after-separator', 'true');
                    const defaultQ = (() => {
                        const h = document.getElementById('submission-quarter-hidden');
                        return (h && h.value && String(h.value).trim()) ? String(h.value).trim() : '1st Q';
                    })();
                    const quarterInput = firstRowInNewSection.querySelector('input[name*="[quarter]"], select[name*="[quarter]"]');
                    if (quarterInput && defaultQ) quarterInput.value = defaultQ;
                }
                reindexRows();
                computeCalculatedFields();
                renderSummaryRows();
                // Safety: after re-render, ensure this new section still has at least one data row.
                let resolvedFirstRow = firstRowInNewSection;
                const sepAfterRender = tableBody ? tableBody.querySelector(`tr[data-row-type="separator"][data-sep-token="${sepToken}"]`) : null;
                if (sepAfterRender) {
                    let hasDataRowInSection = false;
                    let walker = sepAfterRender.nextElementSibling;
                    while (walker && walker.getAttribute('data-row-type') !== 'separator') {
                        if (walker.getAttribute('data-row-type') === 'data') {
                            hasDataRowInSection = true;
                            resolvedFirstRow = walker;
                            break;
                        }
                        walker = walker.nextElementSibling;
                    }
                    if (!hasDataRowInSection) {
                        editAddRow(null, {}, sepAfterRender, true);
                        resolvedFirstRow = sepAfterRender.nextElementSibling;
                        if (resolvedFirstRow && resolvedFirstRow.getAttribute('data-row-type') === 'data') {
                            resolvedFirstRow.setAttribute('data-after-separator', 'true');
                            const defaultQ2 = (() => {
                                const h = document.getElementById('submission-quarter-hidden');
                                return (h && h.value && String(h.value).trim()) ? String(h.value).trim() : '1st Q';
                            })();
                            const quarterInput2 = resolvedFirstRow.querySelector('input[name*="[quarter]"], select[name*="[quarter]"]');
                            if (quarterInput2 && defaultQ2) quarterInput2.value = defaultQ2;
                        }
                        reindexRows();
                        computeCalculatedFields();
                        renderSummaryRows();
                    }
                    sepAfterRender.removeAttribute('data-sep-token');
                }
                if (typeof markTableDirty === 'function') markTableDirty();
                if (resolvedFirstRow && resolvedFirstRow.getAttribute('data-row-type') === 'data') {
                    if (selectedRowForAdd) selectedRowForAdd.classList.remove(...ROW_SELECT_CLASS);
                    selectedRowForAdd = resolvedFirstRow;
                    resolvedFirstRow.classList.add(...ROW_SELECT_CLASS);
                    showRowActionsPopover(resolvedFirstRow);
                }
            }

            // Custom modal dialog to replace window.prompt for "add N rows"
            function showRowCountDialog(defaultCount) {
                return new Promise(function(resolve) {
                    var existingBackdrop = document.getElementById('pc-rowcount-dialog-backdrop');
                    var backdrop = existingBackdrop || document.createElement('div');
                    if (!existingBackdrop) {
                        backdrop.id = 'pc-rowcount-dialog-backdrop';
                        backdrop.className = 'fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-[100000] flex items-center justify-center';
                        backdrop.style.display = 'none';

                        var modal = document.createElement('div');
                        modal.id = 'pc-rowcount-dialog-modal';
                        modal.className = 'bg-white rounded-xl shadow-2xl max-w-[90vw] w-[420px] border border-gray-200';
                        modal.innerHTML = '' +
                            '<div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">' +
                            '  <h4 id="pc-rowcount-dialog-title" class="text-sm font-semibold text-gray-900">Add rows</h4>' +
                            '  <button type="button" id="pc-rowcount-dialog-close" class="text-gray-400 hover:text-gray-600" aria-label="Close">' +
                            '    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>' +
                            '  </button>' +
                            '</div>' +
                            '<div class="px-5 py-4 space-y-3">' +
                            '  <p class="text-xs text-gray-600">How many rows do you want to add?</p>' +
                            '  <input id="pc-rowcount-dialog-input" type="number" min="1" max="100" step="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />' +
                            '</div>' +
                            '<div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2">' +
                            '  <button type="button" id="pc-rowcount-dialog-cancel" class="px-4 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">Cancel</button>' +
                            '  <button type="button" id="pc-rowcount-dialog-ok" class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-md">OK</button>' +
                            '</div>';

                        backdrop.appendChild(modal);
                        document.body.appendChild(backdrop);
                    }

                    var input = document.getElementById('pc-rowcount-dialog-input');
                    var okBtn = document.getElementById('pc-rowcount-dialog-ok');
                    var cancelBtn = document.getElementById('pc-rowcount-dialog-cancel');
                    var closeBtn = document.getElementById('pc-rowcount-dialog-close');

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

                    if (okBtn) okBtn.onclick = function() {
                        var raw = input ? String(input.value || '').trim() : '';
                        var count = parseInt(raw, 10);
                        if (!Number.isFinite(count) || count <= 0) {
                            closeWith(null);
                            return;
                        }
                        count = Math.min(count, 100);
                        closeWith(count);
                        document.removeEventListener('keydown', escHandler, true);
                    };
                    if (cancelBtn) cancelBtn.onclick = function() {
                        closeWith(null);
                        document.removeEventListener('keydown', escHandler, true);
                    };
                    if (closeBtn) closeBtn.onclick = function() {
                        closeWith(null);
                        document.removeEventListener('keydown', escHandler, true);
                    };

                    backdrop.onclick = function(e) {
                        if (e.target === backdrop) {
                            closeWith(null);
                            document.removeEventListener('keydown', escHandler, true);
                        }
                    };
                });
            }
            
            // Popover buttons - Add row below / Add multiple rows / Separate (appear near selected row)
            const rowActionsAddBtn = document.getElementById('row-actions-add-btn');
            const rowActionsAddRowsBtn = document.getElementById('row-actions-add-rows-btn');
            const rowActionsSepBtn = document.getElementById('row-actions-separate-btn');
            if (rowActionsAddBtn) {
                rowActionsAddBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (selectedRowForAdd) {
                        handleAddRowClick(e);
                        showRowActionsPopover(selectedRowForAdd);
                    }
                });
            }
            if (rowActionsAddRowsBtn) {
                rowActionsAddRowsBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!selectedRowForAdd) return;
                    const anchorRow = selectedRowForAdd;
                    showRowCountDialog(1).then(function(count) {
                        if (count === null) return;

                        const defaultQ = (function() {
                            const h = document.getElementById('submission-quarter-hidden');
                            return (h && h.value && String(h.value).trim()) ? String(h.value).trim() : '1st Q';
                        })();

                        clearRowSelection();

                        let insertAfterEl = anchorRow;
                        let lastInsertedDataRow = null;

                        for (let i = 0; i < count; i++) {
                            editAddRow(null, null, insertAfterEl);

                            const candidate = insertAfterEl.nextElementSibling;
                            if (candidate && candidate.getAttribute('data-row-type') === 'data') {
                                lastInsertedDataRow = candidate;
                                insertAfterEl = candidate;

                                if (defaultQ) {
                                    const quarterInput = candidate.querySelector('input[name*="[quarter]"], select[name*="[quarter]"]');
                                    if (quarterInput) quarterInput.value = defaultQ;
                                }
                            } else {
                                const dataRowsNow = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                                if (dataRowsNow && dataRowsNow.length > 0) {
                                    lastInsertedDataRow = dataRowsNow[dataRowsNow.length - 1];
                                    insertAfterEl = lastInsertedDataRow;
                                }
                            }
                        }

                        reindexRows();
                        const dataRows = tableBody ? tableBody.querySelectorAll('tr[data-row-type="data"]') : [];
                        rowCount = dataRows.length;
                        computeCalculatedFields();
                        renderSummaryRows();
                        if (typeof markTableDirty === 'function') markTableDirty();

                        if (lastInsertedDataRow) {
                            selectedRowForAdd = lastInsertedDataRow;
                            selectedRowForAdd.classList.add(...ROW_SELECT_CLASS);
                            showRowActionsPopover(selectedRowForAdd);
                        }
                    });
                });
            }
            if (rowActionsSepBtn) {
                rowActionsSepBtn.addEventListener('click', function(e) {
                    if (!selectedRowForAdd) return;
                    handleGlobalSeparateClick(e);
                });
            }
            
            // Add Row / Separate via popover only (no standalone buttons)
            
            // Setup remove row handlers
            setupRemoveRowHandlers();
            
            // Row selection setup happens inside renderTemplateFields (after table is built)
            
            // Note: Save Draft and Submit buttons now use standard form submission
            // The form submit handler above will ensure data is properly indexed

            // Auto-load template for fixed template
            const currentTemplateCode = '{{ $submission->template_code }}';
            if (currentTemplateCode) {
                // Use submission's template schema and summary rules already set above; only fetch if schema missing (e.g. create flow).
                if (currentTemplateSchema && Array.isArray(currentTemplateSchema) && currentTemplateSchema.length > 0) {
                    renderTemplateFields();
                } else {
                    loadTemplateSchema(currentTemplateCode);
                }
            }
        });
    </script>

    {{-- Back to Top button (fixed bottom-right, smooth scroll to top — same pattern as Super Admin template show) --}}
    <style>
        #back-to-top-btn {
            opacity: 0;
            transform: translateY(0.75rem);
            pointer-events: none;
            transition: opacity 0.3s ease, transform 0.3s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        #back-to-top-btn.back-to-top-visible {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }
        #back-to-top-btn:hover {
            transform: translateY(0) scale(1.08);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }
        #back-to-top-btn:active {
            transform: translateY(0) scale(0.98);
        }
        @keyframes back-to-top-pulse {
            0%, 100% { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15); }
            50% { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25); }
        }
        #back-to-top-btn.back-to-top-visible {
            animation: back-to-top-pulse 2.5s ease-in-out infinite;
        }
        #back-to-top-btn.back-to-top-visible:hover {
            animation: none;
        }
    </style>
    <button type="button" id="back-to-top-btn" aria-label="Back to top" class="fixed right-6 bottom-6 z-50 flex items-center justify-center w-12 h-12 rounded-full bg-gray-700 text-white shadow-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
        <svg class="w-6 h-6 transition-transform duration-200 group-hover:-translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
        </svg>
    </button>
    <script>
        (function() {
            var btn = document.getElementById('back-to-top-btn');
            if (!btn) return;
            var scrollThreshold = 280;
            function updateVisibility() {
                if (window.scrollY > scrollThreshold) {
                    btn.classList.add('back-to-top-visible');
                } else {
                    btn.classList.remove('back-to-top-visible');
                }
            }
            window.addEventListener('scroll', function() {
                requestAnimationFrame(updateVisibility);
            });
            updateVisibility();
            btn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
</x-app-layout>
