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
                            Edit Accomplishment Values
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">Submission ID: {{ $submission->submission_id }}</p>
                        <p class="text-xs text-indigo-600 mt-1 font-medium">Only Super Admin can edit accomplishment values and performance validation</p>
                    </div>
                    <div>
                        <a href="{{ route('super-admin.validated-templates.show', $submission) }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                            ← Back to View
                        </a>
                    </div>
                </div>
            </div>

            <form action="{{ route('super-admin.validated-templates.update', $submission) }}" method="POST" id="edit-form">
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
                                <span class="block text-sm font-medium text-gray-700 mb-1">KRA / KPI</span>
                                <p class="text-sm text-gray-900">{{ $submission->kra_title ?? 'N/A' }} / {{ $submission->kpi_title ?? 'N/A' }}</p>
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
                        </div>
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
                        @endphp
                        
                        <!-- Target Values (Read-only - Set by QA Coordinator) -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-5 border border-gray-200">
                            <h4 class="text-md font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-4 h-4 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Target Values (Set by QA Coordinator - Read Only) @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Q1 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <input type="text" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white sm:text-sm"
                                           value="{{ number_format($approval->target_q1 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Q2 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <input type="text" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white sm:text-sm"
                                           value="{{ number_format($approval->target_q2 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Q3 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <input type="text" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white sm:text-sm"
                                           value="{{ number_format($approval->target_q3 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Q4 Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                    <input type="text" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white sm:text-sm"
                                           value="{{ number_format($approval->target_q4 ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Target @if(!empty($isPercentageForm)) (%) @endif</label>
                                <input type="text" readonly 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white sm:text-sm"
                                       value="{{ number_format($approval->target_total ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                            </div>
                        </div>

                        <!-- Accomplishment Values (Editable by Super Admin) -->
                        <div class="mt-6 bg-green-50 rounded-lg p-5 border border-green-200">
                            <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Accomplishment Values (Editable by Super Admin) @if(!empty($isPercentageForm)) <span class="text-gray-600 font-normal">(%)</span> @endif
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="accomp_q1" class="block text-sm font-medium text-gray-700 mb-2">Q1 Accomplishment @if(!empty($isPercentageForm)) (%) @endif <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q1" id="accomp_q1" step="0.01" min="0" max="100" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                               value="{{ old('accomp_q1', $approval->accomp_q1 ?? '0') }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                    @error('accomp_q1')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="accomp_q2" class="block text-sm font-medium text-gray-700 mb-2">Q2 Accomplishment @if(!empty($isPercentageForm)) (%) @endif <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q2" id="accomp_q2" step="0.01" min="0" max="100" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                               value="{{ old('accomp_q2', $approval->accomp_q2 ?? '0') }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                    @error('accomp_q2')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="accomp_q3" class="block text-sm font-medium text-gray-700 mb-2">Q3 Accomplishment @if(!empty($isPercentageForm)) (%) @endif <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q3" id="accomp_q3" step="0.01" min="0" max="100" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                               value="{{ old('accomp_q3', $approval->accomp_q3 ?? '0') }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                    @error('accomp_q3')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="accomp_q4" class="block text-sm font-medium text-gray-700 mb-2">Q4 Accomplishment @if(!empty($isPercentageForm)) (%) @endif <span class="text-red-500">*</span></label>
                                    <div class="flex items-center gap-1">
                                        <input type="number" name="accomp_q4" id="accomp_q4" step="0.01" min="0" max="100" required 
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                               value="{{ old('accomp_q4', $approval->accomp_q4 ?? '0') }}">
                                        @if(!empty($isPercentageForm))<span class="text-gray-500 text-sm mt-1">%</span>@endif
                                    </div>
                                    @error('accomp_q4')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Accomplishment (Auto-calculated) @if(!empty($isPercentageForm)) (%) @endif</label>
                                <div class="flex items-center gap-1">
                                    <input type="text" id="accomp_total" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-100 sm:text-sm"
                                           value="{{ number_format($approval->accomp_total ?? 0, 2) }}{{ !empty($isPercentageForm) ? '%' : '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Performance Metrics (Auto-calculated) -->
                        <div class="mt-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Performance Metrics (Auto-calculated)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Variance</label>
                                    <input type="text" id="variance" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ number_format($approval->variance ?? 0, 2) }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Rate of Accomplishment (%)</label>
                                    <input type="text" id="rate" readonly 
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                                           value="{{ number_format($approval->rate ?? 0, 2) }}%">
                                </div>
                                <div>
                                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                                    <select name="rating" id="rating" 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Auto-calculate</option>
                                        <option value="Outstanding" {{ old('rating', $approval->rating) == 'Outstanding' ? 'selected' : '' }}>Outstanding</option>
                                        <option value="Very Satisfactory" {{ old('rating', $approval->rating) == 'Very Satisfactory' ? 'selected' : '' }}>Very Satisfactory</option>
                                        <option value="Satisfactory" {{ old('rating', $approval->rating) == 'Satisfactory' ? 'selected' : '' }}>Satisfactory</option>
                                        <option value="Fair" {{ old('rating', $approval->rating) == 'Fair' ? 'selected' : '' }}>Fair</option>
                                        <option value="Needs Improvement" {{ old('rating', $approval->rating) == 'Needs Improvement' ? 'selected' : '' }}>Needs Improvement</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="mt-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                            <textarea name="remarks" id="remarks" rows="3" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('remarks', $approval->remarks ?? '') }}</textarea>
                            @error('remarks')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('super-admin.validated-templates.show', $submission) }}" 
                               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg font-medium text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Accomplishment Values
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-calculate totals and metrics
        function calculateMetrics() {
            const q1 = parseFloat(document.getElementById('accomp_q1').value) || 0;
            const q2 = parseFloat(document.getElementById('accomp_q2').value) || 0;
            const q3 = parseFloat(document.getElementById('accomp_q3').value) || 0;
            const q4 = parseFloat(document.getElementById('accomp_q4').value) || 0;
            
            const total = q1 + q2 + q3 + q4;
            const targetTotal = {{ $approval->target_total ?? 0 }};
            const variance = targetTotal - total;
            const rate = targetTotal > 0 ? (total / targetTotal) * 100 : 0;
            
            document.getElementById('accomp_total').value = total.toFixed(2);
            document.getElementById('variance').value = variance.toFixed(2);
            document.getElementById('rate').value = rate.toFixed(2) + '%';
            
            // Auto-update rating if not manually set
            if (!document.getElementById('rating').value) {
                let rating = 'Needs Improvement';
                if (rate >= 100) rating = 'Outstanding';
                else if (rate >= 90) rating = 'Very Satisfactory';
                else if (rate >= 80) rating = 'Satisfactory';
                else if (rate >= 70) rating = 'Fair';
                // Don't set the value, just show what it would be
            }
        }
        
        // Add event listeners
        ['accomp_q1', 'accomp_q2', 'accomp_q3', 'accomp_q4'].forEach(id => {
            document.getElementById(id).addEventListener('input', calculateMetrics);
        });
        
        // Calculate on page load
        calculateMetrics();
    </script>
</x-app-layout>

