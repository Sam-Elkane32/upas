<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @php
                $headerActions = '';
                if ($submission->status === 'Pending Review') {
                    $headerActions .= '<a href="' . e(route('super-admin.approvals.review', $submission)) . '" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Review & Approve</a>';
                }
                if ($submission->approval) {
                    $headerActions .= '<a href="' . e(route('super-admin.approvals.edit', $submission)) . '" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Edit Approval</a>';
                }
            @endphp
            @include('super-admin.partials.page-header', [
                'title' => 'View Submission',
                'subtitle' => 'Submission ID: ' . ($submission->submission_id ?? 'N/A'),
                'backUrl' => route('super-admin.approvals.index'),
                'actions' => $headerActions,
            ])
            <!-- Submission Header -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden mb-6">
                <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $submission->form_title ?? 'N/A' }}</h3>
                        <p class="text-sm text-gray-600 mt-0.5">Submission ID: {{ $submission->submission_id ?? 'N/A' }}</p>
                    </div>
                    <div>
                        @if($submission->status === 'Approved')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Approved</span>
                        @elseif($submission->status === 'Pending Review')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">Pending Review</span>
                        @elseif($submission->status === 'Returned')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Returned</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Submission Details -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Template Information -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h4 class="text-base font-semibold text-gray-900">Template Information</h4>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4">
                            <div><dt class="text-sm font-medium text-gray-500">Template Code</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->template_code ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Strategic Goal</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->sg_code ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">KRA Title</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->kra_title ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">KPI Title</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->kpi_title ?? 'N/A' }}</dd></div>
                        </dl>
                    </div>
                </div>

                <!-- Submission Information -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h4 class="text-base font-semibold text-gray-900">Submission Information</h4>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 gap-4">
                            <div><dt class="text-sm font-medium text-gray-500">Campus</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->campus ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Quarter</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->quarter ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Status</dt><dd class="text-sm mt-0.5">
                                @if($submission->status === 'Approved')<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                @elseif($submission->status === 'Pending Review')<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Pending Review</span>
                                @elseif($submission->status === 'Returned')<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Returned</span>
                                @endif
                            </dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Submitted By</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->submitter->name ?? 'N/A' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">Submitted Date</dt><dd class="text-sm text-gray-900 mt-0.5">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : ($submission->created_at->format('M d, Y H:i') ?? 'N/A') }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Submitted Data -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                    <h3 class="text-base font-semibold text-gray-900">Submitted Data</h3>
                </div>
                    
                    @if($submission->table_data && count($submission->table_data) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        @php
                                            $saTableRows = \App\Support\SubmissionTableData::asArray($submission->table_data);
                                            $tableHeaders = \App\Support\SubmissionTableData::dataColumnKeys($saTableRows);
                                        @endphp
                                        @if(count($submission->table_data) > 0)
                                            @foreach($tableHeaders as $header)
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    {{ ucwords(str_replace('_', ' ', $header)) }}
                                                </th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($submission->table_data as $row)
                                        <tr class="hover:bg-gray-50">
                                            @foreach($tableHeaders as $header)
                                                @php
                                                    $value = $row[$header] ?? '';
                                                    if (is_array($value) || is_object($value)) {
                                                        $value = json_encode($value);
                                                    }
                                                    $value = (string) ($value ?? '');
                                                @endphp
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    @if($value !== '' && filter_var($value, FILTER_VALIDATE_URL))
                                                        <a href="{{ $value }}" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                                            {{ $value }}
                                                        </a>
                                                    @else
                                                        {{ $value ?: '-' }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 text-center py-8">No data available</p>
                    @endif
                </div>
            </div>

            @if($submission->approval)
            <!-- Approval Details -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                    <h3 class="text-base font-semibold text-gray-900">Approval Details</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-3">Performance Metrics</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Accomplishment Term</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->approval->accomp_term ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SDP Ref No.</dt>
                                    <dd class="text-sm text-gray-900">{{ $submission->approval->sdp_ref ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Rate of Accomplishment</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->rate ?? 0, 2) }}%</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Rating</dt>
                                    <dd class="text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $submission->approval->rating ?? 'N/A' }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-3">Quarterly Breakdown</h4>
                            <dl class="space-y-2">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Q1: Target / Accomplishment</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->target_q1 ?? 0, 2) }} / {{ number_format($submission->approval->accomp_q1 ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Q2: Target / Accomplishment</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->target_q2 ?? 0, 2) }} / {{ number_format($submission->approval->accomp_q2 ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Q3: Target / Accomplishment</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->target_q3 ?? 0, 2) }} / {{ number_format($submission->approval->accomp_q3 ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Q4: Target / Accomplishment</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->target_q4 ?? 0, 2) }} / {{ number_format($submission->approval->accomp_q4 ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Total: Target / Accomplishment</dt>
                                    <dd class="text-sm text-gray-900 font-semibold">{{ number_format($submission->approval->target_total ?? 0, 2) }} / {{ number_format($submission->approval->accomp_total ?? 0, 2) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Variance</dt>
                                    <dd class="text-sm text-gray-900">{{ number_format($submission->approval->variance ?? 0, 2) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    @if($submission->approval->remarks)
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-gray-900 mb-2">Remarks</h4>
                        <p class="text-sm text-gray-700 bg-gray-50 p-4 rounded-lg">{{ $submission->approval->remarks }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>

