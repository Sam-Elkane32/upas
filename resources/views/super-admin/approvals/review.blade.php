<x-app-layout>
    <x-slot name="header"><div></div></x-slot>

    <div class="pt-2 pb-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('super-admin.partials.page-header', [
                'title' => 'Review Submission',
                'subtitle' => 'Approve or return this submission',
                'backUrl' => route('super-admin.approvals.index'),
            ])
            <form action="{{ route('super-admin.approvals.store', $submission) }}" method="POST" id="approval-form">
                @csrf
                
                <!-- Submission Information -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Submission Information
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Submission ID</span>
                                <p class="text-sm text-gray-900">{{ $submission->submission_id ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Campus</span>
                                <p class="text-sm text-gray-900">{{ $submission->campus ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Template Code</span>
                                <p class="text-sm text-gray-900">{{ $submission->template_code ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">KPI Title</span>
                                <p class="text-sm text-gray-900">{{ $submission->kpi_title ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Submitted By</span>
                                <p class="text-sm text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700 mb-1">Quarter</span>
                                <p class="text-sm text-gray-900">{{ $submission->quarter ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submitted Data -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Submitted Data
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($submission->table_data && count($submission->table_data) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            @php
                                                $qaTableRows = \App\Support\SubmissionTableData::asArray($submission->table_data);
                                                $tableHeaders = \App\Support\SubmissionTableData::dataColumnKeys($qaTableRows);
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
                            <p class="text-sm text-gray-500 text-center py-4">No data available</p>
                        @endif
                    </div>
                </div>

                <!-- Performance Validation -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50/80">
                        <h3 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Performance Validation
                        </h3>
                    </div>
                    <div class="p-6">
                        <!-- Target Values (from Form / Super Admin create & edit) -->
                        @php
                            $ft = $formTargets ?? [];
                            $targetQ1 = old('target_q1', $ft['target_q1'] ?? 0);
                            $targetQ2 = old('target_q2', $ft['target_q2'] ?? 0);
                            $targetQ3 = old('target_q3', $ft['target_q3'] ?? 0);
                            $targetQ4 = old('target_q4', $ft['target_q4'] ?? 0);
                            $targetTotal = old('target_total', $ft['target_total'] ?? ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4));
                            $targetsAvailable = !empty($ft) && (isset($ft['target_q1']) || isset($ft['target_total']));
                        @endphp
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Target Values @if(!$targetsAvailable)<span class="text-amber-600 text-sm font-normal">(per-KPI targets not resolved; showing 0)</span>@endif</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="target_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Target</label>
                                    <input type="number" name="target_q1" id="target_q1" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $targetQ1 }}">
                                </div>
                                <div>
                                    <label for="target_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Target</label>
                                    <input type="number" name="target_q2" id="target_q2" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $targetQ2 }}">
                                </div>
                                <div>
                                    <label for="target_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Target</label>
                                    <input type="number" name="target_q3" id="target_q3" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $targetQ3 }}">
                                </div>
                                <div>
                                    <label for="target_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Target</label>
                                    <input type="number" name="target_q4" id="target_q4" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $targetQ4 }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="target_total" class="block text-sm font-medium text-gray-700 mb-2">Total Target</label>
                                <input type="number" name="target_total" id="target_total" step="0.01" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ $targetTotal }}">
                            </div>
                        </div>

                        <!-- Accomplishment Values (from planning coordinator's submitted data) -->
                        @php
                            $accompFromSubmission = $submissionAccomplishments ?? [];
                            $accompQ1 = old('accomp_q1', $accompFromSubmission['accomp_q1'] ?? $approval?->accomp_q1 ?? 0);
                            $accompQ2 = old('accomp_q2', $accompFromSubmission['accomp_q2'] ?? $approval?->accomp_q2 ?? 0);
                            $accompQ3 = old('accomp_q3', $accompFromSubmission['accomp_q3'] ?? $approval?->accomp_q3 ?? 0);
                            $accompQ4 = old('accomp_q4', $accompFromSubmission['accomp_q4'] ?? $approval?->accomp_q4 ?? 0);
                        @endphp
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Accomplishment Values</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="accomp_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Accomplishment</label>
                                    <input type="number" name="accomp_q1" id="accomp_q1" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $accompQ1 }}">
                                </div>
                                <div>
                                    <label for="accomp_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Accomplishment</label>
                                    <input type="number" name="accomp_q2" id="accomp_q2" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $accompQ2 }}">
                                </div>
                                <div>
                                    <label for="accomp_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Accomplishment</label>
                                    <input type="number" name="accomp_q3" id="accomp_q3" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $accompQ3 }}">
                                </div>
                                <div>
                                    <label for="accomp_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Accomplishment</label>
                                    <input type="number" name="accomp_q4" id="accomp_q4" step="0.01" min="0" required readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $accompQ4 }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="accomp_total" class="block text-sm font-medium text-gray-700 mb-2">Total Accomplishment</label>
                                <input type="number" name="accomp_total" id="accomp_total" step="0.01" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                       value="{{ $accompQ1 + $accompQ2 + $accompQ3 + $accompQ4 }}">
                            </div>
                        </div>

                        <!-- Performance Metrics -->
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Performance Metrics</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="variance" class="block text-sm font-medium text-gray-700 mb-2">Variance</label>
                                    <input type="number" name="variance" id="variance" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ old('variance', $approval->variance ?? '0') }}">
                                </div>
                                <div>
                                    <label for="rate" class="block text-sm font-medium text-gray-700 mb-2">Rate of Accomplishment (%)</label>
                                    <input type="number" name="rate" id="rate" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ old('rate', $approval->rate ?? '0') }}">
                                </div>
                                <div>
                                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Descriptive Rating</label>
                                    <select name="rating" id="rating" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select Rating...</option>
                                        <option value="Outstanding" {{ old('rating', $approval->rating ?? '') == 'Outstanding' ? 'selected' : '' }}>Outstanding</option>
                                        <option value="Very Satisfactory" {{ old('rating', $approval->rating ?? '') == 'Very Satisfactory' ? 'selected' : '' }}>Very Satisfactory</option>
                                        <option value="Satisfactory" {{ old('rating', $approval->rating ?? '') == 'Satisfactory' ? 'selected' : '' }}>Satisfactory</option>
                                        <option value="Fair" {{ old('rating', $approval->rating ?? '') == 'Fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="Needs Improvement" {{ old('rating', $approval->rating ?? '') == 'Needs Improvement' ? 'selected' : '' }}>Needs Improvement</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="performance_category" class="block text-sm font-medium text-gray-700 mb-2">
                                        KPI Status (Summary Category)
                                    </label>
                                    <input type="text" id="performance_category" readonly
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="">
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mt-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                                Remarks / Comments
                            </label>
                            <textarea name="remarks" id="remarks" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                      placeholder="Enter any additional comments or remarks...">{{ old('remarks', $approval->remarks ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between">
                    <a href="{{ route('super-admin.approvals.index') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <div class="flex space-x-3">
                        <button type="submit" name="action" value="return" 
                                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest hover:bg-red-50 focus:bg-red-50 active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            Return
                        </button>
                        <button type="submit" name="action" value="approve" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Approve (Super Admin Override)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const targetInputs = ['target_q1', 'target_q2', 'target_q3', 'target_q4'];
            const accompInputs = ['accomp_q1', 'accomp_q2', 'accomp_q3', 'accomp_q4'];
            const allInputs = [...targetInputs, ...accompInputs];
            
            allInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('input', calculateMetrics);
                }
            });

            calculateMetrics();

            function calculateMetrics() {
                const targetQ1 = parseFloat(document.getElementById('target_q1').value) || 0;
                const targetQ2 = parseFloat(document.getElementById('target_q2').value) || 0;
                const targetQ3 = parseFloat(document.getElementById('target_q3').value) || 0;
                const targetQ4 = parseFloat(document.getElementById('target_q4').value) || 0;
                
                const accompQ1 = parseFloat(document.getElementById('accomp_q1').value) || 0;
                const accompQ2 = parseFloat(document.getElementById('accomp_q2').value) || 0;
                const accompQ3 = parseFloat(document.getElementById('accomp_q3').value) || 0;
                const accompQ4 = parseFloat(document.getElementById('accomp_q4').value) || 0;
                
                const targetTotal = targetQ1 + targetQ2 + targetQ3 + targetQ4;
                const accompTotal = accompQ1 + accompQ2 + accompQ3 + accompQ4;
                const variance = targetTotal - accompTotal;
                const rate = targetTotal > 0 ? (accompTotal / targetTotal) * 100 : 0;

                let rating = 'Needs Improvement';
                if (rate >= 100) rating = 'Outstanding';
                else if (rate >= 90) rating = 'Very Satisfactory';
                else if (rate >= 80) rating = 'Satisfactory';
                else if (rate >= 70) rating = 'Fair';

                // Determine KPI status category for summary (No Target / No Accomplishment / Below / Met / Above)
                let performanceCategory = 'No Target';
                if (targetTotal > 0 || accompTotal > 0) {
                    if (targetTotal <= 0 && accompTotal <= 0) {
                        performanceCategory = 'No Target';
                    } else if (targetTotal > 0 && accompTotal <= 0) {
                        performanceCategory = 'No Accomplishment';
                    } else if (targetTotal > 0 && accompTotal > 0) {
                        if (accompTotal < targetTotal) {
                            performanceCategory = 'Below Target';
                        } else if (accompTotal === targetTotal) {
                            performanceCategory = 'Met Target';
                        } else {
                            performanceCategory = 'Above Target';
                        }
                    }
                }
                
                document.getElementById('target_total').value = targetTotal.toFixed(2);
                document.getElementById('accomp_total').value = accompTotal.toFixed(2);
                document.getElementById('variance').value = variance.toFixed(2);
                document.getElementById('rate').value = rate.toFixed(2);
                document.getElementById('rating').value = rating;

                const perfField = document.getElementById('performance_category');
                if (perfField) {
                    perfField.value = performanceCategory;
                }
            }
        });
    </script>
</x-app-layout>

