<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>.select2-container--default .select2-results__option { white-space: normal !important; word-wrap: break-word; }</style>

    <div class="pt-2 pb-4">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            Edit Template
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Configure template details and data columns
                        </p>
                    </div>
                    <div>
                        <a href="{{ $template->form ? route('forms.show', $template->form->id) : route('super-admin.templates.index') . '?tab=forms' }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            {{ $template->form ? 'Back to Form' : 'Back to Templates' }}
                        </a>
                    </div>
                </div>
            </div>
            <form action="{{ route('super-admin.templates.update', $template) }}" method="POST" id="edit-template-form">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                        <div class="font-semibold mb-1">Update failed</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- session success/error: layouts.flash-popup toast --}}
                
                <!-- Template Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Template Details</h3>
                        
                        <input type="hidden" name="campus_code" value="{{ old('campus_code', $template->campus_code ?? 'ALL') }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" name="status" required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="Unpublished" {{ old('status', $template->status) == 'Unpublished' ? 'selected' : '' }}>Unpublished</option>
                                    <option value="Published" {{ old('status', $template->status) == 'Published' ? 'selected' : '' }}>Published</option>
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
                                        <option value="{{ $code }}" {{ old('sg_code', $template->sg_code) == $code ? 'selected' : '' }}>
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
                                <input type="text" id="template_code" name="template_code" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       value="{{ old('template_code', $template->template_code) }}">
                                @error('template_code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="kra_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    KRA Title <span class="text-red-500">*</span>
                                    @if($template->form)
                                        <span class="text-xs text-gray-500 font-normal">(from Form - Select ONE KRA)</span>
                                    @endif
                                </label>
                                @php
                                    $currentKraTitle = old('kra_title', $template->kra_title);
                                @endphp
                                @if($template->form && !empty($parsedKras))
                                    <!-- Single-Select Dropdown for KRA from Form -->
                                    @php
                                        // Use form's SG code for numbering
                                        $formSgCode = $template->form->sg_code ?? $template->sg_code ?? old('sg_code', 'SG1');
                                        $sgNumber = str_replace('SG', '', $formSgCode);
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
                                            <option value="{{ $kraTitle }}" {{ $currentKraTitle == $kraTitle ? 'selected' : '' }}>
                                                {{ $kraNumber }} - {{ $kraTitle }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KRA title from the form. Available: {{ count($parsedKras) }} KRA(s).</p>
                                @else
                                    <!-- Fallback: Show KRAs from existing templates -->
                                    @php
                                        // Use template's SG code for numbering
                                        $sgCode = $template->sg_code ?? old('sg_code', 'SG1');
                                        $sgNumber = str_replace('SG', '', $sgCode);
                                        if (empty($sgNumber) || !is_numeric($sgNumber)) {
                                            $sgNumber = '1';
                                        }
                                        $kraTitlesArray = $kraTitles ?? [];
                                        $kraTitleExists = in_array($currentKraTitle, $kraTitlesArray);
                                    @endphp
                                    <select id="kra_title" name="kra_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">-- Select KRA Title --</option>
                                        @if(isset($kraTitles) && count($kraTitles) > 0)
                                            @foreach($kraTitles as $index => $kraTitle)
                                                @php
                                                    $kraNumber = $sgNumber . '.' . ($index + 1);
                                                @endphp
                                                <option value="{{ $kraTitle }}" {{ $currentKraTitle == $kraTitle ? 'selected' : '' }}>
                                                    {{ $kraNumber }} - {{ $kraTitle }}
                                                </option>
                                            @endforeach
                                        @endif
                                        @if($currentKraTitle && !$kraTitleExists)
                                            <option value="{{ $currentKraTitle }}" selected>
                                                {{ $currentKraTitle }} (Current)
                                            </option>
                                        @endif
                                    </select>
                                    @if($template->form)
                                        <p class="mt-1 text-xs text-gray-500">No KRAs found in the associated form. Showing KRAs from existing templates.</p>
                                    @endif
                                @endif
                                @error('kra_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="kpi_title" class="block text-sm font-medium text-gray-700 mb-2">
                                    KPI Title <span class="text-red-500">*</span>
                                    @if($template->form)
                                        <span class="text-xs text-gray-500 font-normal">(from Form - Select ONE KPI)</span>
                                    @endif
                                </label>
                                @php
                                    $currentKpiTitle = old('kpi_title', $template->kpi_title);
                                    $currentKraTitle = old('kra_title', $template->kra_title);
                                @endphp
                                @if($template->form && !empty($kraKpiData))
                                    <!-- Single-Select Dropdown for KPI - populated dynamically from selected KRA -->
                                    <select id="kpi_title" name="kpi_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            {{ empty($currentKraTitle) ? 'disabled' : '' }}>
                                        <option value="">Select ONE KPI Title</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Select exactly one KPI title from the form. Available: <span id="kpi-count">0</span> KPI(s).</p>
                                @else
                                    <!-- Fallback: Show KPIs from existing templates -->
                                    <select id="kpi_title" name="kpi_title" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">-- Select KPI Title --</option>
                                        @php
                                            $kpiTitlesArray = $kpiTitles ?? [];
                                            $kpiTitleExists = in_array($currentKpiTitle, $kpiTitlesArray);
                                        @endphp
                                        @if(isset($kpiTitles) && count($kpiTitles) > 0)
                                            @foreach($kpiTitles as $kpiTitle)
                                                <option value="{{ $kpiTitle }}" {{ $currentKpiTitle == $kpiTitle ? 'selected' : '' }}>
                                                    {{ $kpiTitle }}
                                                </option>
                                            @endforeach
                                        @endif
                                        @if($currentKpiTitle && !$kpiTitleExists)
                                            <option value="{{ $currentKpiTitle }}" selected>
                                                {{ $currentKpiTitle }} (Current)
                                            </option>
                                        @endif
                                    </select>
                                    @if($template->form)
                                        <p class="mt-1 text-xs text-gray-500">No KPIs found in the associated form. Showing KPIs from existing templates.</p>
                                    @endif
                                @endif
                                {{-- KPI Title preview: full multi-line display like Create/Edit Form --}}
                                <div id="kpi-title-preview" class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50 text-sm text-gray-900 whitespace-pre-wrap break-words min-h-[60px] {{ $currentKpiTitle ? '' : 'hidden' }}">
                                    @if($currentKpiTitle)
                                        {{ $currentKpiTitle }}
                                    @endif
                                </div>
                                @error('kpi_title')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Assigned Planning Coordinators: selection drives Campus Targets rows (one row per assigned user's campus) --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Assign Planning Coordinator(s)</h3>
                        <p class="text-xs text-gray-500 mb-3">
                            Select the users who will encode data for this template. Each selected user's assigned campus will get one row in the Campus Targets table below. Targets you set there will be reflected for that Planning Coordinator when they encode.
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
                                data-planning-coordinators="{{ json_encode($planningCoordinatorsWithCampus) }}">
                            @php
                                $byCampusCode = $planningCoordinators->groupBy(function ($u) {
                                    $c = trim((string) ($u->campus_code ?? ''));
                                    return $c !== '' ? $c : '_none';
                                })->map->count();
                            @endphp
                            @foreach($planningCoordinators as $pc)
                                @php
                                    $campusName = optional($pc->campusInfo)->name ?? $pc->campus ?? $pc->campus_code ?? '—';
                                    $campusCode = $pc->campus_code ?? '';
                                    $ccKey = $campusCode !== '' ? $campusCode : '_none';
                                    $sameCampusTwice = $campusCode !== '' && ($byCampusCode[$ccKey] ?? 0) > 1;
                                    if ($campusCode !== '') {
                                        $optionLabel = $sameCampusTwice
                                            ? $campusName.' ('.$campusCode.') · '.$pc->name
                                            : $campusName.' ('.$campusCode.')';
                                    } else {
                                        $optionLabel = $pc->name.' — no campus assigned';
                                    }
                                @endphp
                                <option value="{{ $pc->id }}"
                                        title="{{ e($pc->name) }}"
                                        {{ ($template->assignedUsers->contains('id', $pc->id) || old('assigned_user_ids') && in_array($pc->id, old('assigned_user_ids'))) ? 'selected' : '' }}
                                        data-campus-code="{{ $campusCode }}"
                                        data-campus-name="{{ e($campusName) }}">
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select one or more. Campus Targets table below will show one row per selected user's campus.</p>
                    </div>
                </div>

                {{-- Campus-level targets for this KPI/template (optional): rows = assigned users' campuses --}}
                @if(isset($campusesForTargets))
                    @php
                        $existingCampusTargets = $template->fields_json['campus_targets'] ?? [];
                        $overall = $overallTargets ?? ['q1' => 0, 'q2' => 0, 'q3' => 0, 'q4' => 0, 'total' => 0, 'is_percentage' => false];
                        $isPercentage = !empty($overall['is_percentage'] ?? false);
                        $targetSuffix = $isPercentage ? '%' : '';
                    @endphp
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">Campus Targets (Per Quarter & Total)</h3>
                            <p class="text-xs text-gray-500 mb-3">
                                Optional: Set the quarterly and total target for each assigned campus. Rows appear only for Planning Coordinators selected above—if a coordinator is not selected, their campus does not appear here. The gray header row shows the overall target from the form. Total per row is auto-calculated from Q1–Q4; the bottom row shows the sum so you can compare against the overall target. These targets will be reflected for the respective Planning Coordinator when they encode.
                            </p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs border border-gray-200 rounded-lg overflow-hidden" id="campus-targets-table" data-is-percentage="{{ $isPercentage ? '1' : '0' }}">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-1.5 text-left font-medium text-gray-700 border-r border-gray-200 align-bottom">
                                                Campus
                                            </th>
                                            <th class="px-3 py-1.5 text-center font-medium text-gray-700 border-r border-gray-200" colspan="4">
                                                Quarterly Targets
                                            </th>
                                            <th class="px-3 py-1.5 text-right font-medium text-gray-700">
                                                Total
                                            </th>
                                        </tr>
                                        <tr class="bg-gray-100">
                                            <th class="px-3 py-1.5 text-left text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Overall Target (Form)
                                            </th>
                                            <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Q1: {{ number_format($overall['q1'] ?? 0, 2) }}{{ $targetSuffix }}
                                            </th>
                                            <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Q2: {{ number_format($overall['q2'] ?? 0, 2) }}{{ $targetSuffix }}
                                            </th>
                                            <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Q3: {{ number_format($overall['q3'] ?? 0, 2) }}{{ $targetSuffix }}
                                            </th>
                                            <th class="px-3 py-1.5 text-center text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Q4: {{ number_format($overall['q4'] ?? 0, 2) }}{{ $targetSuffix }}
                                            </th>
                                            <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700">
                                                Total: {{ number_format($overall['total'] ?? 0, 2) }}{{ $targetSuffix }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="campus-targets-tbody">
                                        @forelse($campusesForTargets as $campus)
                                            @php
                                                $code = $campus->code ?? $campus->name ?? 'UNKNOWN';
                                                $stored = $existingCampusTargets[$code] ?? [];
                                                $oldQ1 = old("campus_targets.$code.q1");
                                                $oldQ2 = old("campus_targets.$code.q2");
                                                $oldQ3 = old("campus_targets.$code.q3");
                                                $oldQ4 = old("campus_targets.$code.q4");
                                                $oldTotal = old("campus_targets.$code.total_target");
                                                $vQ1 = $oldQ1 !== null ? $oldQ1 : ($stored['q1'] ?? null);
                                                $vQ2 = $oldQ2 !== null ? $oldQ2 : ($stored['q2'] ?? null);
                                                $vQ3 = $oldQ3 !== null ? $oldQ3 : ($stored['q3'] ?? null);
                                                $vQ4 = $oldQ4 !== null ? $oldQ4 : ($stored['q4'] ?? null);
                                                $vTotal = $oldTotal !== null ? $oldTotal : ($stored['total_target'] ?? null);
                                            @endphp
                                            <tr class="border-t border-gray-200 campus-target-row" data-campus-code="{{ $code }}">
                                                <td class="px-3 py-1.5 border-r border-gray-200 text-xs text-gray-800">
                                                    {{ $campus->name ?? $code }}
                                                    <span class="text-[10px] text-gray-500 ml-1">({{ $code }})</span>
                                                </td>
                                                @foreach(['q1','q2','q3','q4'] as $quarter)
                                                    @php
                                                        $val = ${'v' . strtoupper($quarter)} ?? null;
                                                    @endphp
                                                    <td class="px-3 py-1.5 text-right border-r border-gray-200">
                                                        <input
                                                            type="number"
                                                            min="0"
                                                            @if($isPercentage) max="100" @endif
                                                            step="0.01"
                                                            name="campus_targets[{{ $code }}][{{ $quarter }}]"
                                                            value="{{ $val }}"
                                                            class="w-20 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-{{ $quarter }}"
                                                        >
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-1.5 text-right">
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        @if($isPercentage) max="100" @endif
                                                        step="0.01"
                                                        name="campus_targets[{{ $code }}][total_target]"
                                                        value="{{ $vTotal }}"
                                                        class="w-24 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-total"
                                                    >
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="campus-targets-empty-row"><td colspan="6" class="px-3 py-4 text-center text-xs text-gray-500">No Planning Coordinator selected above. Select one or more to see campus rows here—only selected coordinators' campuses will appear.</td></tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50">
                                            <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                Sum of Campus Targets
                                            </th>
                                            @foreach(['q1','q2','q3','q4'] as $quarter)
                                                <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700 border-r border-gray-200">
                                                    <span id="campus-sum-{{ $quarter }}">0.00{{ $targetSuffix }}</span>
                                                    @if(($overall[$quarter] ?? 0) > 0)
                                                        <span class="text-[10px] text-gray-500"> of {{ number_format($overall[$quarter], 2) }}{{ $targetSuffix }}</span>
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th class="px-3 py-1.5 text-right text-[11px] font-semibold text-gray-700">
                                                <span id="campus-sum-total">0.00{{ $targetSuffix }}</span>
                                                @if(($overall['total'] ?? 0) > 0)
                                                    <span class="text-[10px] text-gray-500"> of {{ number_format($overall['total'], 2) }}{{ $targetSuffix }}</span>
                                                @endif
                                            </th>
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
                                var q = ['q1','q2','q3','q4'];
                                var sum = 0;
                                q.forEach(function(k) {
                                    var inp = row.querySelector('input.campus-target-' + k);
                                    if (inp) sum += parseFloat(inp.value || '0') || 0;
                                });
                                return sum;
                            }

                            function updateRowTotal(row) {
                                var totalInput = row.querySelector('input.campus-target-total');
                                if (totalInput) {
                                    var t = rowTotalFromQuarters(row);
                                    totalInput.value = t.toFixed(2);
                                }
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
                                var table = document.getElementById('campus-targets-table');
                                var isPercentage = table && table.getAttribute('data-is-percentage') === '1';
                                var suffix = isPercentage ? '%' : '';
                                var quarters = ['q1','q2','q3','q4'];
                                var sums = { q1: 0, q2: 0, q3: 0, q4: 0, total: 0 };
                                var rows = document.querySelectorAll('#campus-targets-table tr.campus-target-row');
                                rows.forEach(function(row) {
                                    quarters.forEach(function(q) {
                                        var input = row.querySelector('input.campus-target-' + q);
                                        var v = parseFloat(input && input.value ? input.value : '0') || 0;
                                        sums[q] += v;
                                    });
                                    var totalInput = row.querySelector('input.campus-target-total');
                                    if (totalInput) {
                                        var tv = parseFloat(totalInput.value ? totalInput.value : '0') || 0;
                                        sums.total += tv;
                                    }
                                });
                                quarters.forEach(function(q) {
                                    var span = document.getElementById('campus-sum-' + q);
                                    if (span) span.textContent = sums[q].toFixed(2) + suffix;
                                });
                                var totalSpan = document.getElementById('campus-sum-total');
                                if (totalSpan) totalSpan.textContent = sums.total.toFixed(2) + suffix;
                            }

                            document.addEventListener('input', function(e) {
                                if (e.target && e.target.classList && e.target.classList.contains('campus-target-input')) {
                                    normalizeCampusTargetInput(e.target);
                                    var row = e.target.closest('tr.campus-target-row');
                                    if (row) updateRowTotal(row);
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
                                recalcCampusTargetSums();
                                // When assigned users select changes: rebuild campus target rows for selected users' campuses
                                var selectEl = document.getElementById('assigned_user_ids');
                                var tbody = document.getElementById('campus-targets-tbody');
                                if (!selectEl || !tbody) return;

                                function getSelectedCampuses() {
                                    var selected = [];
                                    var $select = jQuery(selectEl);
                                    $select.find('option:selected').each(function() {
                                        var code = jQuery(this).attr('data-campus-code') || '';
                                        var name = jQuery(this).attr('data-campus-name') || jQuery(this).text() || '';
                                        if (code) selected.push({ code: code, name: name });
                                    });
                                    var byCode = {};
                                    selected.forEach(function(c) {
                                        if (c.code && !byCode[c.code]) byCode[c.code] = c;
                                    });
                                    return Object.values(byCode);
                                }

                                function buildRowHtml(code, name, vals) {
                                    vals = vals || {};
                                    var q1 = vals.q1 != null ? vals.q1 : '';
                                    var q2 = vals.q2 != null ? vals.q2 : '';
                                    var q3 = vals.q3 != null ? vals.q3 : '';
                                    var q4 = vals.q4 != null ? vals.q4 : '';
                                    var tot = vals.total_target != null ? vals.total_target : '';
                                    var safeCode = (code || '').replace(/"/g, '&quot;');
                                    var safeName = (name || '').replace(/</g, '&lt;').replace(/"/g, '&quot;');
                                    var maxAttr = (document.getElementById('campus-targets-table') && document.getElementById('campus-targets-table').getAttribute('data-is-percentage') === '1') ? ' max="100"' : '';
                                    return '<tr class="border-t border-gray-200 campus-target-row" data-campus-code="' + safeCode + '">' +
                                        '<td class="px-3 py-1.5 border-r border-gray-200 text-xs text-gray-800">' + safeName + ' <span class="text-[10px] text-gray-500 ml-1">(' + safeCode + ')</span></td>' +
                                        '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01"' + maxAttr + ' name="campus_targets[' + safeCode + '][q1]" value="' + q1 + '" class="w-20 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-q1"></td>' +
                                        '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01"' + maxAttr + ' name="campus_targets[' + safeCode + '][q2]" value="' + q2 + '" class="w-20 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-q2"></td>' +
                                        '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01"' + maxAttr + ' name="campus_targets[' + safeCode + '][q3]" value="' + q3 + '" class="w-20 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-q3"></td>' +
                                        '<td class="px-3 py-1.5 text-right border-r border-gray-200"><input type="number" min="0" step="0.01"' + maxAttr + ' name="campus_targets[' + safeCode + '][q4]" value="' + q4 + '" class="w-20 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-q4"></td>' +
                                        '<td class="px-3 py-1.5 text-right"><input type="number" min="0" step="0.01"' + maxAttr + ' name="campus_targets[' + safeCode + '][total_target]" value="' + tot + '" class="w-24 text-right text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 campus-target-input campus-target-total"></td>' +
                                        '</tr>';
                                }

                                function rebuildCampusTargetTable() {
                                    var selectedCampuses = getSelectedCampuses();
                                    var existingByCode = {};
                                    tbody.querySelectorAll('tr.campus-target-row').forEach(function(tr) {
                                        var code = tr.getAttribute('data-campus-code');
                                        if (code) {
                                            var v = { q1: '', q2: '', q3: '', q4: '', total_target: '' };
                                            ['q1','q2','q3','q4'].forEach(function(q) {
                                                var i = tr.querySelector('input.campus-target-' + q);
                                                if (i && i.value) v[q] = i.value;
                                            });
                                            var tot = tr.querySelector('input.campus-target-total');
                                            if (tot && tot.value) v.total_target = tot.value;
                                            existingByCode[code] = { name: (tr.querySelector('td') && tr.querySelector('td').textContent) || code, vals: v };
                                        }
                                    });
                                    tbody.innerHTML = '';
                                    selectedCampuses.forEach(function(c) {
                                        var name = c.name || c.code;
                                        var vals = existingByCode[c.code] ? existingByCode[c.code].vals : null;
                                        tbody.insertAdjacentHTML('beforeend', buildRowHtml(c.code, c.name || c.code, vals));
                                    });
                                    if (selectedCampuses.length === 0) {
                                        tbody.insertAdjacentHTML('beforeend', '<tr id="campus-targets-empty-row"><td colspan="6" class="px-3 py-4 text-center text-xs text-gray-500">No Planning Coordinator selected. Select one or more above to see campus rows here.</td></tr>');
                                    }
                                    tbody.querySelectorAll('tr.campus-target-row').forEach(function(row) { updateRowTotal(row); });
                                    recalcCampusTargetSums();
                                }

                                jQuery(selectEl).on('change', rebuildCampusTargetTable);
                                setTimeout(function() { rebuildCampusTargetTable(); }, 150);
                            });
                        })();
                    </script>
                @endif

                <!-- Column Structure -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Data Table Columns</h3>
                        <p class="text-xs text-gray-500 mb-2">Define the columns users will see and fill when creating submissions.</p>
                        <p class="text-xs text-gray-400 mb-4 italic">How it works: Each block below defines one column in the submission table. Users add rows of data; values in each column follow the rules you define here. To add formulas or calculations, use the template View page (Field Structure) after saving.</p>
                        
                        <div id="fields-container">
                            <div class="space-y-4" id="fields-list">
                                @if($template->fields_json && isset($template->fields_json['fields']))
                                    @foreach($template->fields_json['fields'] as $field)
                                        @php
                                            $fieldCount = $loop->index + 1;
                                        @endphp
                                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" data-field-id="field_{{ $fieldCount }}">
                                            <div class="flex justify-between items-center mb-3">
                                                <h4 class="text-sm font-medium text-gray-900">Column {{ $fieldCount }}</h4>
                                                <button type="button" class="text-red-600 hover:text-red-800" onclick="removeField('field_{{ $fieldCount }}')">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Column Label</label>
                                                    <input type="text" name="field_label_{{ $fieldCount }}" value="{{ $field['label'] ?? '' }}" 
                                                           class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Data Type</label>
                                                    <select name="field_type_{{ $fieldCount }}" 
                                                            class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 field-type-select">
                                                        <option value="text" {{ ($field['type'] ?? 'text') === 'text' && !isset($field['meta']['calc']) ? 'selected' : '' }}>Text</option>
                                                        <option value="number" {{ ($field['type'] ?? '') === 'number' ? 'selected' : '' }}>Number</option>
                                                        <option value="dropdown" {{ ($field['type'] ?? '') === 'dropdown' ? 'selected' : '' }}>Dropdown</option>
                                                        <option value="textarea" {{ ($field['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                                        <option value="link" {{ ($field['type'] ?? '') === 'link' ? 'selected' : '' }}>Link</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Required?</label>
                                                    <select name="field_required_{{ $fieldCount }}" 
                                                            class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                        <option value="false" {{ !($field['required'] ?? false) ? 'selected' : '' }}>No</option>
                                                        <option value="true" {{ ($field['required'] ?? false) ? 'selected' : '' }}>Yes</option>
                                                    </select>
                                                </div>
                                            </div>

                                            @php
                                                $subheadersField = [];
                                                if (!empty($field['subheaders']) && is_array($field['subheaders'])) {
                                                    $subheadersField = array_values(array_filter(array_map('strval', $field['subheaders']), fn ($s) => trim($s) !== ''));
                                                }
                                                $subheadersPanelOpen = count($subheadersField) > 0;
                                            @endphp
                                            <div class="mt-3 field-subheaders-wrap">
                                                <button type="button" class="field-subheaders-toggle inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-teal-800 bg-teal-50 border border-teal-200 rounded hover:bg-teal-100 focus:outline-none focus:ring-2 focus:ring-teal-500">+ Add sub-headers</button>
                                                <div class="field-subheaders-panel mt-2 border border-dashed border-gray-300 rounded-md p-3 bg-white {{ $subheadersPanelOpen ? '' : 'hidden' }}">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Sub-header labels</label>
                                                    <div class="field-subheaders-list space-y-1.5 mb-2">
                                                        @forelse($subheadersField as $subH)
                                                            <div class="flex gap-1.5 items-center field-subheader-row">
                                                                <input type="text" class="field-subheader-input flex-1 min-w-0 w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-teal-500 focus:border-teal-500" placeholder="Sub-header text" value="{{ $subH }}">
                                                                <button type="button" class="field-subheader-remove flex-shrink-0 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 text-lg leading-none font-bold" title="Remove row">×</button>
                                                            </div>
                                                        @empty
                                                        @endforelse
                                                    </div>
                                                    <button type="button" class="field-subheaders-add-row inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400">+ Add sub-header row</button>
                                                    <p class="mt-1 text-xs text-gray-500">Optional. Two or more sub-headers create separate columns under one grouped header. One sub-header stays a single column with a subtitle.</p>
                                                </div>
                                            </div>
                                            
                                            @php
                                                $ddOpts = (isset($field['options']) && is_array($field['options'])) ? array_values($field['options']) : [];
                                                if (count($ddOpts) === 0) {
                                                    $ddOpts = [''];
                                                }
                                            @endphp
                                            <div class="mt-3" id="options_field_{{ $fieldCount }}" style="display: {{ ($field['type'] ?? '') === 'dropdown' ? 'block' : 'none' }};">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Dropdown choices</label>
                                                <div class="field-dropdown-options-list space-y-1.5 mb-2">
                                                    @foreach($ddOpts as $ddOpt)
                                                        <div class="flex gap-1.5 items-center field-dropdown-option-row">
                                                            <input type="text" class="field-dropdown-option-input flex-1 min-w-0 w-full text-xs border border-gray-300 rounded px-2 py-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter choice" value="{{ $ddOpt }}">
                                                            <button type="button" class="field-dropdown-option-remove flex-shrink-0 w-7 h-7 flex items-center justify-center rounded text-red-600 hover:bg-red-50 text-lg leading-none font-bold" title="Remove row">×</button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="field-dropdown-add-option inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">+ Add option</button>
                                                <p class="mt-1 text-xs text-gray-500">One choice per row.</p>
                                            </div>
                                            
                                            <div class="mt-3 calc-settings hidden" id="calc_settings_field_{{ $fieldCount }}" style="display: none !important;" aria-hidden="true">
                                                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                                                    <label class="block text-xs font-semibold text-gray-700 mb-2">Auto-Calculation</label>
                                                    <p class="text-xs text-gray-600 mb-3 italic">This column is computed from other columns. Values are calculated when users save or submit.</p>
                                                    
                                                    <div class="mb-2" id="calc_apply_all_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && ($field['meta']['calc'] ?? '') !== 'formula' ? 'block' : 'none' }};">
                                                        <p class="text-xs text-gray-600 italic">Apply to: This row only</p>
                                                    </div>

                                                    <div class="mb-2" id="calc_source_settings_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && in_array($field['meta']['calc'], ['unique', 'countif', 'sum', 'avg_percentage']) ? 'block' : 'none' }};">
                                                        <label class="block text-xs font-medium text-gray-700 mb-1">Source Column</label>
                                                        <select name="calc_source_a_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-source-a" data-selected="{{ $field['meta']['sourceA'] ?? '' }}">
                                                            <option value="">-- Select Source Column --</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="mb-2" id="calc_unique_settings_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && $field['meta']['calc'] === 'unique' ? 'block' : 'none' }};">
                                                        <label class="block text-xs font-medium text-gray-700 mb-1">Result Type</label>
                                                        <input type="text" value="Count of unique values" readonly
                                                               class="w-full text-xs border-gray-300 rounded bg-gray-100 text-gray-700">
                                                    </div>
                                                    
                                                    <div class="mb-2" id="calc_countif_settings_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && $field['meta']['calc'] === 'countif' ? 'block' : 'none' }};">
                                                        <p class="text-xs text-gray-600">Counts all non-empty values in the source column (including unique and repeated). Result shows in the summary row below.</p>
                                                    </div>
                                                    
                                                    <div class="mb-2" id="calc_avg_settings_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && $field['meta']['calc'] === 'avg_percentage' ? 'block' : 'none' }};">
                                                        <label class="block text-xs font-medium text-gray-700 mb-1">Apply to</label>
                                                        <select name="calc_avg_scope_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                            <option value="row" {{ isset($field['meta']['scope']) && $field['meta']['scope'] === 'row' ? 'selected' : '' }}>This row only</option>
                                                            <option value="all_rows" {{ !isset($field['meta']['scope']) || $field['meta']['scope'] === 'all_rows' ? 'selected' : '' }}>All rows in the table</option>
                                                        </select>
                                                        <p class="text-xs text-gray-600 mt-1">Uses the source column. &quot;All rows&quot; computes the average across the whole table.</p>
                                                    </div>

                                                    <div class="mb-2" id="calc_formula_settings_field_{{ $fieldCount }}" style="display: {{ isset($field['meta']['calc']) && $field['meta']['calc'] === 'formula' ? 'block' : 'none' }};">
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Operation</label>
                                                                <select name="calc_formula_operation_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                                    <option value="sum" {{ ($field['meta']['operation'] ?? '') === 'sum' ? 'selected' : '' }}>Sum (A + B)</option>
                                                                    <option value="subtract" {{ ($field['meta']['operation'] ?? '') === 'subtract' ? 'selected' : '' }}>Subtract (A - B)</option>
                                                                    <option value="multiply" {{ ($field['meta']['operation'] ?? '') === 'multiply' ? 'selected' : '' }}>Multiply (A * B)</option>
                                                                    <option value="divide" {{ ($field['meta']['operation'] ?? '') === 'divide' ? 'selected' : '' }}>Divide (A / B)</option>
                                                                    <option value="percent_of" {{ ($field['meta']['operation'] ?? '') === 'percent_of' ? 'selected' : '' }}>Percent Of ((A / B) * 100)</option>
                                                                    <option value="sum_over_b_percent" {{ ($field['meta']['operation'] ?? '') === 'sum_over_b_percent' ? 'selected' : '' }}>Percent Of ((A + B) / B) * 100</option>
                                                                    <option value="diff_over_b_percent" {{ ($field['meta']['operation'] ?? '') === 'diff_over_b_percent' ? 'selected' : '' }}>Percent Of ((A - B) / B) * 100</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Apply to</label>
                                                                <select name="calc_formula_scope_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                                    <option value="row" {{ !isset($field['meta']['scope']) || $field['meta']['scope'] === 'row' ? 'selected' : '' }}>This row only</option>
                                                                    <option value="all_rows" {{ ($field['meta']['scope'] ?? '') === 'all_rows' ? 'selected' : '' }}>All rows in the table</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Source Column (A)</label>
                                                                <select name="calc_formula_source_a_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-a" data-selected="{{ $field['meta']['sourceA'] ?? '' }}">
                                                                    <option value="">-- Select Source Column --</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Second Source (B)</label>
                                                                <select name="calc_formula_source_b_field_{{ $fieldCount }}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-b" data-selected="{{ $field['meta']['sourceB'] ?? '' }}">
                                                                    <option value="">-- Select Second Source --</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            <button type="button" id="add-field" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Column
                            </button>
                        </div>

                        <input type="hidden" id="fields_json" name="fields_json" value="{{ old('fields_json', json_encode($template->fields_json ?? ['fields' => []])) }}">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ $template->form ? route('forms.show', $template->form->id) : route('super-admin.templates.index') . '?tab=forms' }}" 
                       class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Update Template
                    </button>
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
            if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
            $el.select2({ width: '100%', placeholder: 'Select ONE KPI Title', allowClear: true });
        }
        document.addEventListener('DOMContentLoaded', function() {
            const fieldsList = document.getElementById('fields-list');
            const addFieldBtn = document.getElementById('add-field');
            const fieldsJsonInput = document.getElementById('fields_json');
            const existingFieldsJson = @json($template->fields_json ?? ['fields' => [], 'summary_rules' => []]);
            let fieldCount = {{ isset($template->fields_json['fields']) ? count($template->fields_json['fields']) : 0 }};

            // Campuses: All vs specific
            const campusCodesAll = document.getElementById('campus_codes_all');
            const campusCodeAllInput = document.getElementById('campus_code_all_input');
            const campusCheckboxes = document.querySelectorAll('.campus-code-cb');
            if (campusCodesAll && campusCodeAllInput) {
                function syncCampusAll() {
                    const allChecked = campusCodesAll.checked;
                    campusCodeAllInput.disabled = !allChecked;
                    campusCodeAllInput.value = allChecked ? 'ALL' : '';
                    if (allChecked) {
                        campusCheckboxes.forEach(function(cb) { cb.checked = false; });
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
                            campusCodeAllInput.value = '';
                        }
                    });
                });
                syncCampusAll();
            }

            addFieldBtn.addEventListener('click', function() {
                addField();
            });

            var fieldsListEl = document.getElementById('fields-list');
            if (fieldsListEl) {
                fieldsListEl.addEventListener('click', function(e) {
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

            function addField(fieldData = {}) {
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
                                       class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
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
                                <p class="mt-1 text-xs text-gray-500">Optional. Two or more sub-headers create separate columns under one grouped header. One sub-header stays a single column with a subtitle.</p>
                            </div>
                        </div>
                        
                        <div class="mt-3" id="options_${fieldId}" style="display: ${fieldData.type === 'dropdown' ? 'block' : 'none'};">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Dropdown choices</label>
                            <div class="field-dropdown-options-list space-y-1.5 mb-2">${dropdownOptsHtml}</div>
                            <button type="button" class="field-dropdown-add-option inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">+ Add option</button>
                            <p class="mt-1 text-xs text-gray-500">One choice per row.</p>
                        </div>
                        
                        <div class="mt-3 calc-settings" id="calc_settings_${fieldId}" style="display: none;">
                            <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                                <label class="block text-xs font-semibold text-gray-700 mb-2">Auto-Calculation</label>
                                <p class="text-xs text-gray-600 mb-3 italic">This column is computed from other columns. Values are calculated when users save or submit.</p>
                                
                                <div class="mb-2" id="calc_apply_all_${fieldId}" style="display: none;">
                                    <p class="text-xs text-gray-600 italic">Apply to: This row only</p>
                                </div>

                                <div class="mb-2" id="calc_source_settings_${fieldId}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Source Column</label>
                                    <select name="calc_source_a_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-source-a" data-selected="${fieldData.meta && fieldData.meta.sourceA ? fieldData.meta.sourceA : ''}">
                                        <option value="">-- Select Source Column --</option>
                                    </select>
                                </div>
                                
                                <div class="mb-2" id="calc_unique_settings_${fieldId}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Result Type</label>
                                    <input type="text" value="Count of unique values" readonly
                                           class="w-full text-xs border-gray-300 rounded bg-gray-100 text-gray-700">
                                </div>
                                
                                <div class="mb-2" id="calc_countif_settings_${fieldId}" style="display: none;">
                                    <p class="text-xs text-gray-600">Counts all non-empty values in the source column (including unique and repeated). Result shows in the summary row below.</p>
                                </div>
                                
                                <div class="mb-2" id="calc_avg_settings_${fieldId}" style="display: none;">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Apply to</label>
                                    <select name="calc_avg_scope_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="row" ${fieldData.meta && fieldData.meta.scope === 'row' ? 'selected' : ''}>This row only</option>
                                        <option value="all_rows" ${!fieldData.meta || !fieldData.meta.scope || fieldData.meta.scope === 'all_rows' ? 'selected' : ''}>All rows in the table</option>
                                    </select>
                                    <p class="text-xs text-gray-600 mt-1">Uses the source column. &quot;All rows&quot; computes the average across the whole table.</p>
                                </div>

                                <div class="mb-2" id="calc_formula_settings_${fieldId}" style="display: none;">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Operation</label>
                                            <select name="calc_formula_operation_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
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
                                            <select name="calc_formula_scope_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="row" ${!fieldData.meta || !fieldData.meta.scope || fieldData.meta.scope === 'row' ? 'selected' : ''}>This row only</option>
                                                <option value="all_rows" ${fieldData.meta && fieldData.meta.scope === 'all_rows' ? 'selected' : ''}>All rows in the table</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Source Column (A)</label>
                                            <select name="calc_formula_source_a_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-a" data-selected="${fieldData.meta && fieldData.meta.sourceA ? fieldData.meta.sourceA : ''}">
                                                <option value="">-- Select Source Column --</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Second Source (B)</label>
                                            <select name="calc_formula_source_b_${fieldId}" class="w-full text-xs border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 calc-formula-source-b" data-selected="${fieldData.meta && fieldData.meta.sourceB ? fieldData.meta.sourceB : ''}">
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
                if (newBlock) newBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
                maybeApplyDropdownDefaults(newBlock);
                updateCalcSourceFields();
                updateFieldsJson();
            }

            window.removeField = function(fieldId) {
                document.querySelector(`[data-field-id="${fieldId}"]`).remove();
                updateCalcSourceFields();
                updateFieldsJson();
            };

            function updateFieldsJson() {
                if (!fieldsList || !fieldsJsonInput) {
                    return;
                }
                const fields = [];
                const fieldElements = fieldsList.querySelectorAll('[data-field-id]');
                
                fieldElements.forEach((element) => {
                    const labelInput = element.querySelector(`input[name*="field_label"]`);
                    const typeSelect = element.querySelector(`select[name*="field_type"]`);
                    const requiredSelect = element.querySelector(`select[name*="field_required"]`);
                    if (!labelInput || !typeSelect || !requiredSelect) {
                        console.warn('Missing required field elements, skipping...');
                        return;
                    }
                    
                    const label = labelInput.value;
                    const type = typeSelect.value;
                    const required = requiredSelect.value === 'true';
                    const fieldKey = normalizeKey(label);
                    const existingField = (existingFieldsJson.fields || []).find(function(f) {
                        return (f.key && f.key === fieldKey) || (f.label && normalizeKey(f.label) === fieldKey);
                    });
                    
                    // Data Table Columns does not expose formula UI; preserve existing meta from template (set on View Template).
                    let baseType = type;
                    let meta = existingField && existingField.meta ? existingField.meta : null;
                    
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
                    summary_rules: Array.isArray(existingFieldsJson.summary_rules) ? existingFieldsJson.summary_rules : []
                });
                console.log('Updated fields JSON:', fieldsJsonInput.value);
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
                        // Use querySelector within the element to find settings divs (works for both naming conventions)
                        const applyAllDiv = element.querySelector('[id*="calc_apply_all"]');
                        const sourceDiv = element.querySelector('[id*="calc_source_settings"]');
                        const uniqueDiv = element.querySelector('[id*="calc_unique_settings"]');
                        const countifDiv = element.querySelector('[id*="calc_countif_settings"]');
                        const avgDiv = element.querySelector('[id*="calc_avg_settings"]');
                        const formulaDiv = element.querySelector('[id*="calc_formula_settings"]');
                        const nonFormulaCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage'].includes(fieldType);
                        
                        if (applyAllDiv) applyAllDiv.style.display = ['text_unique', 'text_countif', 'number_sum'].includes(fieldType) ? 'block' : 'none';
                        if (sourceDiv) sourceDiv.style.display = nonFormulaCalculated ? 'block' : 'none';
                        if (uniqueDiv) uniqueDiv.style.display = fieldType === 'text_unique' ? 'block' : 'none';
                        if (countifDiv) countifDiv.style.display = fieldType === 'text_countif' ? 'block' : 'none';
                        if (avgDiv) avgDiv.style.display = fieldType === 'avg_percentage' ? 'block' : 'none';
                        if (formulaDiv) formulaDiv.style.display = fieldType === 'formula_operation' ? 'block' : 'none';
                        if (fieldType === 'formula_operation' && applyAllDiv) applyAllDiv.style.display = 'none';

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
                    } else {
                        calcSettings.style.display = 'none';
                    }
                });
            }

            document.addEventListener('change', function(e) {
                if (e.target.name && e.target.name.includes('field_')) {
                    if (e.target.name.includes('field_type')) {
                        const fieldElement = e.target.closest('[data-field-id]');
                        const fullFieldId = fieldElement.dataset.fieldId; // e.g., "field_1"
                        const fieldId = fullFieldId.replace('field_', ''); // e.g., "1"
                        const fieldType = e.target.value;
                        
                        // Show/hide dropdown options for this specific field
                        // Check both naming conventions (for existing vs new fields)
                        const optionsDiv = document.getElementById(`options_${fullFieldId}`) || document.getElementById(`options_field_${fieldId}`);
                        if (optionsDiv) {
                            optionsDiv.style.display = fieldType === 'dropdown' ? 'block' : 'none';
                            if (fieldType === 'dropdown') {
                                ensureDropdownOptionsHasRow(optionsDiv);
                                maybeApplyDropdownDefaults(fieldElement);
                            }
                        }
                        
                        // Show/hide calculation settings for this specific field immediately
                        // Use querySelector within the field element to find settings (works for both naming conventions)
                        const calcSettingsDiv = fieldElement.querySelector('.calc-settings');
                        const isCalculated = ['text_unique', 'number_sum', 'text_countif', 'avg_percentage', 'formula_operation'].includes(fieldType);
                        
                        if (calcSettingsDiv) {
                            calcSettingsDiv.style.display = isCalculated ? 'block' : 'none';
                            
                            if (isCalculated) {
                                // Show/hide type-specific settings for this field
                                const applyAllDiv = fieldElement.querySelector('[id*="calc_apply_all"]');
                                const sourceDiv = fieldElement.querySelector('[id*="calc_source_settings"]');
                                const uniqueDiv = fieldElement.querySelector('[id*="calc_unique_settings"]');
                                const countifDiv = fieldElement.querySelector('[id*="calc_countif_settings"]');
                                const avgDiv = fieldElement.querySelector('[id*="calc_avg_settings"]');
                                const formulaDiv = fieldElement.querySelector('[id*="calc_formula_settings"]');
                                const nonFormulaCalculated = ['text_unique', 'text_countif', 'number_sum', 'avg_percentage'].includes(fieldType);
                                
                                if (applyAllDiv) applyAllDiv.style.display = ['text_unique', 'text_countif', 'number_sum'].includes(fieldType) ? 'block' : 'none';
                                if (sourceDiv) sourceDiv.style.display = nonFormulaCalculated ? 'block' : 'none';
                                if (uniqueDiv) uniqueDiv.style.display = fieldType === 'text_unique' ? 'block' : 'none';
                                if (countifDiv) countifDiv.style.display = fieldType === 'text_countif' ? 'block' : 'none';
                                if (avgDiv) avgDiv.style.display = fieldType === 'avg_percentage' ? 'block' : 'none';
                                if (formulaDiv) formulaDiv.style.display = fieldType === 'formula_operation' ? 'block' : 'none';
                                if (fieldType === 'formula_operation' && applyAllDiv) applyAllDiv.style.display = 'none';
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
            setTimeout(function() {
                updateCalcSourceFields();
            }, 100);

            const editTemplateForm = document.getElementById('edit-template-form');
            if (editTemplateForm) {
                editTemplateForm.addEventListener('submit', function(e) {
                    try {
                        updateFieldsJson();
                        const fieldsData = JSON.parse(fieldsJsonInput?.value || '{"fields":[]}');
                        const calculatedMissingSource = (fieldsData.fields || []).find((field) => {
                            const calcType = field?.meta?.calc;
                            return ['unique', 'countif', 'sum', 'avg_percentage'].includes(calcType) && !field?.meta?.sourceA;
                        });
                        if (calculatedMissingSource) {
                            e.preventDefault();
                            if (window.showAlert) {
                                window.showAlert({ title: 'Notice', message: 'Please set Source A for calculated column "' + calculatedMissingSource.label + '".' });
                            } else {
                                alert('Please set Source A for calculated column "' + calculatedMissingSource.label + '".');
                            }
                            return;
                        }
                    } catch (err) {
                        // If client-side validation fails unexpectedly, allow submit so server can validate.
                        console.error('Edit Template submit handler error:', err);
                    }
                });
            }

            // KRA-KPI nested data structure (direct access, no filtering)
            @if($template->form && !empty($kraKpiData))
            const kraKpiData = @json($kraKpiData);
            
            // DEBUG: Log structure to verify it's correct
            console.log('=== KRA-KPI Data Structure ===');
            console.log('Total KRAs:', kraKpiData?.length || 0);
            if (kraKpiData && Array.isArray(kraKpiData)) {
                kraKpiData.forEach((kra, idx) => {
                    console.log(`KRA ${idx}: "${(kra.kra_title || '').trim()}" has ${(kra.kpis || []).length} KPIs`);
                });
            }
            
            const kraTitleSelect = document.getElementById('kra_title');
            const kpiTitleSelect = document.getElementById('kpi_title');
            const kpiCountSpan = document.getElementById('kpi-count');
            const currentKpiTitle = @json($currentKpiTitle);
            const currentKraTitle = @json($currentKraTitle);
            
            if (kraTitleSelect && kpiTitleSelect) {
                function updateKpiOptions() {
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery('#kpi_title').hasClass('select2-hidden-accessible')) {
                        jQuery('#kpi_title').select2('destroy');
                    }
                    const selectedKraTitle = kraTitleSelect.value ? kraTitleSelect.value.trim() : '';
                    
                    // IMMEDIATELY clear KPI dropdown - this is critical
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    kpiTitleSelect.disabled = !selectedKraTitle;
                    
                    if (!selectedKraTitle) {
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
                    console.log('Searching for KRA:', selectedKraTitle);
                    
                    for (let i = 0; i < kraKpiData.length; i++) {
                        const kra = kraKpiData[i];
                        const kraTitle = (kra.kra_title || '').trim();
                        console.log(`  Comparing KRA ${i}: "${kraTitle}" === "${selectedKraTitle}" = ${kraTitle === selectedKraTitle}`);
                        if (kraTitle === selectedKraTitle) {
                            selectedKra = kra;
                            console.log('  ✓ Found matching KRA with', (kra.kpis || []).length, 'KPIs');
                            break; // Found exact match, stop searching
                        }
                    }
                    
                    // STRICT: Only proceed if we found the exact KRA match
                    if (!selectedKra) {
                        console.error('✗ KRA not found:', selectedKraTitle);
                        console.log('Available KRAs:', kraKpiData.map(k => (k.kra_title || '').trim()));
                        kpiTitleSelect.innerHTML = '<option value="">KRA not found in form data</option>';
                        if (kpiCountSpan) kpiCountSpan.textContent = '0';
                        return;
                    }
                    
                    // Get KPIs directly from selected KRA ONLY - verify it's the correct KRA
                    const availableKpis = Array.isArray(selectedKra.kpis) ? selectedKra.kpis : [];
                    
                    // DOUBLE CHECK: Verify the selectedKra actually matches
                    const verifiedKraTitle = (selectedKra.kra_title || '').trim();
                    if (verifiedKraTitle !== selectedKraTitle) {
                        console.error('KRA MISMATCH! Selected:', selectedKraTitle, 'Found:', verifiedKraTitle);
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
                                
                                // Preselect if it matches current KPI
                                if (currentKpiTitle && kpiFull === currentKpiTitle) {
                                    option.selected = true;
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
                        kpiCountSpan.textContent = availableKpis.length;
                    }
                    
                    // Show message if no KPIs
                    if (availableKpis.length === 0) {
                        const noKpiOption = document.createElement('option');
                        noKpiOption.value = '';
                        noKpiOption.textContent = 'No KPIs available for this KRA';
                        noKpiOption.disabled = true;
                        kpiTitleSelect.appendChild(noKpiOption);
                    }
                    
                    // Clear selection if current KPI doesn't belong to selected KRA
                    if (currentKpiTitle && selectedKraTitle !== currentKraTitle) {
                        kpiTitleSelect.value = '';
                    }
                    initKpiSelect2();
                }
                
                // Clear KPI when KRA changes - CRITICAL: Clear immediately
                kraTitleSelect.addEventListener('change', function() {
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
                if (kraTitleSelect.value) {
                    // Clear first, then populate
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    updateKpiOptions();
                } else {
                    // No KRA selected, ensure dropdown is empty
                    kpiTitleSelect.innerHTML = '<option value="">Select ONE KPI Title</option>';
                    kpiTitleSelect.disabled = true;
                    if (kpiCountSpan) kpiCountSpan.textContent = '0';
                }
                
                // Validation before submit
                const form = document.getElementById('edit-template-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        try {
                            const norm = (s) => (s || '').toString().replace(/\s+/g, ' ').trim();
                            const selectedKraTitle = kraTitleSelect.value ? kraTitleSelect.value.trim() : '';
                            const selectedKpi = (kpiTitleSelect.value || '').trim();

                            // Let native required validation handle missing values.
                            if (!selectedKraTitle || !selectedKpi) return;

                            const selectedKra = (kraKpiData || []).find(kra => {
                                const kraTitle = (kra?.kra_title || '').trim();
                                return kraTitle === selectedKraTitle;
                            });

                            const availableKpis = Array.isArray(selectedKra?.kpis) ? selectedKra.kpis : [];
                            const kpiExists = availableKpis.some(kpi => {
                                const kpiNumber = (kpi?.number || '').toString().trim();
                                const kpiTitle = (kpi?.title || '').toString().trim();
                                const kpiFull = kpiNumber ? `${kpiNumber} - ${kpiTitle}` : kpiTitle;
                                return norm(kpiFull) === norm(selectedKpi);
                            });

                            if (kpiExists) return;

                            e.preventDefault();
                            const msg = 'Selected KPI does not belong to the selected KRA. Please select a KPI that matches the selected KRA.';
                            if (window.showAlert) {
                                window.showAlert({ title: 'Notice', message: msg });
                            } else {
                                alert(msg);
                            }
                            kpiTitleSelect.focus();
                            return false;
                        } catch (err) {
                            // Never block submit on JS error; allow server-side validation instead.
                            console.error('Edit Template KRA/KPI submit validation error:', err);
                        }
                    });
                }
            }
            @endif
            // KPI Title preview: full multi-line display (same format as Create/Edit Form)
            const kpiTitleSelectEl = document.getElementById('kpi_title');
            const kpiPreviewEl = document.getElementById('kpi-title-preview');
            if (kpiTitleSelectEl && kpiPreviewEl) {
                function syncKpiPreview() {
                    const val = kpiTitleSelectEl.value ? kpiTitleSelectEl.value.trim() : '';
                    kpiPreviewEl.textContent = val || '';
                    kpiPreviewEl.classList.toggle('hidden', !val);
                }
                kpiTitleSelectEl.addEventListener('change', syncKpiPreview);
                syncKpiPreview();
            }
            if (document.getElementById('kpi_title')) initKpiSelect2();
            if (document.getElementById('assigned_user_ids')) {
                var assignedSelect = jQuery('#assigned_user_ids');
                assignedSelect.select2({ width: '100%', placeholder: 'Select Planning Coordinator(s)', allowClear: true, closeOnSelect: false });

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
        });
    </script>
</x-app-layout>

