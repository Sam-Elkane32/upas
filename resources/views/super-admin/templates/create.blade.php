<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .select2-container--default .select2-results__option { white-space: normal !important; word-wrap: break-word; }
        /* Prevent long dropdown lists from extending onto another monitor. */
        .select2-container--default .select2-results__options { max-height: 240px; overflow-y: auto; }
        /* Allow the selected KPI title to wrap inside the control instead of being truncated */
        .select2-container--default .select2-selection--single { height: auto !important; min-height: 38px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { white-space: normal !important; word-wrap: break-word; line-height: 1.4; padding: 6px 28px 6px 8px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { top: 50% !important; transform: translateY(-50%); }
    </style>

    <div class="pt-2 pb-4">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Create Template
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($form)
                                Creating template from form: <strong>{{ $form->form_title }}</strong>
                                @if(!empty($parsedKpis))
                                    <span class="text-green-600">({{ count($parsedKpis) }} KPI(s) loaded)</span>
                                @endif
                            @else
                                Create templates for any campus or all campuses
                            @endif
                        </p>
                    </div>
                    <div>
                        <a href="{{ $form ? route('forms.show', $form->id) : route('super-admin.templates.index') . '#forms' }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            {{ $form ? 'Back to Form' : 'Back to Forms' }}
                        </a>
                    </div>
                </div>
            </div>
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('super-admin.templates.store') }}" method="POST" id="create-template-form" onsubmit="return validateFormWithIntent()">
                @csrf
                <input type="hidden" id="imitate_intent" name="imitate_intent" value="create">
                @if($form)
                    <input type="hidden" name="form_id" value="{{ $form->id }}">
                @elseif(request()->has('form_id'))
                    <input type="hidden" name="form_id" value="{{ request()->get('form_id') }}">
                @endif
                
                <!-- Template Information -->
                <div class="bg-white shadow-sm sm:rounded-lg mb-6" style="overflow: visible;">
                    <div class="p-6" style="overflow: visible;">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Template Details
                        </h3>
                        
                        <input type="hidden" name="campus_code" value="ALL">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="Unpublished" {{ old('status') == 'Unpublished' ? 'selected' : '' }}>Unpublished</option>
                                    <option value="Published" {{ old('status') == 'Published' ? 'selected' : '' }}>Published</option>
                                </select>
                            </div>

                            <div>
                                <label for="sg_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Strategic Goal (SG) <span class="text-red-500">*</span>
                                </label>
                                <select id="sg_code" name="sg_code" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Strategic Goal</option>
                                    @foreach($strategicGoals as $code => $title)
                                        <option value="{{ $code }}" {{ old('sg_code', $form->sg_code ?? '') == $code ? 'selected' : '' }}>
                                            {{ $title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sg_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="template_code" class="block text-sm font-medium text-gray-700 mb-2">
                                    Template Code <span class="text-red-500">*</span>
                                </label>
                                <select id="template_code" name="template_code" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Template Code</option>
                                    @php
                                        $codesToShow = isset($availableTemplateCodes) && is_array($availableTemplateCodes) && count($availableTemplateCodes) > 0
                                            ? $availableTemplateCodes
                                            : array_map(static fn (int $i) => 'T'.$i, range(1, 5));
                                    @endphp
                                    @foreach($codesToShow as $code)
                                        @php
                                            $isSelected = old('template_code', $defaultTemplateCode ?? '') == $code;
                                        @endphp
                                        <option value="{{ $code }}" {{ $isSelected ? 'selected' : '' }}>
                                            {{ $code }}
                                        </option>
                                    @endforeach
                                    <option value="Custom" {{ old('template_code') == 'Custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <div id="custom_template_code_container" class="mt-2" style="display: none;">
                                    <label for="custom_template_code" class="block text-sm font-medium text-gray-700 mb-2">
                                        Custom Template Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="custom_template_code" name="custom_template_code" 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="e.g., T6, T7, CUSTOM1"
                                           value="{{ old('custom_template_code') }}">
                                    <p class="mt-1 text-xs text-gray-500">Enter a custom template code</p>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    @if($form && isset($availableTemplateCodes) && count($availableTemplateCodes) < 5)
                                        Select an available template code. Only unused codes are shown. If all standard codes are used, select "Custom" to enter a custom code.
                                    @else
                                        Select the next sequential code (T1, T2, …) or choose &quot;Custom&quot; for another code.
                                    @endif
                                </p>
                                @error('template_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('custom_template_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="source_template_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Copy From Template
                                </label>
                                <select id="source_template_id" name="source_template_id"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">(Optional) Choose a template to copy</option>
                                    @foreach($sourceTemplates ?? [] as $st)
                                        <option value="{{ data_get($st, 'id') }}"
                                            {{ (int)($copyFrom ?? 0) === (int)data_get($st, 'id') ? 'selected' : '' }}>
                                            {{ data_get($st, 'template_code') }} - ({{ data_get($st, 'kra_title') }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">
                                    Selecting a template auto-fills SG Code, KRA/KPI Title, Planning Coordinators, Campus Targets, and Data Table Columns. Template Code is automatically set to the next available code.
                                </p>
                                <div id="copy-template-notice" class="hidden mt-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700"></div>
                            </div>
                            @php
                                $sourceTemplatesJs = ($sourceTemplates ?? collect())->map(function ($t) {
                                    $fj = is_array($t->fields_json ?? null) ? $t->fields_json : [];
                                    return [
                                        'id'                => $t->id,
                                        'template_code'     => $t->template_code,
                                        'sg_code'           => $t->sg_code,
                                        'kra_title'         => $t->kra_title,
                                        'kpi_title'         => $t->kpi_title,
                                        'fields_json'       => $fj,
                                        'campus_targets'    => $fj['campus_targets'] ?? [],
                                        'assigned_user_ids' => $t->assignedUsers->pluck('id')->toArray(),
                                    ];
                                })->values()->toArray();
                            @endphp
                            <script>
                            var SOURCE_TEMPLATES_DATA = @json($sourceTemplatesJs);
                            var COPY_FROM_ID = {{ (int)($copyFrom ?? 0) }};
                            </script>
                            
                            <div>
                                <label for="kra_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    KRA Title <span class="text-red-500">*</span>
                                    @if($form)
                                        <span class="text-xs text-gray-500 font-normal">(from Form - Select ONE KRA)</span>
                                    @endif
                                </label>
                                @if($form && !empty($parsedKras))
                                    <!-- Single-Select Dropdown for KRA -->
                                    @php
                                        $sgCode = $form->sg_code ?? old('sg_code', 'SG1');
                                        $sgNumber = str_replace('SG', '', $sgCode);
                                        if (empty($sgNumber) || !is_numeric($sgNumber)) {
                                            $sgNumber = '1';
                                        }
                                    @endphp
                                    <select id="kra_title" name="kra_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select ONE KRA Title</option>
                                        @foreach($parsedKras as $index => $kraTitle)
                                            @php
                                                $kraNumber = $sgNumber . '.' . ($index + 1);
                                            @endphp
                                            <option value="{{ $kraTitle }}" {{ old('kra_title') == $kraTitle ? 'selected' : '' }}>
                                                {{ $kraNumber }} - {{ $kraTitle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KRA title from the form. Available: {{ count($parsedKras) }} KRA(s).</p>
                                @elseif($form && $form->kra_title)
                                    <!-- Fallback: Parse from old format if parsedKras is empty -->
                                    @php
                                        $kraParts = explode('; ', $form->kra_title);
                                        $kraParts = array_filter(array_map('trim', $kraParts));
                                        $sgCode = $form->sg_code ?? old('sg_code', 'SG1');
                                        $sgNumber = str_replace('SG', '', $sgCode);
                                        if (empty($sgNumber) || !is_numeric($sgNumber)) {
                                            $sgNumber = '1';
                                        }
                                    @endphp
                                    <select id="kra_title" name="kra_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select ONE KRA Title</option>
                                        @foreach($kraParts as $index => $kraPart)
                                            @php
                                                $kraNumber = $sgNumber . '.' . ($index + 1);
                                            @endphp
                                            @if(!empty($kraPart))
                                                <option value="{{ $kraPart }}" {{ old('kra_title') == $kraPart ? 'selected' : '' }}>
                                                    {{ $kraNumber }} - {{ $kraPart }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KRA title from the form. Available: {{ count($kraParts) }} KRA(s).</p>
                                @else
                                    <select id="kra_title" name="kra_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select KRA Title</option>
                                        @foreach($kraTitles as $kraTitle)
                                            <option value="{{ $kraTitle }}" {{ old('kra_title') == $kraTitle ? 'selected' : '' }}>
                                                {{ $kraTitle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Or enter a new KRA title in the column structure below.</p>
                                @endif
                                @error('kra_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="kpi_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    KPI Title <span class="text-red-500">*</span>
                                    @if($form)
                                        <span class="text-xs text-gray-500 font-normal">(from Form - Select ONE KPI)</span>
                                    @endif
                                </label>
                                
                                @if($form && !empty($kraKpiData))
                                    <!-- Single-Select Dropdown for KPI - populated dynamically from selected KRA -->
                                    <select id="kpi_title" name="kpi_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            disabled>
                                        <option value="">Select ONE KPI Title</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KPI title from the form. Available: <span id="kpi-count">0</span> KPI(s).</p>
                                @elseif($form && !empty($parsedKpis))
                                    <!-- Fallback: Single-Select Dropdown for KPI (old format) -->
                                    <select id="kpi_title" name="kpi_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select ONE KPI Title</option>
                                        @foreach($parsedKpis as $kpi)
                                            <option value="{{ $kpi['full'] }}" {{ old('kpi_title') == $kpi['full'] ? 'selected' : '' }}>
                                                {{ $kpi['full'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KPI title from the form. Available: {{ count($parsedKpis) }} KPI(s).</p>
                                @else
                                    <!-- Standard KPI input when no form is selected -->
                                    <input type="text" id="kpi_title" name="kpi_title" required 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                           placeholder="e.g., 1 - Program Name"
                                           value="{{ old('kpi_title', '') }}">
                                    <p class="mt-1 text-xs text-gray-500">Enter a single KPI title (e.g., "1 - Program Name")</p>
                                @endif
                                {{-- KPI Title preview: full multi-line display (same format as Create/Edit Form) --}}
                                <div id="kpi-title-preview" class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-900 whitespace-pre-wrap break-words min-h-[60px] hidden"></div>
                                @error('kpi_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Assigned Planning Coordinators: selection creates Campus Targets rows (one per assigned user's campus) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Assign Planning Coordinator(s)</h3>
                        <p class="text-xs text-gray-500 mb-3">
                            Select the users who will encode data for this template. Each selected user's assigned campus will get one row in the Campus Targets table below. Once selected, Q1–Q4 and Total are created for each campus; targets will be reflected for that Planning Coordinator when they encode.
                        </p>
                        <div class="flex items-center justify-between gap-3">
                            <label for="assigned_user_ids" class="block text-xs font-medium text-gray-700 mb-1">Planning Coordinators (by campus)</label>
                            <div class="flex items-center gap-2">
                                <button type="button" id="assigned-user-select-all"
                                        class="text-xs font-medium text-indigo-700 hover:text-indigo-900 underline underline-offset-2">
                                    Select all
                                </button>
                                <span class="text-gray-300" aria-hidden="true">|</span>
                                <button type="button" id="assigned-user-clear-all"
                                        class="text-xs font-medium text-gray-600 hover:text-gray-800 underline underline-offset-2">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <select id="assigned_user_ids" name="assigned_user_ids[]" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                data-planning-coordinators="{{ json_encode($planningCoordinatorsWithCampus ?? []) }}">
                            @php
                                $pcList = collect($planningCoordinators ?? []);
                                $byCampusCode = $pcList->groupBy(function ($u) {
                                    $c = trim((string) data_get($u, 'campus_code', ''));
                                    return $c !== '' ? $c : '_none';
                                })->map->count();
                            @endphp
                            @foreach($pcList as $pc)
                                @php
                                    $campusName = data_get($pc, 'campusInfo.name')
                                        ?? data_get($pc, 'campus')
                                        ?? data_get($pc, 'campus_code')
                                        ?? '—';
                                    $campusCode = (string) data_get($pc, 'campus_code', '');
                                    $pcId = data_get($pc, 'id');
                                    $pcName = data_get($pc, 'name');
                                    $ccKey = $campusCode !== '' ? $campusCode : '_none';
                                    $sameCampusTwice = $campusCode !== '' && ($byCampusCode[$ccKey] ?? 0) > 1;
                                    if ($campusCode !== '') {
                                        $optionLabel = $sameCampusTwice
                                            ? $campusName.' ('.$campusCode.') · '.$pcName
                                            : $campusName.' ('.$campusCode.')';
                                    } else {
                                        $optionLabel = $pcName.' — no campus assigned';
                                    }
                                @endphp
                                <option value="{{ $pcId }}"
                                        title="{{ e($pcName) }}"
                                        {{ old('assigned_user_ids') && in_array($pcId, old('assigned_user_ids')) ? 'selected' : '' }}
                                        data-campus-code="{{ e($campusCode) }}"
                                        data-campus-name="{{ e($campusName) }}">
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select one or more. Campus Targets table below will show one row per selected user's campus with Q1, Q2, Q3, Q4 and Total.</p>
                    </div>
                </div>

                {{-- Campus Targets (optional): rows created from selected Planning Coordinators' campuses --}}
                @php
                    $overall = $overallTargets ?? ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0];
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Campus Targets (Per Quarter & Total)</h3>
                        <p class="text-xs text-gray-500 mb-3">
                            Optional: Rows appear only for Planning Coordinators selected above—if not selected, that campus does not appear. Set quarterly and total targets; Total is auto-calculated from Q1–Q4. These targets will be reflected for the respective Planning Coordinator when they encode.
                        </p>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs border border-gray-200 rounded-lg overflow-hidden" id="campus-targets-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-1.5 text-left font-medium text-gray-700 border-r border-gray-200 align-bottom">Campus</th>
                                        <th class="px-3 py-1.5 text-center font-medium text-gray-700 border-r border-gray-200" colspan="4">Quarterly Targets</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-gray-700">Total</th>
                                    </tr>
                                    <tr class="bg-gray-100">
                                        <th class="px-3 py-1.5 text-left text-[11px] font-semibold text-gray-700 border-r border-gray-200">Overall Target (Form)</th>
                                        <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">Q1: {{ number_format($overall['q1'] ?? 0, 2) }}</th>
                                        <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">Q2: {{ number_format($overall['q2'] ?? 0, 2) }}</th>
                                        <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">Q3: {{ number_format($overall['q3'] ?? 0, 2) }}</th>
                                        <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">Q4: {{ number_format($overall['q4'] ?? 0, 2) }}</th>
                                        <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700">Total: {{ number_format($overall['total'] ?? 0, 2) }}</th>
                                    </tr>
                                </thead>
                                <tbody id="campus-targets-tbody">
                                    <tr id="campus-targets-empty-row"><td colspan="6" class="px-3 py-4 text-center text-xs text-gray-500">No Planning Coordinator selected. Select one or more above—only their campuses will appear here with Q1, Q2, Q3, Q4 and Total.</td></tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50">
                                        <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700 border-r border-gray-200">Sum of Campus Targets</th>
                                        @foreach(['q1','q2','q3','q4'] as $quarter)
                                            <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700 border-r border-gray-200"><span id="campus-sum-{{ $quarter }}">0.00</span></th>
                                        @endforeach
                                        <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700"><span id="campus-sum-total">0.00</span></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <script>
                (function() {
                    var planningCoordinatorsData = document.getElementById('assigned_user_ids') && document.getElementById('assigned_user_ids').dataset.planningCoordinators;
                    try { planningCoordinatorsData = planningCoordinatorsData ? JSON.parse(planningCoordinatorsData) : []; } catch(e) { planningCoordinatorsData = []; }
                    function rowTotalFromQuarters(row) {
                        var q = ['q1','q2','q3','q4'], sum = 0;
                        q.forEach(function(k) { var inp = row.querySelector('input.campus-target-' + k); if (inp) sum += parseFloat(inp.value || '0') || 0; });
                        return sum;
                    }
                    function updateRowTotal(row) {
                        var totalInput = row.querySelector('input.campus-target-total');
                        if (totalInput) totalInput.value = rowTotalFromQuarters(row).toFixed(2);
                    }
                    function normalizeCampusTargetInput(el) {
                        if (!el) return;
                        var v = String(el.value || '');
                        // If user starts typing after a default zero, avoid sticky leading zeroes.
                        if (/^0\d+$/.test(v)) {
                            el.value = String(parseInt(v, 10));
                        } else if (/^0\d+\.\d*$/.test(v)) {
                            el.value = String(parseFloat(v));
                        }
                    }
                    function recalcCampusTargetSums() {
                        var quarters = ['q1','q2','q3','q4'], sums = { q1: 0, q2: 0, q3: 0, q4: 0, total: 0 };
                        document.querySelectorAll('#campus-targets-table tr.campus-target-row').forEach(function(row) {
                            quarters.forEach(function(q) { var input = row.querySelector('input.campus-target-' + q); sums[q] += parseFloat(input && input.value ? input.value : '0') || 0; });
                            var totalInput = row.querySelector('input.campus-target-total'); if (totalInput) sums.total += parseFloat(totalInput.value || '0') || 0;
                        });
                        quarters.forEach(function(q) { var span = document.getElementById('campus-sum-' + q); if (span) span.textContent = sums[q].toFixed(2); });
                        var totalSpan = document.getElementById('campus-sum-total'); if (totalSpan) totalSpan.textContent = sums.total.toFixed(2);
                    }
                    document.addEventListener('input', function(e) {
                        if (e.target && e.target.classList && e.target.classList.contains('campus-target-input')) {
                            normalizeCampusTargetInput(e.target);
                            var row = e.target.closest('tr.campus-target-row'); if (row) updateRowTotal(row);
                            recalcCampusTargetSums();
                        }
                    });
                    document.addEventListener('focusin', function(e) {
                        if (!(e.target && e.target.classList && e.target.classList.contains('campus-target-input'))) return;
                        var v = String(e.target.value || '').trim();
                        if (v === '0' || v === '0.0' || v === '0.00') {
                            e.target.value = '';
                        }
                    });
                    document.addEventListener('DOMContentLoaded', function() {
                        var selectEl = document.getElementById('assigned_user_ids');
                        var tbody = document.getElementById('campus-targets-tbody');
                        if (!selectEl || !tbody) return;
                        function getSelectedCampuses() {
                            var selected = [];
                            jQuery(selectEl).find('option:selected').each(function() {
                                var code = jQuery(this).attr('data-campus-code') || '';
                                var name = jQuery(this).attr('data-campus-name') || jQuery(this).text() || '';
                                if (code) selected.push({ code: code, name: name });
                            });
                            var byCode = {}; selected.forEach(function(c) { if (c.code && !byCode[c.code]) byCode[c.code] = c; });
                            return Object.values(byCode);
                        }
                        function buildRowHtml(code, name, vals) {
                            vals = vals || {};
                            var q1 = vals.q1 != null ? vals.q1 : '', q2 = vals.q2 != null ? vals.q2 : '', q3 = vals.q3 != null ? vals.q3 : '', q4 = vals.q4 != null ? vals.q4 : '';
                            var tot = vals.total_target != null ? vals.total_target : '';
                            var safeCode = (code || '').replace(/"/g, '&quot;');
                            var safeName = (name || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                            return '<tr class="border-t border-gray-200 campus-target-row" data-campus-code="' + safeCode + '">' +
                                '<td class="px-3 py-1.5 border-r border-gray-200 text-xs text-gray-800">' + safeName + ' <span class="text-[10px] text-gray-500 ml-1">(' + safeCode + ')</span></td>' +
                                '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01" name="campus_targets[' + safeCode + '][q1]" value="' + q1 + '" class="w-20 text-right text-xs border-gray-300 rounded campus-target-input campus-target-q1"></td>' +
                                '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01" name="campus_targets[' + safeCode + '][q2]" value="' + q2 + '" class="w-20 text-right text-xs border-gray-300 rounded campus-target-input campus-target-q2"></td>' +
                                '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01" name="campus_targets[' + safeCode + '][q3]" value="' + q3 + '" class="w-20 text-right text-xs border-gray-300 rounded campus-target-input campus-target-q3"></td>' +
                                '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01" name="campus_targets[' + safeCode + '][q4]" value="' + q4 + '" class="w-20 text-right text-xs border-gray-300 rounded campus-target-input campus-target-q4"></td>' +
                                '<td class="px-3 py-1.5 text-right"><input type="number" min="0" step="0.01" name="campus_targets[' + safeCode + '][total_target]" value="' + tot + '" class="w-24 text-right text-xs border-gray-300 rounded campus-target-input campus-target-total"></td></tr>';
                        }
                        function rebuildCampusTargetTable() {
                            var selectedCampuses = getSelectedCampuses();
                            var existingByCode = {};
                            tbody.querySelectorAll('tr.campus-target-row').forEach(function(tr) {
                                var code = tr.getAttribute('data-campus-code');
                                if (code) {
                                    var v = { q1: '', q2: '', q3: '', q4: '', total_target: '' };
                                    ['q1','q2','q3','q4'].forEach(function(q) { var i = tr.querySelector('input.campus-target-' + q); if (i && i.value) v[q] = i.value; });
                                    var tot = tr.querySelector('input.campus-target-total'); if (tot && tot.value) v.total_target = tot.value;
                                    existingByCode[code] = { vals: v };
                                }
                            });
                            tbody.innerHTML = '';
                            selectedCampuses.forEach(function(c) {
                                var vals = existingByCode[c.code] ? existingByCode[c.code].vals : null;
                                tbody.insertAdjacentHTML('beforeend', buildRowHtml(c.code, c.name || c.code, vals));
                            });
                            if (selectedCampuses.length === 0) tbody.insertAdjacentHTML('beforeend', '<tr id="campus-targets-empty-row"><td colspan="6" class="px-3 py-4 text-center text-xs text-gray-500">No Planning Coordinator selected. Select one or more above—only their campuses will appear here.</td></tr>');
                            tbody.querySelectorAll('tr.campus-target-row').forEach(function(row) { updateRowTotal(row); });
                            recalcCampusTargetSums();
                        }
                        jQuery(selectEl).on('change', rebuildCampusTargetTable);
                        setTimeout(function() { rebuildCampusTargetTable(); }, 150);
                    });
                })();
                </script>

                <!-- Column Structure -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Data Table Columns
                        </h3>
                        <p class="text-xs text-gray-500 mb-2">Define the columns users will see and fill when creating submissions.</p>
                        <p class="text-xs text-gray-400 mb-4 italic">How it works: Each block below defines one column in the submission table. Users add rows of data. To add formulas or calculations, use the template View page (Field Structure) after creating the template.</p>
                        
                        <div id="fields-container">
                            <div class="space-y-4" id="fields-list">
                                <!-- Columns will be added here dynamically -->
                            </div>
                            
                            <button type="button" id="add-field" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Column
                            </button>
                        </div>

                        <input type="hidden" id="fields_json" name="fields_json" value="{{ old('fields_json', '{"fields":[]}') }}">
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="bg-white shadow-sm sm:rounded-lg p-5 mb-2">
                    <!-- Button legend / reminder -->
                    <div class="flex flex-col sm:flex-row gap-3 mb-5">
                        <div class="flex-1 flex items-start gap-3 p-3 rounded-lg border border-purple-200 bg-purple-50">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-purple-800 mb-0.5">Imitate Template</p>
                                <p class="text-xs text-purple-700 leading-relaxed">
                                    Use this when you selected a template from <strong>"Copy From Template"</strong> above.
                                    It copies the full field structure, formulas, campus targets, and assigned coordinators from the chosen source template into a <strong>new</strong> template record.
                                    The Template Code is automatically set to the next available code (e.g. T3 if T1 and T2 are taken).
                                </p>
                            </div>
                        </div>
                        <div class="flex-1 flex items-start gap-3 p-3 rounded-lg border border-blue-200 bg-blue-50">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-blue-800 mb-0.5">Create Template</p>
                                <p class="text-xs text-blue-700 leading-relaxed">
                                    Use this when you are building a <strong>brand-new template from scratch</strong> — filling in all the details manually above without copying from any existing template.
                                    The columns you defined in the Data Table section will be saved exactly as entered.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ $form ? route('forms.show', $form->id) : route('super-admin.templates.index') . '#forms' }}"
                           class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors text-sm font-medium">
                            Cancel
                        </a>
                        <button type="submit"
                                formaction="{{ route('super-admin.templates.imitate') }}"
                                class="inline-flex items-center gap-2 px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors text-sm font-medium"
                                onclick="document.getElementById('imitate_intent').value='imitate';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Imitate Template
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Template
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        @php
            $defaultCampusOptions = [
                ['name' => 'LINGAYEN'],
                ['name' => 'ASINGAN'],
                ['name' => 'BAYAMBANG'],
                ['name' => 'URDANETA CITY'],
                ['name' => 'SAN CARLOS CITY'],
                ['name' => 'ALAMINOS CITY'],
                ['name' => 'INFANTA'],
                ['name' => 'BINMALEY'],
                ['name' => 'STA. MARIA'],
            ];
        @endphp
        const campusOptionsForDefault = @json($defaultCampusOptions);
        function initKpiSelect2() {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
            var $el = jQuery('#kpi_title');
            if (!$el.length || !$el.is('select')) return;
            var $parent = jQuery('#create-template-form');
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            // Important for multi-monitor setups: render dropdown inside the form container
            // instead of positioning it globally under <body>.
            $el.select2({
                width: '100%',
                placeholder: 'Select ONE KPI Title',
                allowClear: true,
                dropdownParent: ($parent && $parent.length ? $parent : undefined)
            });
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Defensive: prevent any native <select> from staying "active/open" on multi-monitor.
            try {
                var active = document.activeElement;
                if (active && active.tagName && active.tagName.toUpperCase() === 'SELECT') {
                    active.blur();
                }
            } catch (e) {}

            const fieldsList = document.getElementById('fields-list');
            const addFieldBtn = document.getElementById('add-field');
            const fieldsJsonInput = document.getElementById('fields_json');
            let fieldCount = 0;

            // Campuses: All vs specific
            const campusCodesAll = document.getElementById('campus_codes_all');
            const campusCodeAllInput = document.getElementById('campus_code_all_input');
            const campusCheckboxes = document.querySelectorAll('.campus-code-cb');
            if (campusCodesAll && campusCodeAllInput) {
                function syncCampusAll() {
                    const allChecked = campusCodesAll.checked;
                    campusCodeAllInput.disabled = !allChecked;
                    if (allChecked) {
                        campusCheckboxes.forEach(function(cb) { cb.checked = false; cb.disabled = false; });
                    }
                }
                campusCodesAll.addEventListener('change', function() {
                    if (this.checked) {
                        campusCheckboxes.forEach(function(cb) { cb.checked = false; });
                    }
                    syncCampusAll();
                });
                campusCheckboxes.forEach(function(cb) {
                    cb.addEventListener('change', function() {
                        if (this.checked) {
                            campusCodesAll.checked = false;
                            campusCodeAllInput.disabled = true;
                        }
                    });
                });
                syncCampusAll();
            }
            
            // Handle Template Code dropdown change
            const templateCodeSelect = document.getElementById('template_code');
            const customTemplateCodeContainer = document.getElementById('custom_template_code_container');
            const customTemplateCodeInput = document.getElementById('custom_template_code');
            
            if (templateCodeSelect && customTemplateCodeContainer) {
                // Check initial value
                if (templateCodeSelect.value === 'Custom') {
                    customTemplateCodeContainer.style.display = 'block';
                    if (customTemplateCodeInput) {
                        customTemplateCodeInput.required = true;
                    }
                }
                
                templateCodeSelect.addEventListener('change', function() {
                    if (this.value === 'Custom') {
                        customTemplateCodeContainer.style.display = 'block';
                        if (customTemplateCodeInput) {
                            customTemplateCodeInput.required = true;
                        }
                    } else {
                        customTemplateCodeContainer.style.display = 'none';
                        if (customTemplateCodeInput) {
                            customTemplateCodeInput.required = false;
                            customTemplateCodeInput.value = '';
                        }
                    }
                });
            }

            addFieldBtn.addEventListener('click', function() {
                // When user manually adds a column, scroll to the new block.
                addField({}, { scrollIntoView: true });
            });

            function normalizeKey(label) {
                return (label || '')
                    .toLowerCase()
                    .trim()
                    .replace(/\s+/g, '_')
                    .replace(/[^a-z0-9_]/g, '');
            }

            function escapeHtmlAttr(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/'/g, '&#39;');
            }
            function buildDropdownOptionRowHtml(value) {
                return '<div class="flex gap-1.5 items-center field-dropdown-option-row">' +
                    '<input type="text" class="field-dropdown-option-input flex-1 min-w-0 w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter choice" value="' + escapeHtmlAttr(value) + '">' +
                    '<button type="button" class="field-dropdown-option-remove flex-shrink-0 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 text-lg leading-none font-bold" title="Remove row">×</button>' +
                    '</div>';
            }
            function getQuarterDropdownDefaults() {
                return ['1st Q', '2nd Q', '3rd Q', '4th Q'];
            }
            function getYesNoDropdownDefaults() {
                return ['YES', 'NO'];
            }
            function isQuarterLikeLabel(label) {
                return /quarter|quarters|qtr|qtrs/i.test(String(label || '').trim());
            }
            function isYesNoLikeLabel(label) {
                var normalized = String(label || '').trim().toLowerCase();
                return /\byes\b/.test(normalized) && /\bno\b/.test(normalized);
            }
            function getDefaultDropdownOptionsForLabel(label) {
                if (isQuarterLikeLabel(label)) return getQuarterDropdownDefaults();
                if (isYesNoLikeLabel(label)) return getYesNoDropdownDefaults();
                return null;
            }
            function buildDropdownOptionsListHtml(options) {
                var arr = Array.isArray(options) && options.length
                    ? options.map(function(o) { return String(o); })
                    : [''];
                return arr.map(function(o) { return buildDropdownOptionRowHtml(o); }).join('');
            }
            function buildSubheaderRowHtml(value) {
                return '<div class="flex gap-1.5 items-center field-subheader-row">' +
                    '<input type="text" class="field-subheader-input flex-1 min-w-0 w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-teal-500 focus:border-teal-500" placeholder="Sub-header text" value="' + escapeHtmlAttr(value) + '">' +
                    '<button type="button" class="field-subheader-remove flex-shrink-0 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 text-lg leading-none font-bold" title="Remove row">×</button>' +
                    '</div>';
            }
            function collectSubheadersFromElement(element) {
                var list = element.querySelector('.field-subheaders-list');
                if (!list) return [];
                var out = [];
                list.querySelectorAll('.field-subheader-input').forEach(function(inp) {
                    var v = (inp.value || '').trim();
                    if (v) out.push(v);
                });
                return out;
            }
            function ensureDropdownOptionsHasRow(optionsDiv) {
                if (!optionsDiv) return;
                var list = optionsDiv.querySelector('.field-dropdown-options-list');
                if (!list || list.querySelectorAll('.field-dropdown-option-row').length === 0) {
                    if (list) list.insertAdjacentHTML('beforeend', buildDropdownOptionRowHtml(''));
                }
            }
            function maybeApplyDropdownDefaults(fieldElement) {
                if (!fieldElement) return;
                var labelInput = fieldElement.querySelector('input[name*="field_label"]');
                var typeSelect = fieldElement.querySelector('select[name*="field_type"]');
                if (!labelInput || !typeSelect || typeSelect.value !== 'dropdown') return;
                var defaultOptions = getDefaultDropdownOptionsForLabel(labelInput.value);
                if (!defaultOptions) return;

                var optionsDiv = fieldElement.querySelector('[id^="options_"]');
                var list = optionsDiv ? optionsDiv.querySelector('.field-dropdown-options-list') : null;
                if (!list) return;

                var values = Array.from(list.querySelectorAll('.field-dropdown-option-input')).map(function(inp) {
                    return (inp.value || '').trim();
                }).filter(Boolean);

                if (values.length > 0) return; // Do not overwrite user's existing options

                list.innerHTML = '';
                defaultOptions.forEach(function(opt) {
                    list.insertAdjacentHTML('beforeend', buildDropdownOptionRowHtml(opt));
                });
                updateFieldsJson();
            }
            function collectDropdownOptionsFromElement(element) {
                var list = element.querySelector('.field-dropdown-options-list');
                if (!list) return [];
                var out = [];
                list.querySelectorAll('.field-dropdown-option-input').forEach(function(inp) {
                    var v = (inp.value || '').trim();
                    if (v) out.push(v);
                });
                return out;
            }

            if (fieldsList) {
                fieldsList.addEventListener('click', function(e) {
                    var subToggle = e.target.closest('.field-subheaders-toggle');
                    if (subToggle) {
                        e.preventDefault();
                        var wrap = subToggle.closest('.field-subheaders-wrap');
                        var panel = wrap && wrap.querySelector('.field-subheaders-panel');
                        if (!panel) return;
                        panel.classList.toggle('hidden');
                        if (!panel.classList.contains('hidden')) {
                            var slist = panel.querySelector('.field-subheaders-list');
                            if (slist && slist.querySelectorAll('.field-subheader-row').length === 0) {
                                slist.insertAdjacentHTML('beforeend', buildSubheaderRowHtml(''));
                            }
                        }
                        updateFieldsJson();
                        return;
                    }
                    var subAddRow = e.target.closest('.field-subheaders-add-row');
                    if (subAddRow) {
                        e.preventDefault();
                        var panel2 = subAddRow.closest('.field-subheaders-panel');
                        var slist2 = panel2 && panel2.querySelector('.field-subheaders-list');
                        if (slist2) {
                            slist2.insertAdjacentHTML('beforeend', buildSubheaderRowHtml(''));
                            updateFieldsJson();
                        }
                        return;
                    }
                    var subRem = e.target.closest('.field-subheader-remove');
                    if (subRem) {
                        e.preventDefault();
                        var row = subRem.closest('.field-subheader-row');
                        var list = row && row.parentElement;
                        if (!list || !row) return;
                        var rows = list.querySelectorAll('.field-subheader-row');
                        if (rows.length > 1) {
                            row.remove();
                        } else {
                            var inp = row.querySelector('.field-subheader-input');
                            if (inp) inp.value = '';
                        }
                        updateFieldsJson();
                        return;
                    }
                    var addBtn = e.target.closest('.field-dropdown-add-option');
                    if (addBtn) {
                        e.preventDefault();
                        var wrap = addBtn.closest('[id^="options_"]');
                        if (!wrap) return;
                        var list = wrap.querySelector('.field-dropdown-options-list');
                        if (list) {
                            list.insertAdjacentHTML('beforeend', buildDropdownOptionRowHtml(''));
                            updateFieldsJson();
                        }
                        return;
                    }
                    var remBtn = e.target.closest('.field-dropdown-option-remove');
                    if (remBtn) {
                        e.preventDefault();
                        var row = remBtn.closest('.field-dropdown-option-row');
                        var list = row && row.parentElement;
                        if (!list || !row) return;
                        var rows = list.querySelectorAll('.field-dropdown-option-row');
                        if (rows.length > 1) {
                            row.remove();
                        } else {
                            var inp = row.querySelector('.field-dropdown-option-input');
                            if (inp) inp.value = '';
                        }
                        updateFieldsJson();
                    }
                });
            }

            function addField(fieldData = {}, { scrollIntoView = false } = {}) {
                fieldCount++;
                const fieldId = `field_${fieldCount}`;
                const defaultDropdownOptions = fieldData.type === 'dropdown'
                    ? getDefaultDropdownOptionsForLabel(fieldData.label || '')
                    : null;
                const initialDropdownOptions = Array.isArray(fieldData.options) && fieldData.options.length
                    ? fieldData.options
                    : (defaultDropdownOptions && defaultDropdownOptions.length ? defaultDropdownOptions : ['']);
                const dropdownOptsHtml = buildDropdownOptionsListHtml(initialDropdownOptions);
                var subRaw = Array.isArray(fieldData.subheaders) ? fieldData.subheaders
                    : (Array.isArray(fieldData.sub_headers) ? fieldData.sub_headers : []);
                var initialSubs = subRaw.map(function(s) { return String(s); }).filter(function(s) { return s.trim() !== ''; });
                var subheadersListHtml = initialSubs.length ? initialSubs.map(function(s) { return buildSubheaderRowHtml(s); }).join('') : '';
                var subheadersPanelOpen = initialSubs.length > 0;
                
                const fieldHtml = `
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" data-field-id="${fieldId}">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="text-sm font-medium text-gray-900">Column ${fieldCount}</h4>
                            <button type="button" class="text-red-600 hover:text-red-800" onclick="removeField('${fieldId}')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Column Label</label>
                                <input type="text" name="field_label_${fieldCount}" value="${fieldData.label || ''}" 
                                       class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                       placeholder="e.g., Campus">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Data Type</label>
                                <select name="field_type_${fieldCount}" 
                                        class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 field-type-select">
                                    <option value="text" ${fieldData.type === 'text' ? 'selected' : ''}>Text</option>
                                    <option value="number" ${fieldData.type === 'number' ? 'selected' : ''}>Number</option>
                                    <option value="dropdown" ${fieldData.type === 'dropdown' ? 'selected' : ''}>Dropdown</option>
                                    <option value="textarea" ${fieldData.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                                    <option value="link" ${fieldData.type === 'link' ? 'selected' : ''}>Link</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Required?</label>
                                <select name="field_required_${fieldCount}" 
                                        class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="false" ${!fieldData.required ? 'selected' : ''}>No</option>
                                    <option value="true" ${fieldData.required ? 'selected' : ''}>Yes</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 field-subheaders-wrap">
                            <button type="button" class="field-subheaders-toggle inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-teal-800 bg-teal-50 border border-teal-200 rounded hover:bg-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-500">+ Add sub-headers</button>
                            <div class="field-subheaders-panel mt-2 border border-dashed border-gray-300 rounded-md p-3 bg-white ${subheadersPanelOpen ? '' : 'hidden'}">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Sub-header labels</label>
                                <div class="field-subheaders-list space-y-1.5 mb-2">${subheadersListHtml}</div>
                                <button type="button" class="field-subheaders-add-row inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">+ Add sub-header row</button>
                                <p class="mt-1 text-xs text-gray-500">Optional. Two or more sub-headers create separate columns under one grouped header (e.g. M / F under &quot;1st Year&quot;). One sub-header stays a single column with a subtitle.</p>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="options_${fieldCount}" style="display: ${fieldData.type === 'dropdown' ? 'block' : 'none'};">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Dropdown choices</label>
                            <div class="field-dropdown-options-list space-y-1.5 mb-2">${dropdownOptsHtml}</div>
                            <button type="button" class="field-dropdown-add-option inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">+ Add option</button>
                            <p class="mt-1 text-xs text-gray-500">One choice per row.</p>
                        </div>
                        
                        <div class="mt-3 calc-settings hidden" id="calc_settings_${fieldCount}" style="display: none !important;" aria-hidden="true">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <label class="block text-xs font-semibold text-gray-700 mb-2">Auto-Calculation</label>
                                <p class="text-xs text-gray-600 mb-3 italic">This column is computed from other columns. Values are calculated when users save or submit.</p>
                                
                                <div class="mb-2" id="calc_apply_all_${fieldCount}" style="display: none;">
                                    <p class="text-xs text-gray-600 italic">Apply to: This row only</p>
                                </div>

                                <div class="mb-2" id="calc_source_settings_${fieldCount}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Source Column</label>
                                    <select name="calc_source_a_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-source-a" data-selected="${fieldData.meta && fieldData.meta.sourceA ? fieldData.meta.sourceA : ''}">
                                        <option value="">-- Select Source Column --</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2" id="calc_unique_settings_${fieldCount}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Result Type</label>
                                    <input type="text" value="Count of unique values" readonly
                                           class="w-full text-xs border-gray-300 rounded bg-gray-100 text-gray-700">
                                </div>
                                
                                <div class="mb-2" id="calc_countif_settings_${fieldCount}" style="display: none;">
                                    <p class="text-xs text-gray-600">Counts all non-empty values in the source column (including unique and repeated). Result shows in the summary row below.</p>
                                </div>
                                
                                <div class="mb-2" id="calc_avg_settings_${fieldCount}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Apply to</label>
                                    <select name="calc_avg_scope_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="row" ${fieldData.meta && fieldData.meta.scope === 'row' ? 'selected' : ''}>This row only</option>
                                        <option value="all_rows" ${!fieldData.meta || !fieldData.meta.scope || fieldData.meta.scope === 'all_rows' ? 'selected' : ''}>All rows in the table</option>
                                    </select>
                                    <p class="text-xs text-gray-600 mt-1">Uses the source column. &quot;All rows&quot; computes the average across the whole table.</p>
                                </div>

                                <div class="mb-2" id="calc_formula_settings_${fieldCount}" style="display: none;">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Operation</label>
                                            <select name="calc_formula_operation_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="sum" ${fieldData.meta && fieldData.meta.operation === 'sum' ? 'selected' : ''}>Sum (A + B)</option>
                                                <option value="subtract" ${fieldData.meta && fieldData.meta.operation === 'subtract' ? 'selected' : ''}>Subtract (A - B)</option>
                                                <option value="multiply" ${fieldData.meta && fieldData.meta.operation === 'multiply' ? 'selected' : ''}>Multiply (A * B)</option>
                                                <option value="divide" ${fieldData.meta && fieldData.meta.operation === 'divide' ? 'selected' : ''}>Divide (A / B)</option>
                                                <option value="percent_of" ${fieldData.meta && fieldData.meta.operation === 'percent_of' ? 'selected' : ''}>Percent Of ((A / B) * 100)</option>
                                                <option value="sum_over_b_percent" ${fieldData.meta && fieldData.meta.operation === 'sum_over_b_percent' ? 'selected' : ''}>Percent Of ((A + B) / B) * 100</option>
                                                <option value="diff_over_b_percent" ${fieldData.meta && fieldData.meta.operation === 'diff_over_b_percent' ? 'selected' : ''}>Percent Of ((A - B) / B) * 100</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Apply to</label>
                                            <select name="calc_formula_scope_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="row" ${!fieldData.meta || !fieldData.meta.scope || fieldData.meta.scope === 'row' ? 'selected' : ''}>This row only</option>
                                                <option value="all_rows" ${fieldData.meta && fieldData.meta.scope === 'all_rows' ? 'selected' : ''}>All rows in the table</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Source Column (A)</label>
                                            <select name="calc_formula_source_a_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-a" data-selected="${fieldData.meta && fieldData.meta.sourceA ? fieldData.meta.sourceA : ''}">
                                                <option value="">-- Select Source Column --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Second Source (B)</label>
                                            <select name="calc_formula_source_b_${fieldCount}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-b" data-selected="${fieldData.meta && fieldData.meta.sourceB ? fieldData.meta.sourceB : ''}">
                                                <option value="">-- Select Second Source --</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                fieldsList.insertAdjacentHTML('beforeend', fieldHtml);
                const newBlock = fieldsList.querySelector(`[data-field-id="${fieldId}"]`);
                if (newBlock && scrollIntoView) newBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
                maybeApplyDropdownDefaults(newBlock);
                updateCalcSourceFields();
                updateFieldsJson();
            }

            addField({ label: 'Responsible Work Unit', type: 'text' });

            window.removeField = function(fieldId) {
                document.querySelector(`[data-field-id="${fieldId}"]`).remove();
                updateCalcSourceFields();
                updateFieldsJson();
            };

            function updateFieldsJson() {
                const fields = [];
                const fieldElements = fieldsList.querySelectorAll('[data-field-id]');
                
                fieldElements.forEach((element) => {
                    const label = element.querySelector(`input[name*="field_label"]`).value;
                    const typeSelect = element.querySelector(`select[name*="field_type"]`);
                    const type = typeSelect.value;
                    const required = element.querySelector(`select[name*="field_required"]`).value === 'true';
                    
                    // Determine base type and calc metadata
                    let baseType = type;
                    let meta = null;
                    
                    if (['text_unique', 'text_countif', 'number_sum', 'avg_percentage', 'formula_operation'].includes(type)) {
                        // Determine base type - sum should be stored as number type
                        baseType = ['number_sum', 'formula_operation'].includes(type) ? 'number' : 'text';
                        const applyAllRows = false;
                        
                        if (type === 'formula_operation') {
                            meta = {
                                calc: 'formula',
                                operation: element.querySelector(`select[name*="calc_formula_operation"]`)?.value || 'sum',
                                sourceA: element.querySelector(`select[name*="calc_formula_source_a"]`)?.value || '',
                                sourceB: element.querySelector(`select[name*="calc_formula_source_b"]`)?.value || '',
                                scope: element.querySelector(`select[name*="calc_formula_scope"]`)?.value || 'row'
                            };
                        } else {
                            meta = {
                                calc: type === 'text_unique' ? 'unique' : 
                                      type === 'text_countif' ? 'countif' : 
                                      type === 'number_sum' ? 'sum' : 'avg_percentage',
                                sourceA: element.querySelector(`select[name*="calc_source_a"]`)?.value || '',
                                scope: type === 'avg_percentage'
                                    ? (element.querySelector(`select[name*="calc_avg_scope"]`)?.value || 'all_rows')
                                    : 'row',
                                applyAllRows: false
                            };
                        }
                        
                        if (type === 'text_unique' || type === 'text_countif') {
                            meta.outputMode = 'count';
                        }
                        
                    }
                    
                    const field = {
                        key: normalizeKey(label),
                        label: label,
                        type: baseType,
                        required: required
                    };
                    
                    if (meta) {
                        field.meta = meta;
                    }
                    
                    if (baseType === 'dropdown') {
                        field.options = collectDropdownOptionsFromElement(element);
                    }

                    var subs = collectSubheadersFromElement(element);
                    if (subs.length > 0) {
                        field.subheaders = subs;
                    }
                    
                    fields.push(field);
                });

                fieldsJsonInput.value = JSON.stringify({
                    schema_version: 2,
                    fields: fields,
                    summary_rules: []
                });
            }
            
            // Function to update calculation settings visibility
            function updateCalcSourceFields() {
                const fieldElements = fieldsList.querySelectorAll('[data-field-id]');
                const availableSources = Array.from(fieldElements).map((fieldElement) => {
                    const label = fieldElement.querySelector(`input[name*="field_label"]`)?.value?.trim() || '';
                    const type = fieldElement.querySelector(`select[name*="field_type"]`)?.value || 'text';
                    if (!label) return null;
                    const isCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage', 'formula_operation'].includes(type);
                    return {
                        key: normalizeKey(label),
                        label,
                        type,
                        isCalculated
                    };
                }).filter(Boolean);

                fieldElements.forEach((element) => {
                    const typeSelect = element.querySelector(`select[name*="field_type"]`);
                    const calcSettings = element.querySelector('.calc-settings');
                    
                    if (!calcSettings) return;
                    
                    const fieldType = typeSelect.value;
                    const isCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage', 'formula_operation'].includes(fieldType);
                    
                    if (isCalculated) {
                        calcSettings.style.display = 'block';
                        
                        // Show/hide specific settings based on type
                        const fieldId = element.dataset.fieldId.replace('field_', '');
                        const nonFormulaCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage'].includes(fieldType);
                        document.getElementById(`calc_apply_all_${fieldId}`).style.display = ['text_unique', 'text_countif', 'number_sum'].includes(fieldType) ? 'block' : 'none';
                        document.getElementById(`calc_source_settings_${fieldId}`).style.display = nonFormulaCalculated ? 'block' : 'none';
                        document.getElementById(`calc_unique_settings_${fieldId}`).style.display = fieldType === 'text_unique' ? 'block' : 'none';
                        document.getElementById(`calc_countif_settings_${fieldId}`).style.display = fieldType === 'text_countif' ? 'block' : 'none';
                        document.getElementById(`calc_avg_settings_${fieldId}`).style.display = fieldType === 'avg_percentage' ? 'block' : 'none';
                        document.getElementById(`calc_formula_settings_${fieldId}`).style.display = fieldType === 'formula_operation' ? 'block' : 'none';
                        document.getElementById(`calc_apply_all_${fieldId}`).style.display = fieldType === 'formula_operation' ? 'none' : 'block';

                        if (nonFormulaCalculated) {
                            const currentLabel = element.querySelector(`input[name*="field_label"]`)?.value?.trim() || '';
                            const currentKey = normalizeKey(currentLabel);
                            const sourceASelect = element.querySelector('select[name*="calc_source_a"]');
                            if (sourceASelect) {
                                const prevValue = sourceASelect.value || sourceASelect.dataset.selected || '';
                                sourceASelect.innerHTML = '<option value="">-- Select Source A --</option>';
                                availableSources
                                    .forEach((src) => {
                                        const option = document.createElement('option');
                                        option.value = src.key;
                                        option.textContent = src.label;
                                        if (prevValue === src.key) {
                                            option.selected = true;
                                        }
                                        sourceASelect.appendChild(option);
                                    });
                            }
                        }

                        if (fieldType === 'formula_operation') {
                            const currentLabel = element.querySelector(`input[name*="field_label"]`)?.value?.trim() || '';
                            const currentKey = normalizeKey(currentLabel);
                            const sourceASelect = element.querySelector('select[name*="calc_formula_source_a"]');
                            const sourceBSelect = element.querySelector('select[name*="calc_formula_source_b"]');

                            [sourceASelect, sourceBSelect].forEach((sourceSelect) => {
                                if (!sourceSelect) return;
                                const prevValue = sourceSelect.value || sourceSelect.dataset.selected || '';
                                sourceSelect.innerHTML = '<option value="">-- Select Source --</option>';
                                availableSources
                                    .forEach((src) => {
                                        const option = document.createElement('option');
                                        option.value = src.key;
                                        option.textContent = src.label;
                                        if (prevValue === src.key) {
                                            option.selected = true;
                                        }
                                        sourceSelect.appendChild(option);
                                    });
                            });
                        }
                    } else {
                        calcSettings.style.display = 'none';
                    }
                });
            }

            // Form validation before submit
            window.validateForm = function() {
                updateFieldsJson();
                const fieldsJsonInput = document.getElementById('fields_json');
                const fieldsData = JSON.parse(fieldsJsonInput.value || '{"fields":[]}');
                
                if (!fieldsData.fields || fieldsData.fields.length === 0) {
                    window.showAlert({ title: 'Notice', message: 'Please add at least one column to the template structure.' });
                    document.getElementById('add-field').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return false;
                }

                const calculatedMissingSource = fieldsData.fields.find((field) => {
                    const calcType = field?.meta?.calc;
                    return ['unique', 'countif', 'sum', 'avg_percentage'].includes(calcType) && !field?.meta?.sourceA;
                });
                if (calculatedMissingSource) {
                    window.showAlert({ title: 'Notice', message: 'Please set Source A for calculated column "' + calculatedMissingSource.label + '".' });
                    return false;
                }

                // Validate KPI selection (single select)
                const kpiTitleInput = document.getElementById('kpi_title');
                if (kpiTitleInput) {
                    const kpiValue = kpiTitleInput.value.trim();
                    if (!kpiValue || kpiValue === '') {
                        window.showAlert({ title: 'Notice', message: 'Please select exactly one KPI title.' });
                        kpiTitleInput.focus();
                        return false;
                    }
                }
                
                // Validate Template Code selection
                const templateCodeInput = document.getElementById('template_code');
                if (templateCodeInput) {
                    const templateCode = templateCodeInput.value.trim();
                    if (!templateCode || templateCode === '') {
                        window.showAlert({ title: 'Notice', message: 'Please select a template code.' });
                        templateCodeInput.focus();
                        return false;
                    }
                    
                    // If Custom is selected, validate custom template code
                    if (templateCode === 'Custom') {
                        const customTemplateCodeInput = document.getElementById('custom_template_code');
                        if (customTemplateCodeInput) {
                            const customCode = customTemplateCodeInput.value.trim();
                            if (!customCode || customCode === '') {
                                window.showAlert({ title: 'Notice', message: 'Please enter a custom template code.' });
                                customTemplateCodeInput.focus();
                                return false;
                            }
                        }
                    }
                }
                
                return true;
            };

            // Skip column-structure validation when the user clicks "Imitate Template".
            window.validateFormWithIntent = function() {
                const intentEl = document.getElementById('imitate_intent');
                const intent = intentEl ? String(intentEl.value || 'create') : 'create';
                if (intent === 'imitate') return true;
                if (typeof window.validateForm === 'function') return window.validateForm();
                return true;
            };

            document.addEventListener('change', function(e) {
                if (e.target.name && e.target.name.includes('field_')) {
                    if (e.target.name.includes('field_type')) {
                        const fieldElement = e.target.closest('[data-field-id]');
                        const fieldId = fieldElement.dataset.fieldId.replace('field_', '');
                        const fieldType = e.target.value;
                        
                        // Show/hide dropdown options for this specific field
                        const optionsDiv = document.getElementById(`options_${fieldId}`);
                        if (optionsDiv) {
                            optionsDiv.style.display = fieldType === 'dropdown' ? 'block' : 'none';
                            if (fieldType === 'dropdown') {
                                ensureDropdownOptionsHasRow(optionsDiv);
                                maybeApplyDropdownDefaults(fieldElement);
                            }
                        }
                        
                        // Show/hide calculation settings for this specific field immediately
                        const calcSettingsDiv = document.getElementById(`calc_settings_${fieldId}`);
                        const isCalculated = ['text_unique', 'number_sum', 'text_countif', 'avg_percentage', 'formula_operation'].includes(fieldType);
                        
                        if (calcSettingsDiv) {
                            calcSettingsDiv.style.display = isCalculated ? 'block' : 'none';
                            
                            if (isCalculated) {
                                // Show/hide type-specific settings for this field
                                const nonFormulaCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage'].includes(fieldType);
                                document.getElementById(`calc_apply_all_${fieldId}`).style.display = ['text_unique', 'text_countif', 'number_sum'].includes(fieldType) ? 'block' : 'none';
                                document.getElementById(`calc_source_settings_${fieldId}`).style.display = nonFormulaCalculated ? 'block' : 'none';
                                document.getElementById(`calc_unique_settings_${fieldId}`).style.display = fieldType === 'text_unique' ? 'block' : 'none';
                                document.getElementById(`calc_countif_settings_${fieldId}`).style.display = fieldType === 'text_countif' ? 'block' : 'none';
                                document.getElementById(`calc_avg_settings_${fieldId}`).style.display = fieldType === 'avg_percentage' ? 'block' : 'none';
                                document.getElementById(`calc_formula_settings_${fieldId}`).style.display = fieldType === 'formula_operation' ? 'block' : 'none';
                                if (fieldType === 'formula_operation') {
                                    document.getElementById(`calc_apply_all_${fieldId}`).style.display = 'none';
                                }
                            }
                        }
                        
                        // Then update all source field dropdowns
                        updateCalcSourceFields();
                    }
                    updateFieldsJson();
                }
                
                // Handle calculation settings changes
                if (e.target.name && (e.target.name.includes('calc_') || e.target.classList.contains('field-type-select'))) {
                    updateCalcSourceFields();
                    updateFieldsJson();
                }
            });

            document.addEventListener('input', function(e) {
                if (e.target.classList && e.target.classList.contains('field-dropdown-option-input')) {
                    updateFieldsJson();
                    return;
                }
                if (e.target.classList && e.target.classList.contains('field-subheader-input')) {
                    updateFieldsJson();
                    return;
                }
                if (e.target.name && e.target.name.includes('field_')) {
                    if (e.target.name.includes('field_label')) {
                        updateCalcSourceFields();
                        const fieldElement = e.target.closest('[data-field-id]');
                        maybeApplyDropdownDefaults(fieldElement);
                    }
                    updateFieldsJson();
                }
            });
            
            // Initialize calculation settings on page load
            setTimeout(updateCalcSourceFields, 100);

            // KRA-KPI nested data structure (direct access, no filtering)
            @if($form)
            const kraKpiData = @json($kraKpiData ?? []);
            
            // DEBUG: Log structure to verify it's correct
            console.log('=== KRA-KPI Data Structure ===');
            console.log('Form exists:', !!@json($form ? true : false));
            console.log('kraKpiData exists:', !!kraKpiData);
            console.log('kraKpiData type:', typeof kraKpiData);
            console.log('kraKpiData is array:', Array.isArray(kraKpiData));
            console.log('Total KRAs:', kraKpiData?.length || 0);
            
            if (kraKpiData && Array.isArray(kraKpiData)) {
                kraKpiData.forEach((kra, idx) => {
                    console.log(`KRA ${idx}: "${(kra.kra_title || '').trim()}" has ${(kra.kpis || []).length} KPIs`);
                });
            } else {
                console.error('kraKpiData is not a valid array:', kraKpiData);
            }
            
            const kraTitleSelect = document.getElementById('kra_title');
            const kpiTitleSelect = document.getElementById('kpi_title');
            const kpiCountSpan = document.getElementById('kpi-count');
            
            console.log('Elements found:', {
                kraTitleSelect: !!kraTitleSelect,
                kpiTitleSelect: !!kpiTitleSelect,
                kpiCountSpan: !!kpiCountSpan
            });
            
            if (kraTitleSelect && kpiTitleSelect) {
                function updateKpiOptions() {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#kpi_title').hasClass('select2-hidden-accessible')) {
                        jQuery('#kpi_title').select2('destroy');
                    }
                    const selectedKraValue = kraTitleSelect.value ? kraTitleSelect.value.trim() : '';
                    
                    console.log('=== updateKpiOptions called ===');
                    console.log('Selected KRA value:', selectedKraValue);
                    
                    // IMMEDIATELY clear KPI dropdown
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    kpiTitleSelect.disabled = !selectedKraValue;
                    
                    if (!selectedKraValue) {
                        if (kpiCountSpan) kpiCountSpan.textContent = '0';
                        return;
                    }
                    
                    // Validate kraKpiData exists and is an array
                    if (!kraKpiData || !Array.isArray(kraKpiData) || kraKpiData.length === 0) {
                        console.error('kraKpiData is invalid or empty');
                        kpiTitleSelect.innerHTML = '<option value="">No KRA-KPI data available</option>';
                        if (kpiCountSpan) kpiCountSpan.textContent = '0';
                        return;
                    }
                    
                    // Find selected KRA in nested structure - EXACT MATCH ONLY
                    let selectedKra = null;
                    console.log('Searching for KRA:', selectedKraValue);
                    
                    for (let i = 0; i < kraKpiData.length; i++) {
                        const kra = kraKpiData[i];
                        const kraTitle = (kra.kra_title || '').trim();
                        const matches = kraTitle === selectedKraValue;
                        console.log(`  Comparing KRA ${i}: "${kraTitle}" === "${selectedKraValue}" = ${matches}`);
                        if (matches) {
                            selectedKra = kra;
                            console.log('  ✓ Found matching KRA with', (kra.kpis || []).length, 'KPIs');
                            break; // Found exact match, stop searching
                        }
                    }
                    
                    // STRICT: Only proceed if we found the exact KRA match
                    if (!selectedKra) {
                        console.error('✗ KRA not found:', selectedKraValue);
                        console.log('Available KRAs:', kraKpiData.map(k => (k.kra_title || '').trim()));
                        kpiTitleSelect.innerHTML = '<option value="">KRA not found in form data</option>';
                        if (kpiCountSpan) kpiCountSpan.textContent = '0';
                        return;
                    }
                    
                    // Get KPIs directly from selected KRA ONLY - verify it's the correct KRA
                    const availableKpis = Array.isArray(selectedKra.kpis) ? selectedKra.kpis : [];
                    
                    // DOUBLE CHECK: Verify the selectedKra actually matches
                    const verifiedKraTitle = (selectedKra.kra_title || '').trim();
                    if (verifiedKraTitle !== selectedKraValue) {
                        console.error('KRA MISMATCH! Selected:', selectedKraValue, 'Found:', verifiedKraTitle);
                        kpiTitleSelect.innerHTML = '<option value="">Error: KRA mismatch</option>';
                        if (kpiCountSpan) kpiCountSpan.textContent = '0';
                        return;
                    }
                    
                    console.log('✓ Verified KRA match. Adding', availableKpis.length, 'KPIs');
                    
                    // STRICT: Clear dropdown again before populating
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    
                    // Populate KPI dropdown with KPIs from selected KRA ONLY
                    let addedCount = 0;
                    if (availableKpis.length > 0) {
                        availableKpis.forEach((kpi, idx) => {
                            const kpiNumber = (kpi.number || '').trim();
                            const kpiTitle = (kpi.title || '').trim();
                            const kpiFull = kpiNumber ? `${kpiNumber} - ${kpiTitle}` : kpiTitle;
                            
                            // Only add if we have valid data
                            if (kpiTitle) {
                                const option = document.createElement('option');
                                option.value = kpiFull;
                                option.textContent = kpiFull;
                                // Store level info as data attribute for reference
                                if (kpi.level) {
                                    option.setAttribute('data-level', Array.isArray(kpi.level) ? kpi.level.join(',') : kpi.level);
                                }
                                kpiTitleSelect.appendChild(option);
                                addedCount++;
                                console.log(`  Added KPI ${idx + 1}: "${kpiFull}"`);
                            }
                        });
                    }
                    
                    console.log(`✓ Total KPIs added to dropdown: ${addedCount}`);
                    
                    // Update count
                    if (kpiCountSpan) {
                        kpiCountSpan.textContent = addedCount;
                    }
                    
                    // Show message if no KPIs
                    if (addedCount === 0) {
                        const noKpiOption = document.createElement('option');
                        noKpiOption.value = '';
                        noKpiOption.textContent = 'No KPIs available for this KRA';
                        noKpiOption.disabled = true;
                        kpiTitleSelect.appendChild(noKpiOption);
                    }
                    
                    kpiTitleSelect.disabled = false;
                    initKpiSelect2();
                }
                
                // Clear KPI when KRA changes - CRITICAL: Clear immediately
                kraTitleSelect.addEventListener('change', function() {
                    console.log('KRA changed to:', this.value);
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#kpi_title').hasClass('select2-hidden-accessible')) {
                        jQuery('#kpi_title').select2('destroy');
                    }
                    // IMMEDIATELY clear the KPI dropdown
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    kpiTitleSelect.value = '';
                    kpiTitleSelect.disabled = true;
                    if (kpiCountSpan) kpiCountSpan.textContent = '0';
                    
                    // Then update with filtered KPIs
                    updateKpiOptions();
                });
                
                // Initial load - ensure we filter on page load
                console.log('Setting up initial load...');
                if (kraTitleSelect.value) {
                    console.log('KRA already selected on load:', kraTitleSelect.value);
                    // Clear first, then populate
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    updateKpiOptions();
                } else {
                    console.log('No KRA selected on load');
                    // No KRA selected, ensure dropdown is empty
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    kpiTitleSelect.disabled = true;
                    if (kpiCountSpan) kpiCountSpan.textContent = '0';
                }
                
                // Validation before submit
                const form = document.getElementById('create-template-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const selectedKraTitle = kraTitleSelect.value ? kraTitleSelect.value.trim() : '';
                        const selectedKpi = kpiTitleSelect.value;
                        
                        if (selectedKraTitle && selectedKpi) {
                            const selectedKra = kraKpiData.find(kra => {
                                const kraTitle = (kra.kra_title || '').trim();
                                return kraTitle === selectedKraTitle;
                            });
                            
                            const availableKpis = selectedKra?.kpis || [];
                            const kpiExists = availableKpis.some(kpi => {
                                const kpiNumber = kpi.number || '';
                                const kpiTitle = kpi.title || '';
                                const kpiFull = kpiNumber ? `${kpiNumber} - ${kpiTitle}` : kpiTitle;
                                return kpiFull === selectedKpi;
                            });
                            
                            if (!kpiExists) {
                                e.preventDefault();
                                window.showAlert({ title: 'Notice', message: 'Selected KPI does not belong to the selected KRA. Please select a KPI that matches the selected KRA.' });
                                kpiTitleSelect.focus();
                                return false;
                            }
                        }
                    });
                }
            }
            @endif

            // KPI Title preview: full multi-line display (same format as Create/Edit Form)
            const kpiTitleEl = document.getElementById('kpi_title');
            const kpiPreviewEl = document.getElementById('kpi-title-preview');
            if (kpiTitleEl && kpiPreviewEl) {
                function syncKpiPreview() {
                    const val = kpiTitleEl.value ? kpiTitleEl.value.trim() : '';
                    kpiPreviewEl.textContent = val || '';
                    kpiPreviewEl.classList.toggle('hidden', !val);
                }
                kpiTitleEl.addEventListener('change', syncKpiPreview);
                kpiTitleEl.addEventListener('input', syncKpiPreview);
                syncKpiPreview();
            }
            if (document.getElementById('kpi_title') && document.getElementById('kpi_title').tagName === 'SELECT') initKpiSelect2();
            // Convert long native "Imitate From Template" dropdown into Select2 with capped height.
            if (document.getElementById('source_template_id')) {
                var $parent = jQuery('#create-template-form');
                jQuery('#source_template_id').select2({
                    width: '100%',
                    dropdownParent: ($parent && $parent.length ? $parent : undefined),
                    // Keep the UI compact; user can scroll within the capped dropdown height.
                    dropdownAutoWidth: true,
                    allowClear: true,
                    placeholder: '(Optional) Choose a template to copy',
                });
            }
            if (document.getElementById('assigned_user_ids')) {
                var $parent = jQuery('#create-template-form');
                var assignedSelect = jQuery('#assigned_user_ids');
                assignedSelect.select2({
                    width: '100%',
                    placeholder: 'Select Planning Coordinator(s)',
                    allowClear: true,
                    closeOnSelect: false,
                    dropdownParent: ($parent && $parent.length ? $parent : undefined)
                });

                var btnAll = document.getElementById('assigned-user-select-all');
                var btnClear = document.getElementById('assigned-user-clear-all');
                if (btnAll) {
                    btnAll.addEventListener('click', function() {
                        var el = document.getElementById('assigned_user_ids');
                        if (!el) return;
                        Array.prototype.forEach.call(el.options, function(opt) { opt.selected = true; });
                        assignedSelect.trigger('change');
                    });
                }
                if (btnClear) {
                    btnClear.addEventListener('click', function() {
                        var el = document.getElementById('assigned_user_ids');
                        if (!el) return;
                        Array.prototype.forEach.call(el.options, function(opt) { opt.selected = false; });
                        assignedSelect.trigger('change');
                    });
                }
            }

            // ── Auto-fill everything when user picks a source template to copy ──────────
            var sourceTemplateSelect = document.getElementById('source_template_id');
            if (sourceTemplateSelect) {
                jQuery(sourceTemplateSelect).on('change', function() {
                    var srcId = parseInt(this.value, 10);
                    var noticeEl = document.getElementById('copy-template-notice');
                    if (!srcId) {
                        if (noticeEl) { noticeEl.textContent = ''; noticeEl.classList.add('hidden'); }
                        return;
                    }
                    var src = (typeof SOURCE_TEMPLATES_DATA !== 'undefined' ? SOURCE_TEMPLATES_DATA : [])
                        .find(function(t) { return t.id === srcId; });
                    if (!src) return;

                    // 1. Template Code: select first available (first non-empty, non-Custom option)
                    var templateCodeSelect = document.getElementById('template_code');
                    if (templateCodeSelect) {
                        var firstAvail = Array.from(templateCodeSelect.options)
                            .find(function(opt) { return opt.value && opt.value !== '' && opt.value !== 'Custom'; });
                        if (firstAvail) {
                            templateCodeSelect.value = firstAvail.value;
                            // Hide custom input if shown
                            var customContainer = document.getElementById('custom_template_code_container');
                            if (customContainer) customContainer.style.display = 'none';
                        }
                    }

                    // 2. SG Code
                    var sgSelect = document.getElementById('sg_code');
                    if (sgSelect && src.sg_code) {
                        sgSelect.value = src.sg_code;
                        sgSelect.dispatchEvent(new Event('change'));
                    }

                    // 3. KRA Title
                    var kraSelect = document.getElementById('kra_title');
                    if (kraSelect && src.kra_title) {
                        // Try direct value set first (dropdown mode)
                        kraSelect.value = src.kra_title;
                        kraSelect.dispatchEvent(new Event('change'));
                        // Also handle plain text input
                        if (kraSelect.tagName === 'INPUT') {
                            kraSelect.value = src.kra_title;
                            kraSelect.dispatchEvent(new Event('input'));
                        }
                    }

                    // 4. KPI Title (after a short delay so KPI options rebuild from KRA change)
                    if (src.kpi_title) {
                        setTimeout(function() {
                            var kpiEl = document.getElementById('kpi_title');
                            if (!kpiEl) return;
                            if (kpiEl.tagName === 'SELECT') {
                                // Try to find matching option
                                var matched = Array.from(kpiEl.options).find(function(o) {
                                    return o.value.trim() === src.kpi_title.trim();
                                });
                                if (matched) {
                                    kpiEl.value = matched.value;
                                } else {
                                    // Option not yet rendered — add it temporarily
                                    var opt = document.createElement('option');
                                    opt.value = src.kpi_title;
                                    opt.textContent = src.kpi_title;
                                    opt.setAttribute('data-temp', '1');
                                    kpiEl.appendChild(opt);
                                    kpiEl.value = src.kpi_title;
                                }
                                kpiEl.dispatchEvent(new Event('change'));
                                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                                    jQuery('#kpi_title').trigger('change.select2');
                                }
                            } else {
                                kpiEl.value = src.kpi_title;
                                kpiEl.dispatchEvent(new Event('input'));
                            }
                        }, 350);
                    }

                    // 5. Assigned Planning Coordinators
                    if (Array.isArray(src.assigned_user_ids) && src.assigned_user_ids.length > 0) {
                        var assignedSelect = document.getElementById('assigned_user_ids');
                        if (assignedSelect) {
                            var strIds = src.assigned_user_ids.map(String);
                            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 &&
                                jQuery('#assigned_user_ids').hasClass('select2-hidden-accessible')) {
                                jQuery('#assigned_user_ids').val(strIds).trigger('change');
                            } else {
                                Array.from(assignedSelect.options).forEach(function(opt) {
                                    opt.selected = strIds.includes(String(opt.value));
                                });
                                assignedSelect.dispatchEvent(new Event('change'));
                            }
                        }
                    }

                    // 6. Campus Targets — fill values after assigned-user rows are created
                    if (src.campus_targets && typeof src.campus_targets === 'object') {
                        setTimeout(function() {
                            Object.entries(src.campus_targets).forEach(function(entry) {
                                var campusCode = entry[0];
                                var vals = entry[1];
                                var row = document.querySelector(
                                    '#campus-targets-tbody tr.campus-target-row[data-campus-code="' + campusCode + '"]'
                                );
                                if (!row) return;
                                ['q1','q2','q3','q4'].forEach(function(q) {
                                    var inp = row.querySelector('input.campus-target-' + q);
                                    if (inp && vals[q] != null && vals[q] !== '') inp.value = vals[q];
                                });
                                var totInp = row.querySelector('input.campus-target-total');
                                if (totInp && vals.total_target != null && vals.total_target !== '') totInp.value = vals.total_target;
                            });
                            // Trigger recalculation of column sums
                            document.querySelectorAll('.campus-target-input').forEach(function(inp) {
                                inp.dispatchEvent(new Event('input', { bubbles: true }));
                            });
                        }, 700);
                    }

                    // 7. Data Table Columns + Field Structure from fields_json
                    if (src.fields_json && Array.isArray(src.fields_json.fields) && src.fields_json.fields.length > 0) {
                        // Clear existing columns
                        if (typeof fieldsList !== 'undefined' && fieldsList) {
                            fieldsList.innerHTML = '';
                        }
                        fieldCount = 0;
                        src.fields_json.fields.forEach(function(f) {
                            addField({
                                label: f.label || '',
                                type: f.type || 'text',
                                required: !!f.required,
                                options: Array.isArray(f.options) ? f.options : [],
                                meta: f.meta || null,
                                subheaders: Array.isArray(f.subheaders) ? f.subheaders : (Array.isArray(f.sub_headers) ? f.sub_headers : [])
                            });
                        });
                        if (typeof updateFieldsJson === 'function') updateFieldsJson();
                    }

                    // Notice badge
                    if (noticeEl) {
                        var nextCode = (templateCodeSelect && templateCodeSelect.value) ? templateCodeSelect.value : '';
                        noticeEl.textContent = 'Copied from ' + (src.template_code || 'template') +
                            (nextCode ? ' — new template code will be ' + nextCode : '') + '.';
                        noticeEl.classList.remove('hidden');
                    }
                });

                // Auto-trigger copy fill when arriving via the "Copy" button (copy_from in URL)
                if (typeof COPY_FROM_ID !== 'undefined' && COPY_FROM_ID > 0) {
                    var srcSelectEl = document.getElementById('source_template_id');
                    if (srcSelectEl && srcSelectEl.value == COPY_FROM_ID) {
                        // Use a short delay so Select2 is initialized and KPI options can load
                        setTimeout(function() {
                            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 &&
                                jQuery('#source_template_id').hasClass('select2-hidden-accessible')) {
                                jQuery('#source_template_id').trigger('change');
                            } else {
                                srcSelectEl.dispatchEvent(new Event('change'));
                            }
                        }, 200);
                    }
                }
            }
        });
    </script>
</x-app-layout>

