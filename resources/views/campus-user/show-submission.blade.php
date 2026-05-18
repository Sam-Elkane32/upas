<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('View Submission') }}
                </h2>
                {{-- session info: layouts.flash-popup toast --}}
            </div>
            <!-- Submission Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $submission->form_title }}</h3>
                            <p class="text-sm text-gray-600">Submission ID: {{ $submission->submission_id }}</p>
                            <p class="text-sm text-gray-600">KRA: {{ $submission->kra_title }}</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_badge_class }}">
                                {{ $submission->status }}
                            </span>
                            @if(!empty($canEdit) && $canEdit)
                                <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Template Information (match Super Admin: use linked template as source of truth) -->
                @php
                    $displayTemplate = $submission->template;
                @endphp
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Template Information</h4>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Template Code</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ $displayTemplate ? $displayTemplate->template_code : $submission->template_code }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Strategic Goal</dt>
                                <dd class="text-sm text-gray-900">{{ $displayTemplate ? $displayTemplate->sg_code : ($submission->sg_code ?? 'N/A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">KRA Title</dt>
                                <dd class="text-sm text-gray-900">{{ $displayTemplate ? $displayTemplate->kra_title : ($submission->kra_title ?? 'N/A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">KPI Title</dt>
                                <dd class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">{{ $displayTemplate ? $displayTemplate->kpi_title : ($submission->kpi_title ?? 'N/A') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Form Title</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->form_title }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Campus</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->campus ?? 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Submission Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h4 class="text-lg font-medium text-gray-900 mb-4">Submission Information</h4>
                        <dl class="grid grid-cols-1 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Campus</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->campus }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Quarter</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->quarter }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $submission->status_badge_class }}">
                                        {{ $submission->status }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submitted By</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Submitted At</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->last_updated ? $submission->last_updated->format('M d, Y H:i') : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Table Data -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Submission Data</h4>
                    @php
                        // Safely get and decode table_data
                        $tableData = $submission->table_data;
                        if (is_string($tableData)) {
                            $tableData = json_decode($tableData, true);
                        }
                        if (!is_array($tableData)) {
                            $tableData = [];
                        }
                        // Normalize string for comparison
                        $normalize = function($s) {
                            $s = is_array($s) || is_object($s) ? json_encode($s) : (string) $s;
                            $s = preg_replace('/[\s\xC2\xA0]+/u', ' ', $s);
                            return strtolower(trim($s));
                        };
                        // Keep data rows AND summary rows (result rows); only remove completely empty or non-array rows
                        $tableData = array_values(array_filter($tableData, function($row) use ($normalize) {
                            if (!is_array($row) || empty($row)) return false;
                            // Exclude only rows that are purely "summary" text in content (legacy); keep _meta summary rows for display
                            foreach ($row as $rk => $rv) {
                                if ($rk === '_meta') continue;
                                $v = $normalize($rv);
                                if ($v === 'summary' && !isset($row['_meta']['row_type'])) return false;
                            }
                            return true;
                        }));
                    @endphp
                    
                    @if(!empty($tableData))
                        @php
                            $firstDataRow = collect($tableData)->first(function($row) {
                                return is_array($row) && (($row['_meta']['row_type'] ?? 'data') !== 'summary');
                            });
                            $firstRow = $firstDataRow ?: ($tableData[0] ?? []);
                            $headers = is_array($firstRow) ? array_values(array_diff(array_keys($firstRow), ['_meta'])) : [];
                        @endphp
                        
                        @if(!empty($headers))
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            @foreach($headers as $header)
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ ucwords(str_replace('_', ' ', $header)) }}
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($tableData as $row)
                                            @if(is_array($row))
                                                @php
                                                    $isSummaryRow = isset($row['_meta']['row_type']) && $row['_meta']['row_type'] === 'summary';
                                                @endphp
                                                <tr class="{{ $isSummaryRow ? 'bg-blue-100 font-semibold' : 'hover:bg-gray-50' }}" {{ $isSummaryRow ? 'data-row-type="summary"' : '' }}>
                                                    @foreach($headers as $header)
                                                        @php
                                                            $value = $row[$header] ?? '';
                                                            if (is_array($value) || is_object($value)) {
                                                                $value = json_encode($value);
                                                            }
                                                            $value = (string) ($value ?? '');
                                                            $isLink = $value !== '' && filter_var($value, FILTER_VALIDATE_URL);
                                                            $dataRowBlue = $isLink;
                                                            $displayValue = $value;
                                                            if ($isSummaryRow && strtolower(trim($value)) === 'summary') {
                                                                $displayValue = '—';
                                                            }
                                                        @endphp
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isSummaryRow ? 'bg-blue-100 text-gray-800 font-semibold' : ($dataRowBlue ? 'text-blue-600 font-medium' : 'text-gray-900') }}">
                                                            @if($isLink)
                                                                <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="{{ $isSummaryRow ? 'text-gray-700 hover:text-gray-900 underline' : 'text-blue-600 hover:text-blue-800 underline' }}">{{ $displayValue }}</a>
                                                            @else
                                                                {{ $displayValue ?: '-' }}
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 text-center py-4">No data available</p>
                        @endif
                    @else
                        <p class="text-sm text-gray-500 text-center py-4">No data available</p>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between">
                <a href="{{ route('campus-user.create-submission') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Submissions
                </a>
                
                @if(!empty($canEdit) && $canEdit)
                    <a href="{{ route('campus-user.edit-submission', $submission) }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Submission
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
