<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="w-full min-w-0 max-w-full">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Edit Approval') }}
                </h2>
            </div>
            <form action="{{ route('campus-admin.approvals.update', $submission) }}" method="POST" id="approval-form">
                @csrf
                @method('PUT')
                
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
                                <span class="block text-sm font-medium text-gray-700 mb-1">KRA</span>
                                <p class="text-sm text-gray-900">{{ $submission->kra_title ?? ($submission->form->kra_title ?? 'N/A') }}</p>
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
                                <span class="block text-sm font-medium text-gray-700 mb-1">Submitted Date</span>
                                <p class="text-sm text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : $submission->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submitted Data -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            Submitted Data
                        </h3>
                        
                        @include('partials.submission-review-table', [
                            'submission' => $submission,
                            'showEvidenceHint' => false,
                        ])
                    </div>
                </div>

                <!-- Performance Validation -->
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
                            $ft = $formTargets ?? [];
                            $targetQ1 = old('target_q1', $ft['target_q1'] ?? 0);
                            $targetQ2 = old('target_q2', $ft['target_q2'] ?? 0);
                            $targetQ3 = old('target_q3', $ft['target_q3'] ?? 0);
                            $targetQ4 = old('target_q4', $ft['target_q4'] ?? 0);
                            $targetTotal = old('target_total', $ft['target_total'] ?? ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4));
                            $targetsAvailable = !empty($ft) && (isset($ft['target_q1']) || isset($ft['target_total']));
                        @endphp
                        
                        <!-- Target Values (from Form / Super Admin) -->
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Target Values @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif @if(!$targetsAvailable)<span class="text-amber-600 text-sm font-normal">(per-KPI targets not resolved; showing 0)</span>@endif</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="target_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q1" id="target_q1" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $targetQ1 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q2" id="target_q2" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $targetQ2 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q3" id="target_q3" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $targetQ3 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q4" id="target_q4" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $targetQ4 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="target_total" class="block text-sm font-medium text-gray-700 mb-2">Total Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <div class="flex items-center gap-1">
                                    <input type="number" name="target_total" id="target_total" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $targetTotal }}">
                                    @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                </div>
                            </div>
                        </div>

                        <!-- Accomplishment Values (from planning coordinator's submitted data) -->
                        @php
                            $accompFromSubmission = $submissionAccomplishments ?? [];
                            $accompQ1 = old('accomp_q1', $accompFromSubmission['accomp_q1'] ?? $approval->accomp_q1 ?? 0);
                            $accompQ2 = old('accomp_q2', $accompFromSubmission['accomp_q2'] ?? $approval->accomp_q2 ?? 0);
                            $accompQ3 = old('accomp_q3', $accompFromSubmission['accomp_q3'] ?? $approval->accomp_q3 ?? 0);
                            $accompQ4 = old('accomp_q4', $accompFromSubmission['accomp_q4'] ?? $approval->accomp_q4 ?? 0);
                        @endphp
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Accomplishment Values @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif</h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="accomp_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q1" id="accomp_q1" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $accompQ1 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q2" id="accomp_q2" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $accompQ2 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q3" id="accomp_q3" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $accompQ3 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q4" id="accomp_q4" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                               value="{{ $accompQ4 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label for="accomp_total" class="block text-sm font-medium text-gray-700 mb-2">Total Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <div class="flex items-center gap-1">
                                    <input type="number" name="accomp_total" id="accomp_total" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ $accompQ1 + $accompQ2 + $accompQ3 + $accompQ4 }}">
                                    @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                </div>
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
                                    <input type="text" name="rating" id="rating" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ old('rating', $approval->rating ?? 'Needs Improvement') }}">
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
                    <a href="{{ route('campus-admin.approvals.index') }}" 
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
                            Approve
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initial calculation (fields are readonly, so calculation runs once on page load)
            calculateMetrics();

            function calculateMetrics() {
                // Get target values
                const targetQ1 = parseFloat(document.getElementById('target_q1').value) || 0;
                const targetQ2 = parseFloat(document.getElementById('target_q2').value) || 0;
                const targetQ3 = parseFloat(document.getElementById('target_q3').value) || 0;
                const targetQ4 = parseFloat(document.getElementById('target_q4').value) || 0;
                
                // Get accomplishment values
                const accompQ1 = parseFloat(document.getElementById('accomp_q1').value) || 0;
                const accompQ2 = parseFloat(document.getElementById('accomp_q2').value) || 0;
                const accompQ3 = parseFloat(document.getElementById('accomp_q3').value) || 0;
                const accompQ4 = parseFloat(document.getElementById('accomp_q4').value) || 0;
                
                // Calculate totals
                const targetTotal = targetQ1 + targetQ2 + targetQ3 + targetQ4;
                const accompTotal = accompQ1 + accompQ2 + accompQ3 + accompQ4;
                
                // Calculate variance and rate
                const variance = targetTotal - accompTotal;
                const rate = targetTotal > 0 ? (accompTotal / targetTotal) * 100 : 0;

                // Calculate rating based on new requirements:
                // Outstanding: 100% and above
                // Very Satisfactory: 90-99%
                // Satisfactory: 80-89%
                // Needs Improvement: <80%
                let rating = 'Needs Improvement';
                if (rate >= 100) {
                    rating = 'Outstanding';
                } else if (rate >= 90) {
                    rating = 'Very Satisfactory';
                } else if (rate >= 80) {
                    rating = 'Satisfactory';
                } else {
                    rating = 'Needs Improvement';
                }

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
                
                // Update calculated fields
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

