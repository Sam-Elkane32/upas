<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Form
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Update form information and details
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('forms.show', $form->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    View Form
                </a>
                <a href="{{ route('forms.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to Forms
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('forms.update', $form->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="space-y-6">
                            <!-- Form Type Selection -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Form Type</label>
                                <select name="type" id="type" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Form Type</option>
                                    <option value="sg" {{ old('type', $form->type) === 'sg' ? 'selected' : '' }}>Strategic Goal (SG)</option>
                                    <option value="kra" {{ old('type', $form->type) === 'kra' ? 'selected' : '' }}>Key Result Area (KRA)</option>
                                    <option value="kpi" {{ old('type', $form->type) === 'kpi' ? 'selected' : '' }}>Key Performance Indicator (KPI)</option>
                                    <option value="target" {{ old('type', $form->type) === 'target' ? 'selected' : '' }}>Target</option>
                                    <option value="accomplishment" {{ old('type', $form->type) === 'accomplishment' ? 'selected' : '' }}>Accomplishment</option>
                                </select>
                                @error('type')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" name="title" id="title" value="{{ old('title', $form->title) }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter form title">
                                @error('title')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" id="description" rows="4" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Enter detailed description">{{ old('description', $form->description) }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Target Date -->
                            <div>
                                <label for="target_date" class="block text-sm font-medium text-gray-700">Target Date</label>
                                <input type="date" name="target_date" id="target_date" value="{{ old('target_date', $form->target_date) }}" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('target_date')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Additional Fields (will be shown based on form type) -->
                            <div id="additional-fields" class="space-y-4" style="display: none;">
                                <!-- KPI Specific Fields -->
                                <div id="kpi-fields" class="space-y-4" style="display: none;">
                                    <div>
                                        <label for="measurement_unit" class="block text-sm font-medium text-gray-700">Measurement Unit</label>
                                        <input type="text" name="measurement_unit" id="measurement_unit" value="{{ old('measurement_unit', $form->measurement_unit ?? '') }}"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="e.g., Percentage, Number, Count">
                                    </div>
                                    <div>
                                        <label for="baseline_value" class="block text-sm font-medium text-gray-700">Baseline Value</label>
                                        <input type="number" name="baseline_value" id="baseline_value" value="{{ old('baseline_value', $form->baseline_value ?? '') }}"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="Current baseline value">
                                    </div>
                                    <div>
                                        <label for="target_value" class="block text-sm font-medium text-gray-700">Target Value</label>
                                        <input type="number" name="target_value" id="target_value" value="{{ old('target_value', $form->target_value ?? '') }}"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            placeholder="Desired target value">
                                    </div>
                                </div>

                                <!-- Accomplishment Specific Fields -->
                                <div id="accomplishment-fields" class="space-y-4" style="display: none;">
                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                        <select name="category" id="category"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Select Category</option>
                                            <option value="teaching" {{ old('category', $form->category ?? '') === 'teaching' ? 'selected' : '' }}>Teaching</option>
                                            <option value="research" {{ old('category', $form->category ?? '') === 'research' ? 'selected' : '' }}>Research</option>
                                            <option value="extension" {{ old('category', $form->category ?? '') === 'extension' ? 'selected' : '' }}>Extension</option>
                                            <option value="administrative" {{ old('category', $form->category ?? '') === 'administrative' ? 'selected' : '' }}>Administrative</option>
                                            <option value="professional_development" {{ old('category', $form->category ?? '') === 'professional_development' ? 'selected' : '' }}>Professional Development</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                                        <select name="priority" id="priority"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            <option value="">Select Priority</option>
                                            <option value="low" {{ old('priority', $form->priority ?? '') === 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('priority', $form->priority ?? '') === 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="high" {{ old('priority', $form->priority ?? '') === 'high' ? 'selected' : '' }}>High</option>
                                            <option value="urgent" {{ old('priority', $form->priority ?? '') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Information Notice -->
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Form Update</h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>Your form will be updated and resubmitted for review if it was previously approved.</p>
                                            <p class="mt-1">You will be notified once the updated form is reviewed.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Form
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for dynamic form fields -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const additionalFields = document.getElementById('additional-fields');
            const kpiFields = document.getElementById('kpi-fields');
            const accomplishmentFields = document.getElementById('accomplishment-fields');

            function toggleFields() {
                // Hide all additional fields first
                additionalFields.style.display = 'none';
                kpiFields.style.display = 'none';
                accomplishmentFields.style.display = 'none';

                // Show relevant fields based on type
                if (typeSelect.value === 'kpi') {
                    additionalFields.style.display = 'block';
                    kpiFields.style.display = 'block';
                } else if (typeSelect.value === 'accomplishment') {
                    additionalFields.style.display = 'block';
                    accomplishmentFields.style.display = 'block';
                }
            }

            typeSelect.addEventListener('change', toggleFields);
            
            // Initialize fields on page load
            toggleFields();
        });
    </script>
</x-app-layout>
