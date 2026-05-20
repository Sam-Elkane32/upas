<x-app-layout>
    <x-slot name="header">
        <div></div>
    </x-slot>

    <div class="pt-2 pb-4">
        <div class="w-full min-w-0 max-w-full">
            
            <!-- Floating Header Section -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Review Submission') }}
                </h2>
            </div>
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <h4 class="font-bold mb-2">Please fix the following errors:</h4>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('campus-admin.approvals.store', $submission) }}" method="POST" id="approval-form">
                @csrf
                
                <!-- Submission Information -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            Submission Information
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Form Title</span>
                                <p class="text-base font-medium text-gray-900">{{ $submission->form_title ?? ($submission->form->form_title ?? ($submission->template ? $submission->template->sg_code . ' - ' . $submission->template_code : 'N/A')) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">KRA</span>
                                <p class="text-base font-medium text-gray-900">{{ $submission->kra_title ?? ($submission->form->kra_title ?? ($submission->template ? $submission->template->kra_title : 'N/A')) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 md:col-span-2 lg:col-span-3">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">KPI Title</span>
                                <p class="text-sm text-gray-900 leading-relaxed">{{ $submission->kpi_title ?? ($submission->form->kpi_title ?? ($submission->template ? $submission->template->kpi_title : 'N/A')) }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Submitted By</span>
                                <p class="text-base font-medium text-gray-900">{{ $submission->submitter->name ?? 'N/A' }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Submitted Date</span>
                                <p class="text-base font-medium text-gray-900">{{ $submission->submitted_at ? $submission->submitted_at->format('M d, Y H:i') : $submission->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submitted Data -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            Submitted Data
                        </h3>
                        
                        @include('partials.submission-review-table', ['submission' => $submission])
                    </div>
                </div>

                <!-- Performance Validation -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            Performance Validation
                        </h3>

                        <!-- Target Values -->
                        <div class="mt-6 bg-blue-50 rounded-lg p-5 border border-blue-200">
                            @php
                                $ft = $formTargets ?? [];
                                $targetQ1 = old('target_q1', $ft['target_q1'] ?? 0);
                                $targetQ2 = old('target_q2', $ft['target_q2'] ?? 0);
                                $targetQ3 = old('target_q3', $ft['target_q3'] ?? 0);
                                $targetQ4 = old('target_q4', $ft['target_q4'] ?? 0);
                                $targetTotal = old('target_total', $ft['target_total'] ?? ($targetQ1 + $targetQ2 + $targetQ3 + $targetQ4));
                                $targetsAvailable = !empty($ft) && (isset($ft['target_q1']) || isset($ft['target_total']));
                            @endphp
                            <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Target Values @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif @if(!$targetsAvailable)<span class="text-amber-600 text-sm font-normal">(per-KPI targets not resolved; showing 0)</span>@endif
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="target_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q1" id="target_q1" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $targetQ1 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q2" id="target_q2" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $targetQ2 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q3" id="target_q3" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $targetQ3 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="target_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="target_q4" id="target_q4" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $targetQ4 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 md:col-span-4">
                                <label for="target_total" class="block text-sm font-semibold text-gray-700 mb-2">Total Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <div class="flex items-center gap-1">
                                    <input type="number" name="target_total" id="target_total" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 font-semibold text-gray-900 sm:text-sm"
                                           value="{{ $targetTotal }}">
                                    @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                </div>
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
                        <div class="mt-6 bg-green-50 rounded-lg p-5 border border-green-200">
                            <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Accomplishment Values @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="accomp_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q1" id="accomp_q1" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $accompQ1 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q2" id="accomp_q2" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $accompQ2 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q3" id="accomp_q3" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $accompQ3 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                                <div>
                                    <label for="accomp_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q4" id="accomp_q4" step="0.01" min="0" readonly 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                               value="{{ $accompQ4 }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 md:col-span-4">
                                <label for="accomp_total" class="block text-sm font-semibold text-gray-700 mb-2">Total Accomplishment @if(!empty($isPercentageForm)) (%) @endif</label>
                                <div class="flex items-center gap-1">
                                    <input type="number" name="accomp_total" id="accomp_total" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 font-semibold text-gray-900 sm:text-sm"
                                           value="{{ $accompQ1 + $accompQ2 + $accompQ3 + $accompQ4 }}">
                                    @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                </div>
                            </div>
                        </div>

                        <!-- Performance Metrics -->
                        <div class="mt-6 bg-purple-50 rounded-lg p-5 border border-purple-200">
                            <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                                Performance Metrics
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="variance" class="block text-sm font-medium text-gray-700 mb-2">Variance</label>
                                    <input type="number" name="variance" id="variance" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ old('variance', $approval?->variance ?? '0') }}">
                                </div>
                                <div>
                                    <label for="rate" class="block text-sm font-medium text-gray-700 mb-2">Rate of Accomplishment (%)</label>
                                    <input type="number" name="rate" id="rate" step="0.01" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ old('rate', $approval?->rate ?? '0') }}">
                                </div>
                                <div>
                                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Descriptive Rating</label>
                                    <input type="text" name="rating" id="rating" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                           value="{{ old('rating', $approval?->rating ?? 'Needs Improvement') }}">
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
                                      placeholder="Enter any additional comments or remarks...">{{ old('remarks', $approval?->remarks ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <a href="{{ route('campus-admin.approvals.index') }}" 
                           class="inline-flex items-center px-6 py-3 border-2 border-gray-300 rounded-lg font-semibold text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Cancel
                        </a>
                        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <button type="submit" name="action" value="return" 
                                    class="inline-flex items-center justify-center px-6 py-3 border-2 border-red-400 rounded-lg font-semibold text-sm text-red-700 bg-red-50 hover:bg-red-100 hover:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Return for Revision
                            </button>
                            <button type="submit" name="action" value="approve" 
                                    class="inline-flex items-center justify-center px-6 py-3 bg-green-600 border-2 border-green-600 rounded-lg font-semibold text-sm text-white hover:bg-green-700 hover:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-md">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Approve Submission
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('approval-form');
            
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
                
                // Update calculated fields
                const targetTotalEl = document.getElementById('target_total');
                const accompTotalEl = document.getElementById('accomp_total');
                const varianceEl = document.getElementById('variance');
                const rateEl = document.getElementById('rate');
                const ratingEl = document.getElementById('rating');
                
                if (targetTotalEl) targetTotalEl.value = targetTotal.toFixed(2);
                if (accompTotalEl) accompTotalEl.value = accompTotal.toFixed(2);
                if (varianceEl) varianceEl.value = variance.toFixed(2);
                if (rateEl) rateEl.value = rate.toFixed(2);
                if (ratingEl) ratingEl.value = rating;
            }

            // Track which button was clicked
            let clickedButton = null;
            const submitButtons = form.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    clickedButton = this;
                });
            });

            // Form submission handler - ensure form submits properly
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Ensure rating is calculated before submission
                    calculateMetrics();

                    // Ensure the action is set from the clicked button
                    if (clickedButton && clickedButton.name === 'action') {
                        // Create a hidden input to ensure the action is submitted
                        let actionInput = form.querySelector('input[name="action"]');
                        if (!actionInput) {
                            actionInput = document.createElement('input');
                            actionInput.type = 'hidden';
                            actionInput.name = 'action';
                            form.appendChild(actionInput);
                        }
                        actionInput.value = clickedButton.value;
                        console.log('Form submitting with action:', clickedButton.value);
                    } else {
                        // Fallback: try to get from active element
                        const activeBtn = document.activeElement;
                        if (activeBtn && activeBtn.type === 'submit' && activeBtn.name === 'action') {
                            let actionInput = form.querySelector('input[name="action"]');
                            if (!actionInput) {
                                actionInput = document.createElement('input');
                                actionInput.type = 'hidden';
                                actionInput.name = 'action';
                                form.appendChild(actionInput);
                            }
                            actionInput.value = activeBtn.value;
                        } else {
                            e.preventDefault();
                            window.showAlert({ title: 'Notice', message: 'Please click Approve or Return button.' });
                            return false;
                        }
                    }

                    // Show loading state on buttons
                    submitButtons.forEach(btn => {
                        if (btn.disabled) return; // Already disabled
                        btn.disabled = true;
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...';
                        
                        // Re-enable after 10 seconds as fallback (in case of error)
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        }, 10000);
                    });

                    // Allow form to submit normally
                    return true;
                });
            }
        });
    </script>
</x-app-layout>
