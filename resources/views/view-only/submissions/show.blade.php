<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Submission Details (Read-Only)
            </h2>
            <div>
                <a href="{{ route('view-only.submissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    Back to Submissions
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Read-Only Notice -->
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="font-medium">You are viewing in Read-Only mode. This submission cannot be edited.</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Submission Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Submission ID</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->submission_id ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Campus</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->campus ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Template Code</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->template_code ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Quarter</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->quarter ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Submitted By</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Submitted At</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y g:i A') : 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Template Information</h3>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SG Code</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->sg_code ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">KRA Title</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->kra_title ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">KPI Title</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->kpi_title ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Table Data -->
                    <div class="mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Submission Data</h3>
                        @php
                            $tableData = is_string($submission->table_data) 
                                ? json_decode($submission->table_data, true) 
                                : $submission->table_data;
                            if (!is_array($tableData)) {
                                $tableData = [];
                            }
                            $normalize = function($s) {
                                $s = is_array($s) || is_object($s) ? json_encode($s) : (string) $s;
                                $s = preg_replace('/[\s\xC2\xA0]+/u', ' ', $s);
                                return strtolower(trim($s));
                            };
                            $tableData = array_values(array_filter($tableData, function($row) use ($normalize) {
                                if (!is_array($row) || empty($row)) return false;
                                if (isset($row['_meta']['row_type']) && $row['_meta']['row_type'] === 'summary') return false;
                                foreach ($row as $rk => $rv) {
                                    if ($rk === '_meta') continue;
                                    $v = $normalize($rv);
                                    if ($v === 'summary' || str_contains($v, 'summary')) return false;
                                }
                                return true;
                            }));
                        @endphp

                        @if(!empty($tableData) && count($tableData) > 0)
                            @php
                                $firstRow = $tableData[0] ?? [];
                                $headers = is_array($firstRow) ? array_values(array_diff(array_keys($firstRow), ['_meta'])) : [];
                                $resultHeaders = [];
                                foreach ($headers as $h) {
                                    $hl = preg_replace('/[\s\xC2\xA0]+/u', ' ', strtolower(trim($h)));
                                    $hlClean = preg_replace('/\.+$/', '', $hl);
                                    if ($hlClean === 'no' || $hl === 'no' || $hl === 'no.') $resultHeaders[$h] = true;
                                    if (str_contains($hl, 'evidence') && (str_contains($hl, 'verified') || str_contains($hl, 'qa'))) $resultHeaders[$h] = true;
                                }
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
                                        <tr class="hover:bg-gray-50">
                                            @foreach($headers as $header)
                                            @php
                                                $cell = $row[$header] ?? null;
                                                if (is_array($cell) || is_object($cell)) {
                                                    $cell = json_encode($cell);
                                                }
                                                $cell = $cell !== null ? (string) $cell : '';
                                                $isResultCol = isset($resultHeaders[$header]);
                                                $isLink = $cell !== '' && filter_var($cell, FILTER_VALIDATE_URL);
                                            @endphp
                                            <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isResultCol ? 'text-blue-600 font-medium' : 'text-gray-900' }}">
                                                @if($isLink)
                                                    <a href="{{ $cell }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">{{ $cell }}</a>
                                                @else
                                                    {{ $cell !== '' ? $cell : '-' }}
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
                            <p class="text-gray-500">No data available in this submission.</p>
                            @endif
                        @else
                        <p class="text-gray-500">No data available in this submission.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

