<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                            View Validated Template
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Submission ID: {{ $submission->submission_id }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:gap-3">
                        <a href="{{ route('super-admin.validated-templates.edit', $submission) }}" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Accomplishment Values
                        </a>
                        @php
                            $pcCampusIdForReport = \App\Models\Campus::where('name', $submission->campus)->value('id');
                            $planningCoordinatorFilteredReportUrl = route('super-admin.reports.planning-coordinator', array_filter([
                                'template_code' => $submission->template_code,
                                'campus_user_filter' => $pcCampusIdForReport,
                            ], fn ($v) => $v !== null && $v !== ''));
                        @endphp
                        <a href="{{ $planningCoordinatorFilteredReportUrl }}"
                           class="inline-flex items-center px-4 py-2 bg-slate-700 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Planning report ({{ $submission->template_code }})
                        </a>
                        <a href="{{ route('super-admin.validated-templates.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                            ← Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Submission Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Submission Information
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Template Code</span>
                            <p class="text-sm text-gray-900">{{ $submission->template_code }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Strategic Goal</span>
                            <p class="text-sm text-gray-900">{{ $submission->sg_code ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">KRA Title</span>
                            <p class="text-sm text-gray-900">{{ $submission->kra_title ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">KPI Title</span>
                            <p class="text-sm text-gray-900">{{ $submission->kpi_title ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Campus</span>
                            <p class="text-sm text-gray-900">{{ $submission->campus }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Quarter</span>
                            <p class="text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $submission->quarter }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Submitted By</span>
                            <p class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Submitted Date</span>
                            <p class="text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : $submission->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        @if($submission->approval && $submission->approval->validator)
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Validated By (QA Coordinator)</span>
                            <p class="text-sm text-gray-900">{{ $submission->approval->validator->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="block text-sm font-medium text-gray-700 mb-1">Validated Date</span>
                            <p class="text-sm text-gray-900">{{ $submission->approval->validated_at ? $submission->approval->validated_at->format('M d, Y H:i') : 'N/A' }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Submitted Data -->
            @if($submission->table_data && count($submission->table_data) > 0)
            @php
                $validatedTableRows = \App\Support\SubmissionTableData::asArray($submission->table_data);
                $tableHeaders = \App\Support\SubmissionTableData::dataColumnKeys($validatedTableRows);
            @endphp
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Submitted Data
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach($tableHeaders as $header)
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ ucwords(str_replace('_', ' ', $header)) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($submission->table_data as $row)
                                    @php
                                        $meta = $row['_meta'] ?? [];
                                        if (is_string($meta)) {
                                            $meta = json_decode($meta, true) ?? [];
                                        }
                                        $meta = is_array($meta) ? $meta : [];
                                        $isSummaryRow = ($meta['row_type'] ?? 'data') === 'summary';
                                        // Also detect legacy summary rows by cell value
                                        if (!$isSummaryRow) {
                                            foreach ($row as $rk => $rv) {
                                                if ($rk === '_meta') continue;
                                                if (strtolower(trim((string) $rv)) === 'summary') {
                                                    $isSummaryRow = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    <tr class="{{ $isSummaryRow ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-50' }}" {{ $isSummaryRow ? 'data-row-type="summary"' : '' }}>
                                        @foreach($tableHeaders as $header)
                                            @php
                                                $value = $row[$header] ?? '';
                                                if (is_array($value) || is_object($value)) {
                                                    $value = json_encode($value);
                                                }
                                                $value = (string) $value;
                                                $isLink = $value !== '' && filter_var($value, FILTER_VALIDATE_URL);
                                                $displayValue = $value;
                                                if ($isSummaryRow && strtolower(trim($value)) === 'summary') {
                                                    $displayValue = '—';
                                                }
                                            @endphp
                                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isSummaryRow ? 'bg-blue-100 text-gray-800 font-semibold' : 'text-gray-900' }}">
                                                @if($isLink)
                                                    <a href="{{ $value }}" target="_blank" class="{{ $isSummaryRow ? 'text-gray-700 hover:text-gray-900 underline' : 'text-indigo-600 hover:text-indigo-900' }}">
                                                        {{ $displayValue }}
                                                    </a>
                                                @else
                                                    {{ $displayValue ?: '-' }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Performance Validation -->
            @if($submission->approval)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Performance Validation
                    </h3>
                    
                    @php
                        $approval = $submission->approval;
                    @endphp
                    
                    <!-- Target Values (Read-only) -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Target Values (Set by QA Coordinator) @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q1 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->target_q1 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q2 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->target_q2 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q3 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->target_q3 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q4 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->target_q4 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Target @if(!empty($isPercentageForm)) (%) @endif</label>
                            <input type="text" readonly 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                   value="{{ number_format($approval->target_total ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                        </div>
                    </div>

                    <!-- Accomplishment Values (Current) -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Accomplishment Values (Current) @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q1 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->accomp_q1 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q2 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->accomp_q2 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q3 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->accomp_q3 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Q4 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->accomp_q4 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                            <input type="text" readonly 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                   value="{{ number_format($approval->accomp_total ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div class="mt-6">
                        <h4 class="text-md font-medium text-gray-900 mb-4">Performance Metrics</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Variance</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->variance ?? 0, 2) }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rate of Accomplishment (%)</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ number_format($approval->rate ?? 0, 2) }}%">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ $approval->rating ?? 'N/A' }}">
                            </div>
                        </div>
                    </div>

                    @if($approval->remarks)
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                        <textarea readonly rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm">{{ $approval->remarks }}</textarea>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

